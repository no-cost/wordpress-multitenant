document.addEventListener('DOMContentLoaded', function () {
    const debug = document.querySelector('#debug');
    const clearLogsButton = document.querySelector('#clear_order_records');
    const showOrderLog = document.querySelector('#show_order_logs');

    function toggleDebugFields() {
        if (clearLogsButton && showOrderLog) {
            if (debug.checked) {
                showOrderLog.closest('.admin-gateway-field-input-wrapper').classList.remove('setting-field-pix-desactivated')
                clearLogsButton.closest('.admin-gateway-field-input-wrapper').classList.remove('setting-field-pix-desactivated')
            } else {
                showOrderLog.closest('.admin-gateway-field-input-wrapper').classList.add('setting-field-pix-desactivated')
                clearLogsButton.closest('.admin-gateway-field-input-wrapper').classList.add('setting-field-pix-desactivated')
            }
        }
    }
    toggleDebugFields();
    debug.addEventListener('change', function () {
        toggleDebugFields();
    });

    if (clearLogsButton) {
        clearLogsButton.addEventListener('click', function (e) {
            e.preventDefault();
            clearLogsButton.disabled = true;
            let textoOriginal = clearLogsButton.textContent;
            clearLogsButton.textContent = 'Limpando...';
            fetch('/wp-json/paymentPix/clearOrderLogs', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: JSON.stringify({
                    gateway_id: cieloPixData.gateway_id
                })
            })
                .then(response => {
                    if (response.ok) {
                        clearLogsButton.textContent = 'Logs limpos com sucesso!';
                        setTimeout(() => {
                            clearLogsButton.disabled = false;
                            clearLogsButton.textContent = textoOriginal;
                        }, 2500);
                    } else {
                        clearLogsButton.textContent = 'Erro ao limpar logs!';
                        setTimeout(() => {
                            clearLogsButton.disabled = false;
                            clearLogsButton.textContent = textoOriginal;
                        }, 2500);
                    }
                })
                .catch(error => {
                    clearLogsButton.textContent = 'Erro ao limpar logs!';
                    setTimeout(() => {
                        clearLogsButton.disabled = false;
                        clearLogsButton.textContent = textoOriginal;
                    }, 2500);
                });
        });
    }
});