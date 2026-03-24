<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Exception;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePixRedeRequest;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;

class LknPaymentPixForWoocommercePixRede extends WC_Payment_Gateway
{
    public static $request;
    public $configs;
    public $log;
    public $debug;

    public function __construct()
    {
        $this->id = 'lkn_rede_pix_for_woocommerce';
        $this->title = 'Rede Pix';
        $this->has_fields = true;
        $this->method_title       = esc_attr__('Pay with Rede Pix', 'gateway-de-pagamento-pix-para-woocommerce');
        $this->method_description = esc_attr__('Enables and configures payments with  Rede Pix', 'gateway-de-pagamento-pix-para-woocommerce');

        self::$request = new LknPaymentPixForWoocommercePixRedeRequest();

        // Define os campos de configuração do método de pagamento
        $this->init_form_fields();
        $this->init_settings();

        // Define as configurações do método de pagamento
        $this->title = $this->get_option('title');
        $this->log = $this->get_logger();
        $this->debug = $this->get_option('debug');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_ajax_lkn_pix_for_woocommerce_rede_pix_save_settings', array($this, 'lkn_rede_pix_save_settings'));
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
            'lkn-rede-admin-settings',
            PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Admin/js/PaymentPixForWoocommerceAdminRede.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('lkn-rede-admin-settings', 'redePixData', array(
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
                'block_title'       => __('Ativar pagamento com Rede Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Ative ou desative o Pix na sua loja. Para mais opções, use o <a target="_blank" href="https://br.wordpress.org/plugins/woo-rede/">Plugin Rede</a>', 'gateway-de-pagamento-pix-para-woocommerce'),
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
                'default'     => __('Pix Rede', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Defina o título para o pagamento com Pix no checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'description' => array(
                'title'       => __('Descrição', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira a descrição que será exibida ao usuário durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Descrição no Checkout', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Exibido aos clientes durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'textarea',
                'default'     => __('Pague sua compra com Pix usando a Rede.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Descrição exibida aos clientes durante o checkout.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'env' => array(
                'title'       => __('Ambiente', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Selecione o ambiente para a integração do Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Ambiente', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Selecione entre o ambiente de teste ou de produção.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'select',
                'options'     => array(
                    'test' => esc_attr__('Tests', 'gateway-de-pagamento-pix-para-woocommerce'),
                    'production' => esc_attr__('Production', 'gateway-de-pagamento-pix-para-woocommerce'),
                ),
                'default'     => 'production',
                'input_description' => esc_attr__('Selecione o ambiente para as requisições da API do Pix.', 'gateway-de-pagamento-pix-para-woocommerce'),
            ),
            'pv' => array(
                'title'       => __('Credenciais', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira suas credenciais.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Pv', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Insira seu Pv da Rede API.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'password',
                'input_description' => esc_attr__('Credencial da rede.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'custom_attributes' => array(
                    'required' => 'required'
                ),
            ),
            'token' => array(
                'title'       => __('Credenciais', 'gateway-de-pagamento-pix-para-woocommerce'),
                'description' => __('Insira suas credenciais.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_title'       => __('Token', 'gateway-de-pagamento-pix-para-woocommerce'),
                'block_sub_title' => __('Insira seu Token da Rede API.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'password',
                'input_description' => esc_attr__('Credencial da rede.', 'gateway-de-pagamento-pix-para-woocommerce'),
                'join' => 'pv',
                'custom_attributes' => array(
                    'required' => 'required'
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
                'block_sub_title' => __('Ao clicar, vai limpar todos os logs de pedidos Rede Pix', 'gateway-de-pagamento-pix-para-woocommerce'),
                'type'        => 'button',
                'id'          => 'validateLicense',
                'label' => __('Limpar Logs do Pedido', 'gateway-de-pagamento-pix-para-woocommerce'),
                'input_description' => esc_attr__('Limpa todos os logs armazenados nas páginas de pedido para liberar espaço ou redefinir dados.', 'gateway-de-pagamento-pix-para-woocommerce'),
            )
        );
    }

    public function process_payment($orderId)
    {
        $order = wc_get_order($orderId);

        $valid = true;

        try {
            $pixInfos = array(
                'generated' => $order->get_meta('_wc_rede_pix_integration_transaction_pix_generated'),
                'amount' => $order->get_meta('_wc_rede_pix_integration_transaction_amount'),
            );
            $total = str_replace(".", "", $order->get_total());
            if (empty($pixInfos['generated']) || $total != $pixInfos['amount']) {
                $reference = $orderId;
                if ($total != $pixInfos['amount'] && !empty($pixInfos['amount'])) {
                    $reference = uniqid();
                }

                $reference = $reference . '-' . time();

                $pix = LknPaymentPixForWoocommercePixRedeRequest::getPixRede($order->get_total(), $this, $reference, $order);
                if ("25" == $pix['returnCode'] || "89" == $pix['returnCode']) {
                    throw new Exception(__('PV or Token is invalid!', 'gateway-de-pagamento-pix-para-woocommerce'));
                }
                if ("00" != $pix['returnCode']) {
                    if ('yes' == $this->debug) {
                        $this->log->log('info', $this->id, array(
                            'order' => array(
                                'requestResponse' => $pix,
                            ),
                        ));
                    }
                    throw new Exception(__('An error occurred while processing the payment.', 'gateway-de-pagamento-pix-para-woocommerce'));
                }

                $pixReference = $pix['reference'];
                $pixTid = $pix['tid'];
                $pixAmount = $pix['amount'];
                $pixQrCodeData = $pix['qrCodeResponse']['qrCodeData'];
                $pixQrCodeImage = $pix['qrCodeResponse']['qrCodeImage'];

                if (! wp_next_scheduled('lkn_schedule_check_rede_pix_payment_hook', array($pixTid, $orderId))) {
                    wp_schedule_event(time(), "every_minute", 'lkn_schedule_check_rede_pix_payment_hook', array($pixTid, $orderId));
                }

                $order->update_meta_data('_lkn_rede_pix_integration_transaction_reference', $pixReference);
                $order->update_meta_data('_lkn_rede_pix_integration_transaction_tid', $pixTid);
                $order->update_meta_data('_lkn_rede_pix_integration_transaction_amount', $pixAmount);
                $order->update_meta_data('_lkn_rede_pix_integration_transaction_pix_code', $pixQrCodeData);
                $order->update_meta_data('_lkn_rede_pix_integration_transaction_pix_generated', true);
                $order->update_meta_data('_lkn_rede_pix_integration_transaction_pix_qrcode_base64', $pixQrCodeImage);
                if ('yes' == $this->debug) {
                    $this->log->log('info', $this->id, array(
                        'order' => array(
                            'pixReference' => $pixReference,
                            'pixTid' => $pixTid,
                            'pixAmount' => $pixAmount,
                            'pixQrCodeData' => $pixQrCodeData,
                            'pixQrCodeImage' => $pixQrCodeImage,
                        ),
                    ));
                }
                $order->save();
            }
        } catch (Exception $e) {
            $this->add_error($e->getMessage());
            $valid = false;
        }

        if ($valid) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }
    }

    public static function showPix($order_id): void
    {
        $order = wc_get_order($order_id);
        $paymentMethod = $order->get_payment_method();

        if ('lkn_rede_pix_for_woocommerce' === $paymentMethod && $order->get_total() > 0) {
            $paymentId = $order->get_meta('_lkn_rede_pix_integration_transaction_tid');
            $bas64Image = $order->get_meta('_lkn_rede_pix_integration_transaction_pix_qrcode_base64');
            $pixString = $order->get_meta('_lkn_rede_pix_integration_transaction_pix_code');
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

    public function payment_fields()
    {
        wc_get_template(
            '/pixForWoocommercePaymentFieldsPixRede.php',
            array(),
            'woocommerce/pix/',
            plugin_dir_path(__FILE__) . 'templates/'
        );
        wp_enqueue_style('lknPaymentPixForWoocommerceRedePixCss', PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL . 'Public/css/LknPaymentPixForWoocommercePixRedePaymentFields.css', array(), '1.0.0', 'all');
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

    public function lkn_rede_pix_save_settings()
    {
        check_ajax_referer('lkn_pix_for_woocommerce_rede_pix_settings_nonce');

        $settings = isset($_POST['settings']) ? json_decode(wp_unslash($_POST['settings']), true) : [];

        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $settings[$key] = sanitize_text_field($value);
            }
        }

        if (!is_array($settings)) {
            wp_send_json_error(['message' => 'Invalid settings data']);
        }

        update_option('woocommerce_lkn_rede_pix_for_woocommerce_settings', $settings);
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
