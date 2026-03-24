(function ($) {
    'use strict';

    $(window).on('load', function () {
        let paymentTimer = null;
        let firstRequest = true;
        let time = 60;
        let attempt = 5;
        let activeButton = true;

        const apiUrl = phpVarsPix.apiUrl;

        // Função principal de verificação do Pix
        async function checkPaymentStatus() {
            try {
                if (attempt !== 0) {
                    attempt -= 1;
                }
                $('.pixforwoo-qrcode-check-text').text(phpVarsPix.nextVerify + ' ' + attempt + '):');
                const pixOrderId = $('#pixforwoo-qrcode-donation-id').val();
                const response = await $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    headers: {
                        Accept: 'application/json'
                    },
                    data: {
                        orderId: pixOrderId
                    }
                });

                // Lógica para status concluída
                if (response.status === 'concluida') {
                    const checkPayment = $('.pixforwoo-qrcode-payment-check-btn');
                    const schedule = $('#pixforwoo-qrcode-timer');
                    const now = new Date();
                    const formattedDate = now.getFullYear() + '/' +
                        String(now.getMonth() + 1).padStart(2, '0') + '/' +
                        String(now.getDate()).padStart(2, '0') + ' ' +
                        String(now.getHours()).padStart(2, '0') + ':' +
                        String(now.getMinutes()).padStart(2, '0') + ':' +
                        String(now.getSeconds()).padStart(2, '0');

                    checkPayment.text(formattedDate);
                    checkPayment.prop('disabled', true).css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' }).removeClass('back_hover_button');
                    clearInterval(paymentTimer);
                    schedule.text(phpVarsPix.successPayment).css('font-size', '20px');
                    const clonedSchedule = schedule.clone();
                    clonedSchedule.insertAfter('.pixforwoo-qrcode-img');

                    $('.pixforwoo-qrcode-copy-container').remove();
                    $('.pixforwoo-qrcode-img').remove();
                    return true;
                }

                // Lógica para status expirado
                if (response.status === 'expired') {
                    const checkPayment = $('.pixforwoo-qrcode-payment-check-btn');
                    const schedule = $('#pixforwoo-qrcode-timer');
                    const donationId = $('#pixforwoo-qrcode-expiration-date').val();

                    checkPayment.text(phpVarsPix.expiredPaymentDate + ' ' + donationId);
                    checkPayment.prop('disabled', true).css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' }).removeClass('back_hover_button');
                    clearInterval(paymentTimer);
                    schedule.text(phpVarsPix.expiredPayment).css('font-size', '20px');
                    const clonedSchedule = schedule.clone();
                    clonedSchedule.insertAfter('.pixforwoo-qrcode-img');

                    $('.pixforwoo-qrcode-copy-container').remove();
                    $('.pixforwoo-qrcode-img').remove();
                    return true;
                }

                return false;
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }

        // Função para tentar encontrar o bloco até 7 vezes, uma vez a cada 1 segundo
        function tryFindBlock(attempts, intervalMs, callback) {
            let count = 0;
            let interval = setInterval(function () {
                const block = document.getElementById('pixforwoo-c6-block');
                if (block) {
                    clearInterval(interval);
                    callback(block);
                } else {
                    count++;
                    if (count >= attempts) {
                        clearInterval(interval);
                        callback(null);
                    }
                }
            }, intervalMs);
        }

        // Chama a função de busca 8 vezes, uma a cada 1 segundo
        tryFindBlock(8, 1000, function (block) {
            if (block) {
                firstRequest = true;
                time = 60;
                attempt = 5;
                activeButton = true;

                setTimeout(function () {
                    paymentTimer = setInterval(function () {
                        if (firstRequest) {
                            firstRequest = false;
                            time = 60;
                            activeButton = true;
                        }

                        if (time > 0) {
                            time -= 1;
                        }

                        const schedule = $('#pixforwoo-qrcode-timer');
                        schedule.text(time + 's');

                        if (time === 0) {
                            if (activeButton) {
                                const checkPayment = $('.pixforwoo-qrcode-payment-check-btn');
                                checkPayment.prop('disabled', false).css({ 'background-color': '#3A3A3A', cursor: 'pointer' }).addClass('back_hover_button');
                                activeButton = false;

                                checkPayment.on('click', async function () {
                                    const now = new Date();
                                    const formattedDate = now.getFullYear() + '/' +
                                        String(now.getMonth() + 1).padStart(2, '0') + '/' +
                                        String(now.getDate()).padStart(2, '0') + ' ' +
                                        String(now.getHours()).padStart(2, '0') + ':' +
                                        String(now.getMinutes()).padStart(2, '0') + ':' +
                                        String(now.getSeconds()).padStart(2, '0');

                                    checkPayment.text(formattedDate);
                                    checkPayment.prop('disabled', true).css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' }).removeClass('back_hover_button');
                                    const result = await checkPaymentStatus();
                                    if (attempt !== 0) {
                                        time = 30;
                                    } else {
                                        time = 0;
                                        clearInterval(paymentTimer);
                                        if (result === false) {
                                            schedule.text('');
                                        }
                                    }
                                    if (result === false) {
                                        setTimeout(function () {
                                            checkPayment.prop('disabled', false)
                                                .css({
                                                    'background-color': '#3A3A3A',
                                                    cursor: 'pointer'
                                                }).addClass('back_hover_button');

                                            checkPayment.text(phpVarsPix.pixButton);
                                        }, 7000);
                                    }
                                });
                            }
                            checkPaymentStatus();
                            if (attempt !== 0) {
                                time = 30;
                            } else {
                                time = 0;
                                clearInterval(paymentTimer);
                                schedule.text('');
                            }
                        }
                    }, 1000);
                }, 1000);

                // Botão copiar
                const copyBtn = $('.pixforwoo-qrcode-copy-btn');
                copyBtn.on('click', function () {
                    const pixInput = $('.pixforwoo-qrcode-copy-input');
                    navigator.clipboard.writeText(pixInput.val());
                    copyBtn.text(phpVarsPix.copied);
                    copyBtn.prop('disabled', true).css({ 'background-color': '#28a428', cursor: 'not-allowed' });

                    setTimeout(function () {
                        copyBtn.prop('disabled', false)
                            .css({
                                'background-color': '#3A3A3A',
                                cursor: 'pointer'
                            });
                        copyBtn.text(phpVarsPix.copy);
                    }, 3000);
                });

                // Compartilhar
                const shareBtn = $('.pixforwoo-qrcode-share-btn');
                shareBtn.on('click', function () {
                    const pixInput = $('.pixforwoo-qrcode-copy-input');
                    if (navigator.share) {
                        navigator.share({
                            title: phpVarsPix.shareTitle,
                            text: pixInput.val()
                        });
                    } else {
                        alert(phpVarsPix.shareError);
                    }
                });
            }
        });
    });

})(jQuery);