import './style/admin/admin.scss'
import {AlertMessage} from "./elements/AlertMessage";
import {InputSwitch} from "./elements/InputSwitch";
import {ButtonToggle} from "./elements/ButtonToggle";
import {ConfirmAction} from "./elements/ConfirmAction";
import {ThemeSwitcher} from "./elements/ThemeSwitcher";
import {SortableWrapper} from "./elements/SortableWrapper";
import sidebar from "./function/sidebar";
import mediaCopy from "./function/media";
import {Modal} from "./elements/Modal";
import {CustomizeTable} from "./elements/CustomizeTable";

customElements.define('alert-message', AlertMessage)
customElements.define('button-toggle', ButtonToggle)
customElements.define('confirm-action', ConfirmAction)
customElements.define('input-switch', InputSwitch, { extends: 'input' })
customElements.define('theme-switcher', ThemeSwitcher)
customElements.define('sortable-wrapper', SortableWrapper)
customElements.define('modal-box', Modal)
customElements.define('customize-table', CustomizeTable)

sidebar();
mediaCopy();