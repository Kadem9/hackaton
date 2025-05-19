type ALERT_TYPES = 'success' | 'error' | 'info' | 'primary'

export class AlertMessage extends HTMLElement {
  #type: ALERT_TYPES = 'info';
  #message: string = '';
  #types = ['success', 'error', 'info', 'primary', 'warning']
  #clsCss = {
    success: 'alert-success',
    error: 'alert-danger',
    info: 'alert-info',
    primary: 'alert-primary',
    warning: 'alert-warning'
  }

  constructor() {
    super();
  }

  connectedCallback() {
    this.#type = this.getAttribute('type') as ALERT_TYPES || 'info'
    if(this.#types.indexOf(this.#type) === -1) this.#type = 'info'
    const alertCls = this.#clsCss[this.#type];
    this.classList.add(alertCls)
    this.#message = this.innerHTML
    this.innerHTML = `<div>
    ${this.#message}
</div>
<button type="button" class="alert-close">&times</button>`

    this.querySelector('.alert-close')?.addEventListener('click', (e: Event) => {
      e.preventDefault()
      this.close()
    })
  }

  close() {
    this.remove()
  }
}