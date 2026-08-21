document.addEventListener('DOMContentLoaded', () => {

    // We strictly use regex to add symbols ONLY if there is a number succeeding them.
    // This entirely eliminates the "stuck backspace" issue at the end of an input.
    const formatCep = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        return digits.replace(/^(\d{5})(\d)/, '$1-$2');
    };

    const formatPhone = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);

        if (digits.length <= 10) {
            return digits
                .replace(/^(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        }

        return digits
            .replace(/^(\d{2})(\d{5})(\d)/, '($1) $2-$3');
    };

    const formatCpf = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);

        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1-$2');
    };

    const formatCnpj = (value) => {
        const sanitized = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 14);

        return sanitized
            .replace(/^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2')
            .replace(/([A-Z0-9]{3})([A-Z0-9])/, '$1.$2')
            .replace(/([A-Z0-9]{3})([A-Z0-9])/, '$1/$2')
            .replace(/([A-Z0-9]{4})([A-Z0-9])/, '$1-$2');
    };

    const applyMaskToField = (field) => {
        if (!field) return;

        const id = (field.id || '').toLowerCase();
        const value = field.value || '';

        // 1. Remember cursor position based on raw alphanumeric characters
        let cursorPosition = 0;
        try {
            cursorPosition = field.selectionStart || 0;
        } catch (e) {
            // Ignore error if field type doesn't support selection (like type="email")
        }

        const beforeCursor = value.slice(0, cursorPosition);
        const unformattedBeforeCursor = beforeCursor.replace(/[^A-Z0-9]/ig, '').length;

        // 2. Format the value
        let newValue = value;

        switch (id) {
            case '_billing_postcode':
            case '_shipping_postcode':
                newValue = formatCep(value);
                break;

            case '_billing_phone':
            case '_billing_cellphone':
                newValue = formatPhone(value);
                break;

            case '_billing_cpf':
                newValue = formatCpf(value);
                break;

            case '_billing_cnpj':
                newValue = formatCnpj(value);
                break;

        }

        // 3. Update field and calculate where the cursor should land
        if (value !== newValue) {
            field.value = newValue;

            let newCursorPosition = 0;
            let unformattedCount = 0;

            // Advance the cursor until we've skipped the exact number of raw characters
            while (newCursorPosition < newValue.length && unformattedCount < unformattedBeforeCursor) {
                if (/[A-Z0-9]/i.test(newValue[newCursorPosition])) {
                    unformattedCount++;
                }
                newCursorPosition++;
            }

            // Only set cursor if the field actually has focus, avoiding unwanted auto-focus bugs
            if (document.activeElement === field) {
                try {
                    field.setSelectionRange(newCursorPosition, newCursorPosition);
                } catch (e) { }
            }
        }
    };

    const fieldsToMask = [
        document.getElementById('_billing_cpf'),
        document.getElementById('_billing_cnpj'),
        document.getElementById('_billing_phone'),
        document.getElementById('_billing_cellphone'),
        document.getElementById('_billing_postcode'),
        document.getElementById('_shipping_postcode'),
    ].filter(Boolean);

    fieldsToMask.forEach((field) => {
        field.addEventListener('input', () => applyMaskToField(field));
        field.addEventListener('change', () => applyMaskToField(field));
        applyMaskToField(field);
    });
});