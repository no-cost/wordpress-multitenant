<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePixC6;

final class LknPaymentPixForWoocommercePixC6Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_pix_for_woocommerce_c6';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);
        $this->gateway = new LknPaymentPixForWoocommercePixC6();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_enqueue_style('woo-lkn-pix-for-woocommerce-c6-style-blocks', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixC6Blocks.css', array(), '1.0.0', 'all');
        wp_register_script(
            'lkn_pix_for_woocommerce_c6-blocks-integration',
            plugin_dir_url(__FILE__) . '../Public/js/blockCheckout/LknPaymentPixForWoocommercePixC6Blocks.js',
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
            wp_set_script_translations('lkn_pix_for_woocommerce_c6-blocks-integration');
        }

        return [ 'lkn_pix_for_woocommerce_c6-blocks-integration' ];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->get_option('description'),
            'generate_pix_button' => $this->gateway->get_option('generate_pix_button'),
            'pixButton' => __('Complete and Generate PIX', 'gateway-de-pagamento-pix-para-woocommerce'),
            'icon' => plugin_dir_url(__FILE__) . 'assets/icons/pix.svg',
        ];
    }
}
