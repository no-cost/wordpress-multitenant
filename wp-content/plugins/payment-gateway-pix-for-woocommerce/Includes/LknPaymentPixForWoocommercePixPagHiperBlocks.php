<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePixPagHiper;

final class LknPaymentPixForWoocommercePixPagHiperBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_pix_for_woocommerce_paghiper';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_lkn_pix_for_woocommerce_paghiper_settings', []);
        $this->gateway = new LknPaymentPixForWoocommercePixPagHiper();

    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_enqueue_style('woo-lkn-pix-for-woocommerce-paghiper-style-blocks', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixPagHiperBlocks.css', array(), '1.0.0', 'all');
        wp_register_script(
            'lkn_pix_for_woocommerce_paghiper-blocks-integration',
            plugin_dir_url(__FILE__) . '../Public/js/blockCheckout/LknPaymentPixForWoocommercePixPagHiperBlocks.js',
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
            wp_set_script_translations('lkn_pix_for_woocommerce_paghiper-blocks-integration');

        }

        return [ 'lkn_pix_for_woocommerce_paghiper-blocks-integration' ];
    }

    public function get_payment_method_data()
    {

        return [
            'title' => $this->gateway->title,
            'description' => __('Pay for your purchase with pix using Pix PagHiper', 'gateway-de-pagamento-pix-para-woocommerce'),
        ];
    }

}
