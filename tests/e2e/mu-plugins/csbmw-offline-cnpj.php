<?php
/**
 * Answers CNPJ lookups from a fixed list, so the specs never ask a real
 * service. Any other CNPJ is not found.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

add_filter(
	'csbmw_cnpj_services',
	static fn() => array(
		'e2e' => static fn( $cnpj ) => array(
			'11222333000181' => 'ATIVA',
			'19131243000197' => 'BAIXADA',
		)[ $cnpj ] ?? false,
	)
);
