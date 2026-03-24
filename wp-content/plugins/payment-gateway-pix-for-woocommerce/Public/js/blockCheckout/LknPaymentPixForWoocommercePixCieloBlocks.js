const cieloPixSettingsWoocommerce = window.wc.wcSettings.getSetting('lkn_cielo_pix_for_woocommerce_data', {})
const cieloPixWoocommerce = window.wp.htmlEntities.decodeEntities(cieloPixSettingsWoocommerce.title || 'Cielo Pix')

const showButton = cieloPixSettingsWoocommerce.show_button === 'yes'

const ContentCieloPixWoocommerce = props => {
  const wcComponents = window.wc.blocksComponents
  const [userCpf, setUserCpf] = window.wp.element.useState('')
  const {
    eventRegistration,
    emitResponse,
    onSubmit
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  
  const handleCpfChangeWoocommerce = value => {
    const numericValue = value.replace(/\D/g, '')
    setUserCpf(numericValue)
  }
  
  const handleCpfBlurWoocommerce = () => {
    let maskedValue = ''
    const value = userCpf

    if (value.length === 11) {
      // CPF mask
      maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    } else if (value.length === 14) {
      // CNPJ mask
      maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
    } else {
      maskedValue = value
    }
    setUserCpf(maskedValue)
  }
  
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            billing_cpf: userCpf
          }
        }
      }
    })

    return () => {
      unsubscribe()
    }
  }, [userCpf, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  
  return React.createElement('div', {
    id: 'LknCieloPixFields'
  }, 
    React.createElement('p', null, cieloPixSettingsWoocommerce.description || 'Pay with Cielo Pix'), 
    React.createElement(wcComponents.TextInput, {
      label: 'CPF/CNPJ',
      maxlength: '18',
      value: userCpf,
      onChange: handleCpfChangeWoocommerce,
      onBlur: handleCpfBlurWoocommerce
    }),
    showButton && React.createElement('div', {
      style: { display: 'flex', justifyContent: 'center', marginTop: '20px' }
    },
      React.createElement('button', {
        type: 'button',
        className: 'wc-block-components-button wp-element-button wc-block-components-checkout-place-order-button contained lkn-rede-btn-pix',
        onClick: function() {
          // Trigger o submit do checkout do WooCommerce Blocks
          if (onSubmit) {
            onSubmit();
          } else {
            // Fallback para o método tradicional
            const checkoutForm = document.querySelector('form.wc-block-checkout__form');
            if (checkoutForm) {
              checkoutForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            } else {
              // Último recurso
              const button = document.querySelector('.wc-block-components-checkout-place-order-button');
              if (button) {
                button.click();
              }
            }
          }
        },
        style: {
          padding: '8px 21px',
          borderRadius: '4px'
        }
      },
        React.createElement('div', {
          className: 'wc-block-components-button__text',
          style: { display: 'flex', alignItems: 'center', gap: '10px' }
        },
          React.createElement('svg', {
            xmlns: 'http://www.w3.org/2000/svg',
            x: '0px',
            y: '0px',
            width: '32',
            height: '48',
            viewBox: '0 0 48 48',
            style: {
              left: 'auto',
              position: 'static',
              top: 'auto',
              transform: 'none'
            }
          },
            React.createElement('path', {
              fill: '#4db6ac',
              d: 'M11.9,12h-0.68l8.04-8.04c2.62-2.61,6.86-2.61,9.48,0L36.78,12H36.1c-1.6,0-3.11,0.62-4.24,1.76	l-6.8,6.77c-0.59,0.59-1.53,0.59-2.12,0l-6.8-6.77C15.01,12.62,13.5,12,11.9,12z'
            }),
            React.createElement('path', {
              fill: '#4db6ac',
              d: 'M36.1,36h0.68l-8.04,8.04c-2.62,2.61-6.86,2.61-9.48,0L11.22,36h0.68c1.6,0,3.11-0.62,4.24-1.76	l6.8-6.77c0.59-0.59,1.53-0.59,2.12,0l6.8,6.77C32.99,35.38,34.5,36,36.1,36z'
            }),
            React.createElement('path', {
              fill: '#4db6ac',
              d: 'M44.04,28.74L38.78,34H36.1c-1.07,0-2.07-0.42-2.83-1.17l-6.8-6.78c-1.36-1.36-3.58-1.36-4.94,0	l-6.8,6.78C13.97,33.58,12.97,34,11.9,34H9.22l-5.26-5.26c-2.61-2.62-2.61-6.86,0-9.48L9.22,14h2.68c1.07,0,2.07,0.42,2.83,1.17	l6.8,6.78c0.68,0.68,1.58,1.02,2.47,1.02s1.79-0.34,2.47-1.02l6.8-6.78C34.03,14.42,35.03,14,36.1,14h2.68l5.26,5.26	C46.65,21.88,46.65,26.12,44.04,28.74z'
            })
          ),
          React.createElement('div', {
            className: 'wc-block-components-checkout-place-order-button__text'
          }, 'Finalizar e Gerar PIX')
        )
      )
    )
  )
}

const blockGatewayCieloPixWoocommerce = {
  name: 'lkn_cielo_pix_for_woocommerce', // Corrigido para match com a classe PHP
  label: cieloPixWoocommerce,
  content: window.wp.element.createElement(ContentCieloPixWoocommerce),
  edit: window.wp.element.createElement(ContentCieloPixWoocommerce),
  canMakePayment: () => true,
  ariaLabel: cieloPixWoocommerce,
  supports: {
    features: cieloPixSettingsWoocommerce.supports || []
  }
}

window.wc.wcBlocksRegistry.registerPaymentMethod(blockGatewayCieloPixWoocommerce)