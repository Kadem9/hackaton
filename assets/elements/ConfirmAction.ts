export class ConfirmAction extends HTMLElement {
    #confirmMessage: string = ""
    #isRender: boolean = false
    #isDelete: boolean = false
    #isPost: boolean = false
    #clsCss: string = ""
    connectedCallback() {
        if(this.#isRender) return false;
        this.#isRender = true;
        this.#confirmMessage = this.dataset.message || "Confirmer la suppression ?"
        this.#clsCss = this.dataset.classname || ""
        this.#isDelete = (this.dataset.delete || '') === "true"
        this.#isPost = (this.dataset.post || '') === "true"
        //Création bouton submit
        const btn = document.createElement('button')

        const form = document.createElement('form')
        form.action = this.dataset.action || ''

        //Création input method
        if(this.#isDelete) {
            const input = document.createElement('input')
            input.type = "hidden"
            input.name = "_method"
            input.value = "DELETE"
            form.method = 'post'
            form.append(input)
            btn.classList.add('btn-icon-danger')
            btn.innerHTML = "<svg class=\"icon icon-trash icon-sm\"><use xlink:href=\"/icons.svg?logo#trash\"></use></svg>"
        }
        else if (this.#isPost) {
            form.method = 'post'
            btn.classList.add(this.#clsCss)
            btn.innerHTML = this.innerHTML
            this.innerHTML = ""
        }
        btn.type = "submit"

        //Création formulaire
        form.append(btn)
        form.addEventListener('submit', this.#handleSubmit.bind(this))

        this.append(form)
    }

    #handleSubmit(ev: SubmitEvent) {
        if(!confirm(this.#confirmMessage)) {
            ev.preventDefault()
        }
    }
}