document.addEventListener('DOMContentLoaded', function () {
    const cpfField = document.getElementById('pixForWoocommerceCieloPixBillingCpf');

    // Usando event delegation para campos que podem ser criados dinamicamente
    document.addEventListener('input', function (event) {
        if (event.target.id === 'pixForWoocommerceCieloPixBillingCpf') {
            const value = event.target.value;
            const numericValue = value.replace(/\D/g, '');
            event.target.value = numericValue;
        }
    });

    document.addEventListener('blur', function (event) {
        if (event.target.id === 'pixForWoocommerceCieloPixBillingCpf') {
            let maskedValue = '';
            const value = event.target.value;

            if (value.length === 11) {
                // Máscara CPF
                maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length === 14) {
                // Máscara CNPJ
                maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            } else {
                maskedValue = value;
            }

            event.target.value = maskedValue;
        }
    }, true);

    // Fallback para campos já presentes
    if (cpfField) {
        // Função para remover caracteres não numéricos
        const handleCpfChange = function (event) {
            const value = event.target.value;
            const numericValue = value.replace(/\D/g, '');
            event.target.value = numericValue;
        };

        // Função para aplicar máscara quando o campo perde o foco
        const handleCpfBlur = function (event) {
            let maskedValue = '';
            const value = event.target.value;

            if (value.length === 11) {
                // Máscara CPF
                maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length === 14) {
                // Máscara CNPJ
                maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            } else {
                maskedValue = value;
            }

            event.target.value = maskedValue;
        };

        // Adicionar event listeners
        cpfField.addEventListener('input', handleCpfChange);
        cpfField.addEventListener('blur', handleCpfBlur);
    }
});