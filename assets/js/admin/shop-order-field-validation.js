document.addEventListener('DOMContentLoaded', function () {
    const fieldsToMask = [
        document.getElementById('_billing_cpf'),
        document.getElementById('_billing_cnpj'),
        document.getElementById('_billing_phone'),
        document.getElementById('_billing_cellphone'),
        document.getElementById('_billing_postcode'),
        document.getElementById('_shipping_postcode'),
    ].filter(Boolean);

    fieldsToMask.forEach((field) => {
        field.addEventListener('input', () => validateField(field));
        field.addEventListener('change', () => validateField(field));
        validateField(field);
    });

    function validateField(field) {
        let isValid = false;

        // Clean the input sequence and remove formatting, allowing only alphanumeric characters.
        const sanitizedInput = String(field.value).toUpperCase().replace(/[^A-Z0-9]/g, '');

        // Check if the input is not empty and does not consists of the same character (e.g., '00...0', 'AA...A').
        if (sanitizedInput !== '' && ! /^([A-Z0-9])\1+$/.test(sanitizedInput)) {

            // Check length and structure of the input.
            switch (field.id) {
                case '_billing_cpf':
                    if (/^[0-9]{11}$/.test(sanitizedInput)) isValid = validateCPF(sanitizedInput);
                    break;

                case '_billing_cnpj':
                    if (/^[A-Z0-9]{12}[0-9]{2}$/.test(sanitizedInput)) isValid = validateCNPJ(sanitizedInput);
                    break;

                case '_billing_phone':
                    if (/^[0-9]{10,11}$/.test(sanitizedInput)) isValid = true;
                    break;

                case '_billing_cellphone':
                    if (/^[0-9]{11}$/.test(sanitizedInput)) isValid = true;
                    break;

                case '_billing_postcode':
                case '_shipping_postcode':
                    if (/^[0-9]{8}$/.test(sanitizedInput)) isValid = true;
                    break;
            }
        }

        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }

        return isValid;
    }

    function validateCPF(cpf) {
        // Helper to calculate a check digit
        const weightedSum = (cpfSlice, weight) => {
            let total = 0;
            for (let i = 0; i < cpfSlice.length; i++) {
                const digit = Number(cpfSlice.charAt(i));
                total += digit * (weight - i);
            }
            let remainder = (total * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            return remainder;
        }

        // Calculate first check digit (for positions 1..9)
        const firstDigit = weightedSum(cpf.substring(0, 9), 10);
        if (firstDigit !== Number(cpf.charAt(9))) {
            return false;
        }

        // Calculate second check digit (for positions 1..10)
        const secondDigit = weightedSum(cpf.substring(0, 10), 11);
        if (secondDigit !== Number(cpf.charAt(10))) {
            return false;
        }

        return true;
    }

    function validateCNPJ(cnpj) {
        const base = cnpj.substring(0, 12);

        // Weighted sum helper: receives a string slice and an array of weights to calculate a check digit.
        const weightedSum = (cnpjSlice, weights) => {
            let total = 0;
            for (let i = 0; i < cnpjSlice.length; i++) {
                const char = cnpjSlice[i];
                const value = char.charCodeAt(0) - 48;
                total += value * weights[i];
            }
            const remainder = total % 11;
            return remainder < 2 ? 0 : 11 - remainder;
        };

        const firstDigitWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        const secondDigitWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // Calculate first check digit.
        const firstDigit = weightedSum(base, firstDigitWeights);

        // Append first check digit to the input and calculate second check digit.
        const secondDigit = weightedSum(base + firstDigit.toString(), secondDigitWeights);

        // Return both check digits as a string and compare with the last two digits of the input.
        if ((firstDigit.toString() + secondDigit.toString()) === cnpj.substring(12)) {
            return true;
        }

        return false;
    }
});