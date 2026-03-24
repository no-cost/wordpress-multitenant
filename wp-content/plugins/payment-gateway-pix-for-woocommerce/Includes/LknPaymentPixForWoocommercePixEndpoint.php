<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use WP_REST_Response;

class LknPaymentPixForWoocommercePixEndpoint
{
    public function registerRoutes(): void
    {
        register_rest_route('paymentPix', '/clearOrderLogs', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'clearOrderLogs'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
    }

    public function clearOrderLogs($request)
    {
        $body = $request->get_json_params();
        $gateway_id = isset($body['gateway_id']) ? sanitize_text_field($body['gateway_id']) : '';

        $args = array(
            'limit' => -1, // Sem limite, pega todas as ordens
            'meta_key' => 'lknPaymentPixGatewayOrderLogs', // Meta key específica
            'meta_compare' => 'EXISTS', // Verifica se a meta key existe
            'payment_method' => $gateway_id, // Filtra pelo método de pagamento específico
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            $order->delete_meta_data('lknPaymentPixGatewayOrderLogs');
            $order->save();
        }

        return new WP_REST_Response($orders, 200);
    }
}
