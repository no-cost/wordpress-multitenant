<?php
/**
 * Account data
 *
 * @var string $title - block title
 * @var int $user_id - user ID
 * @var int $login - user login
 */
?>

<tr valign="top" id="wcu-select">
	<th scope="row" class="titledesc">
		<label for="account-data"><?php echo esc_html( $title ); ?></label>
	</th>
	<td class="forminp">
		<strong><?php esc_html_e( 'User ID', 'wcu' ); ?>:</strong> <?php echo esc_html( $user_id ); ?><br>
		<strong><?php esc_html_e( 'Login', 'wcu' ); ?>:</strong> <?php echo esc_html( $login ); ?><br><br>
		<a href="<?php echo esc_url_raw( get_admin_url() ) . 'admin.php?page=wc-settings&tab=wcu_settings&wcu_logout=1'; ?>" class="button"><?php esc_html_e( 'Logout', 'wcu' ); ?></a>
	</td>
</tr>
