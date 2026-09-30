<?php
/**
 * Plugin Name: Fermento Vivo · Núcleo da loja
 * Description: Campos brasileiros no checkout, dados da loja, formulário de contato, segurança, desempenho e SEO básico.
 * Version:     1.0.0
 * Author:      Fermento Vivo
 *
 * Plugin obrigatório (mu-plugin): fica sempre ativo e não aparece para ser desativado no painel.
 * Cada módulo mora em fv-loja/ e pode ser removido individualmente abaixo.
 */

defined( 'ABSPATH' ) || exit;

define( 'FV_LOJA_DIR', __DIR__ . '/fv-loja/' );
define( 'FV_LOJA_VERSION', '1.0.0' );

$fv_modulos = array(
	'dados-loja.php',     // Configurações > Dados da loja (WhatsApp, e-mail, CNPJ, parcelas...).
	'checkout-brasil.php', // CPF/CNPJ, número, bairro e celular no checkout.
	'parcelas.php',        // "ou 6x de R$ X sem juros" nos preços.
	'frete.php',           // Esconde a entrega paga quando há frete grátis; "faltam R$ X".
	'contato.php',         // Shortcode [fv_contato] e caixa de mensagens no painel.
	'seguranca.php',       // Endurecimento básico e limite de tentativas de login.
	'desempenho.php',      // Remove recursos que a loja não usa.
	'seo.php',             // Meta description, Open Graph e dados estruturados da loja.
	'ambiente-local.php',  // Ajustes que só valem no ambiente de teste (WordPress Playground / SQLite).
);

foreach ( $fv_modulos as $fv_modulo ) {
	require_once FV_LOJA_DIR . $fv_modulo;
}
unset( $fv_modulos, $fv_modulo );
