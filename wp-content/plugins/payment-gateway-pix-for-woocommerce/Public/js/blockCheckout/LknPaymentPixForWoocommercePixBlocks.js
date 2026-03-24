const settingsPixForWoocommerce = window.wc.wcSettings.getSetting('lkn_pix_for_woocommerce_data', {});
const labelPixForWoocommerce = window.wp.htmlEntities.decodeEntities(settingsPixForWoocommerce.title);
const ContentPixForWoocommerce = props => {
  return /*#__PURE__*/React.createElement("div", {
    class: "LknPixForWoocommercePaymentFields"
  }, /*#__PURE__*/React.createElement("p", null, settingsPixForWoocommerce.description));
};
const Block_Gateway_lkn_pix_for_woocommerce = {
  name: 'lkn_pix_for_woocommerce',
  label: labelPixForWoocommerce,
  content: window.wp.element.createElement(ContentPixForWoocommerce),
  edit: window.wp.element.createElement(ContentPixForWoocommerce),
  canMakePayment: () => true,
  ariaLabel: labelPixForWoocommerce,
  supports: {
    features: settingsPixForWoocommerce.supports
  }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_lkn_pix_for_woocommerce);