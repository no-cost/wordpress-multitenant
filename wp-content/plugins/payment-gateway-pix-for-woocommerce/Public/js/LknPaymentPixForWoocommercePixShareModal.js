// Função para controlar a visibilidade do modal
function changeModalVisibility() {
    const modal = document.getElementById('lknPaymentPixForWoocommerceShareModal');
    if (modal) {
        if (modal.style.display === 'flex') {
            modal.style.display = 'none';
        } else {
            modal.style.display = 'flex';
        }
    }
}

// Event listener para o botão de compartilhar
document.addEventListener('DOMContentLoaded', function () {
    const shareButton = document.getElementById('lknPaymentPixForWoocommerceSharePixCodeButton');
    const modal = document.getElementById('lknPaymentPixForWoocommerceShareModal');
    const closeButton = document.getElementById('lknPaymentPixForWoocommerceCloseModal');

    // Botão de compartilhar
    if (shareButton) {
        shareButton.addEventListener('click', function (e) {
            e.preventDefault();
            changeModalVisibility();
        });
    }

    // Botão de fechar modal
    if (closeButton) {
        closeButton.addEventListener('click', function (e) {
            e.preventDefault();
            changeModalVisibility();
        });
    }

    // Fechar modal clicando fora dele
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                changeModalVisibility();
            }
        });
    }

    // Configurar links de compartilhamento
    setupShareLinks();
});

function setupShareLinks() {
    const whatsappButton = document.getElementById('lknPaymentPixForWoocommerceShareButtonIconWhatsapp');
    const emailButton = document.getElementById('lknPaymentPixForWoocommerceShareButtonIconEmail');
    const telegramButton = document.getElementById('lknPaymentPixForWoocommerceShareButtonIconTelegram');
    const pixCodeInput = document.getElementById('lknPaymentPixForWoocommercePixCodeInput');

    if (!pixCodeInput) return;

    const pixCode = pixCodeInput.value;
    const shareText = `Código PIX para pagamento: ${pixCode}`;

    // WhatsApp
    if (whatsappButton) {
        whatsappButton.addEventListener('click', function (e) {
            e.preventDefault();
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(shareText)}`;
            window.open(whatsappUrl, '_blank');
        });
    }

    // Email
    if (emailButton) {
        emailButton.addEventListener('click', function (e) {
            e.preventDefault();
            const emailUrl = `mailto:?subject=${encodeURIComponent('Código PIX para pagamento')}&body=${encodeURIComponent(shareText)}`;
            window.open(emailUrl, '_blank');
        });
    }

    // Telegram
    if (telegramButton) {
        telegramButton.addEventListener('click', function (e) {
            e.preventDefault();
            const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(shareText)}`;
            window.open(telegramUrl, '_blank');
        });
    }
}