<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

<div class="pixforwoo-qrcode-content" id="pixforwoo-c6-block">
    <div class="pixforwoo-qrcode-section">
        <div class="pixforwoo-qrcode-title-container">
            <span class="pixforwoo-qrcode-title-text">
                <?php echo esc_attr__('Instructions', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
        </div>
        <div class="pixforwoo-qrcode-instructions">
            <span><?php echo esc_html__('In your bank app, scan the QR Code or copy and paste the PIX code.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
            <span><?php echo esc_html__('Payment confirmation is automatic.', 'gateway-de-pagamento-pix-para-woocommerce'); ?></span>
        </div>
        <div class="pixforwoo-qrcode-check-container">
            <span class="pixforwoo-qrcode-check-text">
                <?php echo esc_attr__('Next verification in (Number of attempts: 5):', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
            <span id="pixforwoo-qrcode-timer">0s</span>
        </div>
        <div class="pixforwoo-qrcode-payment-check">
            <button class="pixforwoo-qrcode-payment-check-btn" disabled>
                <?php echo esc_attr__('I have already paid the PIX', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </button>
        </div>
        <span class="pixforwoo-qrcode-payment-check-info">
            <?php echo esc_attr__('By clicking this button, we will check if the payment has been successfully confirmed.', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
        </span>
    </div>
    <div class="pixforwoo-qrcode-section">
        <div class="pixforwoo-qrcode-value-container">
            <span class="pixforwoo-qrcode-value-title">
                <?php echo esc_attr__('Total', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </span>
            <span class="pixforwoo-qrcode-value-amount" id="pixforwoo-qrcode-currency-text">
                <?php echo isset($currencyTxt) ? wp_kses_post($currencyTxt) : ''; ?>
            </span>
            <span class="pixforwoo-qrcode-value-date">
                <?php echo isset($dueDateMsg) ? esc_attr($dueDateMsg) : ''; ?>
            </span>
        </div>
        <div class="pixforwoo-qrcode-copy-container">
            <input
                type="text"
                class="pixforwoo-qrcode-copy-input"
                readonly
                style="border: none; background-color: #D9D9D9;"
                value="<?php echo isset($pixCodeEmv) ? esc_attr($pixCodeEmv) : ''; ?>"
            >
            <input
                type="hidden"
                id="pixforwoo-qrcode-donation-id"
                value="<?php echo esc_attr($donationId); ?>"
            >

            <input
                type="hidden"
                id="pixforwoo-qrcode-expiration-date"
                value="<?php echo esc_attr($expirationPixDate); ?>"
            >
            <button class="pixforwoo-qrcode-copy-btn">
                <?php echo esc_attr__('COPY', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </button>
        </div>
        <div class="pixforwoo-qrcode-image-container">
            <?php if (!empty($pixCodeBase64)): ?>
                <img src="data:image/png;base64,<?php echo esc_attr($pixCodeBase64); ?>" class="pixforwoo-qrcode-img" alt="PIX QR Code">
            <?php elseif (!empty($pixCodeImageUrl)): ?>
                <img src="<?php echo esc_url($pixCodeImageUrl); ?>" class="pixforwoo-qrcode-img" alt="PIX QR Code">
            <?php endif; ?>
        </div>
    </div>
</div>