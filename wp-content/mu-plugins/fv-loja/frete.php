<?php
/**
 * Frete: quando o pedido ganha frete grátis, esconde a "Entrega padrão" paga
 * (que tem o mesmo prazo). Retirada na loja e opções do Melhor Envio continuam visíveis,
 * para quem quiser pagar por um prazo mais curto.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_package_rates', function ( $taxas ) {
	$tem_gratis = false;
	foreach ( $taxas as $taxa ) {
		if ( 'free_shipping' === $taxa->get_method_id() ) {
			$tem_gratis = true;
			break;
		}
	}
	if ( $tem_gratis ) {
		foreach ( $taxas as $id => $taxa ) {
			if ( 'flat_rate' === $taxa->get_method_id() ) {
				unset( $taxas[ $id ] );
			}
		}
	}
	return $taxas;
}, 100 );

/* ------------------------------------------------------------------ */
/* Proteção: Melhor Envio instalado mas ainda não conectado            */
/* ------------------------------------------------------------------ */

/**
 * Sem token, o plugin do Melhor Envio (v2.16) interrompe a resposta do checkout com
 * "Usuário não autorizado, verificar token do Melhor Envio" DEPOIS de criar o pedido:
 * o cliente vê um erro e o pedido fica pendente. Enquanto não houver token, desligamos
 * só esse passo do plugin. Depois de conectar a conta, ele volta a funcionar sozinho.
 */
function fv_melhor_envio_conectado() {
	if ( ! class_exists( '\MelhorEnvio\Services\TokenService' ) ) {
		return null; // Plugin não instalado.
	}
	return (bool) ( new \MelhorEnvio\Services\TokenService() )->get();
}

add_action( 'woocommerce_checkout_order_processed', function () {
	if ( false !== fv_melhor_envio_conectado() ) {
		return;
	}
	global $wp_filter;
	$gancho = $wp_filter['woocommerce_checkout_order_processed'] ?? null;
	if ( ! $gancho ) {
		return;
	}
	foreach ( $gancho->callbacks as $prioridade => $funcoes ) {
		foreach ( $funcoes as $funcao ) {
			$f = $funcao['function'];
			if ( is_array( $f ) && is_object( $f[0] ) && is_a( $f[0], '\MelhorEnvio\Controllers\QuotationController' ) ) {
				remove_action( 'woocommerce_checkout_order_processed', $f, $prioridade );
			}
		}
	}
}, 1 );

add_action( 'admin_notices', function () {
	if ( false !== fv_melhor_envio_conectado() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>Melhor Envio ainda não conectado.</strong> Os pedidos estão usando a "Entrega padrão" de valor fixo. <a href="%s">Conectar a conta do Melhor Envio</a>.</p></div>',
		esc_url( admin_url( 'admin.php?page=melhor-envio#/token' ) )
	);
} );

/**
 * Carrinho: mostra quanto falta para o frete grátis.
 */
add_action( 'woocommerce_before_cart_totals', 'fv_falta_frete_gratis' );
add_action( 'woocommerce_review_order_before_shipping', 'fv_falta_frete_gratis_linha' );

function fv_falta_para_gratis() {
	$min = (float) fv_dado( 'frete_gratis_min' );
	if ( $min <= 0 || ! WC()->cart ) {
		return 0;
	}
	$subtotal = (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total();
	return max( 0, $min - $subtotal );
}

function fv_falta_frete_gratis() {
	$falta = fv_falta_para_gratis();
	if ( $falta > 0 ) {
		printf( '<p class="fv-falta-frete">Faltam <strong>%s</strong> para ganhar frete grátis.</p>', wp_kses_post( wc_price( $falta ) ) );
	}
}

function fv_falta_frete_gratis_linha() {
	$falta = fv_falta_para_gratis();
	if ( $falta > 0 ) {
		printf( '<tr class="fv-falta-frete"><td colspan="2">Faltam <strong>%s</strong> para ganhar frete grátis.</td></tr>', wp_kses_post( wc_price( $falta ) ) );
	}
}
