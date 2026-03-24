<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

use Lkn\PaymentPixForWoocommerce\Admin\LknPaymentPixForWoocommerceAdmin;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommercePix;
use Lkn\PaymentPixForWoocommerce\PublicView\LknPaymentPixForWoocommercePublic;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommerceHelper;
use WC_Order;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknPaymentPixForWoocommerce
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknPaymentPixForWoocommerceLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION')) {
            $this->version = PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'payment-pix-for-woocommerce';

        $this->load_dependencies();
        $this->set_locale();
        $this->loader->add_action('woocommerce_init', $this, 'define_hook');
    }

    public $LknPaymentPixForWoocommercePixClass;
    public $LknPaymentPixForWoocommercePixPagHiperClass;
    public $LknPaymentPixForWoocommercePixPagHiperEndpointClass;
    public $LknPaymentPixForWoocommercePixC6Class;
    public $LknPaymentPixForWoocommercePixC6EndpointClass;
    public $LknPaymentPixForWoocommercePixCieloClass;
    public $LknPaymentPixForWoocommercePixRedeClass;
    public $LknPaymentPixForWoocommerceHelper;
    public $LknPaymentPixForWoocommercePixEndpointClass;

    public function define_hook()
    {
        if (class_exists('WC_Payment_Gateway')) {
            $this->LknPaymentPixForWoocommercePixClass = new LknPaymentPixForWoocommercePix();
            $this->LknPaymentPixForWoocommercePixPagHiperClass = new LknPaymentPixForWoocommercePixPagHiper();
            $this->LknPaymentPixForWoocommercePixPagHiperEndpointClass = new LknPaymentPixForWoocommercePixPagHiperEndpoint();
            $this->LknPaymentPixForWoocommercePixC6Class = new LknPaymentPixForWoocommercePixC6();
            $this->LknPaymentPixForWoocommercePixC6EndpointClass = new LknPaymentPixForWoocommercePixC6Endpoint();
            $this->LknPaymentPixForWoocommercePixCieloClass = new LknPaymentPixForWoocommercePixCielo();
            $this->LknPaymentPixForWoocommerceHelper = new LknPaymentPixForWoocommerceHelper();
            $this->LknPaymentPixForWoocommercePixEndpointClass = new LknPaymentPixForWoocommercePixEndpoint();
            $this->LknPaymentPixForWoocommercePixRedeClass = new LknPaymentPixForWoocommercePixRede();
            $this->define_admin_hooks();
            $this->define_public_hooks();
        }
        $this->run();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LknPaymentPixForWoocommerceLoader. Orchestrates the hooks of the plugin.
     * - LknPaymentPixForWoocommerceI18n. Defines internationalization functionality.
     * - LknPaymentPixForWoocommerceAdmin. Defines all hooks for the admin area.
     * - LknPaymentPixForWoocommercePublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        $this->loader = new LknPaymentPixForWoocommerceLoader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the LknPaymentPixForWoocommerceI18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new LknPaymentPixForWoocommerceI18n();

        $this->loader->add_action('woocommerce_init', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $this->loader->add_filter('woocommerce_payment_gateways', $this, 'add_gateway');

        $plugin_admin = new LknPaymentPixForWoocommerceAdmin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_update_options_payment_gateways_' . $this->LknPaymentPixForWoocommercePixClass->id, $this->LknPaymentPixForWoocommercePixClass, "process_admin_options");
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->LknPaymentPixForWoocommercePixClass, "showPix");

        $this->loader->add_action('woocommerce_update_options_payment_gateways_' . $this->LknPaymentPixForWoocommercePixPagHiperClass->id, $this->LknPaymentPixForWoocommercePixPagHiperClass, "process_admin_options");
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->LknPaymentPixForWoocommercePixPagHiperClass, "showPix");
        $this->loader->add_action('woocommerce_update_options_payment_gateways_' . $this->LknPaymentPixForWoocommercePixC6Class->id, $this->LknPaymentPixForWoocommercePixC6Class, "process_admin_options");
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->LknPaymentPixForWoocommercePixC6Class, "showPix");
        $this->loader->add_action('wp_ajax_lkn_pix_for_woocommerce_c6_save_settings', $this->LknPaymentPixForWoocommercePixC6Class, 'lkn_pix_for_woocommerce_c6_save_settings');
        $this->loader->add_action('wp_ajax_nopriv_lkn_pix_for_woocommerce_c6_save_settings', $this->LknPaymentPixForWoocommercePixC6Class, 'lkn_pix_for_woocommerce_c6_save_settings');
        $this->loader->add_action('wp_ajax_lkn_pix_for_woocommerce_generate_nonce', $plugin_admin, 'generate_nonce');
        $this->loader->add_action('wp_ajax_nopriv_lkn_pix_for_woocommerce_generate_nonce', $plugin_admin, 'generate_nonce');
        $this->loader->add_action('wp_ajax_pixforwoo_test_c6_pix_charge', $this->LknPaymentPixForWoocommercePixC6Class, 'pixforwoo_test_c6_pix_charge');
        $this->loader->add_action('wp_ajax_nopriv_pixforwoo_test_c6_pix_charge', $this->LknPaymentPixForWoocommercePixC6Class, 'pixforwoo_test_c6_pix_charge');

        $this->loader->add_action('woocommerce_update_options_payment_gateways_' . $this->LknPaymentPixForWoocommercePixCieloClass->id, $this->LknPaymentPixForWoocommercePixCieloClass, "process_admin_options");
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->LknPaymentPixForWoocommercePixCieloClass, "showPix");

        //Cron de verificação de pagamento Cielo PIX
        $this->loader->add_action('lkn_schedule_check_cielo_pix_payment_hook', LknPaymentPixForWoocommercePixCieloRequest::class, 'check_payment', 10, 2);
        $this->loader->add_action('lkn_remove_custom_check_cielo_pix_payment_job_hook', LknPaymentPixForWoocommercePixCieloRequest::class, 'lkn_remove_custom_cron_job', 20, 2);

        $this->loader->add_action('woocommerce_update_options_payment_gateways_' . $this->LknPaymentPixForWoocommercePixRedeClass->id, $this->LknPaymentPixForWoocommercePixRedeClass, "process_admin_options");
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->LknPaymentPixForWoocommercePixRedeClass, "showPix");

        //Cron rede de verificação de pagamento Rede PIX
        $this->loader->add_action('lkn_schedule_check_rede_pix_payment_hook', LknPaymentPixForWoocommercePixRedeRequest::class, 'checkPixRedeStatus', 10, 2);
        $this->loader->add_action('lkn_remove_custom_check_rede_pix_payment_job_hook', LknPaymentPixForWoocommercePixRedeRequest::class, 'lkn_remove_custom_cron_job_rede', 20, 2);

        //Cron de verificação de pagamento C6 PIX
        $this->loader->add_action('lkn_check_c6_pix_payment_hook', LknPaymentPixForWoocommercePixC6Endpoint::class, 'check_c6_pix_payment_status', 10, 1);
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array
     * @param mixed $gateways
     */
    public function add_gateway($gateways)
    {
        if (isset($this->LknPaymentPixForWoocommercePixClass)) {
            $gateways[] = $this->LknPaymentPixForWoocommercePixClass;
        }
        if (isset($this->LknPaymentPixForWoocommercePixPagHiperClass)) {
            $gateways[] = $this->LknPaymentPixForWoocommercePixPagHiperClass;
        }
        if (isset($this->LknPaymentPixForWoocommercePixC6Class)) {
            $gateways[] = $this->LknPaymentPixForWoocommercePixC6Class;
        }
        if (isset($this->LknPaymentPixForWoocommercePixCieloClass)) {
            $gateways[] = $this->LknPaymentPixForWoocommercePixCieloClass;
        }
        if (isset($this->LknPaymentPixForWoocommercePixRedeClass)) {
            $gateways[] = $this->LknPaymentPixForWoocommercePixRedeClass;
        }
        return $gateways;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new LknPaymentPixForWoocommercePublic($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        if (isset($this->LknPaymentPixForWoocommercePixClass)) {
            $this->loader->add_action('wp_enqueue_scripts', $this->LknPaymentPixForWoocommercePixClass, 'checkoutScripts');
        }
        if (isset($this->LknPaymentPixForWoocommercePixPagHiperClass)) {
            $this->loader->add_action('wp_enqueue_scripts', $this->LknPaymentPixForWoocommercePixPagHiperClass, 'checkoutScripts');
        }
        if (isset($this->LknPaymentPixForWoocommercePixC6Class)) {
            $this->loader->add_action('wp_enqueue_scripts', $this->LknPaymentPixForWoocommercePixC6Class, 'checkoutScripts');
        }
        $this->loader->add_filter('plugin_action_links_' . PAYMENT_PIX_FOR_WOOCOMMERCE_BASENAME, $this, 'lknPaymentPixForWoocommercePluginRowMeta', 10, 2);
        
        if (isset($this->LknPaymentPixForWoocommercePixPagHiperEndpointClass)) {
            $this->loader->add_filter('rest_api_init', $this->LknPaymentPixForWoocommercePixPagHiperEndpointClass, 'registerVerifyPixEndPoint');
        }
        if (isset($this->LknPaymentPixForWoocommercePixC6EndpointClass)) {
            $this->loader->add_filter('rest_api_init', $this->LknPaymentPixForWoocommercePixC6EndpointClass, 'registerVerifyPixEndPoint');
        }
        
        if (isset($this->LknPaymentPixForWoocommercePixEndpointClass)) {
            $this->loader->add_filter('rest_api_init', $this->LknPaymentPixForWoocommercePixEndpointClass, 'registerRoutes');
        }
        
        $this->loader->add_filter('cron_schedules', $this, 'add_cron_intervals');

        $this->loader->add_action('before_woocommerce_init', $this, 'wcEditorBlocksActive');
        $this->loader->add_action('woocommerce_blocks_payment_method_type_registration', $this, 'wcEditorBlocksAddPaymentMethod');
        $this->loader->add_action('before_woocommerce_init', $this, 'lknpix_declare_compatibility');

        if (isset($this->LknPaymentPixForWoocommerceHelper)) {
            $this->loader->add_action('add_meta_boxes', $this->LknPaymentPixForWoocommerceHelper, 'showOrderLogs');
        }
    }


    public function lknpix_declare_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                PAYMENT_PIX_FOR_WOOCOMMERCE_BASENAME,
                true
            );
        }
    }

    /**
     * Adiciona intervalos customizados para o cron
     */
    public function add_cron_intervals($schedules)
    {
        $schedules['lkn_five_minutes'] = array(
            'interval' => 300, // 5 minutos em segundos
            'display'  => __('Every 5 Minutes', 'gateway-de-pagamento-pix-para-woocommerce')
        );
        return $schedules;
    }

    public function wcEditorBlocksActive()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                PAYMENT_PIX_FOR_WOOCOMMERCE_BASENAME,
                true
            );
        }
    }

    public function wcEditorBlocksAddPaymentMethod(\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry)
    {
        if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        $payment_method_registry->register(new LknPaymentPixForWoocommercePixBlocks());
        $payment_method_registry->register(new LknPaymentPixForWoocommercePixPagHiperBlocks());
        $payment_method_registry->register(new LknPaymentPixForWoocommercePixC6Blocks());
        $payment_method_registry->register(new LknPaymentPixForWoocommercePixCieloBlocks());
        $payment_method_registry->register(new LknPaymentPixForWoocommercePixRedeBlocks());
    }

    /**
     * Plugin row meta links.
     *
     * @since
     *
     * @param array  $plugin_meta an array of the plugin's metadata
     * @param string $plugin_file path to the plugin file, relative to the plugins directory
     *
     * @return array
     */
    public static function lknPaymentPixForWoocommercePluginRowMeta($plugin_meta, $plugin_file)
    {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'gateway-de-pagamento-pix-para-woocommerce')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }


    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknPaymentPixForWoocommerceLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
