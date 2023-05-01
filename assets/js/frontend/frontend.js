/* global bmwPublicParams */
jQuery(function ($) {
	/**
	 * Frontend actions
	 */
	const bmwFrontEnd = {
		/**
		 * Initialize frontend actions
		 */
		init() {
			if ('0' !== bmwPublicParams.person_type) {
				this.person_type_fields();
			}

			if ('yes' === bmwPublicParams.maskedinput) {
				$(document.body).on('change', '#billing_country', function () {
					if ('BR' === $(this).val()) {
						bmwFrontEnd.maskBilling();
					} else {
						bmwFrontEnd.unmaskBilling();
					}
				});

				$(document.body).on('change', '#shipping_country', function () {
					if ('BR' === $(this).val()) {
						bmwFrontEnd.maskShipping();
					} else {
						bmwFrontEnd.unmaskShipping();
					}
				});

				if ('BR' === $('#billing_country').val()) {
					bmwFrontEnd.maskBilling();
				}

				if ('BR' === $('#shipping_country').val()) {
					bmwFrontEnd.maskShipping();
				}

				this.maskGeneral();
			}

			if ('yes' === bmwPublicParams.mailcheck) {
				this.emailCheck();
			}

			// Check if select2 exists.
			if ($().select2) {
				$('.wc-ecfb-select').select2();
			}
		},

		person_type_fields() {
			// Required fields.
			if ('no' === bmwPublicParams.only_brazil) {
				$('.person-type-field label .required').remove();
				$('.person-type-field').addClass('validate-required');
				$('.person-type-field label').append(
					' <abbr class="required" title="' +
						bmwPublicParams.required +
						'">*</abbr>'
				);
			} else {
				$('#billing_country')
					.on('change', function () {
						const current = $(this).val();

						if ('BR' === current) {
							$('.person-type-field label .required').remove();
							$('.person-type-field').addClass(
								'validate-required'
							);
							$('.person-type-field label').append(
								' <abbr class="required" title="' +
									bmwPublicParams.required +
									'">*</abbr>'
							);
						} else {
							$('.person-type-field').removeClass(
								'validate-required'
							);
							$('.person-type-field label .required').remove();
						}
					})
					.change();
			}

			if ('1' === bmwPublicParams.person_type) {
				$('#billing_persontype')
					.on('change', function () {
						const current = $(this).val();

						$('#billing_cpf_field').hide();
						$('#billing_rg_field').hide();
						$('#billing_company_field').hide();
						$('#billing_cnpj_field').hide();
						$('#billing_ie_field').hide();

						if ('1' === current) {
							$('#billing_cpf_field').show();
							$('#billing_rg_field').show();
						}

						if ('2' === current) {
							$('#billing_company_field').show();
							$('#billing_cnpj_field').show();
							$('#billing_ie_field').show();
						}
					})
					.change();
			}
		},

		maskBilling() {
			bmwFrontEnd.maskPhone('#billing_phone, #billing_cellphone');
			$('#billing_birthdate').mask('00/00/0000');
			$('#billing_postcode').mask('00000-000');
			$(
				'#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode'
			).attr('type', 'tel');
		},

		unmaskBilling() {
			$(
				'#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode'
			)
				.unmask()
				.attr('type', 'text');
		},

		maskShipping() {
			$('#shipping_postcode').mask('00000-000').attr('type', 'tel');
		},

		unmaskShipping() {
			$('#shipping_postcode').unmask().attr('type', 'text');
		},

		maskGeneral() {
			$('#billing_cpf, #credit-card-cpf').mask('000.000.000-00');
			$('#billing_cnpj').mask('00.000.000/0000-00');
			bmwFrontEnd.maskPhone('#credit-card-phone');
		},

		maskPhone(selector) {
			const $element = $(selector),
				MaskBehavior = function (val) {
					return val.replace(/\D/g, '').length === 11
						? '(00) 00000-0000'
						: '(00) 0000-00009';
				},
				maskOptions = {
					onKeyPress(val, e, field, options) {
						field.mask(MaskBehavior.apply({}, arguments), options);
					},
				};

			$element.mask(MaskBehavior, maskOptions);
		},

		emailCheck() {
			const text = bmwPublicParams.suggest_text;
			if ($('#wcbcf-mailsuggest').length < 1) {
				$('#billing_email').after('<div id="wcbcf-mailsuggest"></div>');
			}

			$('#billing_email').on('blur', function () {
				$('#wcbcf-mailsuggest').html('');
				$(this).mailcheck({
					suggested(element, suggestion) {
						$('#wcbcf-mailsuggest').html(
							text.replace('%hint%', suggestion.full)
						);
					},
				});
			});

			$('#wcbcf-mailsuggest').css({
				color: '#c00',
				fontSize: 'small',
			});
		},
	};

	bmwFrontEnd.init();
});
