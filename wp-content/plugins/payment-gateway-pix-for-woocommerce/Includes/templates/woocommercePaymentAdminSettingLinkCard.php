<?php
if (!defined('ABSPATH')) {
    exit();
}
?>

<div class="pixforwoo-link-settings-card" style="background-image: url('<?php echo esc_url($backgrounds['right']); ?>'), url('<?php echo esc_url($backgrounds['left']); ?>');">
    <div class="pixforwoo-link-logo">
        <div>
            <img src="<?php echo esc_url($logo); ?>" alt="Logo">
        </div>
        <p><?php echo esc_attr($versions); ?></p>
    </div>
    <div class="pixforwoo-link-content">
        <div class="pixforwoo-link-links">
            <div>
                <a target="_blank" href="<?php echo esc_url('https://wordpress.org/plugins/woo-better-shipping-calculator-for-brazil/'); ?>">
                    <b>•</b><?php echo esc_attr__('Documentation', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                </a>
                <a target="_blank" href="<?php echo esc_url('https://www.linknacional.com.br/wordpress/'); ?>">
                    <b>•</b><?php echo esc_attr__('Hosting', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                </a>
            </div>
            <div>
                <a target="_blank" href="<?php echo esc_url('https://www.linknacional.com.br/wordpress/plugins/'); ?>">
                    <b>•</b><?php echo esc_attr__('WP Plugin', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                </a>
                <a target="_blank" href="<?php echo esc_url('https://www.linknacional.com.br/wordpress/suporte/'); ?>">
                    <b>•</b><?php echo esc_attr__('WP Support', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                </a>
            </div>
        </div>
        <div class="pixforwoo-support-links">
            <div class="pixforwoo-stars-div">
                <a target="_blank" href="<?php echo esc_url('https://br.wordpress.org/plugins/woo-better-shipping-calculator-for-brazil/#reviews'); ?>">
                    <p><?php echo esc_attr__('Avaliar o plugin', 'gateway-de-pagamento-pix-para-woocommerce'); ?></p>
                    <div class="pixforwoo-stars">
                        <span class="dashicons dashicons-star-filled pixforwoo-stars-icon"></span>
                        <span class="dashicons dashicons-star-filled pixforwoo-stars-icon"></span>
                        <span class="dashicons dashicons-star-filled pixforwoo-stars-icon"></span>
                        <span class="dashicons dashicons-star-filled pixforwoo-stars-icon"></span>
                        <span class="dashicons dashicons-star-filled pixforwoo-stars-icon"></span>
                    </div>
                </a>
            </div>
            <div class="pixforwoo-contact-links">
                <a href="<?php echo esc_url('https://chat.whatsapp.com/IjzHhDXwmzGLDnBfOibJKO'); ?>" target="_blank">
                    <img src="<?php echo esc_url($whatsapp); ?>" alt="Whatsapp Icon" class="pixforwoo-contact-icon">
                </a>
                <a href="<?php echo esc_url('https://t.me/wpprobr'); ?>" target="_blank">
                    <img src="<?php echo esc_url($telegram); ?>" alt="Telegram Icon" class="pixforwoo-contact-icon">
                </a>
            </div>
        </div>
    </div>
</div>