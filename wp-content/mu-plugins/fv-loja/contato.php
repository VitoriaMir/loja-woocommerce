<?php
/**
 * Formulário de contato: shortcode [fv_contato].
 *
 * Cada mensagem é guardada no painel (menu "Mensagens") e enviada por e-mail para o
 * endereço de atendimento. Se o e-mail falhar, a mensagem continua salva.
 * Proteções: nonce, campo-isca contra robôs, tempo mínimo de preenchimento e limite por IP.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'fv_mensagem', array(
		'labels'          => array(
			'name'          => 'Mensagens',
			'singular_name' => 'Mensagem',
			'menu_name'     => 'Mensagens',
			'all_items'     => 'Todas as mensagens',
			'edit_item'     => 'Mensagem',
			'not_found'     => 'Nenhuma mensagem recebida ainda.',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-email-alt',
		'menu_position'   => 58,
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

function fv_contato_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
}

/**
 * Processa o envio antes de qualquer saída, para poder redirecionar (evita reenvio ao atualizar a página).
 */
add_action( 'template_redirect', function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['fv_contato_enviar'] ) ) {
		return;
	}
	// O formulário envia para a própria página; volta para ela (wp_get_referer() ignora a URL atual).
	$voltar = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );

	if ( ! isset( $_POST['fv_contato_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fv_contato_nonce'] ) ), 'fv_contato' ) ) {
		wp_safe_redirect( add_query_arg( 'contato', 'expirado', $voltar ) . '#fv-contato' );
		exit;
	}

	// Robôs costumam preencher todos os campos (isca) ou enviar em menos de 3 segundos.
	$inicio = isset( $_POST['fv_t'] ) ? absint( $_POST['fv_t'] ) : 0;
	if ( ! empty( $_POST['fv_site'] ) || ( time() - $inicio ) < 3 ) {
		wp_safe_redirect( add_query_arg( 'contato', 'ok', $voltar ) . '#fv-contato' );
		exit;
	}

	$chave_ip = 'fv_contato_' . md5( fv_contato_ip() );
	$envios   = (int) get_transient( $chave_ip );
	if ( $envios >= (int) apply_filters( 'fv_contato_limite_por_hora', 5 ) ) {
		wp_safe_redirect( add_query_arg( 'contato', 'limite', $voltar ) . '#fv-contato' );
		exit;
	}

	$nome     = sanitize_text_field( wp_unslash( $_POST['fv_nome'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['fv_email'] ?? '' ) );
	$telefone = sanitize_text_field( wp_unslash( $_POST['fv_telefone'] ?? '' ) );
	$assunto  = sanitize_text_field( wp_unslash( $_POST['fv_assunto'] ?? '' ) );
	$pedido   = sanitize_text_field( wp_unslash( $_POST['fv_pedido'] ?? '' ) );
	$mensagem = sanitize_textarea_field( wp_unslash( $_POST['fv_mensagem'] ?? '' ) );
	$aceite   = ! empty( $_POST['fv_aceite'] );

	if ( ! $nome || ! is_email( $email ) || strlen( $mensagem ) < 10 || ! $aceite ) {
		set_transient( 'fv_contato_rascunho_' . md5( fv_contato_ip() ), compact( 'nome', 'email', 'telefone', 'assunto', 'pedido', 'mensagem' ), 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'contato', 'incompleto', $voltar ) . '#fv-contato' );
		exit;
	}

	set_transient( $chave_ip, $envios + 1, HOUR_IN_SECONDS );

	$corpo = "Nome: {$nome}\nE-mail: {$email}\nTelefone: {$telefone}\nAssunto: {$assunto}\nPedido: {$pedido}\n\n{$mensagem}";

	$id = wp_insert_post( array(
		'post_type'    => 'fv_mensagem',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s · %s', $assunto ? $assunto : 'Contato', $nome ),
		'post_content' => $corpo,
	) );

	wp_mail(
		fv_dado( 'email' ),
		sprintf( '[%s] %s · %s', get_bloginfo( 'name' ), $assunto ? $assunto : 'Contato pelo site', $nome ),
		$corpo . "\n\n---\nVer no painel: " . admin_url( 'post.php?post=' . (int) $id . '&action=edit' ),
		array( 'Reply-To: ' . $nome . ' <' . $email . '>' )
	);

	wp_safe_redirect( add_query_arg( 'contato', 'ok', $voltar ) . '#fv-contato' );
	exit;
} );

add_shortcode( 'fv_contato', function () {
	$status   = isset( $_GET['contato'] ) ? sanitize_key( $_GET['contato'] ) : '';
	$rascunho = 'incompleto' === $status ? get_transient( 'fv_contato_rascunho_' . md5( fv_contato_ip() ) ) : array();
	$r        = wp_parse_args( is_array( $rascunho ) ? $rascunho : array(), array_fill_keys( array( 'nome', 'email', 'telefone', 'assunto', 'pedido', 'mensagem' ), '' ) );

	$avisos = array(
		'ok'         => array( 'woocommerce-message', 'Mensagem enviada. Respondemos em até 1 dia útil pelo e-mail que você informou.' ),
		'incompleto' => array( 'woocommerce-error', 'Preencha nome, um e-mail válido, a mensagem (mínimo de 10 caracteres) e aceite o uso dos dados.' ),
		'expirado'   => array( 'woocommerce-error', 'O formulário ficou aberto por muito tempo. Envie de novo, por favor.' ),
		'limite'     => array( 'woocommerce-error', 'Recebemos várias mensagens deste endereço na última hora. Tente mais tarde ou chame no WhatsApp.' ),
	);

	$assuntos = array( 'Dúvida sobre produto', 'Acompanhar pedido', 'Troca ou devolução', 'Compra para empresa', 'Outro assunto' );

	ob_start();
	?>
	<div class="fv-contato" id="fv-contato">
		<?php if ( isset( $avisos[ $status ] ) ) : ?>
			<div class="<?php echo esc_attr( $avisos[ $status ][0] ); ?>" role="status"><?php echo esc_html( $avisos[ $status ][1] ); ?></div>
		<?php endif; ?>
		<form method="post" class="fv-contato-form" novalidate>
			<?php wp_nonce_field( 'fv_contato', 'fv_contato_nonce' ); ?>
			<input type="hidden" name="fv_t" value="<?php echo esc_attr( time() ); ?>">
			<p class="fv-isca" aria-hidden="true"><label for="fv_site">Deixe em branco</label><input type="text" id="fv_site" name="fv_site" tabindex="-1" autocomplete="off"></p>

			<p class="form-row form-row-first"><label for="fv_nome">Nome <abbr class="required" title="obrigatório">*</abbr></label>
				<input type="text" class="input-text" id="fv_nome" name="fv_nome" autocomplete="name" required value="<?php echo esc_attr( $r['nome'] ); ?>"></p>
			<p class="form-row form-row-last"><label for="fv_email">E-mail <abbr class="required" title="obrigatório">*</abbr></label>
				<input type="email" class="input-text" id="fv_email" name="fv_email" autocomplete="email" required value="<?php echo esc_attr( $r['email'] ); ?>"></p>
			<p class="form-row form-row-first"><label for="fv_telefone">Celular / WhatsApp</label>
				<input type="tel" class="input-text" id="fv_telefone" name="fv_telefone" autocomplete="tel" value="<?php echo esc_attr( $r['telefone'] ); ?>"></p>
			<p class="form-row form-row-last"><label for="fv_pedido">Número do pedido (se tiver)</label>
				<input type="text" class="input-text" id="fv_pedido" name="fv_pedido" inputmode="numeric" value="<?php echo esc_attr( $r['pedido'] ); ?>"></p>
			<p class="form-row form-row-wide"><label for="fv_assunto">Assunto</label>
				<select id="fv_assunto" name="fv_assunto">
					<?php foreach ( $assuntos as $a ) : ?>
						<option <?php selected( $r['assunto'], $a ); ?>><?php echo esc_html( $a ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p class="form-row form-row-wide"><label for="fv_mensagem">Mensagem <abbr class="required" title="obrigatório">*</abbr></label>
				<textarea class="input-text" id="fv_mensagem" name="fv_mensagem" rows="6" required><?php echo esc_textarea( $r['mensagem'] ); ?></textarea></p>
			<p class="form-row form-row-wide fv-aceite"><label for="fv_aceite"><input type="checkbox" id="fv_aceite" name="fv_aceite" value="1" required>
				Autorizo o uso dos meus dados para responder a esta mensagem, conforme a <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">política de privacidade</a>.</label></p>
			<p class="form-row"><button type="submit" class="button alt" name="fv_contato_enviar" value="1">Enviar mensagem</button></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
} );
