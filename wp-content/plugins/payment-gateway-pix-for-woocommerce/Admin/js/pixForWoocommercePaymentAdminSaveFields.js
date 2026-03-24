jQuery(document).on('click', '.admin-gateway-submit-wrapper button', function (e) {
    // Primeiro verifica validação HTML5
    var form = jQuery('form#mainform')[0];
    if (form.checkValidity && !form.checkValidity()) {
        var firstInvalidField = form.querySelector(':invalid');
        document.documentElement.style.scrollBehavior = 'smooth';
        if (firstInvalidField) {
            toggleBlockVisibility(firstInvalidField);
            firstInvalidField.scrollIntoView({ block: 'center' });

            setTimeout(function () {
                firstInvalidField.reportValidity();
                document.documentElement.style.scrollBehavior = 'auto';
            }, 300);
        }
        return;
    }

    // Se chegou até aqui, a validação HTML5 passou
    e.preventDefault();

    var settings = {};
    var formData = new FormData();

    // Detecta automaticamente qual gateway está sendo usado
    var gatewayId = '';
    var actionSuffix = '';

    // Verifica se é C6, Cielo ou Rede baseado nos campos existentes
    if (jQuery('[name^="woocommerce_lkn_pix_for_woocommerce_c6_"]').length > 0) {
        gatewayId = 'lkn_pix_for_woocommerce_c6';
        actionSuffix = 'c6';
    } else if (jQuery('[name^="woocommerce_lkn_cielo_pix_for_woocommerce_"]').length > 0) {
        gatewayId = 'lkn_cielo_pix_for_woocommerce';
        actionSuffix = 'cielo_pix';
    } else if (jQuery('[name^="woocommerce_lkn_rede_pix_for_woocommerce_"]').length > 0) {
        gatewayId = 'lkn_rede_pix_for_woocommerce';
        actionSuffix = 'rede_pix';
    }

    if (!gatewayId) {
        return;
    }

    // Pega todos os campos do formulário
    jQuery('form [name^="woocommerce_' + gatewayId + '_"]').each(function () {
        var name = jQuery(this).attr('name').replace('woocommerce_' + gatewayId + '_', '');
        var type = jQuery(this).attr('type');
        if (type === 'checkbox') {
            settings[name] = jQuery(this).is(':checked') ? 'yes' : 'no';
        } else if (type === 'radio') {
            if (jQuery(this).is(':checked')) {
                settings[name] = jQuery(this).val();
            }
        } else if (type === 'file') {
            // Adiciona o arquivo ao FormData
            if (this.files && this.files.length > 0) {
                formData.append(name, this.files[0]);
                settings[name] = this.files[0].name;
                var fileName = this.files[0].name;
                var $wrapper = jQuery(this).closest('.admin-gateway-field-input-wrapper');
                var $fileCurrent = $wrapper.find('.admin-gateway-file-current');

                if ($fileCurrent.length) {
                    $fileCurrent.find('strong').text(fileName);
                } else {
                    // Cria o elemento do zero se não existir
                    var $newFileCurrent = jQuery(
                        '<div class="admin-gateway-file-current">' +
                        '<span>Last file uploaded: <strong>' + fileName + '</strong></span>' +
                        '</div>'
                    );
                    // Insere logo após o campo file
                    jQuery(this).after($newFileCurrent);
                }
            }
        } else {
            settings[name] = jQuery(this).val();
        }
    });

    // Adiciona settings ao FormData como JSON
    formData.append('settings', JSON.stringify(settings));

    // Primeiro, obtenha o nonce via AJAX
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'lkn_pix_for_woocommerce_generate_nonce',
            action_name: 'lkn_pix_for_woocommerce_' + actionSuffix + '_settings_nonce'
        },
        success: function (nonceResponse) {
            if (!nonceResponse.success || !nonceResponse.data.nonce) {
                alert('Erro ao obter nonce!');
                return;
            }

            formData.append('_ajax_nonce', nonceResponse.data.nonce);
            formData.append('action', 'lkn_pix_for_woocommerce_' + actionSuffix + '_save_settings');

            // Agora salva os dados e arquivos usando o nonce recebido
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    alert(response.data.message);
                }
            });
        }
    });
});

// Bloqueia submit via Enter
jQuery('form').on('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        return false;
    }
});


jQuery('form').on('change input', function () {
    window.onbeforeunload = null;
    window.removeEventListener('beforeunload', function () { });
    jQuery(window).off('beforeunload');
});

jQuery(document).ready(function ($) {
    $('#pix_expiration_minutes').on('input', function () {
        var val = $(this).val();

        // Se for letra, retorna para 1440
        if (isNaN(val) || /[a-zA-Z]/.test(val)) {
            $(this).val(1440);
            return;
        }

        // Se for menor que 1 ou vazio, retorna para 1
        if (parseInt(val) < 1 || val === '') {
            $(this).val(1);
        }
    });
});

function toggleBlockVisibility(input) {
    // Encontra o bloco que contém o campo vazio
    var $fieldBlock = jQuery(input).closest('.admin-gateway-block');
    var blockId = $fieldBlock.attr('id');

    // Se o bloco não estiver ativo, ativa ele
    if (!$fieldBlock.hasClass('active')) {
        // Remove active de todos os blocos e links
        jQuery('.admin-gateway-block').removeClass('active');
        jQuery('.admin-gateway-title-link').removeClass('active');

        // Ativa o bloco correto
        $fieldBlock.addClass('active');

        // Ativa o link correspondente
        jQuery('.admin-gateway-title-link[data-target="' + blockId + '"]').addClass('active');
    }
}