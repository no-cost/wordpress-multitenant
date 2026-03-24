(function ($) {
  'use strict';

  function attachCpfCnpjEvents() {
    var $input = $('#lknPaymentPixForWoocommerceC6Input');

    if (!$input.length) return;

    if (!$input.hasClass('cpf-cnpj-events-attached')) {
      $input.addClass('cpf-cnpj-events-attached');

      $input.on('input', function () {
        var value = $input.val().replace(/\D/g, '');
        $input.val(value);
      });

      $input.on('blur', function () {
        var value = $input.val();
        var maskedValue = '';

        if (value.length === 11) {
          maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        } else if (value.length === 14) {
          maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        } else {
          maskedValue = value;
        }

        $input.val(maskedValue);
      });
    }
  }

  // Observer para detectar quando o input aparece
  var observer = new MutationObserver(function () {
    attachCpfCnpjEvents();
  });

  observer.observe(document.body, { childList: true, subtree: true });

  $(document).ready(function () {
    attachCpfCnpjEvents();
  });
})(jQuery);