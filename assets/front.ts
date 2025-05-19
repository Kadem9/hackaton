import './style/front/front.scss'
import registerPreactCustomElement from "./function/registerPreactCustomElement";
import {App} from "./components/Appointment/App";

registerPreactCustomElement(App, 'make-appointment', '', '')
