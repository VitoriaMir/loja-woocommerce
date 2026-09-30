<?php
/**
 * Gera ilustrações simples (PNG 800 × 800) para os produtos de exemplo, com a extensão GD do PHP.
 * Servem só para a loja não ficar com fotos vazias: troque pelas fotos reais dos produtos.
 */

defined( 'ABSPATH' ) || exit;

function fv_cor( $img, $hex ) {
	return imagecolorallocate( $img, ( $hex >> 16 ) & 255, ( $hex >> 8 ) & 255, $hex & 255 );
}

/**
 * Desenha em 1600 px e reduz para 800 px: as bordas ficam suavizadas.
 */
function fv_desenhar( $forma, $fundo_rgb ) {
	$s   = 2;
	$img = imagecreatetruecolor( 800 * $s, 800 * $s );
	imagefill( $img, 0, 0, imagecolorallocate( $img, $fundo_rgb[0], $fundo_rgb[1], $fundo_rgb[2] ) );

	$sombra = imagecolorallocatealpha( $img, 31, 36, 33, 105 );
	imagefilledellipse( $img, 400 * $s, 680 * $s, 560 * $s, 60 * $s, $sombra );

	switch ( $forma ) {
		case 'banneton':
			$cores = array( fv_cor( $img, 0xB07D43 ), fv_cor( $img, 0xD2A86C ) );
			for ( $i = 0; $i < 11; $i++ ) {
				$w = ( 560 - $i * 48 ) * $s;
				imagefilledellipse( $img, 400 * $s, 420 * $s, $w, (int) ( $w * 0.62 ), $cores[ $i % 2 ] );
			}
			break;

		case 'panela':
			$azul   = fv_cor( $img, 0x1F4FC4 );
			$escuro = fv_cor( $img, 0x173C98 );
			imagefilledrectangle( $img, 120 * $s, 420 * $s, 180 * $s, 460 * $s, $escuro );
			imagefilledrectangle( $img, 620 * $s, 420 * $s, 680 * $s, 460 * $s, $escuro );
			imagefilledrectangle( $img, 170 * $s, 380 * $s, 630 * $s, 600 * $s, $azul );
			imagefilledellipse( $img, 400 * $s, 600 * $s, 460 * $s, 110 * $s, $azul );
			imagefilledellipse( $img, 400 * $s, 380 * $s, 500 * $s, 110 * $s, $escuro );
			imagefilledellipse( $img, 400 * $s, 330 * $s, 110 * $s, 50 * $s, fv_cor( $img, 0x1F2421 ) );
			break;

		case 'lamina':
			imagefilledrectangle( $img, 150 * $s, 380 * $s, 560 * $s, 440 * $s, fv_cor( $img, 0x9C6B3C ) );
			imagefilledellipse( $img, 150 * $s, 410 * $s, 60 * $s, 60 * $s, fv_cor( $img, 0x9C6B3C ) );
			imagefilledrectangle( $img, 560 * $s, 370 * $s, 680 * $s, 450 * $s, fv_cor( $img, 0xB9BFC4 ) );
			imagefilledrectangle( $img, 600 * $s, 400 * $s, 650 * $s, 420 * $s, fv_cor( $img, 0x8A9096 ) );
			break;

		case 'raspador':
			imagefilledrectangle( $img, 220 * $s, 230 * $s, 580 * $s, 310 * $s, fv_cor( $img, 0x1F2421 ) );
			imagefilledrectangle( $img, 220 * $s, 310 * $s, 580 * $s, 580 * $s, fv_cor( $img, 0xC4C9CD ) );
			$tick = fv_cor( $img, 0x5E655F );
			for ( $i = 0; $i <= 15; $i++ ) {
				$x = ( 240 + $i * 21 ) * $s;
				imagefilledrectangle( $img, $x, 540 * $s, $x + 2 * $s, ( 0 === $i % 5 ? 500 : 520 ) * $s, $tick );
			}
			break;

		case 'termometro':
			imagefilledrectangle( $img, 392 * $s, 380 * $s, 408 * $s, 700 * $s, fv_cor( $img, 0xB9BFC4 ) );
			imagefilledellipse( $img, 400 * $s, 290 * $s, 260 * $s, 260 * $s, fv_cor( $img, 0x1F2421 ) );
			imagefilledrectangle( $img, 320 * $s, 250 * $s, 480 * $s, 320 * $s, fv_cor( $img, 0xC9E3C0 ) );
			break;

		case 'balanca':
			imagefilledrectangle( $img, 170 * $s, 400 * $s, 630 * $s, 610 * $s, fv_cor( $img, 0xFFFFFF ) );
			imagefilledrectangle( $img, 170 * $s, 400 * $s, 630 * $s, 430 * $s, fv_cor( $img, 0xDEDFD8 ) );
			imagefilledrectangle( $img, 300 * $s, 480 * $s, 500 * $s, 560 * $s, fv_cor( $img, 0x1F2421 ) );
			imagefilledrectangle( $img, 330 * $s, 505 * $s, 470 * $s, 535 * $s, fv_cor( $img, 0x6FD39A ) );
			break;

		case 'saco':
			$kraft = fv_cor( $img, 0xD8BE92 );
			imagefilledpolygon( $img, array( 230 * $s, 250 * $s, 570 * $s, 250 * $s, 610 * $s, 660 * $s, 190 * $s, 660 * $s ), $kraft );
			imagefilledrectangle( $img, 230 * $s, 220 * $s, 570 * $s, 260 * $s, fv_cor( $img, 0xC4A676 ) );
			imagefilledrectangle( $img, 205 * $s, 430 * $s, 595 * $s, 510 * $s, fv_cor( $img, 0x1F4FC4 ) );
			break;

		case 'pote':
			imagefilledrectangle( $img, 280 * $s, 260 * $s, 520 * $s, 320 * $s, fv_cor( $img, 0xC98A1B ) );
			imagefilledrectangle( $img, 260 * $s, 320 * $s, 540 * $s, 650 * $s, fv_cor( $img, 0xFFFFFF ) );
			imagefilledrectangle( $img, 270 * $s, 470 * $s, 530 * $s, 640 * $s, fv_cor( $img, 0xEBD7AE ) );
			$bolha = fv_cor( $img, 0xFFFFFF );
			foreach ( array( array( 320, 540, 26 ), array( 420, 590, 18 ), array( 470, 510, 22 ), array( 360, 610, 14 ) ) as $b ) {
				imagefilledellipse( $img, $b[0] * $s, $b[1] * $s, $b[2] * $s, $b[2] * $s, $bolha );
			}
			break;
	}

	$final = imagecreatetruecolor( 800, 800 );
	imagecopyresampled( $final, $img, 0, 0, 0, 0, 800, 800, 800 * $s, 800 * $s );
	imagedestroy( $img );

	// Marca "ILUSTRAÇÃO" no canto (fonte embutida do GD, sem acentos).
	$rotulo = imagecreatetruecolor( 90, 16 );
	imagefill( $rotulo, 0, 0, imagecolorallocate( $rotulo, $fundo_rgb[0], $fundo_rgb[1], $fundo_rgb[2] ) );
	imagestring( $rotulo, 2, 2, 1, 'ILUSTRACAO', imagecolorallocate( $rotulo, 94, 101, 95 ) );
	imagecopyresized( $final, $rotulo, 24, 752, 0, 0, 180, 32, 90, 16 );
	imagedestroy( $rotulo );

	return $final;
}

/**
 * Cria a imagem, envia para a biblioteca de mídia e devolve o ID do anexo.
 */
function fv_gerar_imagem_produto( $p ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}
	$categorias = fv_conteudo_categorias();
	$fundo      = $categorias[ $p['categoria'] ]['cor'];

	$pasta = wp_upload_dir();
	if ( ! empty( $pasta['error'] ) ) {
		return 0;
	}
	wp_mkdir_p( $pasta['path'] );
	$arquivo = trailingslashit( $pasta['path'] ) . sanitize_file_name( strtolower( $p['sku'] ) . '.png' );

	$img = fv_desenhar( $p['forma'], $fundo );
	imagepng( $img, $arquivo, 6 );
	imagedestroy( $img );

	$id = wp_insert_attachment( array(
		'post_mime_type' => 'image/png',
		'post_title'     => $p['nome'],
		'post_status'    => 'inherit',
	), $arquivo );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $arquivo ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $p['nome'] );
	return $id;
}
