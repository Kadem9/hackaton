import Sortable from 'sortablejs';
import {jsonFetch} from "../function/api";
export class SortableWrapper extends HTMLElement {
  #targetSelector: string = 'tbody';
  #target: HTMLElement | null = null;
  #sortHandle: string | null = null;
  #targetId: string | null = null;
  #action: string | null = null;
  constructor() {
    super();
  }

  connectedCallback() {
    this.#targetSelector = this.getAttribute('target') || 'tbody'
    this.#targetId = this.getAttribute('target-id') || null
    this.#sortHandle = this.getAttribute('sort-handle') || null
    this.#action = this.getAttribute('action') || null
    this.#target = this.querySelector(this.#targetSelector)
    this.#init()
  }

  #init() {
    if(this.#target) {
      const options : Sortable.Options = {
        onSort: async () => {
          await this.#onSort()
        }
      }
      if(this.#sortHandle) {
        options.handle = this.#sortHandle
      }
      Sortable.create(this.#target, options)
    }
  }

  async #onSort() {
    if(this.#targetId && this.#target) {
      const ids: string[] = []
      this.#target.querySelectorAll(this.#targetId).forEach(el => ids.push(el.getAttribute('sort-id') || ''))
      if(ids.length > 0 && this.#action) {
        await jsonFetch(this.#action, { body : { ids } })
      }
    }
  }
}