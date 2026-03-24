<?php
if (! defined('ABSPATH')) {
    exit();
}
?>
<div>
	<p class="lknPaymentPixForWoocommercePagHiperTitle">
		<?php esc_html_e('Pay for your purchase with pix using Pix PagHiper', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
	</p>
	<div class="form-row form-row">
		<label
			id="labels-with-icons"
			for="lknPaymentPixForWoocommercePagHiperInput"
			class="lknPaymentPixForWoocommercePagHiperInputLabel"
		>
			<?php echo esc_attr('CPF / CNPJ'); ?><span
				class="required"
			>*</span>
			<div
				class="icon-maxipago-input"
				id="lknPaymentPixForWoocommercePagHiperInputIcon"
			>
				<svg
					version="1.1"
					id="Capa_1"
					xmlns="http://www.w3.org/2000/svg"
					xmlns:xlink="http://www.w3.org/1999/xlink"
					x="0px"
					y="4px"
					width="24px"
					height="16px"
					viewBox="0 0 216 146"
					enable-background="new 0 0 216 146"
					xml:space="preserve"
				>
					<g>
						<path
							class="svg"
							d="M107.999,73c8.638,0,16.011-3.056,22.12-9.166c6.111-6.11,9.166-13.483,9.166-22.12c0-8.636-3.055-16.009-9.166-22.12c-6.11-6.11-13.484-9.165-22.12-9.165c-8.636,0-16.01,3.055-22.12,9.165c-6.111,6.111-9.166,13.484-9.166,22.12c0,8.637,3.055,16.01,9.166,22.12C91.99,69.944,99.363,73,107.999,73z"
							style="fill: rgb(21, 140, 186);"
						></path>
						<path
							class="svg"
							d="M165.07,106.037c-0.191-2.743-0.571-5.703-1.141-8.881c-0.57-3.178-1.291-6.124-2.16-8.84c-0.869-2.715-2.037-5.363-3.504-7.943c-1.466-2.58-3.15-4.78-5.052-6.6s-4.223-3.272-6.965-4.358c-2.744-1.086-5.772-1.63-9.085-1.63c-0.489,0-1.63,0.584-3.422,1.752s-3.815,2.472-6.069,3.911c-2.254,1.438-5.188,2.743-8.799,3.909c-3.612,1.168-7.237,1.752-10.877,1.752c-3.639,0-7.264-0.584-10.876-1.752c-3.611-1.166-6.545-2.471-8.799-3.909c-2.254-1.439-4.277-2.743-6.069-3.911c-1.793-1.168-2.933-1.752-3.422-1.752c-3.313,0-6.341,0.544-9.084,1.63s-5.065,2.539-6.966,4.358c-1.901,1.82-3.585,4.02-5.051,6.6s-2.634,5.229-3.503,7.943c-0.869,2.716-1.589,5.662-2.159,8.84c-0.571,3.178-0.951,6.137-1.141,8.881c-0.19,2.744-0.285,5.554-0.285,8.433c0,6.517,1.983,11.664,5.948,15.439c3.965,3.774,9.234,5.661,15.806,5.661h71.208c6.572,0,11.84-1.887,15.806-5.661c3.966-3.775,5.948-8.921,5.948-15.439C165.357,111.591,165.262,108.78,165.07,106.037z"
							style="fill: rgb(21, 140, 186);"
						></path>
					</g>
				</svg>
			</div>
		</label>
		<input
			id="lknPaymentPixForWoocommercePagHiperInput"
			name="pix_for_woocommerce_cpf"
			class="input-text"
			type="text"
			pattern="[0-9]*"
			placeholder="<?php echo esc_attr('CPF / CNPJ'); ?>"
			maxlength="20"
			autocomplete="off"
			style="font-size: 1.5em; padding: 8px 45px;"
			oninput="this.value = this.value.replace(/[^0-9]/g, '')"
			required
		/>
	</div>
</div>