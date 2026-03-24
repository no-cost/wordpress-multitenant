<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use WC_Order;
use WP_Error;
use WC_Logger;
use WP_REST_Response;

class LknPaymentPixForWoocommercePixC6Endpoint
{
    public function registerVerifyPixEndPoint()
    {
        register_rest_route(
            'pixforwoo',
            '/verify_c6_pix_status',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'verify_c6_pix_status'),
                'permission_callback' => '__return_true', // ajuste conforme sua necessidade
            )
        );
    }

    /**
     * Inicia o cron job para verificação do C6 PIX
     */
    public static function schedule_c6_pix_check($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Armazena quando o cron foi iniciado
        $order->update_meta_data('_c6_pix_cron_started', time());
        $order->save();

        // Agenda a primeira verificação em 5 minutos
        if (!wp_next_scheduled('lkn_check_c6_pix_payment_hook', array($order_id))) {
            wp_schedule_event(time() + 300, 'lkn_five_minutes', 'lkn_check_c6_pix_payment_hook', array($order_id));
        }

        return true;
    }

    /**
     * Para o cron job do C6 PIX
     */
    public static function unschedule_c6_pix_check($order_id)
    {
        wp_unschedule_event(wp_next_scheduled('lkn_check_c6_pix_payment_hook', array($order_id)), 'lkn_check_c6_pix_payment_hook', array($order_id));
        
        $order = wc_get_order($order_id);
        if ($order) {
            $order->delete_meta_data('_c6_pix_cron_started');
            $order->save();
        }
    }

    /**
     * Callback do cron - verifica status do PIX
     */
    public static function check_c6_pix_payment_status($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            self::unschedule_c6_pix_check($order_id);
            return;
        }

        // Verifica se já passou 24h (86400 segundos)
        $cron_started = $order->get_meta('_c6_pix_cron_started');
        if ($cron_started && (time() - intval($cron_started)) > 86400) {
            self::unschedule_c6_pix_check($order_id);
            return;
        }

        // Se o pedido não está mais pendente, para o cron
        if ($order->get_status() !== 'pending') {
            self::unschedule_c6_pix_check($order_id);
            return;
        }

        // Verifica o status usando a mesma lógica do REST API
        $result = self::verify_c6_pix_status_internal($order_id);

        // Se o status for concluída, para o cron
        if (isset($result['status']) && $result['status'] === 'concluida') {
            self::unschedule_c6_pix_check($order_id);
        }
    }

    private function check_cert_file($filepath) {
        return file_exists($filepath) && is_readable($filepath);
    }

    public static function get_c6_auth_token($crt_path, $key_path, $client_id, $client_secret, $base_url) {
        $auth_basic = base64_encode($client_id . ':' . $client_secret);
        $auth_url = $base_url . '/v1/auth/';
        $parsed_url = wp_parse_url($base_url);
        $base_url_host = !empty($parsed_url['host']) ? $parsed_url['host'] : '';

        // Hook para adicionar certificados ao cURL
        add_action('http_api_curl', function (&$handle, $r) use ($crt_path, $key_path) {
            if (is_readable($crt_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLCERT, $crt_path);
            }
            if (is_readable($key_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLKEY, $key_path);
            }
            // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
            // This is necessary for secure authentication with the C6 Bank Pix API.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
            curl_setopt($handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }, 10, 2);

        $response = wp_remote_post($auth_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $auth_basic,
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Host'          => $base_url_host
            ],
            'body'    => 'grant_type=client_credentials',
            'timeout' => 20,
        ]);

        // Remove o hook para não afetar outras requisições
        remove_all_actions('http_api_curl');

        if (is_wp_error($response)) {
            return array('error' => __('Authentication error: ', 'gateway-de-pagamento-pix-para-woocommerce') . $response->get_error_message());
        }

        $auth_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($auth_data['access_token'])) {
            return array('error' => __('No token received from C6 API.', 'gateway-de-pagamento-pix-para-woocommerce'));
        }

        return array(
            'access_token' => $auth_data['access_token'],
            'expires_in'   => !empty($auth_data['expires_in']) ? intval($auth_data['expires_in']) : 480
        );
    }

    public static function verify_c6_pix_status($request)
    {
        $orderId = $request->get_param('orderId');
        if (!$orderId) {
            return new WP_REST_Response(array('error' => 'Missing orderId'), 400);
        }

        $result = self::verify_c6_pix_status_internal($orderId);

        if (isset($result['error'])) {
            return new WP_REST_Response(array('error' => $result['error']), isset($result['code']) ? $result['code'] : 500);
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Função interna para verificar status do PIX (usada pelo REST API e pelo cron)
     */
    public static function verify_c6_pix_status_internal($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array('error' => 'Order not found', 'code' => 404);
        }

        $expiration_timestamp = $order->get_meta('_c6_pix_expiration');
        $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
        $now = time() + $offset;
        if ( !empty($expiration_timestamp) && $now > intval($expiration_timestamp) ) {
            $order->update_status('cancelled');
            $order->add_order_note(__('Order automatically cancelled due to PIX expiration.', 'gateway-de-pagamento-pix-para-woocommerce'));
            $order->save();
            return array('status' => 'expired');
        }

        $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);
        $environment = isset($settings['environment']) ? $settings['environment'] : 'production';
        $base_url = ($environment === 'sandbox')
            ? 'https://baas-api-sandbox.c6bank.info'
            : 'https://baas-api.c6bank.info';

        $client_id = isset($settings['client_id']) ? $settings['client_id'] : '';
        $client_secret = isset($settings['client_secret']) ? $settings['client_secret'] : '';
        $crt_path = !empty($settings['certificate_crt_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_crt_path'] : '';
        $key_path = !empty($settings['certificate_key_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_key_path'] : '';

        if (empty($client_id) || empty($client_secret)) {
            return array('error' => 'Client ID or Client Secret not configured.', 'code' => 500);
        }
        if (empty($crt_path) || !file_exists($crt_path)) {
            return array('error' => 'Certificate .crt file not found: ' . $crt_path, 'code' => 500);
        }
        if (empty($key_path) || !file_exists($key_path)) {
            return array('error' => 'Certificate .key file not found: ' . $key_path, 'code' => 500);
        }

        $pixTxID = $order->get_meta('_pix_txid');
        if (empty($pixTxID)) {
            return array('status' => 'pending');
        }

        // Token logic (8 min)
        $access_token = $order->get_meta('_c6_auth_token');
        $token_expiration = $order->get_meta('_c6_auth_expiration');
        $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
        $now = time() + $offset;

        if (empty($access_token) || empty($token_expiration) || ($now > $token_expiration)) {
            $auth_result = self::get_c6_auth_token($crt_path, $key_path, $client_id, $client_secret, $base_url);

            if (!empty($auth_result['error'])) {
                return array('error' => $auth_result['error'], 'code' => 500);
            }

            $access_token = $auth_result['access_token'];
            $expires_in = $auth_result['expires_in'];

            $order->update_meta_data('_c6_auth_token', $access_token);
            $order->update_meta_data('_c6_auth_expiration', $now + $expires_in);
            $order->save();
        }

        // Check Pix status by txid
        $pix_status_url = $base_url . '/v2/pix/cob/' . urlencode($pixTxID);

        // Hook para adicionar certificados ao cURL
        add_action('http_api_curl', function (&$handle, $r) use ($crt_path, $key_path) {
            if (is_readable($crt_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLCERT, $crt_path);
            }
            if (is_readable($key_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLKEY, $key_path);
            }
            // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
            // This is necessary for secure authentication with the C6 Bank Pix API.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
            curl_setopt($handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }, 10, 2);

        $response = wp_remote_get($pix_status_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
                'Host'          => wp_parse_url($base_url)['host']
            ],
            'timeout' => 20,
        ]);

        // Remove o hook para não afetar outras requisições
        remove_all_actions('http_api_curl');

        if (is_wp_error($response)) {
            return array('error' => 'Pix status error: ' . $response->get_error_message(), 'code' => 500);
        }

        $pix_data = json_decode(wp_remote_retrieve_body($response), true);
        $status = !empty($pix_data['status']) ? strtolower($pix_data['status']) : 'pending';

        if ($status === 'concluida') {
            $current_status = $order->get_status();
            
            if ($current_status === 'pending') {
                $order->update_status('processing');
                $order->add_order_note(
                    sprintf(
                        // translators: %s is the payment value formatted by wc_price().
                        __('Payment completed via PIX (C6 Bank). Value: %s', 'gateway-de-pagamento-pix-para-woocommerce'),
                        wc_price($order->get_total())
                    )
                );
                $order->save();
            }
        }

        return array('status' => $status);
    }

    public static function create_pix_charge($access_token, $crt_path, $key_path, $base_url, $pix_key, $order, $extra_data = [])
    {
        $parsed_url = wp_parse_url($base_url);
        $pix_url = $base_url . '/v2/pix/cob';
        $pix_header = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'Host'          => !empty($parsed_url['host']) ? $parsed_url['host'] : ''
        ];

        // Casas decimais do WooCommerce
        $decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

        // Expiration
        $expiration = isset($extra_data['expiration']) 
            ? intval($extra_data['expiration']) 
            : 1440 * 60;

        // Devedor
        $devedor = [];
        if (!empty($extra_data['cpf_cnpj_type']) && !empty($extra_data['cpf_cnpj_value'])) {
            if ($extra_data['cpf_cnpj_type'] === 'cpf') {
                $devedor['cpf'] = $extra_data['cpf_cnpj_value'];
                $devedor['nome'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            } elseif ($extra_data['cpf_cnpj_type'] === 'cnpj') {
                $devedor['cnpj'] = $extra_data['cpf_cnpj_value'];
                $company = $order->get_billing_company();
                if (!empty($company)) {
                    $devedor['nome'] = $company;
                } else {
                    $devedor['nome'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                }
            }
        }

        $pix_body = [
            "calendario" => ["expiracao" => $expiration],
            "devedor"    => $devedor,
            "valor"      => [
                "original" => number_format($order->get_total(), $decimals, '.', ''),
                "modalidadeAlteracao" => 1
            ],
            "chave" => $pix_key,
            "solicitacaoPagador" => "C6 PIX",
        ];

        // Hook para adicionar certificados ao cURL
        add_action('http_api_curl', function (&$handle, $r) use ($crt_path, $key_path) {
            if (is_readable($crt_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLCERT, $crt_path);
            }
            if (is_readable($key_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLKEY, $key_path);
            }
            // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
            // This is necessary for secure authentication with the C6 Bank Pix API.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
            curl_setopt($handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }, 10, 2);

        $response = wp_remote_post($pix_url, [
            'headers' => $pix_header,
            'body'    => wp_json_encode($pix_body),
            'timeout' => 20,
        ]);

        // Remove o hook para não afetar outras requisições
        remove_all_actions('http_api_curl');

        if (is_wp_error($response)) {
            return ['error' => 'Error creating Pix: ' . $response->get_error_message()];
        }

        $pix_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($pix_data['pixCopiaECola'])) {
            return ['error' => 'Pix not generated correctly. Try again.'];
        }

        $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
        $local_time = time() + $offset;

        $expiration_timestamp = $local_time + $expiration;
        $order->update_meta_data('_c6_pix_expiration', $expiration_timestamp);
        $order->update_meta_data('_c6_pix_created', $local_time);
        $order->save();

        return $pix_data;
    }

    public static function generate_c6_pix_qrcode_stub($client_id, $client_secret, $pix_key, $environment = 'sandbox')
    {
        $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);
        $crt_path = !empty($settings['certificate_crt_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_crt_path'] : '';
        $key_path = !empty($settings['certificate_key_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_key_path'] : '';
        $amount     = 10;
        $cpf_cnpj_type = 'cpf';
        $cpf_cnpj_value = '83845155060';
        $expiration = 1440 * 60;

        // Define a URL conforme o ambiente
        $base_url = ($environment === 'production')
            ? 'https://baas-api.c6bank.info'
            : 'https://baas-api-sandbox.c6bank.info';

        // Autenticação
        $auth_result = self::get_c6_auth_token($crt_path, $key_path, $client_id, $client_secret, $base_url);
        if (!empty($auth_result['error'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid credentials, please check the filled data or save the settings and try again.'
            ];
        }
        $access_token = $auth_result['access_token'];

        // Corpo da requisição
        $devedor = [
            'cpf' => $cpf_cnpj_value,
            'nome' => 'Test User'
        ];

        $pix_body = [
            "calendario" => ["expiracao" => $expiration],
            "devedor" => $devedor,
            "valor"      => [
                "original" => number_format($amount, 2, '.', ''),
                "modalidadeAlteracao" => 1
            ],
            "chave" => $pix_key,
            "solicitacaoPagador" => "C6 PIX Test",
        ];

        $parsed_url = wp_parse_url($base_url);
        $pix_url = $base_url . '/v2/pix/cob';
        $pix_header = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'Host'          => !empty($parsed_url['host']) ? $parsed_url['host'] : ''
        ];

        // Hook para adicionar certificados ao cURL
        add_action('http_api_curl', function (&$handle, $r) use ($crt_path, $key_path) {
            if (is_readable($crt_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLCERT, $crt_path);
            }
            if (is_readable($key_path)) {
                // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
                // This is necessary for secure authentication with the C6 Bank Pix API.
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
                curl_setopt($handle, CURLOPT_SSLKEY, $key_path);
            }
            // We use cURL here because WordPress HTTP API does not support SSL certificates (CURLOPT_SSLCERT/CURLOPT_SSLKEY).
            // This is necessary for secure authentication with the C6 Bank Pix API.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Required for mTLS integration with C6 Bank Pix API.
            curl_setopt($handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }, 10, 2);

        $response = wp_remote_post($pix_url, [
            'headers' => $pix_header,
            'body'    => wp_json_encode($pix_body),
            'timeout' => 20,
        ]);

        // Remove o hook para não afetar outras requisições
        remove_all_actions('http_api_curl');

        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'message' => 'Invalid credentials, please check the filled data or save the settings and try again.'
            ];
        }

        $pix_data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($pix_data['pixCopiaECola'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid credentials, please check the filled data or save the settings and try again.'
            ];
        }

        $pixCopiaECola = $pix_data['pixCopiaECola'];
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($pixCopiaECola);

        return [
            'status' => 'success',
            'message' => 'PIX successfully created.',
            'qrcode_url' => $qrCodeUrl
        ];
    }
}