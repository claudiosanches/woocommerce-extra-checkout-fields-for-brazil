/* global wcbcf_public_params */
jQuery(document).ready(function($) {

    /**
     * Fix checkout fields.
     *
     * @return {void}
     */
    function fix_checkout_fields() {
        // Billing.
        $( '#billing_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
        $( '#billing_postcode_field' ).insertAfter( '#billing_country_field' );

        // Shipping.
        if ( $( '#shipping_state_field' ).length ) {
            $( '#shipping_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
            $( '#shipping_postcode_field' ).insertAfter( '#shipping_country_field' );
        }
    }

    // Load on bind country_to_state_changing.
    $( 'body' ).bind( 'country_to_state_changing', function() {
        fix_checkout_fields();
    });

    // Run on load.
    fix_checkout_fields();
});
