export class Modal extends HTMLElement {
    #modal : HTMLElement | null = null;
    connectedCallback() {
        const trigger : string = this.getAttribute('trigger') || '';
        const template : string = this.getAttribute('template') || '';
        if(trigger.length > 0) {
            const title: string = this.getAttribute('title') || '';
            this.#modal = document.createElement('div');
            this.#modal.classList.add('modal');

            //Header
            const modalHeader = document.createElement('div');
            modalHeader.classList.add('modal-header');
            const titleElement = document.createElement('p');
            if (title.length > 0) {
                titleElement.classList.add('h4');
                titleElement.innerText = title;
            }
            modalHeader.appendChild(titleElement);

            const closeButton = document.createElement('button')
            closeButton.classList.add('btn-icon-secondary');
            closeButton.innerHTML = `<svg class="icon icon-times">
    <use xlink:href="/icons.svg?1.04=1&amp;logo#times"></use>
</svg>`;
            closeButton.addEventListener('click', () => {
                if (this.#modal) {
                    this.#modal.classList.remove('show');
                }
            });
            modalHeader.appendChild(closeButton);

            //Body
            const modalBody = document.createElement('div');
            modalBody.classList.add('modal-body');
            this.childNodes.forEach((node) => {
                modalBody.appendChild(node.cloneNode(true));
            })

            //Build
            const modalContent = document.createElement('div');
            modalContent.classList.add('modal-content');
            if(template === 'large') {
                modalContent.classList.add('large');
            }
            modalContent.appendChild(modalHeader);
            modalContent.appendChild(modalBody);
            this.#modal.appendChild(modalContent);
            document.body.appendChild(this.#modal);

            document.querySelectorAll(trigger).forEach((element) => {
               element.addEventListener('click', () => {
                     if (this.#modal) {
                          this.#modal.classList.add('show');
                     }
               });
            });
        }
        this.innerHTML = '';
    }
}