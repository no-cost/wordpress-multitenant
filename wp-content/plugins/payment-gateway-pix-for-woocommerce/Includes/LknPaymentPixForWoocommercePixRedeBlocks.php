<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class LknPaymentPixForWoocommercePixRedeBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_rede_pix_for_woocommerce';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_rede_pix_for_woocommerce_settings', []);
        $this->gateway = new LknPaymentPixForWoocommercePixRede();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_enqueue_style('lkn-payment-pix-rede-style-blocks', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixRedePaymentFields.css', array(), '1.0.0', 'all');
        wp_register_script(
            'lkn_pix_for_woocommerce_rede-blocks-integration',
            plugin_dir_url(__FILE__) . '../Public/js/blockCheckout/LknPaymentPixForWoocommercePixRedeBlocks.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            '1.0.0',
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('lkn_pix_for_woocommerce_rede-blocks-integration');
        }

        return ['lkn_pix_for_woocommerce_rede-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        $option = get_option('woocommerce_lkn_rede_pix_for_woocommerce_settings');

        return array(
            'title' => $this->gateway->title,
            'description' => $option['description'] ?? __('Pague sua compra com Pix usando a Rede.', 'gateway-de-pagamento-pix-para-woocommerce'),
            'show_button' => $option['show_button'] ?? 'no'
        );
    }
}
