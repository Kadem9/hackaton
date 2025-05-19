import {jsonFetch} from "../function/api";

export class ThemeSwitcher extends HTMLElement {
  #button: HTMLButtonElement | null = null
  connectedCallback() {
    this.#button = document.createElement('button')
    this.#button.addEventListener('click', this.#handleSwitch.bind(this))
    this.#setIcon()
    this.append(this.#button)
  }

  #setIcon() {
    if(this.#button !== null) {
      const icon = document.body.classList.contains('light') ? 'moon' : 'sun';
      this.#button.innerHTML = `<svg class="icon icon-${icon} icon-sm">
    <use xlink:href="/icons.svg?1.02=1&logo#${icon}"></use>
</svg>`
      this.#button.title = `${icon === 'moon' ? 'dark' : 'light'} mode`
    }
  }

  async #handleSwitch() {
    const body = document.body
    const removeTheme = body.classList.contains('light') ? 'light' : 'dark'
    const addTheme = body.classList.contains('light') ? 'dark' : 'light'
    body.classList.add(`${addTheme}`)
    body.classList.remove(`${removeTheme}`)
    this.#setIcon()
    await jsonFetch('/admin/api/profile/theme', {
      body : { theme : addTheme }
    })
  }

}