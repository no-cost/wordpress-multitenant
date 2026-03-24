const pixCopyButton = document.querySelector('#pixforwoo-qrcode-copy-btn')
if(pixCopyButton){
    // Armazena o texto original do botão
    const originalButtonText = pixCopyButton.textContent;
    const originalBackgroundColor = window.getComputedStyle(pixCopyButton).backgroundColor;
    let isAnimating = false;
    
    pixCopyButton.addEventListener('click', function (e) {
        e.preventDefault();

        if (isAnimating) {
            return;
        }

        const linkInput = document.querySelector('#pixforwoo-qrcode-copy-input')
        linkInput.select()
        navigator.clipboard.writeText(linkInput.value)
        
        if (pixCopyButton.textContent === originalButtonText) {
            isAnimating = true;
            
            pixCopyButton.textContent = phpVariables.copiedText;
            pixCopyButton.style.cursor = 'not-allowed';
            
            pixCopyButton.style.transition = 'background-color 0.3s ease-in-out';
            pixCopyButton.style.backgroundColor = '#28A428';
            
            setTimeout(function() {
                pixCopyButton.style.backgroundColor = originalBackgroundColor;
                pixCopyButton.textContent = originalButtonText;
                pixCopyButton.style.cursor = '';
                isAnimating = false;
                
                setTimeout(function() {
                    pixCopyButton.style.transition = '';
                }, 300);
            }, 5000);
        }
    });
}

function lknPGPFGGiveWPCrcChecksum(string) {
    let crc = 0xFFFF
    const strlen = string.length

    for (let c = 0; c < strlen; c++) {
        crc ^= string.charCodeAt(c) << 8
        for (let i = 0; i < 8; i++) {
            if (crc & 0x8000) {
                crc = (crc << 1) ^ 0x1021
            } else {
                crc = crc << 1
            }
        }
    }
    let hex = crc & 0xFFFF
    if (hex < 0) {
        hex = 0xFFFFFFFF + hex + 1
    }
    hex = parseInt(hex, 10).toString(16).toUpperCase().padStart(4, '0')

    return hex
}

function lknPGPFGGiveWPPixBuilder(amount = '') {
    // Verificar se as variáveis existem
    if (!phpVariables || !phpVariables.pixKey || !phpVariables.pixName || !phpVariables.pixCity) {
        return '';
    }

    const pixType = phpVariables.pixKeyType || ''
    const pixKey = phpVariables.pixKey || ''
    const pixName = phpVariables.pixName || ''
    const pixCity = phpVariables.pixCity || ''
    
    let key
    switch (pixType) {
        case 'tel':
            key = (pixKey.substring(0, 3) === '+55') ? pixKey : '+55' + pixKey.replace(/\D/g, '')
            break
        case 'cpf':
            key = pixKey.replace(/\D/g, '')
            break
        case 'cnpj':
            key = pixKey.replace(/\D/g, '')
            break
        default:
            key = pixKey
            break
    }
    const keyName = (pixName.length > 25) ? pixName.substring(0, 25).normalize('NFD').replace(/[\u0300-\u036f]/g, '') : pixName.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    const keyCity = (pixCity.length > 15) ? pixCity.substring(0, 15).normalize('NFD').replace(/[\u0300-\u036f]/g, '') : pixCity.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    const keyId = '***'

    // Summary of the PIX QR Code structure:
    // (00 Payload Format Indicator)
    // (26 Merchant Account Information)
    // (00 GUI - Default br.gov.bcb.pix)
    // (01 Chave Pix)
    // (52 Merchant Category Code)
    // (53 Transaction  Currency - BRL 986)
    // (54 Transaction Amount - Optional)
    // (58 Country Code - BR)
    // (59 Merchant Name)
    // (60 Merchant City)
    // (62 Additional Data Field - Default ***)
    // (63 CRC16 Chcksum)

    let qr = '000201'
    qr += '26' + (22 + key.length).toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false })
    qr += '0014BR.GOV.BCB.PIX'
    qr += '01' + key.length.toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + key
    qr += '52040000'
    qr += '5303986' + ((amount.length === 0) ? '' : ('54' + amount.length.toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + amount))
    qr += '5802BR'
    qr += '59' + keyName.length.toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + keyName
    qr += '60' + keyCity.length.toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + keyCity
    qr += '62' + (4 + keyId.length).toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + '05' + keyId.length.toLocaleString('en-US', { minimumIntegerDigits: 2, useGrouping: false }) + keyId
    qr += '6304'
    qr += lknPGPFGGiveWPCrcChecksum(qr)

    return qr
}

// Inicializar PIX quando o DOM estiver pronto
function initializePixPayment() {
    if (typeof phpVariables === 'undefined') {
        return;
    }

    // Gerar código PIX e popular interface
    const pixCode = lknPGPFGGiveWPPixBuilder(phpVariables.pixAmount);
    
    if (!pixCode) {
        return;
    }
    
    // Admin page: inserir QR Code no local do input hidden
    const adminPixInput = document.querySelector('#woocommerce_lkn_pix_for_woocommerce_pix_qr_code');
    if (adminPixInput) {
        const qr = qrcode(10, 'L');
        qr.addData(pixCode);
        qr.make();
        const qrImage = qr.createImgTag(5);
        adminPixInput.parentNode.innerHTML = qrImage;
        return;
    }
    
    const pixCodeInput = document.querySelector('#pixforwoo-qrcode-copy-input')
    if(pixCodeInput){
        pixCodeInput.value = pixCode;
    }

    const currencyTextElement = document.querySelector('#pixforwoo-qrcode-currency-text');
    if(currencyTextElement && phpVariables.pixAmount){
        const formattedAmount = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(phpVariables.pixAmount);
        currencyTextElement.textContent = formattedAmount;
    }

    const qr = qrcode(10, 'L');
    qr.addData(pixCode);
    qr.make();

    const qrImage = qr.createImgTag(5);
    const divPixQRCode = document.querySelector('#pixforwoo-qrcode-image')
    if(divPixQRCode){
        divPixQRCode.innerHTML = qrImage;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePixPayment);
} else {
    initializePixPayment();
}