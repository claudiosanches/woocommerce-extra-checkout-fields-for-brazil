jQuery(document).ready(function($) {

    /**
     * Address Autocomplete.
     *
     * @param  {string} field Field name.
     *
     * @return {void}         Autocomplete fields.
     */
    function addressAutoComplete(field) {
        // Checks with *_postcode field exist.
        if ( $('#' + field + '_postcode').length ) {

            // Valid CEP.
            var cep = $('#' + field + '_postcode').val().replace('.', '').replace('-', ''),
                country = $('#' + field + '_country').val(),
                address_1 = $('#' + field + '_address_1').val();

            // Check country is BR.
            if (cep !== '' && 8 === cep.length && 'BR' === country && 0 === address_1.length) {

                // Gets the address.
                $.ajax({
                    type: "GET",
                    url: '//correiosapi.apphb.com/cep/' + cep,
                    dataType: 'jsonp',
                    crossDomain: true,
                    contentType: "application/json",
                    success: function(address) {

                        // Address.
                        if ('' !== address.tipoDeLogradouro) {
                            $('#' + field + '_address_1').val(address.tipoDeLogradouro + ' ' + address.logradouro);
                        } else {
                            $('#' + field + '_address_1').val(address.logradouro);
                        }

                        // Neighborhood.
                        $('#' + field + '_neighborhood').val(address.bairro);

                        // City.
                        $('#' + field + '_city').val(address.cidade);

                        // State.
                        $('#' + field + '_state option:selected').attr('selected', false);
                        $('#' + field + '_state option[value="' + address.estado + '"]').attr('selected', true);

                        // Chosen support.
                        $('#' + field + '_state').trigger("liszt:updated");
                    }
                });
            }
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
