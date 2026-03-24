<?php
if (!isset($form_fields) || !is_array($form_fields)) return;

// Agrupa campos por títulos (type 'title')
$blocks = [];
$current_block = null;
$first_block_id = null;
foreach ($form_fields as $key => $field) {
    if ($field['type'] === 'title') {
        $current_block = sanitize_title($field['title']);
        if ($first_block_id === null) {
            $first_block_id = $current_block;
        }
        $blocks[$current_block]['title'] = $field['title'];
        continue;
    }
    if ($current_block !== null) {
        $blocks[$current_block]['fields'][$key] = $field;
    }
}

// Indexa todos os campos para busca rápida de pai/filho
$field_index = [];
foreach ($form_fields as $key => $field) {
    $field_index[$key] = $field;
}
?>
<div class="admin-gateway-page-wrapper">
    <div class="admin-gateway-content">
        <div class="admin-gateway-main">
            <div class="admin-gateway-header">
                <h2 class="admin-gateway-title"><?php echo esc_html($method_title); ?></h2>
            </div>
            <nav class="admin-gateway-top-menu">
                <?php foreach ($blocks as $block_id => $block): ?>
                    <a href="#" class="admin-gateway-title-link<?php echo $block_id === $first_block_id ? ' active' : ''; ?>" data-target="block-<?php echo esc_attr($block_id); ?>">
                        <?php echo esc_html($block['title'] ?? ucfirst($block_id)); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('woocommerce-options'); ?>
                <?php foreach ($blocks as $block_id => $block): ?>
                    <div class="admin-gateway-block<?php echo $block_id === $first_block_id ? ' active' : ''; ?>" id="block-<?php echo esc_attr($block_id); ?>">
                        <?php
                        foreach ($block['fields'] as $key => $field):
                            if (!empty($field['join'])) continue;

                            // Busca filhos deste campo
                            $children = [];
                            foreach ($block['fields'] as $child_key => $child_field) {
                                if (!empty($child_field['join']) && $child_field['join'] === $key) {
                                    $children[$child_key] = $child_field;
                                }
                            }
                        ?>
                        <div class="admin-gateway-field-parent-flex">
                            <div class="admin-gateway-field-label-desc">
                                <?php if (!empty($field['title']) && $field['type'] !== 'title'): ?>
                                    <span class="admin-gateway-label">
                                        <?php echo esc_html($field['title']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($field['description'])): ?>
                                    <span class="admin-gateway-description">
                                        <?php echo esc_html($field['description']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-gateway-field-component-bg">
                                <span class="admin-gateway-label">
                                    <?php
                                    if (!empty($field['block_title'])) {
                                        echo esc_html($field['block_title']);
                                    } elseif (!empty($field['title']) && $field['type'] !== 'title') {
                                        echo esc_html($field['title']);
                                    }
                                    ?>
                                </span>
                                <?php
                                $sub = !empty($field['block_sub_title']) ? $field['block_sub_title'] : (!empty($field['description']) ? $field['description'] : '');
                                if ($sub):
                                ?>
                                    <span class="admin-gateway-description">
                                        <?php echo wp_kses_post($sub); ?>
                                    </span>
                                <?php endif; ?>
                                <hr class="admin-gateway-hr">
                                <div class="admin-gateway-field-input-wrapper">
                                    <?php
                                    // Renderiza o campo pai
                                    switch ($field['type']) {
                                        case 'text':
                                        case 'password':
                                        case 'number':
                                            ?>
                                            <input
                                                type="<?php echo esc_attr($field['type']); ?>"
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                value="<?php echo esc_attr($gateway->get_option($key)); ?>"
                                                class="admin-gateway-input"
                                                <?php
                                                if (isset($field['custom_attributes']) && is_array($field['custom_attributes'])) {
                                                    foreach ($field['custom_attributes'] as $attr => $val) {
                                                        echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                    }
                                                }
                                                ?>
                                            />
                                            <?php
                                            break;
                                        case 'color':
                                            ?>
                                            <input
                                                type="color"
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                value="<?php echo esc_attr($gateway->get_option($key)); ?>"
                                                class="admin-gateway-input"
                                            />
                                            <?php
                                            break;
                                        case 'url':
                                            ?>
                                            <input
                                                type="url"
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                value="<?php echo esc_attr($gateway->get_option($key)); ?>"
                                                placeholder="https://"
                                                class="admin-gateway-input"
                                            />
                                            <?php
                                            break;
                                        case 'button':
                                            $button_class = 'admin-gateway-button';
                                            if (empty($field['class'])) {
                                                $button_class .= ' button button-primary';
                                            } else {
                                                $button_class .= ' ' . esc_attr($field['class']);
                                            }
                                            ?>
                                            <button
                                                type="button"
                                                id="<?php echo esc_attr($key); ?>"
                                                class="<?php echo esc_attr($button_class); ?>"
                                                <?php
                                                if (isset($field['custom_attributes']) && is_array($field['custom_attributes'])) {
                                                    foreach ($field['custom_attributes'] as $attr => $val) {
                                                        echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                    }
                                                }
                                                ?>
                                            ><?php echo !empty($field['label']) ? esc_html($field['label']) : esc_html($field['title']); ?></button>
                                            <?php
                                            break;
                                        case 'textarea':
                                            ?>
                                            <textarea
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                class="admin-gateway-textarea"
                                            ><?php echo esc_textarea($gateway->get_option($key)); ?></textarea>
                                            <?php
                                            break;
                                        case 'checkbox':
                                            ?>
                                            <div class="admin-gateway-checkbox-wrapper">
                                                <label class="admin-gateway-checkbox-label">
                                                    <input
                                                        type="checkbox"
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                        id="<?php echo esc_attr($key); ?>"
                                                        value="yes"
                                                        class="admin-gateway-checkbox"
                                                        <?php checked($gateway->get_option($key), 'yes'); ?>
                                                    />
                                                    <?php if (!empty($field['label'])): ?>
                                                        <span class="admin-gateway-checkbox-label-text">
                                                            <?php echo wp_kses_post($field['label']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <?php
                                            break;
                                        case 'radio':
                                            if (!empty($field['options']) && is_array($field['options'])) {
                                                foreach ($field['options'] as $option_value => $option_label) {
                                                    ?>
                                                    <label class="admin-gateway-radio-label">
                                                        <input
                                                            type="radio"
                                                            name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                            value="<?php echo esc_attr($option_value); ?>"
                                                            <?php checked($gateway->get_option($key), $option_value); ?>
                                                            class="<?php echo !empty($field['class']) ? esc_attr($field['class']) : 'admin-gateway-radio'; ?>"
                                                            <?php
                                                            if (isset($field['custom_attributes']) && is_array($field['custom_attributes'])) {
                                                                foreach ($field['custom_attributes'] as $attr => $val) {
                                                                    echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                                }
                                                            }
                                                            ?>
                                                        />
                                                        <span class="admin-gateway-radio-label-text">
                                                            <?php echo esc_html($option_label); ?>
                                                        </span>
                                                    </label>
                                                    <?php
                                                }
                                            }
                                            break;
                                        case 'select':
                                            ?>
                                            <select
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                class="admin-gateway-select"
                                            >
                                                <?php foreach ($field['options'] as $option_key => $option_label): ?>
                                                    <option value="<?php echo esc_attr($option_key); ?>" <?php selected($gateway->get_option($key), $option_key); ?>>
                                                        <?php echo esc_html($option_label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php
                                            break;
                                        case 'file':
                                            ?>
                                            <input
                                                type="file"
                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $key); ?>"
                                                id="<?php echo esc_attr($key); ?>"
                                                class="admin-gateway-file"
                                            />
                                            <?php
                                            // Mostra apenas a mensagem e o nome do último arquivo salvo
                                            $file_path = $gateway->get_option($key);
                                            if (!empty($file_path)) {
                                                $file_name = basename($file_path);
                                                ?>
                                                <div class="admin-gateway-file-current">
                                                    <span>
                                                        <?php esc_html_e('Last file uploaded:', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                                                        <strong><?php echo esc_html($file_name); ?></strong>
                                                    </span>
                                                </div>
                                                <?php
                                            }
                                            break;
                                    }
                                    ?>
                                    <?php if (!empty($field['input_description'])): ?>
                                        <div class="admin-gateway-input-description">
                                            <?php echo esc_html($field['input_description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Renderiza os filhos dentro do .admin-gateway-field-component-bg do pai
                                foreach ($children as $child_key => $child_field): ?>
                                    <div class="admin-gateway-joined-label-desc">
                                        <?php if (!empty($child_field['block_title'])): ?>
                                            <span class="admin-gateway-label">
                                                <?php echo esc_html($child_field['block_title']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php
                                        $sub = !empty($child_field['block_sub_title']) ? $child_field['block_sub_title'] : (!empty($child_field['description']) ? $child_field['description'] : '');
                                        if ($sub):
                                        ?>
                                            <span class="admin-gateway-description admin-gateway-joined-description">
                                                <?php echo esc_html($sub); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="admin-gateway-joined-component-bg">
                                        <hr class="admin-gateway-hr">
                                        <div class="admin-gateway-field-input-wrapper">
                                            <?php
                                            // Renderiza o componente do filho
                                            switch ($child_field['type']) {
                                                case 'text':
                                                case 'password':
                                                case 'number':
                                                    ?>
                                                    <input
                                                        type="<?php echo esc_attr($child_field['type']); ?>"
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        value="<?php echo esc_attr($gateway->get_option($child_key)); ?>"
                                                        class="admin-gateway-input"
                                                        <?php
                                                        if (isset($child_field['custom_attributes']) && is_array($child_field['custom_attributes'])) {
                                                            foreach ($child_field['custom_attributes'] as $attr => $val) {
                                                                echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                            }
                                                        }
                                                        ?>
                                                    />
                                                    <?php
                                                    break;
                                                case 'color':
                                                    ?>
                                                    <input
                                                        type="color"
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        value="<?php echo esc_attr($gateway->get_option($child_key)); ?>"
                                                        class="admin-gateway-input"
                                                    />
                                                    <?php
                                                    break;
                                                case 'url':
                                                    ?>
                                                    <input
                                                        type="url"
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        value="<?php echo esc_attr($gateway->get_option($child_key)); ?>"
                                                        placeholder="https://"
                                                        class="admin-gateway-input"
                                                    />
                                                    <?php
                                                    break;
                                                case 'button':
                                                    $button_class = 'admin-gateway-button';
                                                    if (empty($child_field['class'])) {
                                                        $button_class .= ' button button-primary';
                                                    } else {
                                                        $button_class .= ' ' . esc_attr($child_field['class']);
                                                    }
                                                    ?>
                                                    <button
                                                        type="button"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        class="<?php echo esc_attr($button_class); ?>"
                                                        <?php
                                                        if (isset($child_field['custom_attributes']) && is_array($child_field['custom_attributes'])) {
                                                            foreach ($child_field['custom_attributes'] as $attr => $val) {
                                                                echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                            }
                                                        }
                                                        ?>
                                                    ><?php echo !empty($child_field['label']) ? esc_html($child_field['label']) : esc_html($child_field['title']); ?></button>
                                                    <?php
                                                    break;
                                                case 'textarea':
                                                    ?>
                                                    <textarea
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        class="admin-gateway-textarea"
                                                    ><?php echo esc_textarea($gateway->get_option($child_key)); ?></textarea>
                                                    <?php
                                                    break;
                                                case 'checkbox':
                                                    ?>
                                                    <div class="admin-gateway-checkbox-wrapper">
                                                        <label class="admin-gateway-checkbox-label">
                                                            <input
                                                                type="checkbox"
                                                                name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                                id="<?php echo esc_attr($child_key); ?>"
                                                                value="yes"
                                                                class="admin-gateway-checkbox"
                                                                <?php checked($gateway->get_option($child_key), 'yes'); ?>
                                                            />
                                                            <?php if (!empty($child_field['label'])): ?>
                                                                <span class="admin-gateway-checkbox-label-text">
                                                                    <?php echo wp_kses_post($child_field['label']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                    <?php
                                                    break;
                                                case 'radio':
                                                    if (!empty($child_field['options']) && is_array($child_field['options'])) {
                                                        foreach ($child_field['options'] as $option_value => $option_label) {
                                                            ?>
                                                            <label class="admin-gateway-radio-label">
                                                                <input
                                                                    type="radio"
                                                                    name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                                    value="<?php echo esc_attr($option_value); ?>"
                                                                    <?php checked($gateway->get_option($child_key), $option_value); ?>
                                                                    class="<?php echo !empty($child_field['class']) ? esc_attr($child_field['class']) : 'admin-gateway-radio'; ?>"
                                                                    <?php
                                                                    if (isset($child_field['custom_attributes']) && is_array($child_field['custom_attributes'])) {
                                                                        foreach ($child_field['custom_attributes'] as $attr => $val) {
                                                                            echo esc_attr($attr) . '="' . esc_attr($val) . '" ';
                                                                        }
                                                                    }
                                                                    ?>
                                                                />
                                                                <span class="admin-gateway-radio-label-text">
                                                                    <?php echo esc_html($option_label); ?>
                                                                </span>
                                                            </label>
                                                            <?php
                                                        }
                                                    }
                                                    break;
                                                case 'select':
                                                    ?>
                                                    <select
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        class="admin-gateway-select"
                                                    >
                                                        <?php foreach ($child_field['options'] as $option_key => $option_label): ?>
                                                            <option value="<?php echo esc_attr($option_key); ?>" <?php selected($gateway->get_option($child_key), $option_key); ?>>
                                                                <?php echo esc_html($option_label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php
                                                    break;
                                                case 'file':
                                                    ?>
                                                    <input
                                                        type="file"
                                                        name="woocommerce_<?php echo esc_attr($gateway->id . '_' . $child_key); ?>"
                                                        id="<?php echo esc_attr($child_key); ?>"
                                                        class="admin-gateway-file"
                                                    />
                                                    <?php
                                                    // Mostra apenas a mensagem e o nome do último arquivo salvo
                                                    $file_path = $gateway->get_option($child_key);
                                                    if (!empty($file_path)) {
                                                        $file_name = basename($file_path);
                                                        ?>
                                                        <div class="admin-gateway-file-current">
                                                            <span>
                                                                <?php esc_html_e('Last file uploaded:', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                                                                <strong><?php echo esc_html($file_name); ?></strong>
                                                            </span>
                                                        </div>
                                                        <?php
                                                    }
                                                    break;
                                            }
                                            if (!empty($child_field['input_description'])): ?>
                                                <div class="admin-gateway-input-description">
                                                    <?php echo esc_html($child_field['input_description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="admin-gateway-submit-wrapper">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save changes', 'gateway-de-pagamento-pix-para-woocommerce'); ?>
                    </button>
                </div>
            </form>
        </div>
        <aside class="admin-gateway-sidebar-bar">
            <div class="admin-gateway-sidebar">
                <?php
                $versions = 'Payment PIX for Woo ' . PAYMENT_PIX_FOR_WOOCOMMERCE_VERSION . ' | WooCommerce v' . WC()->version;

                wc_get_template(
                    'woocommercePaymentAdminSettingLinkCard.php',
                    array(
                        'backgrounds' => array(
                            'right' => plugin_dir_url(__FILE__) . '../assets/icons/backgroundCardRight.svg',
                            'left' => plugin_dir_url(__FILE__) . '../assets/icons/backgroundCardLeft.svg'
                        ),
                        'logo' => plugin_dir_url(__FILE__) . '../assets/images/linkNacionalLogo.webp',
                        'whatsapp' => plugin_dir_url(__FILE__) . '../assets/icons/whatsapp.svg',
                        'telegram' => plugin_dir_url(__FILE__) . '../assets/icons/telegram.svg',
                        'stars' => plugin_dir_url(__FILE__) . '../assets/icons/stars.svg',
                        'versions' => $versions
                    ),
                    '', // subpasta, pode ser vazio se não usar
                    plugin_dir_path(__FILE__) . '/'
                );
                ?>

                <div class="block-status-card block-status-card--success">
                    <div class="block-status-card-header">
                        <span class="dashicons dashicons-yes"></span>
                        <h4 class="block-status-card-title">NEW: C6 Payment Gateway</h4>
                    </div>
                    <p class="block-status-card-description">
                        Now you can integrate Pix payments directly with C6 Bank in WooCommerce.
                    </p>
                </div>

                <div class="block-status-card block-status-card--success">
                    <div class="block-status-card-header">
                        <span class="dashicons dashicons-layout"></span>
                        <h4 class="block-status-card-title">NEW: Robust and Custom Template</h4>
                    </div>
                    <p class="block-status-card-description">
                        Try the new admin interface, more modern and intuitive for managing your payments.
                    </p>
                </div>

                <div class="block-promotional-card">
                    <div class="block-promotional-card-bg"></div>
                    <div class="block-promotional-card-content">
                        <h3 class="block-promotional-card-title">
                            Plugin: Invoice Payment Link for WooCommerce
                        </h3>
                        <p class="block-promotional-card-description">
                            The Invoice Payment Plugin is the complete solution for your business. With it, you can generate payment links, split purchases across multiple cards, set up recurring charges, apply discounts and fees, and create detailed quotes.
                        </p>
                        <div class="block-promotional-card-buttons">
                            <a href="https://br.wordpress.org/plugins/invoice-payment-for-woocommerce/" target="_blank" class="block-promotional-card-btn block-promotional-card-btn-learn">
                                Learn more
                            </a>
                            <?php if (empty($invoice_plugin_installed) || !$invoice_plugin_installed): ?>
                                <a href="<?php echo esc_url('/wp-admin/update.php?action=install-plugin&plugin=invoice-payment-for-woocommerce&_wpnonce=' . $install_nonce); ?>"
                                target="_blank"
                                class="block-promotional-card-btn block-promotional-card-btn-install">
                                    Install
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>