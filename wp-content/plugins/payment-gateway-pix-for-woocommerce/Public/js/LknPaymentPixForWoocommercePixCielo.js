function changeModalVisibility() {
  const modal = document.querySelector('#lknCieloShareModal')
  if (modal) {
    if (modal.style.display == 'none' || !modal.style.display) {
      modal.style.display = 'flex'
    } else {
      modal.style.display = 'none'
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const lknCieloPixCodeButton = document.querySelector('.pixforwoo-qrcode-copy-btn-cielo')
  if (lknCieloPixCodeButton) {
    const originalButtonText = lknCieloPixCodeButton.textContent
    lknCieloPixCodeButton.addEventListener('click', (e) => {
      e.preventDefault()

      const linkInput = document.querySelector('.pixforwoo-qrcode-copy-input-cielo')
      //linkInput.select()
      navigator.clipboard.writeText(linkInput.value)
      lknCieloPixCodeButton.style.backgroundColor = '#28a428'
      lknCieloPixCodeButton.style.cursor = 'not-allowed'

      // Verifica se o texto do botão é o texto original antes de executar o código
      if (lknCieloPixCodeButton.textContent === originalButtonText) {
        lknCieloPixCodeButton.textContent = phpVariables.copiedText
        setTimeout(function () {
          lknCieloPixCodeButton.textContent = originalButtonText
          lknCieloPixCodeButton.style.backgroundColor = '#3A3A3A'
          lknCieloPixCodeButton.style.cursor = 'pointer'
        }, 1000)
      }
    })
  }

})
