<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Exception;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePixCieloRequest;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

class LknPaymentPixForWoocommercePixCielo extends WC_Payment_Gateway
{

    public static $request;
    public $configs;
    public $log;
    public $debug;

    public function __construct()
    {
        $this->id = 'lkn_cielo_pix_for_woocommerce';
        $this->title = 'Cielo Pix';
        $this->has_fields = true;
        $this->method_title       = esc_attr__('Pay with Cielo Pix', 'gateway-de-pagamento-pix-para-woocommerce');
        $this->method_description = esc_attr__('Enables and configures payments with  Cielo Pix', 'gateway-de-pagamento-pix-para-woocommerce');

        self::$request = new LknPaymentPixForWoocommercePixCieloRequest();

        // Define os campos de configuração do método de pagamento
        $this->init_form_fields();
        $this->init_settings();

        // Define as configurações do método de pagamento
        $this->title = $this->get_option('title');
        $this->log = $this->get_logger();
        $this->debug = $this->get_option('debug');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_ajax_lkn_pix_for_woocommerce_cielo_pix_save_settings', array($this, 'lkn_cielo_pix_save_settings'));
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
        // Carregar JavaScript para as configurações administrativas
        wp_enqueue_script(
            'lkn-cielo-admin-settings',
            PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Admin/js/PaymentPixForWoocommerceAdminCielo.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('lkn-cielo-admin-settings', 'cieloPixData', array(
            'gateway_id' => $this->id
        ));

        // Chamar o método pai para mostrar as opções
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title'   => esc_attr__('Enable/Disable', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Ative esta opção para permitir que os clientes paguem com Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Ativar pagamento com Cielo Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Ative ou desative o Pix na sua loja. Para mais opções, use o <a target="_blank" href="https://br.wordpress.org/plugins/lkn-wc-gateway-cielo/">Plugin Cielo</a>', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        =>   'checkbox',
                'label'       => __('Permite pagamento com Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Ative ou desative o pagamento via Pix na sua loja.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'     => 'no',
                'desc_tip'    => __('Select this option to enable Pix payment method.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'title' => array(
                'title'       => __('Title', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Este campo controla o título que o usuário vê durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Título no Checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Exibido aos clientes durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'text',
                'default'     => __('Pix Cielo', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Defina o título para o pagamento com Pix no checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'description' => array(
                'title'       => __('Descrição', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira a descrição que será exibida ao usuário durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Descrição no Checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Exibido aos clientes durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'textarea',
                'default'     => __('Pague sua compra com Pix usando a Cielo.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Descrição exibida aos clientes durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'env' => array(
                'title'       => __('Ambiente', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Selecione o ambiente para a integração do Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Ambiente', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Selecione entre o ambiente de teste ou de produção.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'select',
                'options'     => array(
                    'production' => __('Production', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'sandbox'    => __('Development', 'gateway-de-pagamento-pix-para-woocommerce'),
                ),
                'default'     => 'production',
                'input_description' => esc_attr__('Selecione o ambiente para as requisições da API do Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'merchant_id' => array(
                'title'       => __('Credenciais', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira suas credenciais.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Merchant Id', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Insira seu Merchant ID da Cielo API 3.0.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'password',
                'input_description' => esc_attr__('Credencial da cielo.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'merchant_key' => array(
                'title'       => __('Credenciais', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira suas credenciais.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Merchant Key', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Insira sua Merchant Key da Cielo API 3.0.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'password',
                'input_description' => esc_attr__('Credencial da cielo.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'merchant_id',
                'custom_attributes' => array(
                    'required' => 'required',
                ),
            ),
            'payment_complete_status' => array(
                'title'       => esc_attr__('Status do Pagamento Concluído', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Escolha o status que será atribuído automaticamente após a confirmação do pagamento.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Status do Pagamento Concluído', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Define automaticamente o status do pedido após pagamento.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'select',
                'options'     => array(
                    'processing' => _x('Processing', 'Order status', 'woocommerce'),
                    'on-hold'    => _x('On hold', 'Order status', 'woocommerce'),
                    'completed'  => _x('Completed', 'Order status', 'woocommerce'),
                ),
                'default'     => 'processing',
                'input_description' => esc_attr__('Define o status que o WooCommerce deve atribuir ao pedido após um pagamento bem-sucedido usando este gateway.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'show_button' => array(
                'title' => esc_attr__('Ativar botão do Pix no checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Ative esta opção para exibir o botão de pagamento com Pix na página de checkout para os clientes.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Botão Gerar PIX', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Exibir botão de pagamento com Pix para os clientes.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
                'label' => __('Exibir botão de pagamento com Pix durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Exibe o botão "Finalizar e Gerar PIX" no checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'developer' => array(
                'title' => esc_attr__('Developer', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type' => 'title',
            ),
            'debug' => array(
                'title'       => __('Depuração', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Ative esta opção para registrar eventos de pagamento Pix para solução de problemas.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Modo de Depuração', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Ativar logs de depuração para solução de problemas.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'checkbox',
                'label'       => sprintf(
                    '%1$s. <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
                    __('Habilitar registros de depuração', 'gateway-de-pagamento-pix-para-woocommerce'),
                    admin_url('admin.php?page=wc-status&tab=logs'),
                    __('Ver logs', 'gateway-de-pagamento-pix-para-woocommerce')
                ),
                'default'     => 'no',
                'input_description' => esc_attr__('Ativa o registro de logs para ajudar na solução de problemas.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'show_order_logs' => array(
                'title'       => __('Exibir Logs do Pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Ative para ajudar no monitoramento e na resolução de problemas com transações.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Ativar Logs do pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Exibe registros de log associados ao pedido para análise.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Ativar a visualização de logs de transação na página do pedido.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'default'     => 'no',
                'input_description' => esc_attr__('Permite que o administrador visualize os logs de transação diretamente na página do pedido.', 'gateway-de-pagamento-pix-para-woocommerce'),

            ),
            'clear_order_records' => array(
                'title'       => __('Limpar Logs do Pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => esc_attr__('Use com cautela, pois esta ação é irreversível.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Limpar Logs do Pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Ao clicar, vai limpar todos os logs de pedidos Cielo Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'button',
                'id'          => 'validateLicense',
                'label' => __('Limpar Logs do Pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Limpa todos os logs armazenados nas páginas de pedido para liberar espaço ou redefinir dados.', 'gateway-de-pagamento-pix-para-woocommerce'),
            )
        );
    }

    public function payment_fields()
    {
        $template_path = plugin_dir_path(__FILE__) . 'templates/pixForWoocommercePaymentFieldsPixCielo.php';

        if (file_exists($template_path)) {
            wc_get_template(
                '/pixForWoocommercePaymentFieldsPixCielo.php',
                array(),
                'woocommerce/pix/',
                plugin_dir_path(__FILE__) . 'templates/'
            );
        } else {
            // Fallback: mostra descrição simples se template não existir
            if ($this->description) {
                echo '<p>' . wp_kses_post($this->description) . '</p>';
            }
            echo '<div id="lkn-cielo-pix-payment-fields">
                    <p>' . esc_html__('PIX payment will be generated after order confirmation.', 'gateway-de-pagamento-pix-para-woocommerce') . '</p>
                </div>';
        }
        wp_enqueue_style('lknPaymentPixForWoocommercePixCss', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePaymentFields.css', array(), '1.0.0', 'all');

        wp_enqueue_script('lknPaymentPixForWoocommercePixJs', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixCieloShortcode.js', array(), '1.0.0', false);
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

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $paymentComplete = true;
        try {
            $firstName = sanitize_text_field($first_name);
            $lastName = sanitize_text_field($last_name);
            $currency = (string) $order->get_currency();

            $fullName = $firstName . ' ' . $lastName;
            if (empty($fullName)) {
                throw new Exception('Nome não informado');
            }

            if (isset($_POST['billing_cpf']) && '' === $_POST['billing_cpf']) {
                $_POST['billing_cpf'] = isset($_POST['billing_cnpj']) ? sanitize_text_field(wp_unslash($_POST['billing_cnpj'])) : '';
            }

            $billingCpfCpnj = array(
                'Identity' => isset($_POST['billing_cpf']) ? sanitize_text_field(wp_unslash($_POST['billing_cpf'])) : '',
                'IdentityType' => isset($_POST['billing_cpf']) && strlen(sanitize_text_field(wp_unslash($_POST['billing_cpf']))) === 14 ? 'CPF' : 'CNPJ'
            );

            if ('' === $billingCpfCpnj['Identity'] || ! $this->validateCpfCnpj($billingCpfCpnj['Identity'])) {
                throw new Exception(__('Please enter a valid CPF or CNPJ.', 'gateway-de-pagamento-pix-para-woocommerce'));
            }
            $amount = number_format((float) $order->get_total(), 2, '.', '');

            if (! $amount) {
                throw new Exception('Não foi possivel recuperar o valor da compra!', 1);
            }

            $response = self::$request->pix_request($fullName, $amount, $billingCpfCpnj, $this, $order);

            if (isset($response['success']) && $response['success'] === false) {
                throw new Exception(json_encode($response['response']), 1);
            }
            if (! is_array($response) && ! is_object($response)) {
                throw new Exception(json_encode($response), 1);
            }
            if (! $response['response']) {
                throw new Exception('Erro na Requisição. Tente novamente!', 1);
            }
            if (! wp_next_scheduled('lkn_schedule_check_cielo_pix_payment_hook', array($response["response"]["paymentId"], $order_id))) {
                wp_schedule_event(time(), "every_minute", 'lkn_schedule_check_cielo_pix_payment_hook', array($response["response"]["paymentId"], $order_id));
            }

            $order->update_meta_data('_pix_wc_cielo_qrcode_image', $response['response']['qrcodeImage']);
            $order->update_meta_data('_pix_wc_cielo_qrcode_string', $response['response']['qrcodeString']);
            $order->update_meta_data('_pix_wc_cielo_qrcode_payment_id', $response['response']['paymentId']);

            $order->save();
        } catch (\Throwable $th) {
            //throw $th;
            $paymentComplete = false;
            $this->add_error($th->getMessage());
        }

        if ($paymentComplete) {

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            $this->log->log('error', 'PIX Payment failed ', array('source' => 'gateway-de-pagamento-pix-para-woocommerce'));
            $this->add_notice_once(__('PIX Payment Failed', 'gateway-de-pagamento-pix-para-woocommerce'), 'error');
            throw new Exception(esc_attr(__('PIX Payment Failed', 'gateway-de-pagamento-pix-para-woocommerce')));
        }
    }

    public static function showPix($order_id): void
    {
        $order = wc_get_order($order_id);
        $paymentMethod = $order->get_payment_method();

        if ('lkn_cielo_pix_for_woocommerce' === $paymentMethod && $order->get_total() > 0) {
            $paymentId = $order->get_meta('pix_wc_cielo_qrcode_payment_id');
            $bas64Image = $order->get_meta('_pix_wc_cielo_qrcode_image');
            $pixString = $order->get_meta('_pix_wc_cielo_qrcode_string');
            wc_get_template('pixForWoocommercePaymentQRCodePixCielo.php', array(
                'paymentId' => $paymentId,
                'pixString' => $pixString,
                'base64Image' => $bas64Image,
                'currencyTxt' => wc_price($order->get_total()),
                'donationId' => $order->get_id()
            ), 'woocommerce/pix/', plugin_dir_path(__FILE__) . 'templates/');

            wp_enqueue_style('lkn-pix-for-woocommerce-cielo-style', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixCielo.css', array(), '1.0.0', 'all');

            wp_enqueue_script('lkn-pix-for-woocommerce-cielo-script', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/js/LknPaymentPixForWoocommercePixCielo.js', array(), '1.0.0', false);
            wp_localize_script('lkn-pix-for-woocommerce-cielo-script', 'phpVariables', array(
                'copiedText' => __('Copied!', 'gateway-de-pagamento-pix-para-woocommerce'),
                'currentTheme' => wp_get_theme()->get('Name') ?? ''
            ));
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
                $sum += (int) ($cpfCnpj[$i]) * (10 - $i);
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * (11 - $i);
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
            $weights = array(5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            $weights = array(6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 13; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
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

    public function lkn_cielo_pix_save_settings()
    {
        check_ajax_referer('lkn_pix_for_woocommerce_cielo_pix_settings_nonce');

        $settings = isset($_POST['settings']) ? json_decode(wp_unslash($_POST['settings']), true) : [];

        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $settings[$key] = sanitize_text_field($value);
            }
        }

        if (!is_array($settings)) {
            wp_send_json_error(['message' => 'Invalid settings data']);
        }

        update_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings', $settings);
        wp_send_json_success(['message' => 'Settings saved successfully!']);
    }

    public function process_admin_options()
    {
        if (wp_doing_ajax()) {
            return;
        }
        parent::process_admin_options();
    }

    private function add_notice_once($message, $type): void
    {
        if (! wc_has_notice($message, $type)) {
            wc_add_notice($message, $type);
        }
    }

    public function add_error($message): void
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
}
