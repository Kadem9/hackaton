export class CustomizeTable extends HTMLElement {
    #settings: Record<string, string> = {}
    #targets: Array<string> = []
    #triggerModal: string = ''
    #preferenceKey: string = ''
    #form: HTMLFormElement | null = null

    connectedCallback() {
        const targetList = this.getAttribute('hidden-target') || '';
        this.#targets = targetList.length > 0 ? targetList.split(',') : [];
        this.#triggerModal = this.getAttribute('trigger-modal') || '.btn-customize-table';
        this.#preferenceKey = this.getAttribute('preference-key') || '';
        const settings = this.getAttribute('settings') || '';
        this.#settings = JSON.parse(settings);
        this.#customizeTable();
        this.#showSettings();
    }

    #customizeTable() {
        if(this.#targets.length === 0) return;
        this.#targets.forEach((target) => {
            this.querySelectorAll(target).forEach((el: Element) => {
                if(el instanceof  HTMLElement) {
                    el.style.display = 'none';
                }
            })
        })
    }

    #showSettings() {
        const modal = document.createElement('modal-box');
        modal.setAttribute('title', 'Définir les colonnes à afficher');
        modal.setAttribute('trigger', this.#triggerModal);

        this.#form = document.createElement('form');
        this.#form.setAttribute('method', 'post');
        this.#form.setAttribute('action', '/admin/current-user/save-preference');
        const div = document.createElement('div');
        div.classList.add('grid', 'grid-cols-3', 'gap-3');
        let index = 0;
        for(const key in this.#settings) {
            index++;
            const checkbox = this.#createFormCheckbox(index, `td[${key}]`, this.#settings[key], this.#targets.indexOf(`.${key}`) === -1);
            const hidden = this.#createFormHiddePreference(key);
            div.appendChild(hidden);
            div.appendChild(checkbox);
        }
        this.#form.appendChild(div);
        const hiddenRedirect = document.createElement('input');
        hiddenRedirect.setAttribute('type', 'hidden');
        hiddenRedirect.setAttribute('name', 'redirect');
        hiddenRedirect.setAttribute('value', window.location.href);
        const hiddenTypePreference = document.createElement('input');
        hiddenTypePreference.setAttribute('type', 'hidden');
        hiddenTypePreference.setAttribute('name', 'type_reference');
        hiddenTypePreference.setAttribute('value', 'checkbox');
        const hiddenPreferenceKey = document.createElement('input');
        hiddenPreferenceKey.setAttribute('type', 'hidden');
        hiddenPreferenceKey.setAttribute('name', 'preference_key');
        hiddenPreferenceKey.setAttribute('value', this.#preferenceKey);
        this.#form.appendChild(hiddenPreferenceKey);
        this.#form.appendChild(hiddenTypePreference);
        this.#form.appendChild(hiddenRedirect);

        const submit = document.createElement('button');
        submit.setAttribute('type', 'submit');
        submit.classList.add('btn-success', 'btn-small', 'mt-2');
        submit.innerText = 'Enregistrer';
        this.#form.appendChild(submit);

        const p = document.createElement('p');
        p.classList.add('mb-2');
        p.innerText = 'La configuration des colonnes affichées est propre à votre profil et sera conservée lors de vos prochaines visites.';
        modal.appendChild(p);
        modal.appendChild(this.#form);
        document.body.appendChild(modal);
    }

    #createFormCheckbox(index: number, name: string, label: string, checked: boolean) {
        const labelElement = document.createElement('label');
        labelElement.classList.add('label-checkbox');
        labelElement.setAttribute('for', `checkbox-${index}`);
        const span = document.createElement('span');
        span.innerText = label;
        const input = document.createElement('input');
        input.setAttribute('type', 'checkbox');
        if(checked) {
            input.setAttribute('checked', 'checked');
        }
        input.setAttribute('name', name);
        input.setAttribute('id', `checkbox-${index}`);
        labelElement.appendChild(input);
        labelElement.appendChild(span);
        return labelElement;
    }

    #createFormHiddePreference(value: string) {
        const hidden = document.createElement('input');
        hidden.setAttribute('type', 'hidden');
        hidden.setAttribute('name', 'values[]');
        hidden.setAttribute('value', value);
        return hidden;
    }

    /*
    #createShortcuts() {
        const div = document.createElement('div');
        div.classList.add('flex', 'justify-end', 'mt-2', 'gap-1');
        const buttonCheckAll = document.createElement('button');
        buttonCheckAll.innerText = 'Tout cocher';
        buttonCheckAll.setAttribute('type', 'button');
        buttonCheckAll.addEventListener('click', () => {
            console.log('buttonCheckAll');
            if(this.#form === null) return;
            this.#form.querySelectorAll('input[type="checkbox"]').forEach((el: Element) => {
                el.setAttribute('checked', 'checked');
            })
        })
        const buttonUncheckAll = document.createElement('button');
        buttonUncheckAll.innerText = 'Tout décocher';
        buttonUncheckAll.setAttribute('type', 'button');
        buttonUncheckAll.addEventListener('click', () => {
            console.log('buttonUncheckAll');
            if(this.#form === null) return;
            this.#form.querySelectorAll('input[type="checkbox"]').forEach((el: Element) => {
                el.removeAttribute('checked');
            })
        })
        div.appendChild(buttonCheckAll);
        const span = document.createElement('span');
        span.innerText = '/';
        div.appendChild(span);
        div.appendChild(buttonUncheckAll);
        return div;
    }*/
}