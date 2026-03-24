<?php

namespace Lkn\PaymentPixForWoocommerce\Includes;

/**
 * Fired during plugin deactivation
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    LknPaymentPixForWoocommerce
 * @subpackage LknPaymentPixForWoocommerce/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknPaymentPixForWoocommerceDeactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{
		wp_unschedule_hook('lkn_schedule_check_rede_pix_payment_hook');
		wp_unschedule_hook('lkn_schedule_check_cielo_pix_payment_hook');
		wp_unschedule_hook('lkn_check_c6_pix_payment_hook');

		wp_unschedule_hook('lkn_remove_custom_check_rede_pix_payment_job_hook');
		wp_unschedule_hook('lkn_remove_custom_check_cielo_pix_payment_job_hook');
	}
}
