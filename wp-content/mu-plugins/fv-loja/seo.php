<?php
/**
 * SEO básico. O WordPress já gera <title>, URL canônica e o sitemap (/wp-sitemap.xml),
 * e o WooCommerce já gera os dados estruturados de produto (preço, estoque).
 * Aqui entram: meta description, Open Graph (links bonitos no WhatsApp/Instagram),
 * dados da loja para o Google e o robots.txt.
 *
 * Se você instalar Rank Math ou Yoast depois, este módulo se desliga sozinho.
 */

defined( 'ABSPATH' ) || exit;

function fv_seo_plugin_ativo() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function fv_seo_resumo( $texto, $limite = 155 ) {
	$texto = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $texto ) ) ) );
	return mb_strlen( $texto ) > $limite ? rtrim( mb_substr( $texto, 0, $limite - 1 ) ) . '…' : $texto;
}

/**
 * Descrição, imagem e tipo da página atual.
 */
function fv_seo_dados() {
	$dados = array(
		'titulo'    => wp_get_document_title(),
		'descricao' => get_bloginfo( 'description' ),
		'imagem'    => '',
		'tipo'      => 'website',
		'url'       => home_url( add_query_arg( array() ) ),
	);

	if ( function_exists( 'is_product' ) && is_product() ) {
		$produto = wc_get_product( get_queried_object_id() );
		if ( $produto ) {
			$dados['descricao'] = $produto->get_short_description() ? $produto->get_short_description() : $produto->get_description();
			$dados['imagem']    = wp_get_attachment_image_url( $produto->get_image_id(), 'large' );
			$dados['tipo']      = 'product';
			$dados['preco']     = wc_get_price_to_display( $produto );
			$dados['url']       = get_permalink( $produto->get_id() );
		}
	} elseif ( is_tax( 'product_cat' ) || is_category() || is_tag() ) {
		$termo              = get_queried_object();
		$dados['descricao'] = $termo->description ? $termo->description : sprintf( '%s na %s: veja preços, medidas e prazos de entrega.', $termo->name, get_bloginfo( 'name' ) );
		$thumb              = get_term_meta( $termo->term_id, 'thumbnail_id', true );
		$dados['imagem']    = $thumb ? wp_get_attachment_image_url( $thumb, 'large' ) : '';
		$dados['url']       = get_term_link( $termo );
	} elseif ( is_singular() ) {
		$post               = get_queried_object();
		$dados['descricao'] = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
		$dados['imagem']    = get_the_post_thumbnail_url( $post, 'large' );
		$dados['url']       = get_permalink( $post );
		if ( is_front_page() ) {
			$dados['descricao'] = get_bloginfo( 'description' );
		}
	}

	$dados['descricao'] = fv_seo_resumo( $dados['descricao'] );
	if ( ! $dados['imagem'] ) {
		$logo            = get_theme_mod( 'custom_logo' );
		$dados['imagem'] = $logo ? wp_get_attachment_image_url( $logo, 'full' ) : get_site_icon_url( 512 );
	}
	return $dados;
}

add_action( 'wp_head', function () {
	if ( fv_seo_plugin_ativo() || is_404() ) {
		return;
	}
	$d = fv_seo_dados();

	if ( $d['descricao'] ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $d['descricao'] ) );
	}
	printf( "<meta property=\"og:locale\" content=\"pt_BR\">\n" );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( "<meta property=\"og:type\" content=\"%s\">\n", esc_attr( $d['tipo'] ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $d['titulo'] ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $d['url'] ) );
	if ( $d['descricao'] ) {
		printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $d['descricao'] ) );
	}
	if ( $d['imagem'] ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $d['imagem'] ) );
	}
	if ( isset( $d['preco'] ) ) {
		printf( "<meta property=\"product:price:amount\" content=\"%s\">\n", esc_attr( wc_format_decimal( $d['preco'], 2 ) ) );
		echo "<meta property=\"product:price:currency\" content=\"BRL\">\n";
	}
	echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
}, 2 );

/**
 * Dados da loja (nome, contato, endereço) na página inicial, no formato que o Google lê.
 */
add_action( 'wp_head', function () {
	if ( fv_seo_plugin_ativo() || ! is_front_page() || ! function_exists( 'WC' ) ) {
		return;
	}
	$loja = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'OnlineStore',
		'name'      => get_bloginfo( 'name' ),
		'legalName' => fv_dado( 'razao_social' ),
		'taxID'     => fv_dado( 'cnpj' ),
		'url'       => home_url( '/' ),
		'email'     => fv_dado( 'email' ),
		'telephone' => fv_dado( 'telefone' ),
		'address'   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => WC()->countries->get_base_address(),
			'addressLocality' => WC()->countries->get_base_city(),
			'addressRegion'   => WC()->countries->get_base_state(),
			'postalCode'      => WC()->countries->get_base_postcode(),
			'addressCountry'  => 'BR',
		),
	);
	if ( fv_dado( 'instagram' ) ) {
		$loja['sameAs'] = array( 'https://www.instagram.com/' . fv_dado( 'instagram' ) . '/' );
	}
	$logo = get_theme_mod( 'custom_logo' );
	if ( $logo ) {
		$loja['logo'] = wp_get_attachment_image_url( $logo, 'full' );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $loja, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}, 3 );

/**
 * robots.txt: aponta o sitemap e tira do Google as páginas que não fazem sentido na busca.
 */
add_filter( 'robots_txt', function ( $saida, $publico ) {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return $saida;
	}
	$caminho = function ( $pagina ) {
		$id = wc_get_page_id( $pagina );
		return $id > 0 ? wp_make_link_relative( get_permalink( $id ) ) : '';
	};
	// Acrescenta ao que o WordPress e o WooCommerce já escreveram.
	$linhas = array( 'Disallow: /*?orderby=', 'Disallow: /*?filter_' );
	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $pagina ) {
		if ( $caminho( $pagina ) ) {
			$linhas[] = 'Disallow: ' . $caminho( $pagina );
		}
	}
	$saida = rtrim( $saida ) . "\n" . implode( "\n", $linhas ) . "\n";
	// O sitemap só existe com a loja liberada para buscadores (Configurações > Leitura).
	return $publico ? $saida . "\nSitemap: " . home_url( '/wp-sitemap.xml' ) . "\n" : $saida;
}, 99, 2 );
