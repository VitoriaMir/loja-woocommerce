<?php
/**
 * Segurança básica no nível do WordPress.
 * O restante (firewall, fail2ban, bloqueio de PHP em uploads, HTTPS) fica no servidor: veja deploy/.
 */

defined( 'ABSPATH' ) || exit;

// XML-RPC não é usado pela loja e é alvo comum de ataques de senha.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

// Não divulga a versão do WordPress.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Mensagem de login genérica: não revela se o usuário existe.
add_filter( 'login_errors', function () {
	return '<strong>Erro:</strong> usuário ou senha incorretos.';
} );

// Impede descobrir nomes de usuário por /?author=1 e pela API REST pública.
add_action( 'template_redirect', function () {
	if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

// Cabeçalhos de segurança nas páginas geradas pelo WordPress (o HSTS fica no Nginx, junto do HTTPS).
add_action( 'send_headers', function () {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self "https://www.mercadopago.com.br" "https://sdk.mercadopago.com")' );
} );

/* ------------------------------------------------------------------ */
/* Limite de tentativas de login: 5 erros em 15 minutos bloqueiam o IP */
/* ------------------------------------------------------------------ */

const FV_LOGIN_MAX     = 5;
const FV_LOGIN_JANELA  = 15 * MINUTE_IN_SECONDS;

function fv_login_chave() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'fv_login_' . md5( $ip );
}

add_filter( 'authenticate', function ( $user ) {
	if ( (int) get_transient( fv_login_chave() ) >= FV_LOGIN_MAX ) {
		return new WP_Error( 'fv_bloqueado', '<strong>Erro:</strong> muitas tentativas de login. Aguarde 15 minutos ou use "Perdeu a senha?".' );
	}
	return $user;
}, 99 );

add_action( 'wp_login_failed', function () {
	$chave = fv_login_chave();
	set_transient( $chave, (int) get_transient( $chave ) + 1, FV_LOGIN_JANELA );
} );

add_action( 'wp_login', function () {
	delete_transient( fv_login_chave() );
} );
