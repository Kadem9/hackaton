export class InputSwitch extends HTMLInputElement{
  connectedCallback() {
    const span = document.createElement('span');
    span.classList.add('switch')
    this.closest('div')?.querySelectorAll('label').forEach((el: HTMLLabelElement) => {
      el.prepend(span)
    })
  }
}