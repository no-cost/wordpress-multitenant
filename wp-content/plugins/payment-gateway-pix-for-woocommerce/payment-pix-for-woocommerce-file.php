<?php

use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommerce;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommerceActivator;
use Lkn\PaymentPixForWoocommerce\Includes\LknPaymentPixForWoocommerceDeactivator;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION', '1.5.0');

if (! defined('PAYMENT_PIX_FOR_WOOCOMMERCE_FILE')) {
    define('PAYMENT_PIX_FOR_WOOCOMMERCE_FILE', __DIR__ . '/payment-pix-for-woocommerce.php');
}

if (! defined('PAYMENT_PIX_FOR_WOOCOMMERCE_DIR')) {
    define('PAYMENT_PIX_FOR_WOOCOMMERCE_DIR', plugin_dir_path(PAYMENT_PIX_FOR_WOOCOMMERCE_FILE));
}

if (! defined('PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL')) {
    define('PAYMENT_PIX_FOR_WOOCOMMERCE_DIR_URL', plugin_dir_url(PAYMENT_PIX_FOR_WOOCOMMERCE_FILE));
}

if (! defined('PAYMENT_PIX_FOR_WOOCOMMERCE_BASENAME')) {
    define('PAYMENT_PIX_FOR_WOOCOMMERCE_BASENAME', plugin_basename(PAYMENT_PIX_FOR_WOOCOMMERCE_FILE));
}

if (! defined('PAYMENT_PIX_FOR_WOOCOMMERCE_GATEWAY_IDS')) {
    define('PAYMENT_PIX_FOR_WOOCOMMERCE_GATEWAY_IDS', json_encode([
        'lkn_pix_for_woocommerce_c6',
        'lkn_cielo_pix_for_woocommerce',
        'lkn_rede_pix_for_woocommerce'
    ]));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-payment-pix-for-woocommerce-activator.php
 */
function activate_payment_pix_for_woocommerce()
{
    LknPaymentPixForWoocommerceActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-payment-pix-for-woocommerce-deactivator.php
 */
function deactivate_payment_pix_for_woocommerce()
{
    LknPaymentPixForWoocommerceDeactivator::deactivate();
}

register_activation_hook(PAYMENT_PIX_FOR_WOOCOMMERCE_FILE, 'activate_payment_pix_for_woocommerce');
register_deactivation_hook(PAYMENT_PIX_FOR_WOOCOMMERCE_FILE, 'deactivate_payment_pix_for_woocommerce');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_payment_pix_for_woocommerce()
{

    $plugin = new LknPaymentPixForWoocommerce();
    $plugin->run();
}
run_payment_pix_for_woocommerce();
