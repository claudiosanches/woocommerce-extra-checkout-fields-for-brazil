jQuery(document).ready(function($) {
    // Required fields.
    $('#billing_company_field label, #billing_cpf_field label, #billing_cnpj_field label').append(' <abbr class="required" title="obrigatório">*</abbr>');

    // Hide and show cpf and cnpj fields
    function personTypeFields(current) {
        $('#billing_cpf_field').hide();
        $('#billing_company_field').hide();
        $('#billing_cnpj_field').hide();

        if (1 == current) {
            $('#billing_cpf_field').show();
        }

        if (2 == current) {
            $('#billing_company_field').show();
            $('#billing_cnpj_field').show();
        }
    }
    personTypeFields($('#billing_persontype').val());

    $('#billing_persontype').on('change', function() {
        var current = $(this).val();

        personTypeFields(current);
    });
});
