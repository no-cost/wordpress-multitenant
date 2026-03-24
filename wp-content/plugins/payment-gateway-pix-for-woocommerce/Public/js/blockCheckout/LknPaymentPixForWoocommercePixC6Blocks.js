const settingsPixForWoocommerceC6 = window.wc.wcSettings.getSetting('lkn_pix_for_woocommerce_c6_data', {});
const labelPixForWoocommerceC6 = window.wp.htmlEntities.decodeEntities(settingsPixForWoocommerceC6.title);
const showPixButton = settingsPixForWoocommerceC6.generate_pix_button === 'yes';

const ContentPixForWoocommerceC6 = props => {
  const wcComponents = window.wc.blocksComponents;
  const [userCpf, setUserCpf] = window.wp.element.useState('');
  const { eventRegistration, emitResponse } = props;
  const { onPaymentSetup } = eventRegistration;

  // Remove a lógica de esconder/mostrar o botão padrão

  const handleCpfChange = value => {
    const numericValue = value.replace(/\D/g, '');
    setUserCpf(numericValue);
  };

  const handleCpfBlur = () => {
    let maskedValue = '';
    const value = userCpf;
    if (value.length === 11) {
      maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length === 14) {
      maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    } else {
      maskedValue = value;
    }
    setUserCpf(maskedValue);
  };

  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            pix_for_woocommerce_cpf_cnpj: userCpf
          }
        }
      };
    });
    return () => {
      unsubscribe();
    };
  }, [userCpf, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);

  // Ao clicar no botão Pix, simula o clique no botão padrão
  const handlePixPayment = (event) => {
    const button = event.currentTarget;
    button.disabled = true; // trava o botão
    const checkoutButton = document.querySelector('.wc-block-components-checkout-place-order-button');
    if (checkoutButton) {
      checkoutButton.click();
    }
    // Reabilita o botão após 7 segundos
    setTimeout(() => {
      button.disabled = false;
    }, 7000);
  };

  return window.wp.element.createElement(
    "div",
    { className: "LknPixForWoocommerceC6PaymentFields" },
    window.wp.element.createElement("p", null, settingsPixForWoocommerceC6.description),
    window.wp.element.createElement(wcComponents.TextInput, {
      id: "pix_for_woocommerce_cpf_cnpj",
      label: "CPF/CNPJ",
      value: userCpf,
      onChange: handleCpfChange,
      onBlur: handleCpfBlur
    }),
    showPixButton && window.wp.element.createElement(
      "button",
      {
        type: "button",
        className: "pix-generate-button",
        onClick: handlePixPayment
      },
      window.wp.element.createElement(
        "img",
        {
          src: settingsPixForWoocommerceC6.icon,
          alt: "Pix Icon"
        }
      ),
      settingsPixForWoocommerceC6.pixButton || "Complete and Generate PIX"
    )
  );
};

const Block_Gateway_lkn_pix_for_woocommerce_c6 = {
  name: 'lkn_pix_for_woocommerce_c6',
  label: labelPixForWoocommerceC6,
  content: window.wp.element.createElement(ContentPixForWoocommerceC6),
  edit: window.wp.element.createElement(ContentPixForWoocommerceC6),
  canMakePayment: () => true,
  ariaLabel: labelPixForWoocommerceC6,
  supports: {
    features: settingsPixForWoocommerceC6.supports
  }
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_lkn_pix_for_woocommerce_c6);