<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://linknacional.com.br
 * @since             1.0.0
 * @package           LknPaymentPixForWoocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Pix for WooCommerce
 * Plugin URI:        https://linknacional.com.br/wordpress
 * Description:       This payment method offers your customers the convenience of making quick and secure payments using PIX, Brazil's instant payment system.
 * Version:           1.5.0
 * Author:            Link Nacional
 * Author URI:        https://linknacional.com.br/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gateway-de-pagamento-pix-para-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once 'payment-pix-for-woocommerce-file.php';
