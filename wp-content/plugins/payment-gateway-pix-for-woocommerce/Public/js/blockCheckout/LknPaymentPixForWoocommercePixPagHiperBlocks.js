const settingsPixForWoocommercePagHiper = window.wc.wcSettings.getSetting('lkn_pix_for_woocommerce_paghiper_data', {});
const labelPixForWoocommercePagHiper = window.wp.htmlEntities.decodeEntities(settingsPixForWoocommercePagHiper.title);
const ContentPixForWoocommercePagHiper = props => {
  const wcComponents = window.wc.blocksComponents;
  const [userCpf, setUserCpf] = window.wp.element.useState('');
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onPaymentSetup
  } = eventRegistration;
  const handleCpfChange = value => {
    const numericValue = value.replace(/\D/g, '');
    setUserCpf(numericValue);
  };
  const handleCpfBlur = () => {
    let maskedValue = '';
    const value = userCpf;

    // Determine the mask based on the input value
    if (value.length === 11) {
      // CPF mask
      maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length === 14) {
      // CNPJ mask
      maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    } else {
      // Invalid input length, do not apply any mask
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
            pix_for_woocommerce_cpf: userCpf
          }
        }
      };
    });

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe();
    };
  }, [userCpf, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  return /*#__PURE__*/React.createElement("div", {
    className: "LknPixForWoocommercePagHiperPaymentFields"
  }, /*#__PURE__*/React.createElement("p", null, settingsPixForWoocommercePagHiper.description), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "pix_for_woocommerce_cpf",
    label: "CPF/CNPJ",
    value: userCpf,
    onChange: handleCpfChange,
    onBlur: handleCpfBlur
  }));
};
const Block_Gateway_lkn_pix_for_woocommerce_paghiper = {
  name: 'lkn_pix_for_woocommerce_paghiper',
  label: labelPixForWoocommercePagHiper,
  content: window.wp.element.createElement(ContentPixForWoocommercePagHiper),
  edit: window.wp.element.createElement(ContentPixForWoocommercePagHiper),
  canMakePayment: () => true,
  ariaLabel: labelPixForWoocommercePagHiper,
  supports: {
    features: settingsPixForWoocommercePagHiper.supports
  }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_lkn_pix_for_woocommerce_paghiper);