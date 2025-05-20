export default function register() {
    const checkbox = document.querySelector<HTMLInputElement>('#is_conductor');
    const conducteurFields = [
        document.querySelector<HTMLInputElement>('#name_conductor'),
        document.querySelector<HTMLInputElement>('#firstname_conductor'),
        document.querySelector<HTMLInputElement>('#tel_conductor'),
    ];

    const userFirstname = document.querySelector<HTMLInputElement>('#registration_form_firstname');
    const userLastname = document.querySelector<HTMLInputElement>('#registration_form_lastname');
    const userEmail = document.querySelector<HTMLInputElement>('#registration_form_email');

    const toggleConductorFields = () => {
        if (!checkbox) return;

        const isChecked = checkbox.checked;

        conducteurFields.forEach(field => {
            if (field) {
                field.closest('.form-group')?.classList.toggle('hidden', isChecked);
                if (isChecked) {
                    if (field.id === 'firstname_conductor' && userFirstname) {
                        field.value = userFirstname.value;
                    }
                    if (field.id === 'name_conductor' && userLastname) {
                        field.value = userLastname.value;
                    }
                    if (field.id === 'tel_conductor' && userEmail) {
                        field.value = '';
                    }
                } else {
                    field.value = '';
                }
            }
        });
    };

    checkbox?.addEventListener('change', toggleConductorFields);
    toggleConductorFields();
}
