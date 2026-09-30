/* Checkout brasileiro: máscaras, pessoa física/jurídica e endereço pelo CEP (ViaCEP). */
(function ($) {
	'use strict';

	function digitos(v) { return (v || '').replace(/\D/g, ''); }

	var mascaras = {
		cpf: function (v) {
			v = digitos(v).slice(0, 11);
			return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
		},
		// CNPJ alfanumérico: 12 caracteres (letras ou números) + 2 dígitos verificadores.
		cnpj: function (v) {
			v = (v || '').toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 14);
			return v.replace(/^(\w{2})(\w)/, '$1.$2').replace(/^(\w{2})\.(\w{3})(\w)/, '$1.$2.$3')
				.replace(/\.(\w{3})(\w)/, '.$1/$2').replace(/(\w{4})(\w)/, '$1-$2');
		},
		cep: function (v) {
			v = digitos(v).slice(0, 8);
			return v.replace(/(\d{5})(\d)/, '$1-$2');
		},
		telefone: function (v) {
			v = digitos(v).slice(0, 11);
			if (v.length > 10) { return v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3'); }
			return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/[-\s(]+$/, '').replace(/^\($/, '');
		}
	};

	function aplicar(seletor, mascara) {
		$(document.body).on('input', seletor, function () {
			var novo = mascaras[mascara](this.value);
			if (novo !== this.value) { this.value = novo; }
		});
		$(seletor).each(function () { this.value = mascaras[mascara](this.value); });
	}

	function alternarPessoa() {
		var $tipo = $('#billing_persontype');
		if (!$tipo.length) { return; }
		var pj = $tipo.val() === '2';
		$('.fv-pf').toggle(!pj);
		$('.fv-pj').toggle(pj);
	}

	var ultimoCep = {};
	function buscarCep(tipo) {
		var cep = digitos($('#' + tipo + '_postcode').val());
		if (cep.length !== 8 || ultimoCep[tipo] === cep) { return; }
		ultimoCep[tipo] = cep;
		var $linha = $('#' + tipo + '_postcode_field');
		$linha.addClass('fv-buscando').find('.fv-cep-msg').remove();

		fetch('https://viacep.com.br/ws/' + cep + '/json/')
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (d.erro) {
					$linha.append('<span class="fv-cep-msg">CEP não encontrado. Preencha o endereço manualmente.</span>');
					return;
				}
				if (d.logradouro) { $('#' + tipo + '_address_1').val(d.logradouro); }
				if (d.bairro) { $('#' + tipo + '_neighborhood').val(d.bairro); }
				$('#' + tipo + '_city').val(d.localidade);
				$('#' + tipo + '_state').val(d.uf).trigger('change');
				$('#' + tipo + '_number').trigger('focus');
				$(document.body).trigger('update_checkout');
			})
			.catch(function () {
				$linha.append('<span class="fv-cep-msg">Não foi possível consultar o CEP agora. Preencha o endereço manualmente.</span>');
			})
			.finally(function () { $linha.removeClass('fv-buscando'); });
	}

	$(function () {
		aplicar('#billing_cpf', 'cpf');
		aplicar('#billing_cnpj', 'cnpj');
		aplicar('#billing_postcode, #shipping_postcode, #calc_shipping_postcode', 'cep');
		aplicar('#billing_phone', 'telefone');

		alternarPessoa();
		$(document.body).on('change', '#billing_persontype', alternarPessoa);

		$(document.body).on('input change', '#billing_postcode', function () { buscarCep('billing'); });
		$(document.body).on('input change', '#shipping_postcode', function () { buscarCep('shipping'); });
	});
})(jQuery);
