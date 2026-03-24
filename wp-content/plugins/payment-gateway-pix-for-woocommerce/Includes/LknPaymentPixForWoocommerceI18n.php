<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknPaymentPixForWoocommerceI18n
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {
        // No need to call load_plugin_textdomain() for plugins hosted on WordPress.org.
        // WordPress loads translations automatically since version 4.6.

        // load_plugin_textdomain(
        //     'gateway-de-pagamento-pix-para-woocommerce',
        //     false,
        //     dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        // );
    }



}
