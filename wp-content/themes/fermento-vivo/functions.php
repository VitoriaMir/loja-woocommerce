<?php
/**
 * Tema Fermento Vivo (filho do Storefront).
 *
 * Tudo que é visual e de layout mora aqui. Regras de negócio (checkout, contato, SEO,
 * segurança) ficam no mu-plugin fv-loja, para continuarem funcionando se o tema mudar.
 */

defined( 'ABSPATH' ) || exit;

define( 'FV_TEMA_VERSION', '1.0.0' );

/* ------------------------------------------------------------------ */
/* Estilos e fontes                                                    */
/* ------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'fv-fontes',
		'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Figtree:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'fv-loja', get_stylesheet_directory_uri() . '/assets/css/loja.css', array( 'storefront-style' ), FV_TEMA_VERSION );
}, 30 );

add_filter( 'wp_resource_hints', function ( $urls, $tipo ) {
	if ( 'preconnect' === $tipo ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

// O Storefront gera CSS de cores pelo Personalizar; as cores da marca ficam em loja.css.
add_filter( 'storefront_customizer_css', '__return_empty_string' );
add_filter( 'storefront_customizer_woocommerce_css', '__return_empty_string' );

/* ------------------------------------------------------------------ */
/* Cabeçalho: faixa de frete grátis                                    */
/* ------------------------------------------------------------------ */

add_action( 'storefront_before_header', function () {
	$min = function_exists( 'fv_dado' ) ? (float) fv_dado( 'frete_gratis_min' ) : 0;
	echo '<div class="fv-faixa" role="note"><div class="col-full">';
	if ( $min > 0 ) {
		printf( 'Frete grátis para todo o Brasil em compras acima de <strong>%s</strong>', wp_kses_post( wc_price( $min, array( 'decimals' => 0 ) ) ) );
	} else {
		echo 'Enviamos para todo o Brasil';
	}
	echo ' · Pix com aprovação na hora</div></div>';
} );

/* ------------------------------------------------------------------ */
/* Página inicial (modelo "Página inicial" do Storefront)              */
/* ------------------------------------------------------------------ */

add_filter( 'storefront_product_categories_args', function ( $args ) {
	return array_merge( $args, array( 'title' => 'Compre por categoria', 'limit' => 4, 'columns' => 4 ) );
} );
add_filter( 'storefront_featured_products_args', function ( $args ) {
	return array_merge( $args, array( 'title' => 'Mais escolhidos para começar', 'limit' => 4, 'columns' => 4 ) );
} );
add_filter( 'storefront_recent_products_args', function ( $args ) {
	return array_merge( $args, array( 'title' => 'Novidades', 'limit' => 4, 'columns' => 4 ) );
} );
add_filter( 'storefront_on_sale_products_args', function ( $args ) {
	return array_merge( $args, array( 'title' => 'Em promoção', 'limit' => 4, 'columns' => 4 ) );
} );
add_filter( 'storefront_best_selling_products_args', function ( $args ) {
	return array_merge( $args, array( 'title' => 'Mais vendidos', 'limit' => 4, 'columns' => 4 ) );
} );

add_action( 'init', function () {
	// Seções da página inicial: categorias, destaques, promoção, novidades. "Mais avaliados" sai.
	remove_action( 'homepage', 'storefront_popular_products', 50 );
	remove_action( 'homepage', 'storefront_best_selling_products', 70 );
	remove_action( 'homepage', 'storefront_on_sale_products', 60 );
	add_action( 'homepage', 'storefront_on_sale_products', 35 );
	// Seção "Compre por marca" do Storefront: a loja não usa marcas.
	remove_action( 'homepage', 'storefront_woocommerce_brands_homepage_section', 80 );
} );

// Faixa de benefícios logo abaixo do destaque da página inicial.
add_action( 'homepage', function () {
	?>
	<section class="fv-beneficios" aria-label="Por que comprar aqui">
		<div><strong>Envio em 1 dia útil</strong><span>Pedidos pagos até 14h saem no mesmo dia</span></div>
		<div><strong>Até <?php echo esc_html( function_exists( 'fv_dado' ) ? fv_dado( 'parcelas_max' ) : '6' ); ?>x sem juros</strong><span>No cartão, pelo Mercado Pago</span></div>
		<div><strong>7 dias para devolver</strong><span>Direito de arrependimento garantido</span></div>
		<div><strong>Atendimento de padeiro</strong><span>Tire dúvidas antes de comprar</span></div>
	</section>
	<?php
}, 15 );

/* ------------------------------------------------------------------ */
/* Página do produto                                                   */
/* ------------------------------------------------------------------ */

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$msg = sprintf( 'Olá! Tenho uma dúvida sobre o produto %s: %s', $product->get_name(), get_permalink( $product->get_id() ) );
	$wa  = function_exists( 'fv_whatsapp_url' ) ? fv_whatsapp_url( $msg ) : '';
	?>
	<ul class="fv-garantias">
		<li>Pix, cartão em até <?php echo esc_html( fv_dado( 'parcelas_max' ) ); ?>x sem juros ou boleto</li>
		<li>Calcule o frete pelo CEP no carrinho</li>
		<li>Troca ou devolução em até 7 dias após o recebimento</li>
	</ul>
	<?php if ( $wa ) : ?>
		<p class="fv-duvida"><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Tirar dúvida pelo WhatsApp</a></p>
	<?php endif; ?>
	<?php
}, 35 );

// Título "Produtos relacionados" mais direto.
add_filter( 'woocommerce_product_related_products_heading', function () {
	return 'Combina com este produto';
} );

/* ------------------------------------------------------------------ */
/* Rodapé                                                              */
/* ------------------------------------------------------------------ */

add_action( 'init', function () {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
} );

add_action( 'storefront_footer', function () {
	if ( ! function_exists( 'fv_dado' ) ) {
		return;
	}
	?>
	<div class="fv-rodape">
		<div class="fv-rodape-col">
			<p class="fv-rodape-marca"><?php bloginfo( 'name' ); ?></p>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>
		<div class="fv-rodape-col">
			<p class="fv-rodape-titulo">Atendimento</p>
			<p><?php echo esc_html( fv_dado( 'horario' ) ); ?></p>
			<p><?php echo esc_html( fv_dado( 'email' ) ); ?><br><?php echo esc_html( fv_dado( 'telefone' ) ); ?></p>
		</div>
		<div class="fv-rodape-col">
			<p class="fv-rodape-titulo">Informações</p>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'fv_rodape',
				'container'      => false,
				'menu_class'     => 'fv-rodape-menu',
				'fallback_cb'    => false,
				'depth'          => 1,
			) );
			?>
		</div>
		<div class="fv-rodape-col">
			<p class="fv-rodape-titulo">Pagamento seguro</p>
			<p class="fv-selos"><span>Pix</span><span>Cartão</span><span>Boleto</span></p>
			<p class="fv-rodape-miudo">Pagamentos processados pelo Mercado Pago. Não guardamos dados de cartão.</p>
		</div>
	</div>
	<p class="fv-rodape-legal">
		<?php echo esc_html( fv_dado( 'razao_social' ) ); ?> · CNPJ <?php echo esc_html( fv_dado( 'cnpj' ) ); ?><br>
		<?php echo esc_html( fv_dado( 'endereco' ) ); ?>
	</p>
	<?php
}, 30 );

add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'fv_rodape' => 'Rodapé: informações' ) );
} );

/* ------------------------------------------------------------------ */
/* Botão flutuante de WhatsApp                                         */
/* ------------------------------------------------------------------ */

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'fv_whatsapp_url' ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
		return;
	}
	$url = fv_whatsapp_url( 'Olá! Vim pelo site da ' . get_bloginfo( 'name' ) . '.' );
	if ( ! $url ) {
		return;
	}
	?>
	<a class="fv-whatsapp" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
		<svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M16 3C9 3 3.3 8.6 3.3 15.6c0 2.2.6 4.4 1.7 6.3L3.2 28.8l7.1-1.8c1.8 1 3.8 1.5 5.8 1.5 7 0 12.7-5.7 12.7-12.7C28.8 8.7 23.1 3 16 3zm0 23.2c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-4.2 1.1 1.1-4.1-.3-.4c-1.1-1.7-1.6-3.6-1.6-5.5 0-5.8 4.7-10.5 10.6-10.5 5.8 0 10.5 4.7 10.5 10.5.1 5.9-4.6 10.6-10.3 10.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1c-.3-.2-1.3-.5-2.5-1.6-.9-.8-1.6-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.6s-.7-1.7-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4s-1.2 1.1-1.2 2.7 1.2 3.1 1.3 3.3c.2.2 2.3 3.5 5.6 4.9 2.8 1.1 3.3.9 3.9.8.6-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.3-.7-.4z"/></svg>
	</a>
	<?php
} );
