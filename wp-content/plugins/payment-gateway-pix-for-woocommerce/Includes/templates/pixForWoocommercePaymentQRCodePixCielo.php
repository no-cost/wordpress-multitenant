<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

<div class="pixforwoo-qrcode-content-cielo" id="pixforwoo-cielo-block">
    <div class="pixforwoo-qrcode-section-cielo">
        <div class="pixforwoo-qrcode-title-container-cielo">
            <span class="pixforwoo-qrcode-title-text-cielo">
                <?php echo esc_attr__('Instruções de Pagamento (PIX):', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
        </div>
        <div class="pixforwoo-qrcode-instructions-cielo">
            <ol>
                <li>
                    <span><?php echo esc_html__('Clique no botão "Copiar" (código PIX).', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Acesse ao Aplicativo do seu Banco.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Entre na opção de PIX.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Selecione a opção PIX QR Code ou Copia e Cola.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Para pagamento via QR Code, utilize a câmera do seu celular, aponte para o código apresentado na tela.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Confirme todos os dados, verifique o valor e o destinatário do pagamento.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Finalize o PIX.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
                <li>
                    <span><?php echo esc_html__('Pagamento concluído em segundos.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
                </li>
            </ol>
        </div>
    </div>
    <div class="pixforwoo-qrcode-section-cielo">
        <div class="pixforwoo-qrcode-value-container-cielo">
            <span class="pixforwoo-qrcode-value-title-cielo">
                <?php echo esc_attr__('Total:', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
            <span class="pixforwoo-qrcode-value-amount-cielo" id="pixforwoo-qrcode-currency-text">
                <?php echo isset($currencyTxt) ? wp_kses_post($currencyTxt) : ''; ?>
            </span>
            <span class="pixforwoo-qrcode-value-date-cielo">
                <?php echo isset($dueDateMsg) ? esc_attr($dueDateMsg) : ''; ?>
            </span>
        </div>
        <div class="pixforwoo-qrcode-copy-container-cielo">
            <input
                type="text"
                class="pixforwoo-qrcode-copy-input-cielo"
                readonly
                style="border: none; background-color: #D9D9D9;"
                value="<?php echo isset($pixString) ? esc_attr($pixString) : ''; ?>"
            >
            <input
                type="hidden"
                id="pixforwoo-qrcode-donation-id"
                value="<?php echo esc_attr($donationId); ?>"
            >

            <button class="pixforwoo-qrcode-copy-btn-cielo">
                <?php echo esc_attr__('COPY', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </button>
        </div>
        <div class="pixforwoo-qrcode-image-container-cielo">
            <?php if (!empty($base64Image)): ?>
                <img src="data:image/png;base64,<?php echo esc_attr($base64Image); ?>" class="pixforwoo-qrcode-img-cielo" alt="PIX QR Code">
            <?php elseif (!empty($pixString)): ?>
                <img src="<?php echo esc_url($pixString); ?>" class="pixforwoo-qrcode-img-cielo" alt="PIX QR Code">
            <?php endif; ?>
        </div>
    </div>
</div>