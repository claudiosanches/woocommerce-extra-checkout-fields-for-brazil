/* global wcecfb_fix_params */
jQuery(document).ready(function($) {

    /**
     * Fix checkout fields.
     *
     * @return {void}
     */
    function fix_checkout_fields() {
        // Billing.
        $( '#billing_state_field label' ).html( wcecfb_fix_params.state + ' <abbr class="required" title="' + wcecfb_fix_params.required + '">*</abbr>' );
        $( '#billing_postcode_field' ).insertAfter( '#billing_country_field' );

        // Shipping.
        if ( $( '#shipping_state_field' ).length ) {
            $( '#shipping_state_field label' ).html( wcecfb_fix_params.state + ' <abbr class="required" title="' + wcecfb_fix_params.required + '">*</abbr>' );
            $( '#shipping_postcode_field' ).insertAfter( '#shipping_country_field' );
        }
    }

    $( 'body' ).bind( 'country_to_state_changing', function() {
        fix_checkout_fields();
    });

    fix_checkout_fields();
});