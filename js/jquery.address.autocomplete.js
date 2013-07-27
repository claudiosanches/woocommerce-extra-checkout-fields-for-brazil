jQuery(document).ready(function($) {

    /**
     * Address Autocomplete.
     *
     * @param  {string} field Field name.
     * @return Autocomplete fields.
     */
    function addressAutoComplete(field) {
        $('#' + field + '_postcode').on('blur', function() {
            // Valid CEP.
            var cep = $(this).val().replace('.','').replace('-', '');
            var country = $('#' + field + '_country').val();

            // Check country is BR.
            if (cep !== '' && cep.length === 8 && country === 'BR') {
                $.getScript('http://www.toolsweb.com.br/webservice/clienteWebService.php?cep=' + cep + '&formato=javascript', function() {

                    if (unescape(resultadoCEP['tipoLogradouro']) !== '') {
                        $('#' + field + '_address_1').val(unescape(resultadoCEP['tipoLogradouro']) + ' ' + unescape(resultadoCEP['logradouro']));
                    }
                    else {
                        $('#' + field + '_address_1').val(unescape(resultadoCEP['logradouro']));
                    }
                    $('#' + field + '_neighborhood').val(unescape(resultadoCEP['bairro']));
                    $('#' + field + '_city').val(unescape(resultadoCEP['cidade']));
                    $('#' + field + '_state').val(unescape(resultadoCEP['estado']));

                });
            }
        });
    }

    // Auto complete billing address.
    addressAutoComplete('billing');

    // Auto complete shipping address.
    addressAutoComplete('shipping');
});
