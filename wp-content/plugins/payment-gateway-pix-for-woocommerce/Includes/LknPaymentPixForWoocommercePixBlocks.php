<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePix;

final class LknPaymentPixForWoocommercePixBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_pix_for_woocommerce';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_lkn_pix_for_woocommerce_settings', []);
        $this->gateway = new LknPaymentPixForWoocommercePix();

    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script('lkn-payment-pix-for-woocommerce-qrcode', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . '/Public/js/LknPaymentPixForWoocommerceQRCode.js', array(), '1.0', false);
        wp_enqueue_style('woo-lkn-pix-for-woocommerce-style-blocks', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');
        wp_register_script(
            'lkn_pix_for_woocommerce-blocks-integration',
            plugin_dir_url(__FILE__) . '../Public/js/blockCheckout/LknPaymentPixForWoocommercePixBlocks.js',
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
            wp_set_script_translations('lkn_pix_for_woocommerce-blocks-integration');

        }

        return [ 'lkn_pix_for_woocommerce-blocks-integration' ];
    }

    public function get_payment_method_data()
    {

        return [
            'title' => $this->gateway->title,
            'description' => __('Pay for your purchase with pix using Pix QR Code', 'gateway-de-pagamento-pix-para-woocommerce'),
        ];
    }

}
