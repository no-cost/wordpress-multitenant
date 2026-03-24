<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use DateTime;
use DateTimeZone;
use WC_Logger;

final class LknPaymentPixForWoocommercePixRedeRequest
{

    private const WC_STATUS_PENDING = 'pending';

    public $log;

    public function __construct()
    {
        $this->log = $this->get_logger();
    }

    private function get_logger()
    {
        if (class_exists('WC_Logger')) {
            return new WC_Logger();
        }
        return null;
    }

    public static function getPixRede($total, $pixInstance, $reference, $order)
    {
        // Determinar o ID do gateway baseado na instância
        $gateway_id = isset($pixInstance->id) ? $pixInstance->id : 'lkn_rede_pix_for_woocommerce';

        // Obter token OAuth2 usando o sistema de cache específico do gateway
        $access_token = LknPaymentPixForWoocommercePixRedeHelper::get_rede_oauth_token_pix($gateway_id);
        if ($access_token === null) {
            return false;
        }

        // Agora usar o token para a requisição de PIX
        $total = str_replace(".", "", $total);

        $environment = $pixInstance->get_option('env');
        $date = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $date->modify('+' . 2 . ' hours');
        $dateTimeExpiration = $date->format('Y-m-d\TH:i:s');
        $order->update_meta_data('_lkn_rede_pix_integration_time_expiration', $dateTimeExpiration);

        // Usar a nova URL da API v2 com o Bearer token
        if ('production' === $environment) {
            $apiUrl = 'https://api.userede.com.br/erede/v2/transactions';
        } else {
            $apiUrl = 'https://sandbox-erede.useredecloud.com.br/v2/transactions';
        }

        $body = array(
            'kind' => 'pix',
            'reference' => $reference,
            'amount' => $total,
            'qrCode' => array(
                'dateTimeExpiration' => $dateTimeExpiration
            )
        );

        $response = wp_remote_post($apiUrl, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'body' => wp_json_encode($body),
        ));

        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if ($pixInstance->get_option('debug') == 'yes') {
            $headersLog = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            );

            $orderLogsArray = array(
                'headers' => $headersLog,
                'oauth_token_cached' => true,
                'url' => $apiUrl,
                'body' => $body,
                'response' => $response_body
            );

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknPaymentPixGatewayOrderLogs', $orderLogs);
        }

        return $response_body;
    }

    public static function checkPixRedeStatus($tid, $order_id)
    {
        if (empty($tid)) {
            $timestamp = wp_next_scheduled('lkn_schedule_check_rede_pix_payment_hook', array($tid, $order_id));
            if ($timestamp !== false) {
                wp_unschedule_event($timestamp, 'lkn_schedule_check_rede_pix_payment_hook', array($tid, $order_id));
            }
        } else {
            $order = wc_get_order($order_id);

            if (!$order || $order->get_payment_method() !== 'lkn_rede_pix_for_woocommerce') {
                // Se não for o método correto, cancela o cron de verificação
                self::lkn_remove_custom_cron_job_rede($tid, $order_id);
                return;
            }

            $instance = new self();
            $order = wc_get_order($order_id);
            $response = $instance->payment_request($tid);

            if ($order->get_status() !== self::WC_STATUS_PENDING) {
                self::lkn_remove_custom_cron_job_rede($tid, $order_id);
                return;
            }

            if (! wp_next_scheduled('lkn_remove_custom_check_rede_pix_payment_job_hook', array($order->get_meta('_lkn_rede_pix_integration_transaction_tid'), $order_id))) {
                wp_schedule_single_event(time() + (120 * 60), 'lkn_remove_custom_check_rede_pix_payment_job_hook', array($order->get_meta('_lkn_rede_pix_integration_transaction_tid'), $order_id));
            }

            $pix_settings = get_option('woocommerce_lkn_rede_pix_for_woocommerce_settings');
            $pix_settings = is_array($pix_settings) ? $pix_settings : array();
            if (isset($pix_settings['debug']) && 'yes' === $pix_settings['debug']) {
                $instance->log->notice(wp_json_encode($response), array('source' => 'lkn-rede-pix-for-woocommerce'));
            }
            if ($order->get_status() === self::WC_STATUS_PENDING) {
                if ($instance->update_status($response)) {
                    $order->update_status($instance->pixCompleteStatus());
                }
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
        $response = json_decode(json_encode($response), true);

        if (!is_array($response) || !isset($response['authorization'])) {
            return false;
        }
        $payment_status = $response['authorization']['status'];

        switch ($payment_status) {
            case 'Approved':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    private function payment_request($tid)
    {
        $access_token = LknPaymentPixForWoocommercePixRedeHelper::get_rede_oauth_token_pix();
        if ($access_token === null) {
            return false;
        }
        $pixInstance = get_option('woocommerce_lkn_rede_pix_for_woocommerce_settings');
        $environment = $pixInstance['env'] ?? 'production';

        // Usar a nova URL da API v2 com o Bearer token
        if ('production' === $environment) {
            $apiUrl = 'https://api.userede.com.br/erede/v2/transactions/' . $tid;
        } else {
            $apiUrl = 'https://sandbox-erede.useredecloud.com.br/v2/transactions/' . $tid;
        }
        $response = wp_remote_get($apiUrl, array(
            'method' => 'GET',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
        ));

        $response_body = wp_remote_retrieve_body($response);

        $response_body = json_decode($response_body, true);

        return $response_body;
    }

    public static function lkn_remove_custom_cron_job_rede($tid, $orderId): void
    {
        $timestamp = wp_next_scheduled('lkn_schedule_check_rede_pix_payment_hook', array($tid, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_schedule_check_rede_pix_payment_hook', array($tid, $orderId));
        }
        $timestamp = wp_next_scheduled('lkn_remove_custom_check_rede_pix_payment_job_hook', array($tid, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_remove_custom_check_rede_pix_payment_job_hook', array($tid, $orderId));
        }
    }
}
