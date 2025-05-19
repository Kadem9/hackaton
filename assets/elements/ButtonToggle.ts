export class ButtonToggle extends HTMLElement {
  #target: string
  #toggle: string
  #selfToggle: string

  constructor() {
    super();
    this.#target = this.getAttribute('target') || ''
    this.#toggle = this.getAttribute('target-toggle') || ''
    this.#selfToggle = this.getAttribute('self-toggle') || ''
    this.addEventListener('click', this.#onClick.bind(this))
  }

  #onClick() {
    this.classList.toggle(this.#selfToggle)
    Array.from(document.querySelectorAll(this.#target)).forEach((el: Element) => {
      el.classList.toggle(this.#toggle)
    })
  }
}