<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Exception;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

class LknPaymentPixForWoocommercePixC6 extends WC_Payment_Gateway
{
    public $configs;
    public $log;
    public $debug;

    public function __construct()
    {
        $this->id = 'lkn_pix_for_woocommerce_c6';
        $this->title = 'Pix C6';
        $this->has_fields = true;
        $this->method_title       = esc_attr__('Pay with Pix C6', 'gateway-de-pagamento-pix-para-woocommerce');
        $this->method_description = esc_attr__('Enable automatic payment confirmation through a C6 account', 'gateway-de-pagamento-pix-para-woocommerce');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->log = $this->get_logger();
        $this->debug = $this->get_option('debug');
    }

    public function showPix($orderWCID)
    {
        $order = wc_get_order($orderWCID);

        if ($order->get_payment_method() == 'lkn_pix_for_woocommerce_c6') {
            if ((($order->get_status() == 'processing' || $order->get_status() == 'completed') && $this->get_option('hidde_paid_pix') === 'yes')) {
                return;
            }

            $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);

            $created_timestamp = $order->get_meta('_c6_pix_created');
            $created_date_order = !empty($created_timestamp) ? date_i18n('d/m/Y H:i:s', intval($created_timestamp)) : '';

            $expiration_timestamp = $order->get_meta('_c6_pix_expiration');
            $expirationPixDate = !empty($expiration_timestamp) ? date_i18n('d/m/Y H:i:s', intval($expiration_timestamp)) : '';

            $generate_after_exp = isset($settings['generate_pix_after_expiration']) ? $settings['generate_pix_after_expiration'] : 'no';
            $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
            $now = time() + $offset;

            // Se está marcado para gerar novo Pix, pedido cancelado e expirado
            if (
                $generate_after_exp === 'yes' &&
                ($order->get_status() === 'cancelled' || $order->get_status() === 'failed') &&
                !empty($expiration_timestamp) &&
                $now > intval($expiration_timestamp)
            ) {
                // Muda status para pending
                $order->update_status('pending');
                $order->add_order_note(__('Order returned to pending and new Pix generated after expiration.', 'gateway-de-pagamento-pix-para-woocommerce'));

                // Dados necessários para gerar novo Pix
                $environment = isset($settings['environment']) ? $settings['environment'] : 'production';
                $base_url = ($environment === 'sandbox')
                    ? 'https://baas-api-sandbox.c6bank.info'
                    : 'https://baas-api.c6bank.info';

                $pix_key = isset($settings['pix_key']) ? $settings['pix_key'] : '';
                $client_id = isset($settings['client_id']) ? $settings['client_id'] : '';
                $client_secret = isset($settings['client_secret']) ? $settings['client_secret'] : '';
                $crt_path = !empty($settings['certificate_crt_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_crt_path'] : '';
                $key_path = !empty($settings['certificate_key_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_key_path'] : '';

                // Auth token
                $auth_result = LknPaymentPixForWoocommercePixC6Endpoint::get_c6_auth_token(
                    $crt_path,
                    $key_path,
                    $client_id,
                    $client_secret,
                    $base_url
                );
                if (!empty($auth_result['error'])) {
                    $order->add_order_note(__('Error generating new Pix: ', 'gateway-de-pagamento-pix-para-woocommerce') . $auth_result['error']);
                    $order->save();
                    return;
                }
                $access_token = $auth_result['access_token'];

                // CPF/CNPJ do pedido
                $cpfCnpjType = $order->get_meta('_cpf_cnpj_type');
                $cpfCnpjValue = $order->get_meta('_cpf_cnpj_value');

                $extra_data = [
                    'cpf_cnpj_type' => $cpfCnpjType,
                    'cpf_cnpj_value' => $cpfCnpjValue,
                    'expiration' => isset($settings['pix_expiration_minutes'])
                        ? intval($settings['pix_expiration_minutes']) * 60
                        : 1440 * 60
                ];

                // Gera novo Pix
                $pix_result = LknPaymentPixForWoocommercePixC6Endpoint::create_pix_charge(
                    $access_token,
                    $crt_path,
                    $key_path,
                    $base_url,
                    $pix_key,
                    $order,
                    $extra_data
                );

                if (!empty($pix_result['error'])) {
                    $order->add_order_note(__('Error generating new Pix: ', 'gateway-de-pagamento-pix-para-woocommerce') . $pix_result['error']);
                    $order->save();
                    return;
                }

                $pixCopiaECola = $pix_result['pixCopiaECola'];
                $pixTxID = $pix_result['txid'];
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($pixCopiaECola);

                // Atualiza metadados do Pix
                $order->update_meta_data('_pix_qrcode_emv', $pixCopiaECola);
                $order->update_meta_data('_pix_qrcode_image_url', $qrCodeUrl);
                $order->update_meta_data('_pix_txid', $pixTxID);
                $order->update_meta_data('_c6_pix_created', time() + $offset);
                $order->update_meta_data('_c6_pix_expiration', time() + $offset + $extra_data['expiration']);
                $order->save();

                // Inicia o cron de verificação de status do PIX C6
                LknPaymentPixForWoocommercePixC6Endpoint::schedule_c6_pix_check($orderWCID);

                // Atualiza datas para template
                $created_date_order = date_i18n('d/m/Y H:i:s', time() + $offset);
                $expirationPixDate = date_i18n('d/m/Y H:i:s', time() + $offset + $extra_data['expiration']);
            }

            wc_get_template(
                'pixForWoocommercePaymentQRCodePixC6.php',
                array(
                    'pixCodeBase64'    => $order->get_meta('_pix_qrcode_base64'),
                    'pixCodeImageUrl'  => $order->get_meta('_pix_qrcode_image_url'),
                    'pixCodeEmv'       => $order->get_meta('_pix_qrcode_emv'),
                    'currencyTxt'      => wc_price($order->get_total()),
                    'dueDateMsg'       => $created_date_order,
                    'expirationPixDate' => $expirationPixDate,
                    'donationId'       => $order->get_id()
                ),
                'woocommerce/payment/',
                plugin_dir_path(__FILE__) . 'templates/'
            );
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'title_general' => array(
                'title' => esc_attr__('General settings', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'enabled' => array(
                'title'   => esc_attr__('Enable/Disable', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enables payment with Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Enable this option to allow customers to pay using Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' => 'no',
                'block_title' => esc_attr__('Enable Pix Payment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Activate or deactivate Pix for your store.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Enable or disable Pix payment for your store.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'title' => array(
                'title'       => esc_attr__('Title', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'text',
                'description' => __('This field controls the title which the user sees during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'     => __('Pay with Pix C6', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip'    => true,
                'block_title' => esc_attr__('Checkout Title', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Displayed to customers during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Set the title for Pix payment on checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'description' => array(
                'title'       => esc_attr__('Description', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'textarea',
                'description' => esc_attr__('Enter the description that will be shown to the user during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'     => esc_attr__('Pay for your purchase with Pix using C6 Bank.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip'    => true,
                'block_title' => esc_attr__('Checkout Description', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Shown to customers during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Description shown to customers during checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'environment' => array(
                'title' => esc_attr__('Environment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'select',
                'description' => esc_attr__('Select the environment for Pix integration.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => 'production',
                'options' => array(
                    'sandbox'    => esc_attr__('Sandbox', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'production' => esc_attr__('Production', 'gateway-de-pagamento-pix-para-woocommerce'),
                ),
                'block_title' => esc_attr__('Environment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Choose between test or production environment.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Select the environment for Pix API requests.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'pix_key' => array(
                'title' => esc_attr__('Credentials', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'text',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter your credentials.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'block_title' => esc_attr__('Pix Key', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Your registered Pix key at C6 Bank.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Your Pix key registered at C6 Bank.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'client_id' => array(
                'title' => esc_attr__('C6 Client ID', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'password',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter your C6 Client ID.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'block_title' => esc_attr__('Client ID', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Provided by C6 Bank for API access.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Your C6 Bank Client ID.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'pix_key'
            ),
            'client_secret' => array(
                'title' => esc_attr__('C6 Client Secret', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'password',
                'custom_attributes' => array(
                    'required' => 'required'
                ),
                'description' => esc_attr__('Enter your C6 Client Secret.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'block_title' => esc_attr__('Client Secret', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Provided by C6 Bank for API access.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Your C6 Bank Client Secret.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'pix_key'
            ),
            'certificate_crt_path' => array(
                'title' => esc_attr__('Certificates', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'file',
                'description' => esc_attr__('Upload your .crt and .key certificates files.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'block_title' => esc_attr__('Certificate .crt', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Required for secure Pix transactions.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Upload the .crt certificate file for secure Pix transactions.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'certificate_key_path' => array(
                'title' => esc_attr__('Certificate .key File', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'file',
                'description' => esc_attr__('Upload your .key certificate file.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => '',
                'block_title' => esc_attr__('Certificate .key', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Required for secure Pix transactions.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Upload the .key certificate file for secure Pix transactions.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'certificate_crt_path'
            ),
            'additional_resources' => array(
                'title' => esc_attr__('Additional Resources', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'pix_expiration_minutes' => array(
                'title'             => esc_attr__('Expiration option', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'              => 'number',
                'description'       => esc_attr__('Set the expiration time for Pix payment.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'           => 1440, // 24 horas em minutos
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'min' => 1
                ),
                'block_title'       => esc_attr__('Pix Expiration Time (minutes)', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title'   => esc_attr__('Set how long Pix payment is valid.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Expiration time for Pix payment in minutes. For example, 1440 minutes is equivalent to 1 day (24 hours). Adjust this value according to your business needs.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'generate_pix_after_expiration' => array(
                'title' => esc_attr__('Generate Pix After Expiration', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_attr__('Enable to generate a new Pix payment when the order status changes from cancelled or failed back to pending.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Enable this option to automatically generate a new Pix payment if the order returns to pending after being cancelled or failed.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => 'no',
                'block_title' => esc_attr__('Generate Pix After Expiration', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Automatically generate Pix for returned orders.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Generate a new Pix payment if the order returns to pending.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'pix_expiration_minutes',
            ),
            'generate_pix_button' => array(
                'title' => esc_attr__('Enable Pix Button on Checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_attr__('Show Pix payment button during checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Enable this option to display the Pix payment button on the checkout page for customers.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'default' => 'yes',
                'block_title' => esc_attr__('Pix Button on Checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Show Pix payment button for customers.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Show Pix payment button on the checkout page.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'hidde_paid_pix' => array(
                'title' => esc_attr__('Hide Pix after payment', 'gateway-de-pagamento-pix-para-woocommerce'),
                'label' => esc_attr__('Hide Pix QRCode for logged in customers with processing or completed order', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'desc_tip'    => true,
                'description' => esc_attr__('Enable this option to hide the Pix QR Code in my customer account that is processing or completed.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' =>  'no',
                'block_title' => esc_attr__('Hide Pix QRCode', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Hide Pix QRCode for completed or processing orders.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Hide Pix QRCode for customers with completed or processing orders.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'developer' => array(
                'title' => esc_attr__('Developer', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'title',
            ),
            'debug' => array(
                'title' => esc_attr__('Debug', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_attr__('Enable debug logs.', 'gateway-de-pagamento-pix-para-woocommerce') . ' ' . wp_kses_post('<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs')) . '" target="_blank">' . __('See logs', 'gateway-de-pagamento-pix-para-woocommerce') . '</a>'),
                'description' => esc_attr__('Enable this option to log Pix payment events for troubleshooting.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default' =>  'no',
                'block_title' => esc_attr__('Debug Mode', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Enable debug logs for troubleshooting.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Enable debug logs for troubleshooting.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'test_integration' => array(
                'title' => esc_attr__('Test Integration', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'  => 'button',
                'label' => esc_attr__('Test Integration', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Click to test the integration after saving your settings.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'desc_tip' => true,
                'block_title' => esc_attr__('Test Integration', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => esc_attr__('Test your Pix integration after saving.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Test your Pix integration after saving.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
        );
    }

    public function payment_fields()
    {
        $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);
        $generate_pix_button = isset($settings['generate_pix_button']) ? $settings['generate_pix_button'] : 'no';

        wc_get_template(
            '/pixForWoocommercePaymentFieldsPixC6.php',
            array(
                'generate_pix_button' => $generate_pix_button,
            ),
            'woocommerce/pix/',
            plugin_dir_path(__FILE__) . 'templates/'
        );
        wp_enqueue_script(
            'lkn-pix-for-woocommerce-c6-shortcode',
            PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixC6Shortcode.js',
            array('jquery'),
            PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
            true
        );
        wp_enqueue_style('lknPaymentPixForWoocommercePixC6Css', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixC6.css', array(), '1.0.0', 'all');
        wp_enqueue_style('lknPaymentPixForWoocommercePixC6CssFields', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');
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

        try {
            // Get settings
            $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);

            // Get environment
            $environment = isset($settings['environment']) ? $settings['environment'] : 'production';
            $base_url = ($environment === 'sandbox')
                ? 'https://baas-api-sandbox.c6bank.info'
                : 'https://baas-api.c6bank.info';

            // Get Pix Key, Client ID and Client Secret
            $pix_key = isset($settings['pix_key']) ? $settings['pix_key'] : '';
            $client_id = isset($settings['client_id']) ? $settings['client_id'] : '';
            $client_secret = isset($settings['client_secret']) ? $settings['client_secret'] : '';

            // Get certificate paths
            $crt_path = !empty($settings['certificate_crt_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_crt_path'] : '';
            $key_path = !empty($settings['certificate_key_path']) ? PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . $settings['certificate_key_path'] : '';

            // Validations
            if (empty($pix_key)) {
                throw new Exception('Pix Key not configured.');
            }
            if (empty($client_id) || empty($client_secret)) {
                throw new Exception('Client ID or Client Secret not configured.');
            }
            if (empty($crt_path) || !file_exists($crt_path)) {
                throw new Exception('Certificate .crt file not found: ' . $crt_path);
            }
            if (empty($key_path) || !file_exists($key_path)) {
                throw new Exception('Certificate .key file not found: ' . $key_path);
            }

            // Validate CPF/CNPJ
            $cpfCnpj = isset($_POST['pix_for_woocommerce_cpf_cnpj']) ? sanitize_text_field(wp_unslash($_POST['pix_for_woocommerce_cpf_cnpj'])) : '';
            $cpfCnpjData = $this->validateCpfCnpjType($cpfCnpj);

            if (!$cpfCnpjData['valid']) {
                throw new Exception(__('Please enter a valid CPF or CNPJ.', 'gateway-de-pagamento-pix-para-woocommerce'));
            }

            $order->update_meta_data('_cpf_cnpj_type', $cpfCnpjData['type']);
            $order->update_meta_data('_cpf_cnpj_value', $cpfCnpjData['value']);

            // Prepare extra data for Pix charge
            $extra_data = [
                'cpf_cnpj_type' => $cpfCnpjData['type'],
                'cpf_cnpj_value' => $cpfCnpjData['value'],
                'expiration'      => isset($settings['pix_expiration_minutes'])
                    ? intval($settings['pix_expiration_minutes']) * 60
                    : 1440 * 60
            ];

            // 1. Get Auth Token using static method
            $auth_result = LknPaymentPixForWoocommercePixC6Endpoint::get_c6_auth_token(
                $crt_path,
                $key_path,
                $client_id,
                $client_secret,
                $base_url
            );

            if (!empty($auth_result['error'])) {
                throw new Exception($auth_result['error']);
            }

            $access_token = $auth_result['access_token'];

            // 2. Create Pix using static method, passing extra data
            $pix_result = LknPaymentPixForWoocommercePixC6Endpoint::create_pix_charge(
                $access_token,
                $crt_path,
                $key_path,
                $base_url,
                $pix_key,
                $order,
                $extra_data // <-- agora envia o array de dados extras
            );

            $debug_mode = isset($settings['debug']) ? $settings['debug'] : 'no';

            if ($debug_mode === 'yes' && !empty($this->log)) {
                $masked_pix_result = $this->mask_sensitive_data($pix_result);
                $this->log->log('info', $this->id, $masked_pix_result);
            }

            if (!empty($pix_result['error'])) {
                throw new Exception($pix_result['error']);
            }

            $pixCopiaECola = $pix_result['pixCopiaECola'];
            $pixTxID = $pix_result['txid'];

            // Generate QR Code URL using public API
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($pixCopiaECola);

            // Save to order
            $order->update_meta_data('_pix_qrcode_emv', $pixCopiaECola);
            $order->update_meta_data('_pix_qrcode_image_url', $qrCodeUrl);
            $order->update_meta_data('_generate_pix_after_expiration', isset($settings['generate_pix_after_expiration']) ? $settings['generate_pix_after_expiration'] : 'no');
            $order->update_meta_data('_pix_txid', $pixTxID);
            $order->save();

            // Inicia o cron de verificação de status do PIX C6
            LknPaymentPixForWoocommercePixC6Endpoint::schedule_c6_pix_check($orderId);

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        } catch (Exception $e) {
            $this->add_error($e->getMessage());
            return [
                'result'   => 'fail',
                'redirect' => ''
            ];
        }
    }

    private function mask_sensitive_data($data)
    {
        // Mascara CPF/CNPJ
        if (isset($data['devedor'])) {
            if (isset($data['devedor']['cpf'])) {
                $cpf = $data['devedor']['cpf'];
                $data['devedor']['cpf'] = substr($cpf, 0, 3) . str_repeat('*', strlen($cpf) - 3);
            }
            if (isset($data['devedor']['cnpj'])) {
                $cnpj = $data['devedor']['cnpj'];
                $data['devedor']['cnpj'] = substr($cnpj, 0, 3) . str_repeat('*', strlen($cnpj) - 3);
            }
        }
        // Mascara chave Pix
        if (isset($data['chave'])) {
            $chave = $data['chave'];
            $data['chave'] = substr($chave, 0, 5) . str_repeat('*', strlen($chave) - 5);
        }
        return $data;
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

    public function validateCpfCnpjType($cpfCnpj)
    {
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

        if (strlen($cpfCnpj) === 11) {
            $isValid = $this->validateCpfCnpj($cpfCnpj);
            return [
                'valid' => $isValid,
                'type'  => $isValid ? 'cpf' : null,
                'value' => $cpfCnpj
            ];
        } elseif (strlen($cpfCnpj) === 14) {
            $isValid = $this->validateCpfCnpj($cpfCnpj);
            return [
                'valid' => $isValid,
                'type'  => $isValid ? 'cnpj' : null,
                'value' => $cpfCnpj
            ];
        }
        return [
            'valid' => false,
            'type'  => null,
            'value' => $cpfCnpj
        ];
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
        wp_enqueue_style(
            'payment-pix-for-woo-pix-template',
            PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixC6.css',
            array(),
            PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
            'all'
        );

        wp_enqueue_script(
            'payment-pix-for-woo-pix-template',
            PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixC6.js',
            array('jquery'),
            PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
            true
        );

        $api_url = get_rest_url(null, 'pixforwoo/verify_c6_pix_status');

        $settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);
        $generate_after_exp = isset($settings['generate_pix_after_expiration']) ? $settings['generate_pix_after_expiration'] : 'no';

        if ($generate_after_exp === 'yes') {
            $expiredPaymentMsg = __('Pix expired, please refresh the page to update the payment order.', 'gateway-de-pagamento-pix-para-woocommerce');
        } else {
            $expiredPaymentMsg = __('Pix expired, please generate a new order.', 'gateway-de-pagamento-pix-para-woocommerce');
        }

        wp_localize_script(
            'payment-pix-for-woo-pix-template',
            'phpVarsPix',
            array(
                'nextVerify'        => __('Next verification in (Number of attempts:', 'gateway-de-pagamento-pix-para-woocommerce'),
                'successPayment'    => __('Payment confirmed!', 'gateway-de-pagamento-pix-para-woocommerce'),
                'pixButton'         => __('I have already paid the PIX', 'gateway-de-pagamento-pix-para-woocommerce'),
                'copy'              => __('COPY', 'gateway-de-pagamento-pix-para-woocommerce'),
                'copied'            => __('COPIED!', 'gateway-de-pagamento-pix-para-woocommerce'),
                'shareTitle'        => __('Share PIX code', 'gateway-de-pagamento-pix-para-woocommerce'),
                'shareError'        => __('Your browser does not support sharing.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'expiredPayment'    => $expiredPaymentMsg,
                'expiredPaymentDate' => __('Pix expired at:', 'gateway-de-pagamento-pix-para-woocommerce'),
                'apiUrl'            => $api_url,
            )
        );
    }

    public function admin_options()
    {
        $plugin_path = 'invoice-payment-for-woocommerce/wc-invoice-payment.php';
        $invoice_plugin_installed = file_exists(WP_PLUGIN_DIR . '/' . $plugin_path);

        wc_get_template(
            'pixForWoocommercePaymentAdminFields.php', // nome do template
            array(
                'form_fields'  => $this->form_fields,
                'method_title' => $this->get_method_title(),
                'gateway'      => $this, // opcional, para acessar métodos do gateway
                'install_nonce' => wp_create_nonce('install-plugin_invoice-payment-for-woocommerce'),
                'plugin_slug' => 'invoice-payment-for-woocommerce',
                'invoice_plugin_installed' => $invoice_plugin_installed
            ),
            '', // subpasta, se necessário
            plugin_dir_path(__FILE__) . 'templates/'
        );
    }

    public function lkn_pix_for_woocommerce_c6_save_settings()
    {
        check_ajax_referer('lkn_pix_for_woocommerce_c6_settings_nonce');

        // The $_POST['settings'] value is sanitized right after decoding below.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $settings = isset($_POST['settings']) ? json_decode(wp_unslash($_POST['settings']), true) : [];

        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $settings[$key] = sanitize_text_field($value);
            }
        }

        if (!is_array($settings)) {
            wp_send_json_error(['message' => 'Invalid settings data']);
        }

        if (isset($settings['pix_expiration_minutes'])) {
            $settings['pix_expiration_minutes'] = max(1, intval($settings['pix_expiration_minutes']));
        }

        // Inicializa o WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Diretório dos certificados
        $base_dir = PAYMENT_PIX_FOR_WOOCOMMERCE_DIR . 'Includes/files/';
        $certs_dir = $base_dir . 'certs_c6/';

        if (!$wp_filesystem->is_dir($base_dir)) {
            $wp_filesystem->mkdir($base_dir, FS_CHMOD_DIR);
        }
        if (!$wp_filesystem->is_dir($certs_dir)) {
            $wp_filesystem->mkdir($certs_dir, FS_CHMOD_DIR);
        }

        $has_crt = !empty($_FILES['certificate_crt_path']['name']);
        $has_key = !empty($_FILES['certificate_key_path']['name']);

        if ($has_crt || $has_key) {
            $this->lkn_clear_cert_folder($certs_dir);
        }

        // Processa o upload do arquivo .crt
        if ($has_crt) {
            $crt_filename = isset($_FILES['certificate_crt_path']['name']) ? sanitize_file_name($_FILES['certificate_crt_path']['name']) : '';
            $crt_target = $certs_dir . $crt_filename;

            $tmp_file = isset($_FILES['certificate_crt_path']['tmp_name']) ? sanitize_text_field($_FILES['certificate_crt_path']['tmp_name']) : '';
            if (is_uploaded_file($tmp_file)) {
                $wp_filesystem->copy($tmp_file, $crt_target, true);
                $settings['certificate_crt_path'] = 'Includes/files/certs_c6/' . $crt_filename;
            } else {
                wp_send_json_error(['message' => 'Failed to upload .crt certificate file.']);
            }
        }

        // Processa o upload do arquivo .key
        if ($has_key) {
            $key_filename = isset($_FILES['certificate_key_path']['name']) ? sanitize_file_name($_FILES['certificate_key_path']['name']) : '';
            $key_target = $certs_dir . $key_filename;

            $tmp_file = isset($_FILES['certificate_key_path']['tmp_name']) ? sanitize_text_field($_FILES['certificate_key_path']['tmp_name']) : '';
            if (is_uploaded_file($tmp_file)) {
                $wp_filesystem->copy($tmp_file, $key_target, true);
                $settings['certificate_key_path'] = 'Includes/files/certs_c6/' . $key_filename;
            } else {
                wp_send_json_error(['message' => 'Failed to upload .key certificate file.']);
            }
        }

        $old_settings = get_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', []);

        if (!$has_crt && isset($old_settings['certificate_crt_path'])) {
            $settings['certificate_crt_path'] = $old_settings['certificate_crt_path'];
        }
        if (!$has_key && isset($old_settings['certificate_key_path'])) {
            $settings['certificate_key_path'] = $old_settings['certificate_key_path'];
        }

        update_option('woocommerce_lkn_pix_for_woocommerce_c6_settings', $settings);

        wp_send_json_success(['message' => 'Settings saved successfully!']);
    }

    // Função para limpar a pasta
    private function lkn_clear_cert_folder($dir)
    {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        $files = $wp_filesystem->dirlist($dir);
        if (is_array($files)) {
            foreach ($files as $file) {
                $file_path = $dir . $file['name'];
                if ($wp_filesystem->is_file($file_path)) {
                    $wp_filesystem->delete($file_path);
                }
            }
        }
    }

    public function pixforwoo_test_c6_pix_charge()
    {
        $nonce = isset($_POST['pixforwoo_nonce']) ? sanitize_text_field(wp_unslash($_POST['pixforwoo_nonce'])) : '';
        if (
            empty($nonce) ||
            !wp_verify_nonce($nonce, 'pixforwoo_test_c6_pix_charge')
        ) {
            wp_send_json_error([
                'status' => 'error',
                'message' => 'Invalid nonce. Please reload the page and try again.'
            ]);
            wp_die();
        }

        // Sanitiza os parâmetros recebidos
        $client_id    = isset($_POST['client_id']) ? sanitize_text_field(wp_unslash($_POST['client_id'])) : '';
        $client_secret = isset($_POST['client_secret']) ? sanitize_text_field(wp_unslash($_POST['client_secret'])) : '';
        $pix_key      = isset($_POST['pix_key']) ? sanitize_text_field(wp_unslash($_POST['pix_key'])) : '';
        $environment  = isset($_POST['environment']) ? sanitize_text_field(wp_unslash($_POST['environment'])) : 'sandbox';

        // Validação básica
        if (empty($client_id) || empty($client_secret) || empty($pix_key)) {
            wp_send_json_error([
                'status' => 'error',
                'message' => 'Missing required parameters: client_id, client_secret or pix_key.'
            ]);
        }

        // Chama a função stub
        $result = LknPaymentPixForWoocommercePixC6Endpoint::generate_c6_pix_qrcode_stub(
            $client_id,
            $client_secret,
            $pix_key,
            $environment
        );

        // Retorna a resposta
        if ($result['status'] === 'success') {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
