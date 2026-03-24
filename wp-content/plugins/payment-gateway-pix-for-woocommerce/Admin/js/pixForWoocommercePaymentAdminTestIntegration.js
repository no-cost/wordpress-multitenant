jQuery(document).ready(function ($) {
    $('#test_integration').on('click', function (e) {
        e.preventDefault();

        // Pega os campos necessários
        var client_id = $('#client_id').val();
        var client_secret = $('#client_secret').val();
        var pix_key = $('#pix_key').val();
        var environment = $('#environment').val() || 'sandbox';

        // Cria ou seleciona a div de resultado
        var $resultDiv = $('#pix-test-integration-result');
        if (!$resultDiv.length) {
            $resultDiv = $('<div id="pix-test-integration-result"></div>');
            $('#test_integration').after($resultDiv);
        }
        $resultDiv.html('<span>Testing integration...</span>');

        // Primeiro, obtém o nonce via AJAX
        $.post(ajaxurl, {
            action: 'lkn_pix_for_woocommerce_generate_nonce',
            action_name: 'pixforwoo_test_c6_pix_charge'
        }, function (nonceResponse) {
            if (!nonceResponse.success || !nonceResponse.data.nonce) {
                $resultDiv.html('<span class="pix-test-integration-error">Failed to get nonce.</span>');
                return;
            }

            // Faz a requisição AJAX principal com o nonce
            $.post(ajaxurl, {
                action: 'pixforwoo_test_c6_pix_charge',
                client_id: client_id,
                client_secret: client_secret,
                pix_key: pix_key,
                environment: environment,
                pixforwoo_nonce: nonceResponse.data.nonce
            }, function (response) {
                $resultDiv.empty();
                if (response.success && response.data.status === 'success') {
                    var img = $('<img />', {
                        src: response.data.qrcode_url,
                        alt: 'Pix QRCode',
                        class: 'pix-test-integration-qrcode'
                    });
                    $resultDiv.append('<span>PIX successfully created!</span><br/>');
                    $resultDiv.append(img);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error.';
                    $resultDiv.append('<span class="pix-test-integration-error">' + errorMsg + '</span>');
                }
            }).fail(function () {
                $resultDiv.html('<span class="pix-test-integration-error">AJAX request failed.</span>');
            });
        }).fail(function () {
            $resultDiv.html('<span class="pix-test-integration-error">Failed to get nonce.</span>');
        });
    });
});