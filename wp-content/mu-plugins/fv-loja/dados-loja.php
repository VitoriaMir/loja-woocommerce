<?php
/**
 * Dados da loja: uma única tela (Configurações > Dados da loja) com as informações
 * usadas no rodapé, na página de contato, no botão de WhatsApp e no SEO.
 */

defined( 'ABSPATH' ) || exit;

const FV_DADOS_OPCAO = 'fv_dados_loja';

/**
 * Campos da tela, com rótulo e valor padrão.
 */
function fv_dados_campos() {
	return array(
		'razao_social'     => array( 'Razão social', 'Fermento Vivo Utensílios Ltda.' ),
		'cnpj'             => array( 'CNPJ', '00.000.000/0001-00' ),
		'email'            => array( 'E-mail de atendimento', get_option( 'admin_email' ) ),
		'telefone'         => array( 'Telefone', '(11) 3000-0000' ),
		'whatsapp'         => array( 'WhatsApp (só números, com DDI e DDD)', '5511900000000' ),
		'endereco'         => array( 'Endereço', 'Rua Exemplo, 100 · Pinheiros · São Paulo/SP · 05422-000' ),
		'horario'          => array( 'Horário de atendimento', 'Segunda a sexta, das 9h às 18h' ),
		'instagram'        => array( 'Instagram (sem @)', 'fermentovivo' ),
		'frete_gratis_min' => array( 'Frete grátis a partir de (R$)', '299' ),
		'parcelas_max'     => array( 'Parcelas sem juros (máximo)', '6' ),
		'parcela_min'      => array( 'Valor mínimo da parcela (R$)', '30' ),
	);
}

/**
 * Lê um dado da loja. Ex.: fv_dado( 'whatsapp' ).
 */
function fv_dado( $chave ) {
	$salvos = get_option( FV_DADOS_OPCAO, array() );
	$campos = fv_dados_campos();
	if ( isset( $salvos[ $chave ] ) && '' !== $salvos[ $chave ] ) {
		return $salvos[ $chave ];
	}
	return isset( $campos[ $chave ] ) ? $campos[ $chave ][1] : '';
}

add_action( 'admin_menu', function () {
	add_options_page( 'Dados da loja', 'Dados da loja', 'manage_woocommerce', 'fv-dados-loja', 'fv_dados_tela' );
} );

add_action( 'admin_init', function () {
	register_setting( 'fv_dados_loja', FV_DADOS_OPCAO, array(
		'type'              => 'array',
		'sanitize_callback' => 'fv_dados_sanitizar',
		'default'           => array(),
	) );
} );

function fv_dados_sanitizar( $entrada ) {
	$limpo = array();
	foreach ( fv_dados_campos() as $chave => $campo ) {
		$valor = isset( $entrada[ $chave ] ) ? wp_unslash( $entrada[ $chave ] ) : '';
		switch ( $chave ) {
			case 'email':
				$limpo[ $chave ] = sanitize_email( $valor );
				break;
			case 'whatsapp':
				$limpo[ $chave ] = preg_replace( '/\D/', '', $valor );
				break;
			case 'instagram':
				$limpo[ $chave ] = ltrim( sanitize_text_field( $valor ), '@' );
				break;
			case 'frete_gratis_min':
			case 'parcela_min':
				$limpo[ $chave ] = (string) max( 0, (float) str_replace( ',', '.', $valor ) );
				break;
			case 'parcelas_max':
				$limpo[ $chave ] = (string) min( 24, max( 1, absint( $valor ) ) );
				break;
			default:
				$limpo[ $chave ] = sanitize_text_field( $valor );
		}
	}
	return $limpo;
}

function fv_dados_tela() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Dados da loja</h1>
		<p>Estas informações aparecem no rodapé, na página de contato, no botão de WhatsApp e nos dados que o Google lê.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'fv_dados_loja' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( fv_dados_campos() as $chave => $campo ) : ?>
					<tr>
						<th scope="row"><label for="fv-<?php echo esc_attr( $chave ); ?>"><?php echo esc_html( $campo[0] ); ?></label></th>
						<td><input type="text" class="regular-text" id="fv-<?php echo esc_attr( $chave ); ?>"
							name="<?php echo esc_attr( FV_DADOS_OPCAO . '[' . $chave . ']' ); ?>"
							value="<?php echo esc_attr( fv_dado( $chave ) ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Salvar dados' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Link de WhatsApp com mensagem inicial.
 */
function fv_whatsapp_url( $mensagem = '' ) {
	$numero = fv_dado( 'whatsapp' );
	if ( ! $numero ) {
		return '';
	}
	$url = 'https://wa.me/' . $numero;
	return $mensagem ? $url . '?text=' . rawurlencode( $mensagem ) : $url;
}

/**
 * [fv_dado chave="email"] para usar os dados dentro de páginas.
 */
add_shortcode( 'fv_dado', function ( $atts ) {
	$atts = shortcode_atts( array( 'chave' => '' ), $atts );
	return esc_html( fv_dado( $atts['chave'] ) );
} );
