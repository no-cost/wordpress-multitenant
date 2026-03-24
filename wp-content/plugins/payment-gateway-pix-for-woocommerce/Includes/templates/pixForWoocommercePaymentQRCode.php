<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

<div class="pixforwoo-qrcode-content" id="pixforwoo-basic-pix">
    <div class="pixforwoo-qrcode-section">
        <div class="pixforwoo-qrcode-title-container">
            <span class="pixforwoo-basic-qrcode-title-text">
                <?php echo esc_html__('Payment Instructions (PIX):', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
        </div>
        <div class="pixforwoo-basic-qrcode-instructions">
            <ol class="pixforwoo-qrcode-instructions-list">
                <li><?php echo esc_html__('Click the "Copy" button (PIX code).', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Access your bank app.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Go to the PIX option.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Select the PIX QR Code or Copy and Paste option.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('For QR Code payment, use your phone camera and point it at the code shown on screen.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Confirm all data, check the amount and payment recipient.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Complete the PIX transaction.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
                <li><?php echo esc_html__('Payment completed in seconds.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></li>
            </ol>
            <span class="pixforwoo-qrcode-important-notice">
                <strong><?php echo esc_html__('Important Notice:', 'gateway-de-pagamento-pix-para-woocommerce'); ?></strong>
                <?php echo esc_html__('This payment method does not have automatic confirmation. You must contact the store and send proof of payment for your order to be processed.', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
        </div>
    </div>
    <div class="pixforwoo-qrcode-section">
        <div class="pixforwoo-qrcode-value-container">
            <span class="pixforwoo-qrcode-value-title">
                <?php echo esc_html__('Total', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
            <span class="pixforwoo-qrcode-value-amount" id="pixforwoo-qrcode-currency-text">
                <!-- Valor será preenchido via JavaScript -->
            </span>
            <span class="pixforwoo-qrcode-value-date">
                <!-- Data será preenchida via JavaScript se necessário -->
            </span>
        </div>
        <div class="pixforwoo-qrcode-copy-container">
            <input
                type="text"
                class="pixforwoo-qrcode-copy-input"
                id="pixforwoo-qrcode-copy-input"
                readonly
                style="border: none; background-color: #D9D9D9;"
                value=""
            >
            <button class="pixforwoo-qrcode-copy-btn" id="pixforwoo-qrcode-copy-btn">
                <?php echo esc_html__('COPY', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </button>
        </div>
        <div class="pixforwoo-qrcode-image-container" id="pixforwoo-qrcode-image">
            <!-- QR Code será gerado via JavaScript -->
        </div>
    </div>
</div>