<br />
<div class="form-row form-row">
    <label
        id="labels-with-icons"
        for="pixForWoocommerceCieloPixBillingCpf"
        style="display: flex; align-items: center;">
        <?php echo esc_attr('CPF / CNPJ'); ?><span
            class="required">*</span>
        <div>
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
                xml:space="preserve">
                <g>
                    <path
                        class="svg"
                        d="M107.999,73c8.638,0,16.011-3.056,22.12-9.166c6.111-6.11,9.166-13.483,9.166-22.12c0-8.636-3.055-16.009-9.166-22.12c-6.11-6.11-13.484-9.165-22.12-9.165c-8.636,0-16.01,3.055-22.12,9.165c-6.111,6.111-9.166,13.484-9.166,22.12c0,8.637,3.055,16.01,9.166,22.12C91.99,69.944,99.363,73,107.999,73z"
                        style="fill: rgb(21, 140, 186);"></path>
                    <path
                        class="svg"
                        d="M165.07,106.037c-0.191-2.743-0.571-5.703-1.141-8.881c-0.57-3.178-1.291-6.124-2.16-8.84c-0.869-2.715-2.037-5.363-3.504-7.943c-1.466-2.58-3.15-4.78-5.052-6.6s-4.223-3.272-6.965-4.358c-2.744-1.086-5.772-1.63-9.085-1.63c-0.489,0-1.63,0.584-3.422,1.752s-3.815,2.472-6.069,3.911c-2.254,1.438-5.188,2.743-8.799,3.909c-3.612,1.168-7.237,1.752-10.877,1.752c-3.639,0-7.264-0.584-10.876-1.752c-3.611-1.166-6.545-2.471-8.799-3.909c-2.254-1.439-4.277-2.743-6.069-3.911c-1.793-1.168-2.933-1.752-3.422-1.752c-3.313,0-6.341,0.544-9.084,1.63s-5.065,2.539-6.966,4.358c-1.901,1.82-3.585,4.02-5.051,6.6s-2.634,5.229-3.503,7.943c-0.869,2.716-1.589,5.662-2.159,8.84c-0.571,3.178-0.951,6.137-1.141,8.881c-0.19,2.744-0.285,5.554-0.285,8.433c0,6.517,1.983,11.664,5.948,15.439c3.965,3.774,9.234,5.661,15.806,5.661h71.208c6.572,0,11.84-1.887,15.806-5.661c3.966-3.775,5.948-8.921,5.948-15.439C165.357,111.591,165.262,108.78,165.07,106.037z"
                        style="fill: rgb(21, 140, 186);"></path>
                </g>
            </svg>
        </div>
    </label>
    <input
        id="pixForWoocommerceCieloPixBillingCpf"
        name="billing_cpf"
        class="input-text"
        type="text"
        pattern="[0-9]*"
        placeholder="<?php echo esc_attr('CPF / CNPJ'); ?>"
        maxlength="14"
        autocomplete="off"
        style="font-size: 1.5em; padding: 8px 45px;"
        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
        required />
</div>

<?php 
// Verificar se a opção de botão está ativada (usando a mesma lógica do blocks)
// No blocks: cieloPixSettingsWoocommerce.show_button === 'yes'
$gateway_settings = get_option('woocommerce_lkn_cielo_pix_for_woocommerce_settings', array());
$show_button = isset($gateway_settings['show_button']) && $gateway_settings['show_button'] === 'yes';
?>

<?php if ($show_button): ?>
<div style="display: flex; justify-content: center; margin-top: 20px;">
    <button 
        type="button" 
        id="lkn-cielo-pix-button"
        class="wc-block-components-button wp-element-button wc-block-components-checkout-place-order-button contained lkn-rede-btn-pix"
        onclick="(function() {
            const button = document.querySelector('#place_order');
            if (button) {
                button.click();
            }
        })()"
        style="padding: 8px 21px; border-radius: 4px;">
        <div class="wc-block-components-button__text" style="display: flex; align-items: center; gap: 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="32" height="48" viewBox="0 0 48 48" style="left: auto; position: static; top: auto; transform: none;">
                <path fill="#4db6ac" d="M11.9,12h-0.68l8.04-8.04c2.62-2.61,6.86-2.61,9.48,0L36.78,12H36.1c-1.6,0-3.11,0.62-4.24,1.76	l-6.8,6.77c-0.59,0.59-1.53,0.59-2.12,0l-6.8-6.77C15.01,12.62,13.5,12,11.9,12z"></path>
                <path fill="#4db6ac" d="M36.1,36h0.68l-8.04,8.04c-2.62,2.61-6.86,2.61-9.48,0L11.22,36h0.68c1.6,0,3.11-0.62,4.24-1.76	l6.8-6.77c0.59-0.59,1.53-0.59,2.12,0l6.8,6.77C32.99,35.38,34.5,36,36.1,36z"></path>
                <path fill="#4db6ac" d="M44.04,28.74L38.78,34H36.1c-1.07,0-2.07-0.42-2.83-1.17l-6.8-6.78c-1.36-1.36-3.58-1.36-4.94,0	l-6.8,6.78C13.97,33.58,12.97,34,11.9,34H9.22l-5.26-5.26c-2.61-2.62-2.61-6.86,0-9.48L9.22,14h2.68c1.07,0,2.07,0.42,2.83,1.17	l6.8,6.78c0.68,0.68,1.58,1.02,2.47,1.02s1.79-0.34,2.47-1.02l6.8-6.78C34.03,14.42,35.03,14,36.1,14h2.68l5.26,5.26	C46.65,21.88,46.65,26.12,44.04,28.74z"></path>
            </svg>
            <div class="wc-block-components-checkout-place-order-button__text">
                Finalizar e Gerar PIX
            </div>
        </div>
    </button>
</div>
<?php endif; ?>