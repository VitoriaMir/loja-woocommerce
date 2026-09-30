<?php
/**
 * Conteúdo das páginas. Os textos de políticas são um ponto de partida baseado no
 * Código de Defesa do Consumidor e na LGPD: revise com seu contador ou advogado.
 */

defined( 'ABSPATH' ) || exit;

function fv_conteudo_paginas() {
	$p = function ( $texto ) {
		return "<!-- wp:paragraph -->\n<p>{$texto}</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$h = function ( $texto ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$texto}</h2>\n<!-- /wp:heading -->\n\n";
	};
	$lista = function ( $itens ) {
		$li = '';
		foreach ( $itens as $i ) {
			$li .= "<!-- wp:list-item -->\n<li>{$i}</li>\n<!-- /wp:list-item -->\n";
		}
		return "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n{$li}</ul>\n<!-- /wp:list -->\n\n";
	};
	$pergunta = function ( $q, $a ) {
		return "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>{$q}</summary><!-- wp:paragraph -->\n<p>{$a}</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->\n\n";
	};
	$botao = function ( $texto, $url ) {
		return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"{$url}\">{$texto}</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n";
	};

	return array(
		'inicio' => array(
			'titulo'   => 'Pão de verdade começa com a ferramenta certa',
			'modelo'   => 'template-homepage.php',
			'conteudo' => $p( 'Bannetons, panelas de ferro, lâminas e farinhas escolhidos por quem assa toda semana. Enviamos para todo o Brasil.' )
				. $botao( 'Ver todos os produtos', '/loja/' ),
		),

		'sobre' => array(
			'titulo'   => 'Sobre a Fermento Vivo',
			'conteudo' => $p( 'A Fermento Vivo começou numa cozinha de apartamento em São Paulo, com um pote de fermento natural e muitas fornadas achatadas. Hoje vendemos só o que usamos de verdade na nossa bancada.' )
				. $h( 'Como escolhemos os produtos' )
				. $lista( array(
					'Testamos cada item em pelo menos 20 fornadas antes de colocar na loja.',
					'Preferimos fabricantes brasileiros quando a qualidade é a mesma.',
					'Informamos peso, medidas e material de tudo, para você saber o que vai chegar.',
				) )
				. $h( 'Atendimento de quem assa' )
				. $p( 'Quem responde suas mensagens também faz pão. Pode perguntar sobre hidratação, temperatura ou qual banneton serve para a sua receita.' ),
		),

		'contato' => array(
			'titulo'   => 'Contato',
			'conteudo' => $p( 'Respondemos em até 1 dia útil. Para acompanhar um pedido, informe o número dele: ele está no e-mail de confirmação.' )
				. $lista( array(
					'<strong>WhatsApp e telefone:</strong> [fv_dado chave="telefone"]',
					'<strong>E-mail:</strong> [fv_dado chave="email"]',
					'<strong>Horário:</strong> [fv_dado chave="horario"]',
					'<strong>Endereço para retirada:</strong> [fv_dado chave="endereco"]',
				) )
				. "<!-- wp:shortcode -->\n[fv_contato]\n<!-- /wp:shortcode -->",
		),

		'entregas-e-frete' => array(
			'titulo'   => 'Entregas e frete',
			'conteudo' => $h( 'Prazo de envio' )
				. $p( 'Pedidos com pagamento aprovado até 14h (dias úteis) são postados no mesmo dia. Depois disso, no próximo dia útil. No Pix a aprovação é imediata; no boleto, leva até 2 dias úteis.' )
				. $h( 'Como calculamos o frete' )
				. $p( 'O valor e o prazo aparecem no carrinho assim que você informa o CEP. Cotamos Correios (PAC e SEDEX) e transportadoras parceiras pelo Melhor Envio, e você escolhe a opção que preferir.' )
				. $lista( array(
					'<strong>Frete grátis</strong> para todo o Brasil em compras acima de R$ 299, na modalidade econômica.',
					'<strong>Retirada grátis</strong> no nosso endereço em Pinheiros, São Paulo, depois do aviso de pedido separado.',
					'Panelas de ferro e farinhas de 5 kg são mais pesadas, por isso o frete delas é maior.',
				) )
				. $h( 'Rastreamento' )
				. $p( 'Você recebe o código de rastreio por e-mail no dia da postagem. Ele também aparece em Minha conta > Pedidos.' )
				. $h( 'Problemas na entrega' )
				. $p( 'Se a embalagem chegar violada ou o produto avariado, recuse o recebimento ou fotografe a caixa e fale com a gente em até 7 dias. Reenviamos ou devolvemos o valor, sem custo para você.' ),
		),

		'trocas-e-devolucoes' => array(
			'titulo'   => 'Trocas e devoluções',
			'conteudo' => $h( 'Arrependimento: 7 dias' )
				. $p( 'Em compras pela internet você pode desistir em até 7 dias corridos após o recebimento, sem precisar explicar o motivo (Código de Defesa do Consumidor, art. 49). Devolvemos o valor total, incluindo o frete.' )
				. $h( 'Produto com defeito: 90 dias' )
				. $p( 'Produtos duráveis têm 90 dias de garantia legal contra defeitos (CDC, art. 26). Farinhas e fermentos devem ser reclamados em até 30 dias. Consertamos, trocamos ou devolvemos o valor.' )
				. $h( 'Como solicitar' )
				. $lista( array(
					'Envie o número do pedido e o motivo pela página de contato ou pelo WhatsApp.',
					'Mandamos um código de postagem reversa dos Correios, sem custo.',
					'Poste o produto na embalagem original ou em outra caixa que o proteja.',
					'O reembolso é feito em até 5 dias úteis após recebermos o produto, na mesma forma de pagamento.',
				) )
				. $p( 'No cartão de crédito, o estorno pode aparecer em até duas faturas, conforme a operadora.' ),
		),

		'perguntas-frequentes' => array(
			'titulo'   => 'Perguntas frequentes',
			'conteudo' => $pergunta( 'Quais formas de pagamento vocês aceitam?', 'Pix, cartão de crédito em até 6 vezes sem juros e boleto, todos pelo Mercado Pago. Não guardamos os dados do seu cartão.' )
				. $pergunta( 'Qual banneton eu escolho?', 'O redondo de 18 cm serve para massas de até 500 g de farinha e água somadas. O de 22 cm, até 1 kg. O oval é para pães alongados de 700 g a 900 g.' )
				. $pergunta( 'A panela de ferro serve no meu forno?', 'A de 24 cm tem 30 cm de largura com as alças e 18 cm de altura com a tampa. Cabe na maioria dos fornos domésticos; confira a altura da grade antes.' )
				. $pergunta( 'Preciso de conta para comprar?', 'Não. Você pode comprar só com o e-mail. Se criar a conta, acompanha os pedidos e não precisa digitar o endereço de novo.' )
				. $pergunta( 'Vocês emitem nota fiscal?', 'Sim. A nota fiscal vai por e-mail no dia do envio. Para compras de empresa, escolha "Pessoa jurídica" no checkout e informe o CNPJ.' )
				. $pergunta( 'Como acompanho meu pedido?', 'Pelo código de rastreio que enviamos por e-mail no dia da postagem, ou em Minha conta > Pedidos.' ),
		),

		'politica-de-privacidade' => array(
			'titulo'   => 'Política de privacidade',
			'conteudo' => $p( 'Esta política explica quais dados pessoais a Fermento Vivo coleta, para que usa e com quem compartilha, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018).' )
				. $h( 'Dados que coletamos' )
				. $lista( array(
					'<strong>Para entregar o pedido:</strong> nome, CPF ou CNPJ, endereço, e-mail e celular.',
					'<strong>Para o pagamento:</strong> os dados do cartão são digitados no ambiente do Mercado Pago e não passam pelo nosso servidor.',
					'<strong>Para o site funcionar:</strong> cookies do carrinho e da sessão de login.',
					'<strong>Quando você fala com a gente:</strong> o que você escrever no formulário de contato.',
				) )
				. $h( 'Com quem compartilhamos' )
				. $lista( array(
					'Mercado Pago, para processar o pagamento.',
					'Melhor Envio, Correios e transportadoras, para entregar o pedido.',
					'Nosso emissor de nota fiscal, para cumprir a obrigação fiscal.',
				) )
				. $p( 'Não vendemos nem alugamos seus dados.' )
				. $h( 'Por quanto tempo guardamos' )
				. $p( 'Os dados de pedidos ficam guardados por 5 anos, o prazo exigido pela legislação fiscal. Mensagens de contato são apagadas após 2 anos.' )
				. $h( 'Seus direitos' )
				. $p( 'Você pode pedir a qualquer momento acesso, correção, portabilidade ou exclusão dos seus dados (LGPD, art. 18). Escreva para [fv_dado chave="email"] e respondemos em até 15 dias.' )
				. $p( 'Controlador: [fv_dado chave="razao_social"], CNPJ [fv_dado chave="cnpj"].' ),
		),

		'termos-de-uso' => array(
			'titulo'   => 'Termos de uso',
			'conteudo' => $p( 'Ao comprar na Fermento Vivo, você concorda com estes termos. Eles complementam o Código de Defesa do Consumidor e não retiram nenhum direito previsto nele.' )
				. $h( 'Preços e estoque' )
				. $p( 'Os preços valem para compras pelo site e podem mudar sem aviso, mas o valor do pedido finalizado não muda. Se um produto esgotar depois da compra, avisamos e devolvemos o valor integral.' )
				. $h( 'Pedido e pagamento' )
				. $p( 'O pedido só é confirmado após a aprovação do pagamento. Boletos não pagos no vencimento e Pix não pagos em 30 minutos cancelam o pedido automaticamente.' )
				. $h( 'Entrega' )
				. $p( 'Os prazos começam a contar a partir da postagem e são informados pela transportadora. Veja a página de Entregas e frete.' )
				. $h( 'Trocas e devoluções' )
				. $p( 'Seguem a página de Trocas e devoluções, com 7 dias para arrependimento e 90 dias de garantia legal.' )
				. $h( 'Contato' )
				. $p( '[fv_dado chave="razao_social"], CNPJ [fv_dado chave="cnpj"], [fv_dado chave="endereco"]. E-mail: [fv_dado chave="email"].' ),
		),
	);
}
