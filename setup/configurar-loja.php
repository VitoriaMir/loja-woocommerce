<?php
/**
 * Configura a loja Fermento Vivo do zero (ou atualiza, se já existir).
 *
 * Uso:   wp eval-file setup/configurar-loja.php local
 *        wp eval-file setup/configurar-loja.php producao
 *
 * "local"    → bloqueia o Google e ativa o Pix manual para testar o fluxo de compra.
 * "producao" → libera o Google e deixa o Pix manual desligado (use o Mercado Pago).
 *
 * Pode rodar quantas vezes quiser: produtos são encontrados pelo SKU e páginas pelo endereço.
 */

defined( 'ABSPATH' ) || exit( "Rode com: wp eval-file setup/configurar-loja.php local\n" );

$ambiente = isset( $args[0] ) && 'producao' === $args[0] ? 'producao' : 'local';
$dir      = __DIR__;

if ( ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "O WooCommerce precisa estar instalado e ativo.\n" );
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once $dir . '/conteudo/paginas.php';
require_once $dir . '/conteudo/produtos.php';

function fv_log( $msg ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $msg );
	} else {
		echo $msg . "\n";
	}
}

/* ================================================================== */
/* 1. WordPress                                                        */
/* ================================================================== */

fv_log( '1/9 Opções gerais do WordPress' );

$opcoes_wp = array(
	'blogname'             => 'Fermento Vivo',
	'blogdescription'      => 'Utensílios, farinhas e fermento para pão de fermentação natural',
	'timezone_string'      => 'America/Sao_Paulo',
	'date_format'          => 'd/m/Y',
	'time_format'          => 'H:i',
	'start_of_week'        => 0,
	'permalink_structure'  => '/%postname%/',
	'blog_public'          => 'producao' === $ambiente ? 1 : 0,
	'default_comment_status' => 'closed',
	'default_ping_status'  => 'closed',
	'users_can_register'   => 0,
	'uploads_use_yearmonth_folders' => 1,
);
foreach ( $opcoes_wp as $k => $v ) {
	update_option( $k, $v );
}

// Conteúdo de exemplo do WordPress.
foreach ( array( 'hello-world', 'ola-mundo' ) as $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}
foreach ( array( 'sample-page', 'pagina-exemplo' ) as $slug ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

/* ================================================================== */
/* 2. WooCommerce                                                      */
/* ================================================================== */

fv_log( '2/9 Configurações do WooCommerce (Brasil, R$, kg, cm)' );

$opcoes_wc = array(
	// Endereço da loja (origem do frete).
	'woocommerce_store_address'           => 'Rua Exemplo, 100',
	'woocommerce_store_address_2'         => 'Pinheiros',
	'woocommerce_store_city'              => 'São Paulo',
	'woocommerce_default_country'         => 'BR:SP',
	'woocommerce_store_postcode'          => '05422-000',
	// Venda e entrega só no Brasil.
	'woocommerce_allowed_countries'       => 'specific',
	'woocommerce_specific_allowed_countries' => array( 'BR' ),
	'woocommerce_ship_to_countries'       => '',
	'woocommerce_default_customer_address' => 'base',
	// Moeda: R$ 1.234,56.
	'woocommerce_currency'                => 'BRL',
	'woocommerce_currency_pos'            => 'left_space',
	'woocommerce_price_thousand_sep'      => '.',
	'woocommerce_price_decimal_sep'       => ',',
	'woocommerce_price_num_decimals'      => 2,
	// Medidas usadas no cálculo de frete.
	'woocommerce_weight_unit'             => 'kg',
	'woocommerce_dimension_unit'          => 'cm',
	// Impostos já inclusos no preço (padrão no varejo brasileiro).
	'woocommerce_calc_taxes'              => 'no',
	// Estoque.
	'woocommerce_manage_stock'            => 'yes',
	'woocommerce_hold_stock_minutes'      => 60,
	'woocommerce_notify_low_stock_amount' => 2,
	'woocommerce_notify_no_stock_amount'  => 0,
	'woocommerce_stock_format'            => 'low_amount',
	// Conta e checkout.
	'woocommerce_enable_guest_checkout'   => 'yes',
	'woocommerce_enable_checkout_login_reminder' => 'yes',
	'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
	'woocommerce_enable_myaccount_registration' => 'yes',
	'woocommerce_registration_generate_password' => 'yes',
	'woocommerce_registration_generate_username' => 'yes',
	'woocommerce_checkout_company_field'  => 'optional',
	'woocommerce_checkout_address_2_field' => 'optional',
	'woocommerce_checkout_phone_field'    => 'required',
	'woocommerce_checkout_highlight_required_fields' => 'yes',
	'woocommerce_checkout_privacy_policy_text' => 'Usamos seus dados para processar o pedido, entregar a compra e dar suporte, conforme a nossa [privacy_policy].',
	'woocommerce_registration_privacy_policy_text' => 'Usamos seus dados para criar sua conta e acompanhar pedidos, conforme a nossa [privacy_policy].',
	// Frete.
	'woocommerce_enable_shipping_calc'    => 'yes',
	'woocommerce_shipping_cost_requires_address' => 'no',
	'woocommerce_ship_to_destination'     => 'billing',
	// Avaliações só de quem comprou.
	'woocommerce_enable_reviews'          => 'yes',
	'woocommerce_review_rating_verification_label' => 'yes',
	'woocommerce_review_rating_verification_required' => 'yes',
	'woocommerce_enable_review_rating'    => 'yes',
	'woocommerce_review_rating_required'  => 'yes',
	// E-mails.
	'woocommerce_email_from_name'         => 'Fermento Vivo',
	'woocommerce_email_base_color'        => '#1F4FC4',
	'woocommerce_email_footer_text'       => 'Fermento Vivo · Dúvidas? Responda este e-mail ou fale no WhatsApp.',
	// Loja aberta, sem assistentes e sem rastreamento de uso.
	'woocommerce_coming_soon'             => 'no',
	'woocommerce_store_pages_only'        => 'no',
	'woocommerce_task_list_hidden'        => 'yes',
	'woocommerce_task_list_complete'      => 'yes',
	'woocommerce_extended_task_list_hidden' => 'yes',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_allow_tracking'          => 'no',
	'woocommerce_onboarding_profile'      => array( 'skipped' => true, 'completed' => true ),
	// HPOS + sincronização: o Melhor Envio ainda lê alguns dados pelo formato antigo de pedidos.
	'woocommerce_custom_orders_table_data_sync_enabled' => 'yes',
	// Endereços em português.
	'woocommerce_checkout_pay_endpoint'            => 'pagar-pedido',
	'woocommerce_checkout_order_received_endpoint' => 'pedido-recebido',
	'woocommerce_myaccount_add_payment_method_endpoint' => 'adicionar-forma-de-pagamento',
	'woocommerce_myaccount_delete_payment_method_endpoint' => 'excluir-forma-de-pagamento',
	'woocommerce_myaccount_set_default_payment_method_endpoint' => 'forma-de-pagamento-padrao',
	'woocommerce_myaccount_orders_endpoint'        => 'pedidos',
	'woocommerce_myaccount_view_order_endpoint'    => 'ver-pedido',
	'woocommerce_myaccount_downloads_endpoint'     => 'downloads',
	'woocommerce_myaccount_edit_account_endpoint'  => 'editar-conta',
	'woocommerce_myaccount_edit_address_endpoint'  => 'enderecos',
	'woocommerce_myaccount_payment_methods_endpoint' => 'formas-de-pagamento',
	'woocommerce_myaccount_lost_password_endpoint' => 'recuperar-senha',
	'woocommerce_logout_endpoint'                  => 'sair',
	'woocommerce_permalinks'                       => array(
		'product_base'           => 'produto',
		'category_base'          => 'categoria',
		'tag_base'               => 'tag-produto',
		'attribute_base'         => '',
		'use_verbose_page_rules' => false,
	),
);
foreach ( $opcoes_wc as $k => $v ) {
	update_option( $k, $v );
}

/* ================================================================== */
/* 3. Páginas                                                          */
/* ================================================================== */

fv_log( '3/9 Páginas da loja e páginas institucionais' );

WC_Install::create_pages();

$paginas_wc = array(
	'shop'      => array( 'Loja', 'loja', '' ),
	// Carrinho e checkout clássicos: são os que Mercado Pago, Melhor Envio e os campos brasileiros suportam por completo.
	'cart'      => array( 'Carrinho', 'carrinho', "<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->" ),
	'checkout'  => array( 'Finalizar compra', 'finalizar-compra', "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->" ),
	'myaccount' => array( 'Minha conta', 'minha-conta', "<!-- wp:shortcode -->\n[woocommerce_my_account]\n<!-- /wp:shortcode -->" ),
);
foreach ( $paginas_wc as $chave => $pg ) {
	$id = wc_get_page_id( $chave );
	if ( $id > 0 ) {
		$dados = array( 'ID' => $id, 'post_title' => $pg[0], 'post_name' => $pg[1], 'post_status' => 'publish' );
		if ( $pg[2] ) {
			$dados['post_content'] = $pg[2];
		}
		wp_update_post( $dados );
	}
}

/**
 * Cria ou atualiza uma página pelo endereço (slug).
 */
function fv_pagina( $slug, $titulo, $conteudo, $extra = array() ) {
	$existente = get_page_by_path( $slug );
	$dados     = array_merge( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_title'     => $titulo,
		'post_name'      => $slug,
		'post_content'   => $conteudo,
		'comment_status' => 'closed',
	), $extra );
	if ( $existente ) {
		$dados['ID'] = $existente->ID;
		return wp_update_post( $dados );
	}
	return wp_insert_post( $dados );
}

$ids = array();
foreach ( fv_conteudo_paginas() as $slug => $pg ) {
	$ids[ $slug ] = fv_pagina( $slug, $pg['titulo'], $pg['conteudo'], isset( $pg['extra'] ) ? $pg['extra'] : array() );
	if ( ! empty( $pg['modelo'] ) ) {
		update_post_meta( $ids[ $slug ], '_wp_page_template', $pg['modelo'] );
	}
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['inicio'] );
update_option( 'page_for_posts', 0 );
update_option( 'wp_page_for_privacy_policy', $ids['politica-de-privacidade'] );
update_option( 'woocommerce_terms_page_id', $ids['termos-de-uso'] );

// Rascunho de privacidade que o WordPress cria em inglês.
foreach ( array( 'privacy-policy' ) as $slug ) {
	$p = get_page_by_path( $slug );
	if ( $p && (int) $p->ID !== (int) $ids['politica-de-privacidade'] ) {
		wp_delete_post( $p->ID, true );
	}
}

/* ================================================================== */
/* 4. Categorias, atributos e classes de entrega                       */
/* ================================================================== */

fv_log( '4/9 Categorias, atributo "Tamanho" e classe de entrega "Pesados"' );

function fv_termo( $nome, $taxonomia, $slug, $descricao = '', $pai = 0 ) {
	$t = get_term_by( 'slug', $slug, $taxonomia );
	if ( $t ) {
		wp_update_term( $t->term_id, $taxonomia, array( 'name' => $nome, 'description' => $descricao, 'parent' => $pai ) );
		return (int) $t->term_id;
	}
	$novo = wp_insert_term( $nome, $taxonomia, array( 'slug' => $slug, 'description' => $descricao, 'parent' => $pai ) );
	return is_wp_error( $novo ) ? 0 : (int) $novo['term_id'];
}

$categorias = array();
$ordem      = 0;
foreach ( fv_conteudo_categorias() as $slug => $cat ) {
	$categorias[ $slug ] = fv_termo( $cat['nome'], 'product_cat', $slug, $cat['descricao'] );
	update_term_meta( $categorias[ $slug ], 'order', $ordem++ );
}

// "Sem categoria" continua existindo (o WooCommerce exige), mas fica vazia e escondida.
$sem_cat = (int) get_option( 'default_product_cat' );
if ( $sem_cat ) {
	wp_update_term( $sem_cat, 'product_cat', array( 'name' => 'Sem categoria', 'slug' => 'sem-categoria' ) );
}

// Atributo global "Tamanho" para produtos com variação.
if ( ! wc_attribute_taxonomy_id_by_name( 'tamanho' ) ) {
	wc_create_attribute( array( 'name' => 'Tamanho', 'slug' => 'tamanho', 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
}
if ( ! taxonomy_exists( 'pa_tamanho' ) ) {
	register_taxonomy( 'pa_tamanho', array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
}

$classe_pesados = fv_termo( 'Pesados (acima de 4 kg)', 'product_shipping_class', 'pesados', 'Panelas de ferro e sacos de farinha de 5 kg.' );

/* ================================================================== */
/* 5. Produtos                                                         */
/* ================================================================== */

fv_log( '5/9 Produtos de exemplo (com fotos ilustrativas geradas aqui)' );

require_once $dir . '/imagens.php';

foreach ( fv_conteudo_produtos() as $p ) {
	$id_existente = wc_get_product_id_by_sku( $p['sku'] );
	$variavel     = ! empty( $p['variacoes'] );

	if ( $id_existente ) {
		$produto = wc_get_product( $id_existente );
	} else {
		$produto = $variavel ? new WC_Product_Variable() : new WC_Product_Simple();
	}

	$produto->set_name( $p['nome'] );
	$produto->set_slug( sanitize_title( $p['nome'] ) );
	$produto->set_sku( $p['sku'] );
	$produto->set_status( 'publish' );
	$produto->set_short_description( $p['resumo'] );
	$produto->set_description( $p['descricao'] );
	$produto->set_category_ids( array( $categorias[ $p['categoria'] ] ) );
	$produto->set_featured( ! empty( $p['destaque'] ) );
	$produto->set_weight( $p['peso'] );
	$produto->set_length( $p['medidas'][0] );
	$produto->set_width( $p['medidas'][1] );
	$produto->set_height( $p['medidas'][2] );
	$produto->set_shipping_class_id( ! empty( $p['pesado'] ) ? $classe_pesados : 0 );
	$produto->set_reviews_allowed( true );

	$atributos = array();
	foreach ( $p['ficha'] as $nome => $valor ) {
		$a = new WC_Product_Attribute();
		$a->set_id( 0 );
		$a->set_name( $nome );
		$a->set_options( array( $valor ) );
		$a->set_visible( true );
		$a->set_variation( false );
		$atributos[] = $a;
	}

	if ( $variavel ) {
		$termos = array();
		foreach ( $p['variacoes'] as $v ) {
			$termos[] = fv_termo( $v['tamanho'], 'pa_tamanho', sanitize_title( $v['tamanho'] ) );
		}
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( 'tamanho' ) );
		$a->set_name( 'pa_tamanho' );
		$a->set_options( $termos );
		$a->set_visible( true );
		$a->set_variation( true );
		array_unshift( $atributos, $a );
		$produto->set_manage_stock( false );
	} else {
		$produto->set_regular_price( $p['preco'] );
		$produto->set_sale_price( isset( $p['promocao'] ) ? $p['promocao'] : '' );
		$produto->set_manage_stock( true );
		$produto->set_stock_quantity( $p['estoque'] );
		$produto->set_backorders( 'no' );
	}
	$produto->set_attributes( $atributos );

	if ( ! $produto->get_image_id() ) {
		$imagem = fv_gerar_imagem_produto( $p );
		if ( $imagem ) {
			$produto->set_image_id( $imagem );
		}
	}

	$pid = $produto->save();

	if ( $variavel ) {
		foreach ( $p['variacoes'] as $v ) {
			$sku_v   = $p['sku'] . '-' . $v['sufixo'];
			$id_var  = wc_get_product_id_by_sku( $sku_v );
			$variacao = $id_var ? wc_get_product( $id_var ) : new WC_Product_Variation();
			$variacao->set_parent_id( $pid );
			$variacao->set_sku( $sku_v );
			$variacao->set_attributes( array( 'pa_tamanho' => sanitize_title( $v['tamanho'] ) ) );
			$variacao->set_regular_price( $v['preco'] );
			$variacao->set_sale_price( isset( $v['promocao'] ) ? $v['promocao'] : '' );
			$variacao->set_manage_stock( true );
			$variacao->set_stock_quantity( $v['estoque'] );
			$variacao->set_weight( $v['peso'] );
			$variacao->set_length( $v['medidas'][0] );
			$variacao->set_width( $v['medidas'][1] );
			$variacao->set_height( $v['medidas'][2] );
			$variacao->set_status( 'publish' );
			$variacao->save();
		}
		WC_Product_Variable::sync( $pid );
		$primeiro = sanitize_title( $p['variacoes'][0]['tamanho'] );
		update_post_meta( $pid, '_default_attributes', array( 'pa_tamanho' => $primeiro ) );
	}

	fv_log( sprintf( '   · %s (%s)', $p['nome'], $p['sku'] ) );
}

// Uma imagem por categoria, usando a do primeiro produto dela.
foreach ( $categorias as $slug => $term_id ) {
	if ( get_term_meta( $term_id, 'thumbnail_id', true ) ) {
		continue;
	}
	$ids_prod = wc_get_products( array( 'category' => array( $slug ), 'limit' => 1, 'return' => 'ids' ) );
	if ( $ids_prod ) {
		$img = get_post_thumbnail_id( $ids_prod[0] );
		if ( $img ) {
			update_term_meta( $term_id, 'thumbnail_id', $img );
		}
	}
}

/* ================================================================== */
/* 6. Frete                                                            */
/* ================================================================== */

fv_log( '6/9 Frete: zona Brasil (entrega padrão, frete grátis e retirada)' );

foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Brasil' === $z['zone_name'] ) {
		( new WC_Shipping_Zone( $z['id'] ) )->delete();
	}
}

$zona = new WC_Shipping_Zone();
$zona->set_zone_name( 'Brasil' );
$zona->set_zone_order( 0 );
$zona->add_location( 'BR', 'country' );
$zona->save();

$metodos = array(
	// Reserva enquanto o Melhor Envio não está conectado. Depois de conectar, pode desativar.
	'flat_rate'     => array(
		'title'                            => 'Entrega padrão (5 a 10 dias úteis)',
		'tax_status'                       => 'none',
		'cost'                             => '24,90',
		'type'                             => 'class',
		'class_cost_' . $classe_pesados    => '25',
		'no_class_cost'                    => '',
	),
	'free_shipping' => array(
		'title'            => 'Frete grátis (5 a 10 dias úteis)',
		'requires'         => 'min_amount',
		'min_amount'       => '299',
		'ignore_discounts' => 'no',
	),
	'local_pickup'  => array(
		'title'      => 'Retirar na loja · Pinheiros, São Paulo (grátis)',
		'tax_status' => 'none',
		'cost'       => '0',
	),
);
foreach ( $metodos as $tipo => $config ) {
	$instancia = $zona->add_shipping_method( $tipo );
	update_option( "woocommerce_{$tipo}_{$instancia}_settings", $config );
}

// O valor de frete grátis também aparece na faixa do topo.
$dados_loja                     = get_option( 'fv_dados_loja', array() );
$dados_loja['frete_gratis_min'] = '299';
update_option( 'fv_dados_loja', $dados_loja );

/* ================================================================== */
/* 7. Pagamento                                                        */
/* ================================================================== */

fv_log( '7/9 Pagamento: Mercado Pago (credenciais no painel) e Pix manual para testes' );

update_option( 'woocommerce_bacs_settings', array(
	'enabled'      => 'local' === $ambiente ? 'yes' : 'no',
	'title'        => 'Pix manual (chave Pix)',
	'description'  => 'Depois de finalizar, mostramos a chave Pix. O pedido é enviado quando o pagamento for confirmado.',
	'instructions' => 'Pague pelo Pix usando a chave CNPJ 00.000.000/0001-00 (Fermento Vivo Utensílios Ltda.) e envie o comprovante pelo WhatsApp. Seu pedido é separado assim que o pagamento for confirmado.',
) );
update_option( 'woocommerce_bacs_accounts', array() );
foreach ( array( 'cod', 'cheque' ) as $gw ) {
	$cfg            = get_option( "woocommerce_{$gw}_settings", array() );
	$cfg['enabled'] = 'no';
	update_option( "woocommerce_{$gw}_settings", $cfg );
}

/* ================================================================== */
/* 8. Menus e barra lateral                                            */
/* ================================================================== */

fv_log( '8/9 Menus (principal, celular e rodapé) e layout sem barra lateral' );

function fv_menu( $nome, $itens ) {
	$menu = wp_get_nav_menu_object( $nome );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		$menu_id = $menu->term_id;
	} else {
		$menu_id = wp_create_nav_menu( $nome );
	}
	foreach ( $itens as $item ) {
		wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-status' => 'publish' ), $item ) );
	}
	return $menu_id;
}

$item_pagina = function ( $id, $titulo = '' ) {
	return array( 'menu-item-object' => 'page', 'menu-item-object-id' => $id, 'menu-item-type' => 'post_type', 'menu-item-title' => $titulo );
};
$item_cat = function ( $slug ) use ( $categorias ) {
	$cats = fv_conteudo_categorias();
	return array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $categorias[ $slug ], 'menu-item-type' => 'taxonomy', 'menu-item-title' => $cats[ $slug ]['menu'] );
};

$principal = fv_menu( 'Principal', array_merge(
	array( $item_pagina( wc_get_page_id( 'shop' ), 'Loja' ) ),
	array_map( $item_cat, array_keys( $categorias ) ),
	array( $item_pagina( $ids['contato'] ) )
) );
$rodape = fv_menu( 'Rodapé', array(
	$item_pagina( $ids['sobre'] ),
	$item_pagina( $ids['entregas-e-frete'] ),
	$item_pagina( $ids['trocas-e-devolucoes'] ),
	$item_pagina( $ids['perguntas-frequentes'] ),
	$item_pagina( $ids['politica-de-privacidade'] ),
	$item_pagina( $ids['termos-de-uso'] ),
	$item_pagina( $ids['contato'] ),
) );
set_theme_mod( 'nav_menu_locations', array(
	'primary'   => $principal,
	'handheld'  => $principal,
	'secondary' => 0,
	'fv_rodape' => $rodape,
) );

// Sem widgets na lateral: o Storefront usa a largura toda. O rodapé é do tema filho.
$widgets = get_option( 'sidebars_widgets', array() );
foreach ( array( 'sidebar-1', 'header-1', 'footer-1', 'footer-2', 'footer-3', 'footer-4' ) as $area ) {
	if ( isset( $widgets[ $area ] ) && $widgets[ $area ] ) {
		$widgets['wp_inactive_widgets'] = array_merge( isset( $widgets['wp_inactive_widgets'] ) ? $widgets['wp_inactive_widgets'] : array(), $widgets[ $area ] );
	}
	$widgets[ $area ] = array();
}
update_option( 'sidebars_widgets', $widgets );

/* ================================================================== */
/* 9. Final                                                            */
/* ================================================================== */

fv_log( '9/9 Atualizando endereços (permalinks) e caches' );

flush_rewrite_rules( false );
// Os endereços em português só valem depois de recarregar o WooCommerce: pede um novo flush na próxima visita.
update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
wc_delete_product_transients();
if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
	wc_update_product_lookup_tables();
}
delete_transient( 'wc_term_counts' );
foreach ( $categorias as $term_id ) {
	wp_update_term_count_now( array( $term_id ), 'product_cat' );
}

fv_log( sprintf( 'Pronto (%s). Loja: %s', $ambiente, home_url( '/' ) ) );
