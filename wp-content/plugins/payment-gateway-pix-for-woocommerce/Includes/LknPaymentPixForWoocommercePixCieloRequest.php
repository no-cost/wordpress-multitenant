<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommerceHelper;
use WC_Logger;

final class LknPaymentPixForWoocommercePixCieloRequest
{
    private $urls = array('https://apisandbox.cieloecommerce.cielo.com.br', 'https://api.cieloecommerce.cielo.com.br/');
    private $queryUrl = array('https://apiquerysandbox.cieloecommerce.cielo.com.br/', 'https://apiquery.cieloecommerce.cielo.com.br/');
    private $log;
    private const WC_STATUS_PENDING = 'pending';

    public function __construct()
    {
        if (class_exists('WC_Logger')) {
            $this->log = new WC_Logger();
        }
    }

    public function pix_request($name, $amount, $billingCpfCpnj, $instance, $order)
    {
        $options = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings');
        $env = isset($options['env']) ? $options['env'] : 'production';
        $postUrl = $env === 'production' ? $this->urls[1] : $this->urls[0];
        // Format the amount to not have decimal separator
        $amount = (int) number_format($amount, 2, '', '');

        $body = array(
            'MerchantOrderId' => $order->get_id() . '-' . time(),
            'Customer' => array(
                'Name' => $name,
                'Identity' => $this->maskSensitiveData($billingCpfCpnj['Identity']),
                'IdentityType' => $billingCpfCpnj['IdentityType'],
            ),
            'Payment' => array(
                'Type' => 'Pix',
                'Amount' => $amount
            )
        );

        if (!isset($options['merchant_id']) || !isset($options['merchant_key']) || empty(trim($options['merchant_id'])) || empty(trim($options['merchant_key']))) {
            return array(
                'success' => false,
                'response' => __('MerchantId or MerchantKey not set.', 'gateway-de-pagamento-pix-para-woocommerce')
            );
        }

        $header = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $options['merchant_id'],
            'MerchantKey' => $options['merchant_key']
        );

        $response = wp_remote_post($postUrl . '/1/sales/', array(
            'body' => wp_json_encode($body),
            'headers' => $header,
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'response' => null
            );
        }

        $response = json_decode($response['body'], true);
        if (isset($response['Payment']['ReturnCode']) && $response['Payment']['ReturnCode'] === '422') {
            return array(
                'success' => false,
                'response' => __('Error on merchantResponse integration.', 'lkn-payment-pix-for-woocommerce')
            );
        }

        if (
            $response == null ||
            (is_array($response) && isset($response[0]) && isset($response[0]['Code']) &&
                ($response[0]['Code'] == '129' || $response[0]['Code'] == '132' || $response[0]['Code'] == '101' || $response[0]['Code'] == '131'))
        ) {
            return array(
                'success' => false,
                'response' => __('Invalid credential(s).', 'lkn-payment-pix-for-woocommerce')
            );
        }
        if ($instance->get_option('debug') === 'yes') {
            $LknPaymentPixHelper = new LknPaymentPixForWoocommerceHelper();

            $orderLogsArray = array(
                'url' => $postUrl . '1/sales',
                'headers' => array(
                    'Content-Type' => $header['Content-Type'],
                    'MerchantId' => $LknPaymentPixHelper->censorString($header['MerchantId'], 10),
                    'MerchantKey' => $LknPaymentPixHelper->censorString($header['MerchantKey'], 10)
                ),
                'body' => $body,
                'response' => json_decode(json_encode($response), true)
            );

            unset($orderLogsArray['response']['Payment']['Links']);

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknPaymentPixGatewayOrderLogs', $orderLogs);
        }

        // Mascarar campos sensíveis na resposta antes de fazer o log completo
        $response['Customer']['Identity'] = $this->maskSensitiveData($response['Customer']['Identity']);

        // Da mesma forma, mascarar os campos sensíveis do header
        $header['MerchantId'] = $this->maskSensitiveData($header['MerchantId']);
        $header['MerchantKey'] = $this->maskSensitiveData($header['MerchantKey']);

        // Registrar o log completo com os dados mascarados
        if ('yes' == $instance->debug) {
            $this->log->log('info', 'pixRequest Cielo Payment Pix', array(
                'request' => array(
                    'url' => $postUrl . '/1/sales/',
                    'current_time' => current_time('mysql'),
                    'body' => $body,
                    'header' => $header,
                ),
                'response' => $response
            ));
        }

        return array(
            'success' => true,
            'response' => array(
                'qrcodeImage' => $response['Payment']['QrCodeBase64Image'],
                'qrcodeString' => $response['Payment']['QrCodeString'],
                'status' => $response['Payment']['Status'],
                'paymentId' => $response['Payment']['PaymentId']
            )
        );
    }

    private function maskSensitiveData($string)
    {
        $length = strlen($string);

        if ($length <= 12) {
            return $string;
        } // Retorna sem alterações se o texto for muito curto

        // Calcula quantos caracteres manter no início e no final
        $startLength = intdiv($length - 8, 2);
        $endLength = $length - $startLength - 8;

        $start = substr($string, 0, $startLength);
        $end = substr($string, -$endLength);

        return $start . str_repeat('*', 8) . $end;
    }

    public static function check_payment($paymentId, $order_id): void
    {
        if (empty($paymentId)) {
            $timestamp = wp_next_scheduled('lkn_schedule_check_cielo_pix_payment_hook', array($paymentId, $order_id));
            if ($timestamp !== false) {
                wp_unschedule_event($timestamp, 'lkn_schedule_check_cielo_pix_payment_hook', array($paymentId, $order_id));
            }
        } else {
            $order = wc_get_order($order_id);

            if (!$order || $order->get_payment_method() !== 'lkn_cielo_pix_for_woocommerce') {
                // Se não for o método correto, cancela o cron de verificação
                self::lkn_remove_custom_cron_job($paymentId, $order_id);
                return;
            }
            $instance = new self();
            $order = wc_get_order($order_id);
            $response = $instance->payment_request($paymentId);

            if ($order->get_status() !== self::WC_STATUS_PENDING) {
                self::lkn_remove_custom_cron_job($paymentId, $order_id);
                return;
            }

            $response = wp_remote_retrieve_body($response);
            if (! wp_next_scheduled('lkn_remove_custom_check_cielo_pix_payment_job_hook', array($paymentId, $order_id))) {
                wp_schedule_single_event(time() + (120 * 60), 'lkn_remove_custom_check_cielo_pix_payment_job_hook', array($paymentId, $order_id));
            }

            $pix_settings = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings');
            $pix_settings = is_array($pix_settings) ? $pix_settings : array();
            if (isset($pix_settings['debug']) && 'yes' === $pix_settings['debug']) {
                $instance->log->notice($response, array('source' => 'pix-cielo-for-woocommerce'));
            }
            if ($order->get_status() === self::WC_STATUS_PENDING) {
                $order->update_status($instance->update_status($response));
            }
        }
    }

    public function pixCompleteStatus()
    {
        $pixOptions = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings') ?? [];
        $status = $pixOptions['payment_complete_status'] ?? "";

        if ("" == $status) {
            $status = 'processing';
        }

        return $status;
    }

    private function update_status($response)
    {
        $response = json_decode($response, true);
        if (!is_array($response) || !isset($response['Payment'])) {
            return 'cancelled';
        }
        $payment_status = (int) $response['Payment']['Status'];

        switch ($payment_status) {
            case 1:
                return $this->pixCompleteStatus();
                break;
            case 2:
                return $this->pixCompleteStatus();
                break;
            case 12:
                return 'pending';
                break;
            case 3:
                return 'cancelled';
                break;
            case 10:
                return 'cancelled';
                break;
            default:
                return 'cancelled';
                break;
        }
    }

    private function payment_request($paymentId)
    {
        $options = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings');
        $env = isset($options['env']) ? $options['env'] : 'production';
        $postUrl = $env === 'production' ? $this->queryUrl[1] : $this->queryUrl[0];

        if (!isset($options['merchant_id']) || !isset($options['merchant_key'])) {
            return new \WP_Error('missing_credentials', 'MerchantId or MerchantKey not set.');
        }

        $header = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $options['merchant_id'],
            'MerchantKey' => $options['merchant_key']
        );

        $response = wp_remote_get($postUrl . '1/sales/' . $paymentId, array(
            'headers' => $header,
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            $this->log->error('Request failed', array('error' => $response->get_error_message()));
            return $response;
        }

        return $response;
    }

    public static function lkn_remove_custom_cron_job($paymentId, $orderId): void
    {
        $timestamp = wp_next_scheduled('lkn_schedule_check_cielo_pix_payment_hook', array($paymentId, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_schedule_check_cielo_pix_payment_hook', array($paymentId, $orderId));
        }
        $timestamp = wp_next_scheduled('lkn_remove_custom_check_cielo_pix_payment_job_hook', array($paymentId, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_remove_custom_check_cielo_pix_payment_job_hook', array($paymentId, $orderId));
        }
    }
}
