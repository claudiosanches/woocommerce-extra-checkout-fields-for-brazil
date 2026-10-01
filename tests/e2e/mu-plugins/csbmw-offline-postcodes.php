<?php
/**
 * Keeps the e2e store from asking any CEP service, so the specs only see the
 * addresses global-setup.js seeds.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

add_filter( 'csbmw_postcode_services', '__return_empty_array' );
