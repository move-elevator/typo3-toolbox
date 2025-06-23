import FormEngine from '@typo3/backend/form-engine.js'

class SaveAndClose {
  constructor () {
    this.button = document.querySelector('[data-js=save-and-close-button]')
    this.init()
  }

  #addEvents () {
    this.button?.addEventListener('click', (e) => {
      e.preventDefault()
      FormEngine.saveAndCloseDocument()
    })
  }

  init () {
    this.#addEvents()
  }
}

export default new SaveAndClose()
