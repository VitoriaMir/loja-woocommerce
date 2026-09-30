<?php
/**
 * Desempenho: remove o que a loja não usa. O ganho maior vem do servidor
 * (cache de página no Nginx, OPcache e Redis): veja deploy/.
 */

defined( 'ABSPATH' ) || exit;

// Emojis: o navegador já desenha emoji; o script extra só pesa.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
add_filter( 'emoji_svg_url', '__return_false' );

// Links de descoberta que a loja não precisa.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'feed_links_extra', 3 );

// Heartbeat: a cada 60 s no painel, desligado no site.
add_filter( 'heartbeat_settings', function ( $settings ) {
	$settings['interval'] = 60;
	return $settings;
} );
add_action( 'wp_enqueue_scripts', function () {
	wp_deregister_script( 'heartbeat' );
}, 100 );

// Limita revisões guardadas por post/produto.
add_filter( 'wp_revisions_to_keep', function () {
	return 5;
} );
