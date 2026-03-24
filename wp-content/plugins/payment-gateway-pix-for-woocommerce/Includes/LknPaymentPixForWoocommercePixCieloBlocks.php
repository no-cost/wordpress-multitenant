<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class LknPaymentPixForWoocommercePixCieloBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_pix_for_woocommerce';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings', []);
        $this->gateway = new LknPaymentPixForWoocommercePixCielo();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'lkn_pix_for_woocommerce_cielo-blocks-integration',
            plugin_dir_url(__FILE__) . '../Public/js/blockCheckout/LknPaymentPixForWoocommercePixCieloBlocks.js',
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

        return ['lkn_pix_for_woocommerce_cielo-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'label' => $this->get_setting('title'),
            'show_button' => $this->get_setting('show_button'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }
}