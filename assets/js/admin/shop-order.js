/* global bmwShopOrderParams, woocommerce_admin_meta_boxes */
/* eslint-disable no-alert */
(function ($) {
	$(function () {
		if ('1' === bmwShopOrderParams.person_type) {
			$('#_billing_persontype')
				.on('change', function () {
					const current = $(this).val();

					$(
						'._billing_cpf_field, ._billing_rg_field, ._billing_company_field, ._billing_cnpj_field, ._billing_ie_field'
					).hide();

					if ('1' === current) {
						$('._billing_cpf_field, ._billing_rg_field').show();
					}

					if ('2' === current) {
						$(
							'._billing_company_field, ._billing_cnpj_field, ._billing_ie_field'
						).show();
					}
				})
				.change();
		}

		$('.load_customer_billing').on('click', function () {
			const userId = $('#customer_user').val();

			if (window.confirm(bmwShopOrderParams.load_message)) {
				if (!userId) {
					window.alert(
						woocommerce_admin_meta_boxes.no_customer_selected // eslint-disable-line camelcase
					);
					return false;
				}

				$(this)
					.closest('.edit_address')
					.block({
						message: null,
						overlayCSS: {
							background:
								'#fff url(' +
								woocommerce_admin_meta_boxes.plugin_url + // eslint-disable-line camelcase
								'/assets/images/ajax-loader.gif) no-repeat center',
							opacity: 0.6,
						},
					});

				$.ajax({
					url: woocommerce_admin_meta_boxes.ajax_url, // eslint-disable-line camelcase
					data: {
						user_id: userId,
						type_to_load: 'billing',
						action: 'woocommerce_get_customer_details',
						security:
							woocommerce_admin_meta_boxes.get_customer_details_nonce, // eslint-disable-line camelcase
					},
					type: 'POST',
					success(response) {
						if (response) {
							$('input#_billing_number').val(
								response.billing_number
							);
							$('input#_billing_neighborhood').val(
								response.billing_neighborhood
							);
							$('input#_billing_persontype').val(
								response.billing_persontype
							);
							$('input#_billing_cpf').val(response.billing_cpf);
							$('input#_billing_rg').val(response.billing_rg);
							$('input#_billing_cnpj').val(response.billing_cnpj);
							$('input#_billing_ie').val(response.billing_ie);
							$('input#_billing_birthdate').val(
								response.billing_birthdate
							);
							$('input#_billing_gender').val(
								response.billing_gender
							);
							$('input#_billing_cellphone').val(
								response.billing_cellphone
							);
						}

						$('.edit_address').unblock();
					},
				});
			}

			return false;
		});

		$('.load_customer_shipping').on('click', function () {
			const userId = $('#customer_user').val();

			if (window.confirm(bmwShopOrderParams.load_message)) {
				if (!userId) {
					window.alert(
						woocommerce_admin_meta_boxes.no_customer_selected // eslint-disable-line camelcase
					);
					return false;
				}

				$(this)
					.closest('.edit_address')
					.block({
						message: null,
						overlayCSS: {
							background:
								'#fff url(' +
								woocommerce_admin_meta_boxes.plugin_url + // eslint-disable-line camelcase
								'/assets/images/ajax-loader.gif) no-repeat center',
							opacity: 0.6,
						},
					});

				$.ajax({
					url: woocommerce_admin_meta_boxes.ajax_url, // eslint-disable-line camelcase
					data: {
						user_id: userId,
						type_to_load: 'shipping',
						action: 'woocommerce_get_customer_details',
						security:
							woocommerce_admin_meta_boxes.get_customer_details_nonce, // eslint-disable-line camelcase
					},
					type: 'POST',
					success(response) {
						if (response) {
							$('input#_shipping_number').val(
								response.shipping_number
							);
							$('input#_shipping_neighborhood').val(
								response.shipping_neighborhood
							);
						}

						$('.edit_address').unblock();
					},
				});
			}

			return false;
		});

		$('button.billing-same-as-shipping').on('click', function () {
			if (window.confirm(bmwShopOrderParams.copy_message)) {
				$('input#_shipping_number').val(
					$('input#_billing_number').val()
				);
				$('input#_shipping_neighborhood').val(
					$('input#_billing_neighborhood').val()
				);
			}

			return false;
		});
	});
})(jQuery);
