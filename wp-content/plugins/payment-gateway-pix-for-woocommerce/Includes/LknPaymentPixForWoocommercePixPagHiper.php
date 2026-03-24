<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Exception;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

class LknPaymentPixForWoocommercePixPagHiper extends WC_Payment_Gateway
{
    public $configs;
    public $log;
    public $debug;

    public function __construct()
    {
        $this->id = 'lkn_pix_for_woocommerce_paghiper';
        $this->title = 'Pix PagHiper';
        $this->has_fields = true;
        $this->method_title       = esc_attr__('Pay with the Pix PagHiper', 'gateway-de-pagamento-pix-para-woocommerce');
        $this->method_description = esc_attr__('Enable automatic payment confirmation through a PagHiper account', 'gateway-de-pagamento-pix-para-woocommerce');


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

        if ($order->get_payment_method() == 'lkn_pix_for_woocommerce_paghiper') {
            if ((($order->get_status() == 'processing' || $order->get_status() == 'completed') && $this->get_option('hidde_paid_pix') === 'yes')) {
                return;
            }
            
            // Verificar se o PIX expirou
            $expirationTimestamp = $order->get_meta('_pix_expiration_timestamp');
            $isExpired = $expirationTimestamp && time() > $expirationTimestamp;
            
            wc_get_template(
                '/pixForWoocommercePaymentQRCodePixPagHiper.php',
                array(
                    'pix_code_base64' => $order->get_meta('_pix_qrcode_base64'),
                    'pix_code_image_url' => $order->get_meta('_pix_qrcode_image_url'),
                    'pix_code_emv' => $order->get_meta('_pix_qrcode_emv'),
                    'currency_txt' => wc_price($order->get_total()),
                    'due_date_msg' => $order->get_meta('_pix_expiration_date') ? 
                        sprintf(__('Valid until: %s', 'gateway-de-pagamento-pix-para-woocommerce'), 
                                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), 
                                         strtotime($order->get_meta('_pix_expiration_date')))) : '',
                    'donation_id' => $order->get_id(),
                    'expiration_pix_date' => $order->get_meta('_pix_expiration_date'),
                    'pix_is_expired' => $isExpired
                ),
                'woocommerce/payment/',
                plugin_dir_path(__FILE__) . 'templates/'
            );

            wp_enqueue_script('lkn-woo-payment-pix-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixPagHiper.js', array(), '1.0.0', 'all');
            wp_localize_script('lkn-woo-payment-pix-js', 'phpVariables', array(
                'copiedText' => __('Copied!', 'gateway-de-pagamento-pix-para-woocommerce'),
                'apiUrl' => home_url('/wp-json/pixPagHiper/checkStatus'),
                'nextVerify' => __('Next verification in (Number of attempts:', 'gateway-de-pagamento-pix-para-woocommerce'),
                'successPayment' => __('Payment successful!', 'gateway-de-pagamento-pix-para-woocommerce'),
                'expiredPayment' => __('Payment expired', 'gateway-de-pagamento-pix-para-woocommerce'),
                'expiredPaymentDate' => __('Expired on:', 'gateway-de-pagamento-pix-para-woocommerce'),
                'pixButton' => __('I have already paid the PIX', 'gateway-de-pagamento-pix-para-woocommerce')
            ));

            wp_enqueue_script('lkn-woo-payment-pix-share-modal-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixShareModal.js', array(), '1.0.0', 'all');
            wp_localize_script('lkn-woo-payment-pix-share-modal-js', 'currentTheme', wp_get_theme()->get('Name'));

            wp_enqueue_style('lkn-woo-payment-pix-style', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixPagHiper.css', array(), '1.0.0', 'all');
            wp_enqueue_style('lkn-woo-payment-pix-style-fields', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');
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
                'default'     => __('Pay with the Pix PagHiper', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip'    => true,
            ),
            'title_general' => array(
                'title' => esc_attr__('General settings', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'api_key' => array(
                'title' => esc_attr__('PagHiper API Key', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'password',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter your PagHiper API key.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            ),
            'api_token' => array(
                'title' => esc_attr__('PagHiper API Token', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'password',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter your PagHiper API token.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
            ),
            'pix_key_type' => array(
                'title'       => esc_attr__('Pix validity in days', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'number',
                'description' => esc_attr__('Enter the validity of the pix in days. By default, the validity lasts until the end of the day.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'        => 0,
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '1',
                ),
            ),
            'pix_min_value' => array(
                'title'       => esc_attr__('Minimum value to generate Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'number',
                'description' => esc_attr__('Enter the minimum purchase value to generate the pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'        => 3,
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'min'  => '3',
                    'max'  => '3',
                    'step' => '1',
                    'readonly' => 'readonly',
                ),
            ),
            'hidde_paid_pix' => array(
                'title' => esc_attr__('Hide Pix after payment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'label' => esc_attr__('Hide Pix QRCode for logged in customers with processing or completed order', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'desc_tip'    => true,
                'description' => esc_attr__('Enable this option to hide the Pix QR Code in my customer account that is processing or completed.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' =>  'no',
            ),
            'debug' => array(
                'title' => esc_attr__('Debug', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_attr__('Enable debug logs.', 'gateway-de-pagamento-pix-para-woocommerce') . ' ' . wp_kses_post('<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs')) . '" target="_blank">'. __('See logs', 'gateway-de-pagamento-pix-para-woocommerce') .'</a>'),
                'default' =>  'no',
            )

        );
    }

    public function payment_fields()
    {

        wc_get_template(
            '/pixForWoocommercePaymentFieldsPixPagHiper.php',
            array(),
            'woocommerce/pix/',
            plugin_dir_path(__FILE__) . 'templates/'
        );
        wp_enqueue_style('lknPaymentPixForWoocommercePixPagHiperCss', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixPagHiper.css', array(), '1.0.0', 'all');
        wp_enqueue_style('lknPaymentPixForWoocommercePixPagHiperCssFields', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');

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

                $cpf = isset($_POST['pix_for_woocommerce_cpf']) ? sanitize_text_field(wp_unslash($_POST['pix_for_woocommerce_cpf'])) : '';

                if ($cpf === '' || !$this->validateCpfCnpj($cpf)) {
                    throw new Exception(__('Please enter a valid CPF or CNPJ.', 'gateway-de-pagamento-pix-para-woocommerce'));
                }
                if ($this->get_option('api_key') == '') {
                    throw new Exception(__('API key is not configured. Please set the api key in the plugin settings.', 'gateway-de-pagamento-pix-para-woocommerce'));
                }
                
                // Calcular data de expiração
                $daysToExpire = (int) $this->get_option('pix_key_type');
                if ($daysToExpire > 0) {
                    $expirationDate = new \DateTime();
                    $expirationDate->add(new \DateInterval('P' . $daysToExpire . 'D'));
                } else {
                    // Padrão: até o final do dia atual
                    $expirationDate = new \DateTime();
                    $expirationDate->setTime(23, 59, 59);
                }
                
                $email = sanitize_email($order->get_billing_email());
                $tel = sanitize_text_field($order->get_billing_phone());

                $data = array(
                    "apiKey" => $this->get_option('api_key'),
                    "order_id" => $order->get_id(),
                    "payer_email" => $email,
                    "payer_name" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    "payer_cpf_cnpj" => $cpf,
                    "days_due_date" => $this->get_option('pix_key_type'),
                    "notification_url" => home_url('/wp-json/pixPagHiper/verifyPix'),
                    'partners_id' => '14P9ZE4C',
                    "items" => array()
                );

                // Adicione os itens da ordem ao array de itens
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    $data['items'][] = array(
                        "item_id" => $product->get_id(),
                        "description" => $product->get_name(),
                        "quantity" => $item->get_quantity(),
                        "price_cents" => $product->get_price() * 100
                    );
                }

                $data['items'][] = array(
                    "item_id" => 'shipping',
                    "description" => 'Shipping',
                    "quantity" => 1,
                    "price_cents" => $order->get_shipping_total() * 100
                );

                $url = "https://pix.paghiper.com/invoice/create/";

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
                    throw new Exception($error_message);
                } else {
                    $bodyResponse = json_decode(wp_remote_retrieve_body($response))->pix_create_request;
                    if ($this->get_option('debug') === 'yes') {
                        $this->log->log('info', $this->id, array(
                            'response' => array(
                                'image_url' => $bodyResponse->pix_code->qrcode_image_url,
                                'qrcode_base64' => $bodyResponse->pix_code->qrcode_base64,
                                'qrcode_emv' => $bodyResponse->pix_code->qrcode_emv,
                                'transaction_id' => $bodyResponse->transaction_id,
                                'result' => $bodyResponse->result,
                                'response_message' => $bodyResponse->response_message
                            ),
                            'request' => array(
                                'url' => $url,
                                'data' => $data
                            ),
                        ));
                    }
                    if ($bodyResponse->result == 'success') {
                        $order->update_meta_data('_pix_qrcode_image_url', $bodyResponse->pix_code->qrcode_image_url);
                        $order->update_meta_data('_pix_qrcode_base64', $bodyResponse->pix_code->qrcode_base64);
                        $order->update_meta_data('_pix_qrcode_emv', $bodyResponse->pix_code->emv);
                        $order->update_meta_data('_pix_transaction_id', $bodyResponse->transaction_id);
                        
                        // Salvar data de expiração
                        $order->update_meta_data('_pix_expiration_date', $expirationDate->format('Y-m-d H:i:s'));
                        $order->update_meta_data('_pix_expiration_timestamp', $expirationDate->getTimestamp());
                        
                        $order->save();
                    } else {
                        throw new Exception($bodyResponse->response_message);
                    }
                }
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
                $valid = false;
            }
        }

        if ($valid) {
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            return array(
                'result'   => 'fail',
                'redirect' => ''
            );
        }

    }

    public function validateCpfCnpj($cpfCnpj)
    {
        // Remove caracteres especiais
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

        // Verifica se é CPF
        if (strlen($cpfCnpj) === 11) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{10}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += intval($cpfCnpj[$i]) * (10 - $i);
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += intval($cpfCnpj[$i]) * (11 - $i);
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[9] == $digit1 && $cpfCnpj[10] == $digit2) {
                return true;
            } else {
                return false;
            }
        }
        // Verifica se é CNPJ
        elseif (strlen($cpfCnpj) === 14) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{13}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0; $i < 12; $i++) {
                $sum += intval($cpfCnpj[$i]) * $weights[$i];
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0; $i < 13; $i++) {
                $sum += intval($cpfCnpj[$i]) * $weights[$i];
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[12] == $digit1 && $cpfCnpj[13] == $digit2) {
                return true;
            } else {
                return false;
            }
        }

        return false;
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
        wp_enqueue_script('LknPaymentPixForWoocommercePixPagHiper-js', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixPagHiper.js', array(), '1.0.0', true);
        wp_localize_script('LknPaymentPixForWoocommercePixPagHiper', 'phpVariables', array(
            'copiedText' => __('Copied!', 'gateway-de-pagamento-pix-para-woocommerce'),
        ));
    }
}
