(function ($) {
    'use strict';

    // Verificar se já foi inicializado
    if (window.pixPaymentManagerInitialized) {
        return;
    }
    window.pixPaymentManagerInitialized = true;

    // Classe para gerenciar a API do PagHiper
    class PagHiperAPIService {
        constructor(apiUrl) {
            this.apiUrl = apiUrl;
            this.isChecking = false;
        }

        async checkPaymentStatus(orderId) {
            if (this.isChecking) {
                return { status: 'checking' };
            }

            this.isChecking = true;

            try {
                const response = await $.ajax({
                    url: this.apiUrl,
                    type: 'GET',
                    headers: { Accept: 'application/json' },
                    data: { orderId: orderId }
                });

                return response;
            } catch (error) {
                console.error('API Error:', error);
                return { status: 'error' };
            } finally {
                this.isChecking = false;
            }
        }
    }

    // Classe para gerenciar o timer
    class PaymentTimer {
        constructor() {
            this.timer = null;
            this.time = 60;
            this.isRunning = false;
            this.observers = [];
        }

        addObserver(observer) {
            this.observers.push(observer);
        }

        notifyObservers(event, data) {
            this.observers.forEach(observer => {
                if (observer[event]) {
                    observer[event](data);
                }
            });
        }

        start() {
            if (this.isRunning) return;

            this.isRunning = true;
            this.time = 60;

            this.timer = setInterval(() => {
                if (this.time > 0) {
                    this.time -= 1;
                    this.notifyObservers('onTick', this.time);
                } else {
                    this.notifyObservers('onComplete', null);
                    this.reset(30); // Próximo ciclo em 30s
                }
            }, 1000);
        }

        reset(newTime = 60) {
            this.time = newTime;
            this.notifyObservers('onTick', this.time);
        }

        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
                this.isRunning = false;
            }
        }
    }

    // Classe para gerenciar as tentativas
    class AttemptManager {
        constructor(maxAttempts = 5) {
            this.maxAttempts = maxAttempts;
            this.currentAttempts = maxAttempts;
        }

        hasAttemptsLeft() {
            return this.currentAttempts > 0;
        }

        useAttempt() {
            if (this.currentAttempts > 0) {
                this.currentAttempts -= 1;
            }
            return this.currentAttempts;
        }

        reset() {
            this.currentAttempts = this.maxAttempts;
        }

        getRemainingAttempts() {
            return this.currentAttempts;
        }
    }

    // Classe principal para gerenciar o pagamento PIX
    class PixPaymentManager {
        constructor() {
            this.apiService = new PagHiperAPIService(phpVariables.apiUrl);
            this.timer = new PaymentTimer();
            this.attemptManager = new AttemptManager(5);
            this.elements = {};
            this.isInitialized = false;
            
            this.setupObservers();
        }

        setupObservers() {
            // Observer para eventos do timer
            this.timer.addObserver({
                onTick: (time) => this.updateTimerDisplay(time),
                onComplete: () => this.handleTimerComplete()
            });
        }

        async init() {
            if (this.isInitialized) {
                return;
            }

            if (!this.findElements()) {
                return;
            }

            this.setupEventListeners();
            this.timer.start();
            this.isInitialized = true;
        }

        findElements() {
            this.elements = {
                block: document.getElementById('pixforwoo-paghiper-block'),
                timer: $('#pixforwoo-qrcode-timer'),
                checkButton: $('.pixforwoo-qrcode-payment-check-btn'),
                checkText: $('.pixforwoo-qrcode-check-text'),
                copyButton: $('.pixforwoo-qrcode-copy-btn'),
                copyInput: $('.pixforwoo-qrcode-copy-input'),
                orderId: $('#pixforwoo-qrcode-donation-id'),
                qrImage: $('.pixforwoo-qrcode-img'),
                copyContainer: $('.pixforwoo-qrcode-copy-container')
            };

            return this.elements.block !== null;
        }

        setupEventListeners() {
            // Event listener para botão de cópia
            this.elements.copyButton.off('click.pix').on('click.pix', () => {
                this.handleCopyClick();
            });

            // Event listener para verificação manual (será adicionado quando necessário)
        }

        updateTimerDisplay(time) {
            this.elements.timer.text(time + 's');
        }

        async handleTimerComplete() {
            // Verificação automática quando timer chega a 0
            const orderId = this.elements.orderId.val();
            
            // Se ainda há tentativas, reduzir uma tentativa
            if (this.attemptManager.hasAttemptsLeft()) {
                const remainingAttempts = this.attemptManager.useAttempt();
                this.elements.checkText.text(
                    phpVariables.nextVerify + ' ' + remainingAttempts + '):'
                );
            }

            const result = await this.apiService.checkPaymentStatus(orderId);
            
            if (result.status === 'completed') {
                this.handlePaymentCompleted();
                return;
            } 
            
            if (result.status === 'expired') {
                this.handlePaymentExpired();
                return;
            }

            // Pagamento ainda pendente
            if (this.attemptManager.hasAttemptsLeft()) {
                // Ainda há tentativas - continuar com timer de 30s
                this.enableManualCheck();
            } else {
                // Acabaram as tentativas - parar timer mas manter botão funcionando
                this.elements.timer.text('');
                this.timer.stop();
                this.enableManualCheck(); // Botão continua funcionando sempre
            }
        }

        handlePaymentCompleted() {
            const now = this.getCurrentDateTime();
            
            this.elements.checkButton.text(now)
                .prop('disabled', true)
                .css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' });

            this.elements.timer.text(phpVariables.successPayment)
                .css('font-size', '20px');

            const clonedTimer = this.elements.timer.clone();
            clonedTimer.insertAfter(this.elements.qrImage);

            this.elements.copyContainer.remove();
            this.elements.qrImage.remove();
            this.timer.stop();
        }

        handlePaymentExpired() {
            const expirationDate = $('#pixforwoo-qrcode-expiration-date').val();
            
            this.elements.checkButton.text(phpVariables.expiredPaymentDate + ' ' + expirationDate)
                .prop('disabled', true)
                .css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' });

            this.elements.timer.text(phpVariables.expiredPayment)
                .css('font-size', '20px');

            const clonedTimer = this.elements.timer.clone();
            clonedTimer.insertAfter(this.elements.qrImage);

            this.elements.copyContainer.remove();
            this.elements.qrImage.remove();
            this.timer.stop();
        }

        enableManualCheck() {
            this.elements.checkButton
                .prop('disabled', false)
                .css({ 'background-color': '#3A3A3A', cursor: 'pointer' })
                .off('click.manual')
                .on('click.manual', async () => {
                    await this.handleManualCheck();
                });
        }

        async handleManualCheck() {
            const now = this.getCurrentDateTime();
            
            this.elements.checkButton.text(now)
                .prop('disabled', true)
                .css({ 'background-color': '#D9D9D9', cursor: 'not-allowed' });

            // Se ainda há tentativas, reduzir uma tentativa
            if (this.attemptManager.hasAttemptsLeft()) {
                const remainingAttempts = this.attemptManager.useAttempt();
                this.elements.checkText.text(
                    phpVariables.nextVerify + ' ' + remainingAttempts + '):'
                );
            }

            const orderId = this.elements.orderId.val();
            const result = await this.apiService.checkPaymentStatus(orderId);
            
            if (result.status === 'completed') {
                this.handlePaymentCompleted();
                return;
            } 
            
            if (result.status === 'expired') {
                this.handlePaymentExpired();
                return;
            }

            // Pagamento ainda pendente
            if (this.attemptManager.hasAttemptsLeft()) {
                this.timer.reset(30);
            }
            
            // SEMPRE reabilitar o botão após 7 segundos, independente das tentativas
            setTimeout(() => {
                this.elements.checkButton
                    .prop('disabled', false)
                    .css({ 'background-color': '#3A3A3A', cursor: 'pointer' })
                    .text(phpVariables.pixButton);
            }, 7000);
        }

        handleCopyClick() {
            const pixCode = this.elements.copyInput.val();
            navigator.clipboard.writeText(pixCode);
            
            this.elements.copyButton
                .text(phpVariables.copiedText)
                .prop('disabled', true)
                .css({ 'background-color': '#28a428', cursor: 'not-allowed' });

            setTimeout(() => {
                this.elements.copyButton
                    .prop('disabled', false)
                    .css({ 'background-color': '#3A3A3A', cursor: 'pointer' })
                    .text('COPY');
            }, 5000);
        }

        getCurrentDateTime() {
            const now = new Date();
            return now.getFullYear() + '/' +
                String(now.getMonth() + 1).padStart(2, '0') + '/' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
        }
    }

    // Classe para aplicar máscara CPF/CNPJ
    class CPFCNPJMask {
        constructor() {
            this.setupMask();
        }

        setupMask() {
            const input = document.querySelector('#lknPaymentPixForWoocommercePagHiperInput');
            if (!input) return;

            input.addEventListener('focus', () => this.applyMask(input));
            input.addEventListener('blur', () => this.applyMask(input));
        }

        applyMask(input) {
            const value = input.value;
            input.value = this.formatValue(value);
        }

        formatValue(value) {
            const numericValue = value.replace(/\D/g, '');
            
            if (numericValue.length === 11) {
                return numericValue.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (numericValue.length === 14) {
                return numericValue.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
            
            return value;
        }
    }

    // Inicialização usando MutationObserver
    $(document).ready(function() {
        let pixManager = null;
        let cpfMask = null;
        let isInitialized = false;

        function initializeComponents() {
            if (isInitialized) return;
            
            isInitialized = true;
            pixManager = new PixPaymentManager();
            cpfMask = new CPFCNPJMask();
            
            if (document.getElementById('pixforwoo-paghiper-block')) {
                pixManager.init();
            }
        }

        // Observer para detectar quando o elemento aparece
        const observer = new MutationObserver(function(mutations) {
            if (isInitialized) return;
            
            let found = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && !found) {
                    const pixBlock = document.getElementById('pixforwoo-paghiper-block');
                    if (pixBlock) {
                        found = true;
                        initializeComponents();
                        observer.disconnect();
                    }
                }
            });
        });

        // Tentar inicializar imediatamente se o elemento já existir
        if (document.getElementById('pixforwoo-paghiper-block')) {
            initializeComponents();
        } else {
            // Iniciar observação apenas se o elemento não existir ainda
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });

})(jQuery);