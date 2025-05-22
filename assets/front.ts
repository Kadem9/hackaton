import './style/front/front.scss'
import registerPreactCustomElement from "./function/registerPreactCustomElement";
import {App} from "./components/Appointment/App";
import register from "./function/register";
import {AlertMessage} from "./elements/AlertMessage.ts";

registerPreactCustomElement(App, 'make-appointment', '', '')
customElements.define('alert-message', AlertMessage)

document.addEventListener("DOMContentLoaded", () => {
    register();
});
