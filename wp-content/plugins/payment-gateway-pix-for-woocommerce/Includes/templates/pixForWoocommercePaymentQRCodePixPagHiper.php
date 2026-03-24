<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

<div class="pixforwoo-qrcode-content" id="pixforwoo-paghiper-block">
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
                <?php echo isset($currency_txt) ? wp_kses_post($currency_txt) : ''; ?>
            </span>
            <span class="pixforwoo-qrcode-value-date">
                <?php echo isset($due_date_msg) ? esc_attr($due_date_msg) : ''; ?>
            </span>
        </div>
        <div class="pixforwoo-qrcode-copy-container">
            <input
                type="text"
                class="pixforwoo-qrcode-copy-input"
                readonly
                style="border: none; background-color: #D9D9D9;"
                value="<?php echo isset($pix_code_emv) ? esc_attr($pix_code_emv) : ''; ?>"
            >
            <input
                type="hidden"
                id="pixforwoo-qrcode-donation-id"
                value="<?php echo esc_attr($donation_id); ?>"
            >

            <input
                type="hidden"
                id="pixforwoo-qrcode-expiration-date"
                value="<?php echo esc_attr($expiration_pix_date); ?>"
            >
            <button class="pixforwoo-qrcode-copy-btn">
                <?php echo esc_attr__('COPY', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
            </button>
        </div>
        <div class="pixforwoo-qrcode-image-container">
            <?php if (!empty($pix_code_base64)): ?>
                <img src="data:image/png;base64,<?php echo esc_attr($pix_code_base64); ?>" class="pixforwoo-qrcode-img" alt="PIX QR Code">
            <?php elseif (!empty($pix_code_image_url)): ?>
                <img src="<?php echo esc_url($pix_code_image_url); ?>" class="pixforwoo-qrcode-img" alt="PIX QR Code">
            <?php endif; ?>
        </div>
    </div>
</div>