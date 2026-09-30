<?php
/**
 * Ajustes só para o ambiente local (WordPress Playground, banco SQLite).
 * No servidor (MySQL/MariaDB) este arquivo não faz nada.
 */

defined( 'ABSPATH' ) || exit;

$fv_sqlite = ( defined( 'DB_ENGINE' ) && 'sqlite' === DB_ENGINE ) || ( defined( 'DATABASE_TYPE' ) && 'sqlite' === DATABASE_TYPE );

if ( $fv_sqlite ) {
	// A reserva de estoque do checkout usa uma consulta própria do MySQL que o SQLite não executa,
	// e todo pedido falha com "Não há unidades suficientes". No servidor a reserva continua ligada.
	add_filter( 'woocommerce_hold_stock_for_checkout', '__return_false' );

	// Pedidos de teste não baixam o estoque: senão, depois de algumas rodadas de testes,
	// os produtos esgotam (a panela tem 6 unidades) e as compras param de funcionar.
	add_filter( 'woocommerce_can_reduce_order_stock', '__return_false' );

	// Os testes automáticos enviam o formulário de contato várias vezes seguidas do mesmo IP.
	add_filter( 'fv_contato_limite_por_hora', function () {
		return 500;
	} );
}
unset( $fv_sqlite );
