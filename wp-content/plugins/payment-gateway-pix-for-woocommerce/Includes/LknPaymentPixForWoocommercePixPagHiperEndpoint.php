<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use WC_Order;
use WP_Error;
use WC_Logger;
use WP_REST_Response;

class LknPaymentPixForWoocommercePixPagHiperEndpoint
{
    public function registerVerifyPixEndPoint()
    {
        register_rest_route('pixPagHiper', '/verifyPix', array(
            'methods' => 'POST',
            'callback' => array($this, 'verifyPix'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('pixPagHiper', '/checkStatus', array(
            'methods' => 'GET',
            'callback' => array($this, 'checkPaymentStatus'),
            'permission_callback' => '__return_true',
        ));
    }

    public function verifyPix($request)
    {
        $logger = new WC_Logger();
        $data = array(
            'token' => get_option('woocommerce_lkn_pix_for_woocommerce_paghiper_settings')['api_token'],
            'apiKey' => isset($_POST['apiKey']) ? sanitize_text_field(wp_unslash($_POST['apiKey'])) : '',
            'transaction_id' => isset($_POST['transaction_id']) ? sanitize_text_field(wp_unslash($_POST['transaction_id'])) : '',
            'notification_id' => isset($_POST['notification_id']) ? sanitize_text_field(wp_unslash($_POST['notification_id'])) : '',
        );

        $url = "https://pix.paghiper.com/invoice/notification";

        $response = wp_remote_post(
            $url,
            array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => wp_json_encode($data)
            )
        );


        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $logger->log('error', 'plugin-payment-pix-for-woocommerce-endpoint', array(
                'error_message' => $error_message,
            ));
            return new WP_Error($error_message, array('status' => 404));
        } else {
            $bodyResponse = json_decode(wp_remote_retrieve_body($response))->status_request;
            if ($bodyResponse->status == 'paid' || $bodyResponse->status == 'completed') {
                $order = new WC_Order($bodyResponse->order_id);
                $order->update_status('processing');
                $order->save();
                $logger->log('info', 'plugin-payment-pix-for-woocommerce-endpoint', array(
                    'id' => $bodyResponse->order_id,
                    'status' => $bodyResponse->status,
                ));
            } else {
                $logger->log('error', 'plugin-payment-pix-for-woocommerce-endpoint', array(
                    'error_message' => ($bodyResponse->response_message),
                ));
                return new WP_Error($bodyResponse->response_message, array('status' => 404));
            }
        }
    }

    public function checkPaymentStatus($request)
    {
        $orderId = isset($_GET['orderId']) ? sanitize_text_field(wp_unslash($_GET['orderId'])) : '';
        
        if (empty($orderId)) {
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Order ID is required'), 400);
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Order not found'), 404);
        }

        // Verificar se o PIX expirou
        $expirationTimestamp = $order->get_meta('_pix_expiration_timestamp');
        if ($expirationTimestamp && time() > $expirationTimestamp) {
            return new WP_REST_Response(array('status' => 'expired'), 200);
        }

        // Verificar status atual do pedido - se já foi processado, não precisa verificar na API
        if ($order->get_status() === 'processing' || $order->get_status() === 'completed') {
            return new WP_REST_Response(array('status' => 'completed'), 200);
        }

        // Só fazer requisição para o PagHiper se o pedido estiver pendente
        if ($order->get_status() === 'pending') {
            $transactionId = $order->get_meta('_pix_transaction_id');
            if (empty($transactionId)) {
                return new WP_REST_Response(array('status' => 'pending'), 200);
            }

            $settings = get_option('woocommerce_lkn_pix_for_woocommerce_paghiper_settings');
            $data = array(
                'token' => $settings['api_token'],
                'apiKey' => $settings['api_key'],
                'transaction_id' => $transactionId
            );

            $url = "https://pix.paghiper.com/invoice/status/";
            $response = wp_remote_post(
                $url,
                array(
                    'method' => 'POST',
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body' => wp_json_encode($data),
                    'timeout' => 15
                )
            );

            if (is_wp_error($response)) {
                // Em caso de erro na API, retorna status pendente para não interromper o fluxo
                return new WP_REST_Response(array('status' => 'pending'), 200);
            }

            $bodyResponse = json_decode(wp_remote_retrieve_body($response));
            
            // Log para debug
            $logger = new WC_Logger();
            $logger->log('info', 'paghiper-status-check', array(
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'response' => $bodyResponse
            ));

            if (isset($bodyResponse->status_request)) {
                $status = $bodyResponse->status_request->status;
                
                if ($status === 'paid' || $status === 'completed') {
                    // Atualizar status do pedido
                    $order->update_status('processing', __('Payment confirmed via PagHiper API', 'gateway-de-pagamento-pix-para-woocommerce'));
                    $order->save();
                    return new WP_REST_Response(array('status' => 'completed'), 200);
                } elseif ($status === 'canceled' || $status === 'expired') {
                    return new WP_REST_Response(array('status' => 'expired'), 200);
                }
            }
        }

        return new WP_REST_Response(array('status' => 'pending'), 200);
    }
}
