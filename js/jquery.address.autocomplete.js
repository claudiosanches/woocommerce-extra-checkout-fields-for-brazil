jQuery(document).ready(function($) {

    /**
     * Address Autocomplete.
     *
     * @param  {string} field Field name.
     * @return Autocomplete fields.
     */
    function addressAutoComplete(field) {
        $('#' + field + '_postcode').blur(function() {
            // Valid CEP.
            var cep = $(this).val().replace('.','').replace('-', '');
            var country = $('#' + field + '_country').val();

            // Check country is BR.
            if (cep != '' && cep.length == 8 && country == 'BR') {
                $.getScript('http://cep.republicavirtual.com.br/web_cep.php?cep=' + cep + '&formato=javascript', function() {

                    // Check if is valid CEP.
                    if (unescape(resultadoCEP['resultado']) == '1') {
                        if (unescape(resultadoCEP['tipo_logradouro']) != '') {
                            $('#' + field + '_address_1').val(unescape(resultadoCEP['tipo_logradouro']) + ' ' + unescape(resultadoCEP['logradouro']));
                        }
                        else {
                            $('#' + field + '_address_1').val(unescape(resultadoCEP['logradouro']));
                        }
                        $('#' + field + '_neighborhood').val(unescape(resultadoCEP['bairro']));
                        $('#' + field + '_city').val(unescape(resultadoCEP['cidade']));
                        $('#' + field + '_state').val(unescape(resultadoCEP['uf']));
                    }

                    // Check if is a city with only CEP.
                    if (unescape(resultadoCEP['resultado']) == '2') {
                        $('#' + field + '_city').val(unescape(resultadoCEP['cidade']));
                        $('#' + field + '_state').val(unescape(resultadoCEP['uf']));
                    }

                });
            }
        });
    }

    // Auto complete billing address.
    addressAutoComplete('billing');
    // Auto complete shipping address.
    addressAutoComplete('shipping');

});
