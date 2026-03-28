<?php
/**
 * Option value
 *
 * @var array $value
 * @package wcu\FileUploader
 */

?>
<style>
	#wcu-select .select2-selection__choice__remove {
		display: contents !important;
	}
</style>
<tr valign="top" id="wcu-select">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
	</th>
	<td class="forminp">
		<select id="<?php echo esc_attr( $value['id'] ); ?>"
				name="<?php echo esc_attr( $value['id'] ); ?>[]"
				multiple="multiple"
				class="wc-enhanced-select"
				style="width: 400px">
			<?php
			foreach ( $value['options'] as $term_id => $option_name ) {
				$selected = '';
				if ( in_array( $term_id, $value['value'], true ) ) {
					$selected = 'selected=selected';
				}
				?>
				<option value="<?php echo esc_attr( $term_id ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $option_name ); ?></option>
			<?php } ?>
		</select>
		<p class="description">File upload is enabled for the selected categories.<br>
			File upload buttons appear for all products if no categories are selected.</p>
	</td>
</tr>
