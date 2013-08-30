jQuery(document).ready(function($) {

    /**
     * Address Autocomplete.
     *
     * @param  {string} field Field name.
     *
     * @return {void}         Autocomplete fields.
     */
    function addressAutoComplete(field) {
        // Valid CEP.
        var cep = $('#' + field + '_postcode').val().replace('.','').replace('-', '');
        var country = $('#' + field + '_country').val();

        // Check country is BR.
        if (cep !== '' && 8 === cep.length && 'BR' === country) {
            // Gets the address.
            $.getScript('http://www.toolsweb.com.br/webservice/clienteWebService.php?cep=' + cep + '&formato=javascript', function() {

                // Address.
                if ('' !== unescape(resultadoCEP['tipoLogradouro'])) {
                    $('#' + field + '_address_1').val(unescape(resultadoCEP['tipoLogradouro']) + ' ' + unescape(resultadoCEP['logradouro']));
                } else {
                    $('#' + field + '_address_1').val(unescape(resultadoCEP['logradouro']));
                }

                // Neighborhood.
                $('#' + field + '_neighborhood').val(unescape(resultadoCEP['bairro']));

                // City.
                $('#' + field + '_city').val(unescape(resultadoCEP['cidade']));

                // State.
                $('#' + field + '_state option:selected').attr('selected', false);
                $('#' + field + '_state option[value="' + unescape(resultadoCEP['estado']) + '"]').attr('selected', true);

                // Chosen support.
                $('#' + field + '_state').trigger("liszt:updated");
            });
        }
    }

    /**
     * Autocomplete the Address on change the *_postcode input.
     *
     * @param  {string} field Field name.
     *
     * @return {void}         Autocomplete fields.
     */
    function addressAutoCompleteOnChange(field) {
        $('#' + field + '_postcode').on('blur', function() {
            addressAutoComplete(field);
        });
    }

    // Auto complete billing address.
    addressAutoComplete('billing');
    addressAutoCompleteOnChange('billing');

    // Auto complete shipping address.
    addressAutoComplete('shipping');
    addressAutoCompleteOnChange('shipping');
});
