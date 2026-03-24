<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Exception;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

class LknPaymentPixForWoocommercePix extends WC_Payment_Gateway
{
    public $configs;
    public $log;
    public $debug;

    public function __construct()
    {
        $this->id = 'lkn_pix_for_woocommerce';
        $this->title = 'Pix QR Code';
        $this->has_fields = true;
        $this->method_title       = esc_attr__('Pay with the Pix QR Code', 'gateway-de-pagamento-pix-para-woocommerce');
        $this->method_description = esc_attr__('Enables and configures payments with Pix', 'gateway-de-pagamento-pix-para-woocommerce');


        // Define os campos de configuração do método de pagamento
        $this->init_form_fields();
        $this->init_settings();

        // Define as configurações do método de pagamento
        $this->title = $this->get_option('title');
        $this->log = $this->get_logger();
        $this->debug = $this->get_option('debug');
    }

    public function showPix($orderWCID)
    {
        $order = wc_get_order($orderWCID);
        if ($order->get_payment_method() == 'lkn_pix_for_woocommerce') {
            if ((($order->get_status() == 'processing' || $order->get_status() == 'completed') && $this->get_option('hidde_paid_pix') === 'yes')) {
                return;
            }
            wc_get_template(
                '/pixForWoocommercePaymentQRCode.php',
                array(),
                'woocommerce/payment/',
                plugin_dir_path(__FILE__) . 'templates/'
            );

            wp_enqueue_script('lkn-payment-pix-for-woocommerce-qrcode', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . '/Public/js/LknPaymentPixForWoocommerceQRCode.js', array(), '1.0', false);
            wp_enqueue_script('lkn-woo-payment-pix-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePix.js', array('lkn-payment-pix-for-woocommerce-qrcode'), '1.0.0', 'all');

            wp_localize_script('lkn-woo-payment-pix-js', 'phpVariables', array(
                'pixAmount' => $order->get_total(),
                'pixKeyType' => $this->get_option('pix_key_type'),
                'pixKey' => $this->get_option('pix_key'),
                'pixName' => $this->get_option('pix_name'),
                'pixCity' => $this->get_option('pix_city'),
                'copiedText' => __('Copied!', 'gateway-de-pagamento-pix-para-woocommerce'),
            ));

            wp_enqueue_style('lkn-woo-payment-pix-style', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');
        }
        if ($this->get_option('debug') === 'yes' && !$order->get_meta('_payment_pix_log_added')) {
            $this->log->log('info', $this->id, array(
                'Pix' => array(
                    'pixAmount' => $order->get_total(),
                    'pixKeyType' => $this->get_option('pix_key_type'),
                    'pixKey' => $this->get_option('pix_key'),
                    'pixName' => $this->get_option('pix_name'),
                    'pixCity' => $this->get_option('pix_city'),
                ),
            ));
            $order->update_meta_data('_payment_pix_log_added', true);
            $order->save();
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'       => array(
                'title'   => esc_attr__('Enable/Disable', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enables payment with Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' => 'no'
            ),
            'title'         => array(
                'title'       => esc_attr__('Title', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'text',
                'description' => __('This field controls the title which the user sees during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'     => __('Pay with the Pix QR Code', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip'    => true,
            ),
            'title_general' => array(
                'title' => esc_attr__('General settings', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'pix_key_type' => array(
                'title'       => esc_attr__('Key Type', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'select',
                'description' => esc_attr__('Select the type of PIX key.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip'    => true,
                'class'       => 'wc-enhanced-select',
                'options' => array(
                    'tel'       => esc_attr__('Phone', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'cpf'       => esc_attr__('CPF', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'cnpj'      => esc_attr__('CNPJ', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'email'     => esc_attr__('Email', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'randomKey' => esc_attr__('Random key', 'gateway-de-pagamento-pix-para-woocommerce'),
                ),
            ),
            'pix_key' => array(
                'title' => esc_attr__('Pix Key', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'text',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter the PIX key to be used for donations.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            ),
            'pix_name' => array(
                'title' => esc_attr__('Beneficiary Name', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'text',
                'description' => esc_attr__('Enter the name of the key beneficiary.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            ),
            'pix_city' => array(
                'title' => esc_attr__('Beneficiary City', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'text',
                'description' => esc_attr__('Enter the city of the key beneficiary.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            ),
            'hidde_paid_pix' => array(
                'title' => esc_attr__('Hide Pix after payment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'label' => esc_attr__('Hide Pix QRCode for logged in customers with processing or completed order', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'desc_tip'    => true,
                'description' => esc_attr__('Enable this option to hide the Pix QR Code in my customer account that is processing or completed.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' =>  'no',
            ),
            'title_additional_resources' => array(
                'title' => esc_attr__('Additional Resources', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'debug' => array(
                'title' => esc_attr__('Debug', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_attr__('Enable debug logs.', 'gateway-de-pagamento-pix-para-woocommerce') . ' ' . wp_kses_post('<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs')) . '" target="_blank">'. __('See logs', 'gateway-de-pagamento-pix-para-woocommerce') .'</a>'),
                'default' =>  'no',
            ),
            'pix_qr_code' => array(
                'title' => esc_attr__('QR Code', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'hidden',
                'id' => 'lknPaymentPixForWoocommercePixQRCode',
                'description' => esc_attr__('The QR code will only be valid if the key has been registered with a financial institution (Bank).', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            )
        );

        global $pagenow;

        $sectionGet = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';
        if (is_admin() && $pagenow == 'admin.php' && $sectionGet && $sectionGet == 'lkn_pix_for_woocommerce') {
            wp_enqueue_script('lkn-payment-pix-for-woocommerce-qrcode');
            wp_enqueue_script('lkn-woo-payment-pix-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePix.js', array(), '1.0.0', 'all');
            wp_localize_script('lkn-woo-payment-pix-js', 'phpVariables', array(
                'pixAmount' => 1,
                'pixKeyType' => $this->get_option('pix_key_type'),
                'pixKey' => $this->get_option('pix_key'),
                'pixName' => $this->get_option('pix_name'),
                'pixCity' => $this->get_option('pix_city'),
                'downloadQRCodeText' => __('Download QR Code', 'gateway-de-pagamento-pix-para-woocommerce'),
            ));
        }
    }

    public function payment_fields()
    {

        wc_get_template(
            '/pixForWoocommercePaymentFields.php',
            array(),
            'woocommerce/pix/',
            plugin_dir_path(__FILE__) . 'templates/'
        );
        wp_enqueue_style('lknPaymentPixForWoocommercePixCss', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');
    }

    final public function get_logger()
    {
        if (class_exists('WC_Logger')) {
            return new WC_Logger();
        } else {
            global $woocommerce;

            return $woocommerce->logger();
        }
    }

    public function process_payment($orderId)
    {

        $order = wc_get_order($orderId);

        $valid = true;

        if ($valid) {

            try {
                if ($this->get_option('pix_key') == '') {
                    throw new Exception(__('PIX key is not configured. Please set the PIX key in the plugin settings.', 'gateway-de-pagamento-pix-para-woocommerce'));
                }
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
                $valid = false;
            }
        }

        if ($valid) {
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }

    }

    public function add_error($message)
    {
        global $woocommerce;

        $title = '<strong>' . esc_html($this->title) . ':</strong> ';

        if (function_exists('wc_add_notice')) {
            $message = wp_kses($message, array());
            throw new Exception(wp_kses_post("{$title} {$message}"));
        } else {
            $woocommerce->add_error($title . $message);
        }
    }

    public function checkoutScripts(): void
    {
        wp_enqueue_script('LknPaymentPixForWoocommerceFixInfiniteLoading-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommerceFixInfiniteLoading.js', array(), '1.0.0', true);
    }
}
