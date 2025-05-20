import './style/front/front.scss'
import registerPreactCustomElement from "./function/registerPreactCustomElement";
import {App} from "./components/Appointment/App";
import register from "./function/register";

registerPreactCustomElement(App, 'make-appointment', '', '')


document.addEventListener("DOMContentLoaded", () => {
    register();
});
