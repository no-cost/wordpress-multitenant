<?php
namespace Lkn\PaymentPixForWoocommerce\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/admin
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknPaymentPixForWoocommerceAdmin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LknPaymentPixForWoocommerceLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LknPaymentPixForWoocommerceLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/payment-pix-for-woocommerce-admin.css', array(), $this->version, 'all' );

		$page_is_wc_settings = (
			isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'wc-settings' &&
			isset($_GET['section']) && in_array(
				sanitize_text_field(wp_unslash($_GET['section'])),
				json_decode(PAYMENT_PIX_FOR_WOOCOMMERCE_GATEWAY_IDS, true),
				true
			)
		);

		if ($page_is_wc_settings) {
			wp_enqueue_style(
				'payment-pix-for-woo-admin-fields',
				plugin_dir_url( __FILE__ ) . 'css/pixForWoocommercePaymentAdminFields.css',
				array(),
				PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION
			);

			wp_enqueue_style(
				'payment-pix-for-woo-link-card',
				plugin_dir_url( __FILE__ ) . 'css/pixForWoocommercePaymentAdminSettingLinkCard.css',
				array(),
				PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LknPaymentPixForWoocommerceLoader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LknPaymentPixForWoocommerceLoader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/payment-pix-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

		$page_is_wc_settings = (
			isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'wc-settings' &&
			isset($_GET['section']) && in_array(
				sanitize_text_field(wp_unslash($_GET['section'])),
				json_decode(PAYMENT_PIX_FOR_WOOCOMMERCE_GATEWAY_IDS, true),
				true
			)
		);

		if ($page_is_wc_settings) {
			wp_enqueue_script(
				'payment-pix-for-woo-admin-gateway-fields',
				plugin_dir_url( __FILE__ ) . 'js/pixForWoocommercePaymentAdminFields.js',
				array('jquery'),
				PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
				true
			);

			wp_enqueue_script(
				'payment-pix-for-woo-admin-gateway-save-fields',
				plugin_dir_url( __FILE__ ) . 'js/pixForWoocommercePaymentAdminSaveFields.js',
				array('jquery'),
				PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
				true
			);

			wp_enqueue_script(
				'payment-pix-for-woo-admin-gateway-test-integration',
				plugin_dir_url(__FILE__) . 'js/pixForWoocommercePaymentAdminTestIntegration.js',
				array('jquery'),
				PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION,
				true
			);
		}
	}

	public function generate_nonce()
	{
		if (empty($_REQUEST['action_name'])) {
			wp_send_json_error(['message' => 'Missing action_name parameter.'], 400);
		}

		$action = sanitize_text_field(wp_unslash($_REQUEST['action_name']));
		$nonce = wp_create_nonce($action);

		wp_send_json_success(['nonce' => $nonce, 'action' => $action]);
	}
}
