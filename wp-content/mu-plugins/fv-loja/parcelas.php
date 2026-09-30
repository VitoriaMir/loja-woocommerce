<?php
/**
 * Mostra "ou 6x de R$ 33,17 sem juros" abaixo do preço.
 *
 * Importante: o número de parcelas sem juros precisa bater com o que está configurado
 * no Mercado Pago (Parcelamento sem juros / "Parcelas sem acréscimo"). Ajuste em
 * Configurações > Dados da loja.
 */

defined( 'ABSPATH' ) || exit;

function fv_parcelamento( $preco ) {
	$max = (int) fv_dado( 'parcelas_max' );
	$min = (float) fv_dado( 'parcela_min' );
	if ( $preco <= 0 || $max < 2 ) {
		return null;
	}
	$n = $min > 0 ? min( $max, (int) floor( $preco / $min ) ) : $max;
	if ( $n < 2 ) {
		return null;
	}
	return array( 'n' => $n, 'valor' => $preco / $n );
}

add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $html;
	}
	$preco = $product->is_type( 'variable' ) ? (float) $product->get_variation_price( 'min', true ) : (float) wc_get_price_to_display( $product );
	$p     = fv_parcelamento( $preco );
	if ( ! $p ) {
		return $html;
	}
	$prefixo = $product->is_type( 'variable' ) ? 'a partir de ' : 'ou ';
	return $html . sprintf(
		'<span class="fv-parcelas">%s%dx de %s sem juros</span>',
		$prefixo,
		$p['n'],
		wp_strip_all_tags( wc_price( $p['valor'] ) )
	);
}, 20, 2 );
