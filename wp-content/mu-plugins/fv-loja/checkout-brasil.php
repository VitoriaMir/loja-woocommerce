<?php
/**
 * Campos brasileiros no checkout clássico e em "Minha conta > Endereços".
 *
 * Os nomes dos campos seguem o padrão do antigo plugin "Brazilian Market on WooCommerce"
 * (_billing_cpf, _billing_cnpj, _billing_persontype, _billing_cellphone, _shipping_number,
 * _shipping_neighborhood), que é o que o Melhor Envio lê para gerar etiquetas.
 */

defined( 'ABSPATH' ) || exit;

const FV_PF = '1';
const FV_PJ = '2';

/* ------------------------------------------------------------------ */
/* Validação de documentos                                             */
/* ------------------------------------------------------------------ */

function fv_so_digitos( $valor ) {
	return preg_replace( '/\D/', '', (string) $valor );
}

function fv_cpf_valido( $cpf ) {
	$cpf = fv_so_digitos( $cpf );
	if ( 11 !== strlen( $cpf ) || preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
		return false;
	}
	for ( $t = 9; $t < 11; $t++ ) {
		$soma = 0;
		for ( $i = 0; $i < $t; $i++ ) {
			$soma += (int) $cpf[ $i ] * ( ( $t + 1 ) - $i );
		}
		$dv = ( ( 10 * $soma ) % 11 ) % 10;
		if ( (int) $cpf[ $t ] !== $dv ) {
			return false;
		}
	}
	return true;
}

/**
 * Normaliza CNPJ: mantém letras e dígitos, em maiúsculas.
 * Desde julho de 2026 a Receita emite CNPJ alfanumérico (12 primeiras posições podem ter letras).
 */
function fv_cnpj_normalizar( $cnpj ) {
	return strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) $cnpj ) );
}

function fv_cnpj_valido( $cnpj ) {
	$cnpj = fv_cnpj_normalizar( $cnpj );
	if ( ! preg_match( '/^[0-9A-Z]{12}\d{2}$/', $cnpj ) || preg_match( '/^(\d)\1{13}$/', $cnpj ) ) {
		return false;
	}
	// Cada caractere vale (código ASCII - 48): dígitos 0–9, letras A=17 … Z=42.
	$valores = array_map( function ( $c ) {
		return ord( $c ) - 48;
	}, str_split( $cnpj ) );

	foreach ( array( 12, 13 ) as $pos ) {
		$pesos = 12 === $pos ? array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) : array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 );
		$soma  = 0;
		foreach ( $pesos as $i => $peso ) {
			$soma += $valores[ $i ] * $peso;
		}
		$resto = $soma % 11;
		$dv    = $resto < 2 ? 0 : 11 - $resto;
		if ( $valores[ $pos ] !== $dv ) {
			return false;
		}
	}
	return true;
}

/* ------------------------------------------------------------------ */
/* Campos                                                              */
/* ------------------------------------------------------------------ */

function fv_rotulo_obrigatorio( $texto ) {
	// Campos condicionais não podem ser "required" para o WooCommerce; a marca é visual e a validação é nossa.
	return $texto . ' <abbr class="required" title="obrigatório">*</abbr>';
}

function fv_campos_endereco( $fields, $tipo ) {
	$p = $tipo . '_';

	$fields[ $p . 'postcode' ]['priority']          = 42;
	$fields[ $p . 'postcode' ]['class']             = array( 'form-row-wide', 'fv-cep' );
	$fields[ $p . 'postcode' ]['custom_attributes'] = array( 'inputmode' => 'numeric', 'maxlength' => '9' );
	$fields[ $p . 'postcode' ]['placeholder']       = '00000-000';

	$fields[ $p . 'address_1' ]['label']       = 'Rua / Avenida';
	$fields[ $p . 'address_1' ]['placeholder'] = '';
	$fields[ $p . 'address_1' ]['priority']    = 50;

	$fields[ $p . 'number' ] = array(
		'label'    => 'Número',
		'required' => true,
		'class'    => array( 'form-row-first' ),
		'priority' => 55,
	);

	$fields[ $p . 'address_2' ]['label']       = 'Complemento';
	$fields[ $p . 'address_2' ]['label_class'] = array();
	$fields[ $p . 'address_2' ]['placeholder'] = 'Apto, bloco, referência';
	$fields[ $p . 'address_2' ]['class']       = array( 'form-row-last' );
	$fields[ $p . 'address_2' ]['priority']    = 60;

	$fields[ $p . 'neighborhood' ] = array(
		'label'    => 'Bairro',
		'required' => true,
		'class'    => array( 'form-row-first' ),
		'priority' => 65,
	);

	$fields[ $p . 'city' ]['class']    = array( 'form-row-last' );
	$fields[ $p . 'city' ]['priority'] = 70;
	$fields[ $p . 'state' ]['priority'] = 80;

	return $fields;
}

add_filter( 'woocommerce_billing_fields', function ( $fields ) {
	$fields = fv_campos_endereco( $fields, 'billing' );

	$fields['billing_persontype'] = array(
		'type'     => 'select',
		'label'    => 'Tipo de cliente',
		'required' => true,
		'class'    => array( 'form-row-wide', 'fv-tipo-pessoa' ),
		'options'  => array( FV_PF => 'Pessoa física', FV_PJ => 'Pessoa jurídica' ),
		'default'  => FV_PF,
		'priority' => 22,
	);
	$fields['billing_cpf'] = array(
		'label'             => fv_rotulo_obrigatorio( 'CPF' ),
		'required'          => false,
		'class'             => array( 'form-row-wide', 'fv-pf' ),
		'custom_attributes' => array( 'inputmode' => 'numeric', 'maxlength' => '14', 'autocomplete' => 'off' ),
		'placeholder'       => '000.000.000-00',
		'priority'          => 23,
	);
	$fields['billing_company']['label']    = fv_rotulo_obrigatorio( 'Razão social' );
	$fields['billing_company']['required'] = false;
	$fields['billing_company']['class']    = array( 'form-row-wide', 'fv-pj' );
	$fields['billing_company']['priority'] = 24;
	$fields['billing_cnpj'] = array(
		'label'             => fv_rotulo_obrigatorio( 'CNPJ' ),
		'required'          => false,
		'class'             => array( 'form-row-wide', 'fv-pj' ),
		'custom_attributes' => array( 'maxlength' => '18', 'autocomplete' => 'off', 'style' => 'text-transform:uppercase' ),
		'placeholder'       => '00.000.000/0000-00',
		'priority'          => 25,
	);

	$fields['billing_phone']['label']             = 'Celular / WhatsApp';
	$fields['billing_phone']['required']          = true;
	$fields['billing_phone']['class']             = array( 'form-row-wide' );
	$fields['billing_phone']['custom_attributes'] = array( 'inputmode' => 'tel', 'maxlength' => '15' );
	$fields['billing_phone']['placeholder']       = '(11) 90000-0000';
	$fields['billing_phone']['priority']          = 30;
	$fields['billing_email']['class']             = array( 'form-row-wide' );
	$fields['billing_email']['priority']          = 31;

	return $fields;
}, 20 );

add_filter( 'woocommerce_shipping_fields', function ( $fields ) {
	$fields = fv_campos_endereco( $fields, 'shipping' );
	$fields['shipping_company']['priority'] = 25;
	return $fields;
}, 20 );

/**
 * O address-i18n.js do WooCommerce reordena e reescreve rótulos pelo "locale" do país.
 * Mantemos o locale do Brasil alinhado com a ordem acima para ele não desfazer o layout.
 */
add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$locale['BR'] = array_merge( isset( $locale['BR'] ) ? $locale['BR'] : array(), array(
		'postcode'  => array( 'priority' => 42, 'label' => 'CEP' ),
		'address_1' => array( 'priority' => 50, 'label' => 'Rua / Avenida', 'placeholder' => '' ),
		'address_2' => array( 'priority' => 60, 'label' => 'Complemento', 'label_class' => array(), 'placeholder' => 'Apto, bloco, referência', 'required' => false ),
		'city'      => array( 'priority' => 70, 'label' => 'Cidade' ),
		'state'     => array( 'priority' => 80, 'label' => 'Estado' ),
		// Desde o WooCommerce 9.x o telefone é um campo de endereço e também passa pelo locale.
		'phone'     => array( 'priority' => 30, 'label' => 'Celular / WhatsApp', 'required' => true ),
	) );
	return $locale;
} );

/* ------------------------------------------------------------------ */
/* Validação                                                           */
/* ------------------------------------------------------------------ */

/**
 * @return string[] Mensagens de erro.
 */
function fv_validar_documentos( $dados ) {
	$erros = array();
	$tipo  = isset( $dados['billing_persontype'] ) ? (string) $dados['billing_persontype'] : FV_PF;

	if ( FV_PJ === $tipo ) {
		if ( empty( $dados['billing_company'] ) ) {
			$erros[] = '<strong>Razão social</strong> é obrigatória para pessoa jurídica.';
		}
		if ( empty( $dados['billing_cnpj'] ) ) {
			$erros[] = '<strong>CNPJ</strong> é obrigatório para pessoa jurídica.';
		} elseif ( ! fv_cnpj_valido( $dados['billing_cnpj'] ) ) {
			$erros[] = 'O <strong>CNPJ</strong> informado não é válido. Confira os 14 caracteres.';
		}
	} else {
		if ( empty( $dados['billing_cpf'] ) ) {
			$erros[] = '<strong>CPF</strong> é obrigatório. Ele aparece na nota fiscal e na etiqueta de envio.';
		} elseif ( ! fv_cpf_valido( $dados['billing_cpf'] ) ) {
			$erros[] = 'O <strong>CPF</strong> informado não é válido. Confira os 11 números.';
		}
	}

	foreach ( array( 'billing_phone' ) as $campo ) {
		if ( ! empty( $dados[ $campo ] ) && strlen( fv_so_digitos( $dados[ $campo ] ) ) < 10 ) {
			$erros[] = 'O <strong>celular</strong> precisa ter DDD e número, por exemplo (11) 90000-0000.';
		}
	}
	return $erros;
}

add_action( 'woocommerce_after_checkout_validation', function ( $dados, $errors ) {
	foreach ( fv_validar_documentos( $dados ) as $i => $msg ) {
		$errors->add( 'fv_documento_' . $i, $msg );
	}
}, 10, 2 );

add_action( 'woocommerce_after_save_address_validation', function ( $user_id, $tipo_endereco ) {
	if ( 'billing' !== $tipo_endereco ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- o WooCommerce já verificou o nonce do formulário.
	$dados = wc_clean( wp_unslash( $_POST ) );
	foreach ( fv_validar_documentos( $dados ) as $msg ) {
		wc_add_notice( $msg, 'error' );
	}
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Gravação                                                            */
/* ------------------------------------------------------------------ */

/**
 * O WooCommerce já grava os campos extras como metadados (_billing_cpf etc.).
 * Aqui limpamos o documento que não se aplica e copiamos o celular para _billing_cellphone.
 */
add_action( 'woocommerce_checkout_create_order', function ( $order, $dados ) {
	$tipo = isset( $dados['billing_persontype'] ) ? (string) $dados['billing_persontype'] : FV_PF;
	if ( FV_PJ === $tipo ) {
		$order->update_meta_data( '_billing_cnpj', fv_cnpj_normalizar( $dados['billing_cnpj'] ) );
		$order->delete_meta_data( '_billing_cpf' );
	} else {
		$order->delete_meta_data( '_billing_cnpj' );
	}
	$order->update_meta_data( '_billing_cellphone', $order->get_billing_phone() );
}, 10, 2 );

/**
 * O Mercado Pago preenche o documento do comprador a partir do metadado "billing_document" do usuário.
 */
add_action( 'woocommerce_checkout_update_user_meta', function ( $user_id, $dados ) {
	if ( ! $user_id ) {
		return;
	}
	$tipo = isset( $dados['billing_persontype'] ) ? (string) $dados['billing_persontype'] : FV_PF;
	$doc  = FV_PJ === $tipo ? fv_cnpj_normalizar( $dados['billing_cnpj'] ?? '' ) : fv_so_digitos( $dados['billing_cpf'] ?? '' );
	if ( $doc ) {
		update_user_meta( $user_id, 'billing_document', $doc );
	}
}, 10, 2 );

/* ------------------------------------------------------------------ */
/* Exibição do endereço                                                */
/* ------------------------------------------------------------------ */

add_filter( 'woocommerce_localisation_address_formats', function ( $formatos ) {
	$formatos['BR'] = "{name}\n{company}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city} - {state}\n{postcode}";
	return $formatos;
} );

add_filter( 'woocommerce_formatted_address_replacements', function ( $troca, $args ) {
	$troca['{number}']       = isset( $args['number'] ) ? $args['number'] : '';
	$troca['{neighborhood}'] = isset( $args['neighborhood'] ) ? $args['neighborhood'] : '';
	return $troca;
}, 10, 2 );

foreach ( array( 'billing', 'shipping' ) as $fv_tipo ) {
	add_filter( "woocommerce_order_formatted_{$fv_tipo}_address", function ( $endereco, $order ) use ( $fv_tipo ) {
		if ( is_array( $endereco ) ) {
			$endereco['number']       = $order->get_meta( "_{$fv_tipo}_number" );
			$endereco['neighborhood'] = $order->get_meta( "_{$fv_tipo}_neighborhood" );
		}
		return $endereco;
	}, 10, 2 );
}
unset( $fv_tipo );

add_filter( 'woocommerce_my_account_my_address_formatted_address', function ( $endereco, $customer_id, $tipo ) {
	$endereco['number']       = get_user_meta( $customer_id, $tipo . '_number', true );
	$endereco['neighborhood'] = get_user_meta( $customer_id, $tipo . '_neighborhood', true );
	return $endereco;
}, 10, 3 );

/**
 * Campos editáveis na tela do pedido no painel.
 */
add_filter( 'woocommerce_admin_billing_fields', function ( $campos ) {
	$campos['persontype']   = array( 'label' => 'Tipo (1 = PF, 2 = PJ)', 'show' => false );
	$campos['cpf']          = array( 'label' => 'CPF', 'show' => false );
	$campos['cnpj']         = array( 'label' => 'CNPJ', 'show' => false );
	$campos['number']       = array( 'label' => 'Número', 'show' => false );
	$campos['neighborhood'] = array( 'label' => 'Bairro', 'show' => false );
	return $campos;
} );

add_filter( 'woocommerce_admin_shipping_fields', function ( $campos ) {
	$campos['number']       = array( 'label' => 'Número', 'show' => false );
	$campos['neighborhood'] = array( 'label' => 'Bairro', 'show' => false );
	return $campos;
} );

add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$cnpj = $order->get_meta( '_billing_cnpj' );
	$cpf  = $order->get_meta( '_billing_cpf' );
	echo '<p><strong>' . ( $cnpj ? 'CNPJ' : 'CPF' ) . ':</strong> ' . esc_html( $cnpj ? $cnpj : $cpf ) . '</p>';
} );

/* ------------------------------------------------------------------ */
/* Script: máscaras, PF/PJ e CEP                                       */
/* ------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_account_page() ) ) {
		return;
	}
	$url = plugins_url( 'assets/', __FILE__ );
	wp_enqueue_script( 'fv-checkout-brasil', $url . 'checkout-brasil.js', array( 'jquery' ), FV_LOJA_VERSION, true );
	wp_enqueue_style( 'fv-checkout-brasil', $url . 'checkout-brasil.css', array(), FV_LOJA_VERSION );
} );
