// Fluxo de compra completo da loja Fermento Vivo, como um cliente faria.
// Pagamento: usa o "Pix manual", que o script de configuração ativa no ambiente local.
// O Mercado Pago precisa das credenciais de teste da sua conta: veja o README.
const { test, expect } = require('@playwright/test');

const CPF_VALIDO = '529.982.247-25';
const CNPJ_ALFANUMERICO = '12.ABC.345/01DE-35'; // exemplo oficial da Receita Federal (2026)

test.beforeEach(async ({ context, baseURL }) => {
	// O WordPress Playground faz login automático como admin; o teste precisa ser um visitante.
	await context.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: baseURL }]);
});

/** Espera o WooCommerce terminar de recalcular o checkout (overlay "blockUI"). */
async function esperarCheckout(page) {
	await page.waitForTimeout(300);
	await expect(page.locator('.blockUI.blockOverlay')).toHaveCount(0);
}

async function adicionarBanneton22(page) {
	await page.goto('/produto/banneton-de-vime-redondo/');
	await page.locator('select#pa_tamanho').selectOption({ index: 2 });
	await expect(page.locator('.woocommerce-variation-price')).toContainText('89,90');
	await page.locator('button.single_add_to_cart_button').click();
	await expect(page.locator('.woocommerce-message')).toContainText('adicionado');
}

async function adicionarSimples(page, url) {
	await page.goto(url);
	await page.locator('button.single_add_to_cart_button').click();
	await expect(page.locator('.woocommerce-message')).toContainText('adicionado');
}

async function preencherEndereco(page) {
	await page.fill('#billing_first_name', 'Maria');
	await page.fill('#billing_last_name', 'Teste da Silva');
	await page.fill('#billing_phone', '11987654321');
	await page.fill('#billing_email', `teste+${Date.now()}@example.com`);
	await page.locator('#billing_postcode').pressSequentially('01310100');
	// Endereço preenchido pelo CEP (ViaCEP).
	await expect(page.locator('#billing_city')).toHaveValue('São Paulo');
	await expect(page.locator('#billing_address_1')).toHaveValue(/Paulista/);
	await expect(page.locator('#billing_neighborhood')).toHaveValue(/Bela Vista/);
	await expect(page.locator('#billing_number')).toBeFocused();
	await page.fill('#billing_number', '1578');
	await esperarCheckout(page);
}

/**
 * Escolhe o frete. O WooCommerce redesenha a tabela de frete a cada mudança, então o clique
 * é feito pelo jQuery da página (como o clique do cliente) e conferido depois do recálculo.
 */
async function escolherFrete(page, metodo) {
	await esperarCheckout(page);
	await page.evaluate((m) => {
		jQuery('input.shipping_method[value^="' + m + '"]').prop('checked', true).trigger('change');
	}, metodo);
	await esperarCheckout(page);
	await expect(page.locator(`input.shipping_method[value^="${metodo}"]`)).toBeChecked();
}

async function finalizar(page) {
	await esperarCheckout(page);
	await page.evaluate(() => {
		jQuery('#payment_method_bacs').prop('checked', true).trigger('click');
		document.querySelector('#terms').checked = true;
	});
	await page.locator('#place_order').click();
}

test.describe('Vitrine', () => {
	test('página inicial mostra categorias, produtos e dados da loja', async ({ page }) => {
		await page.goto('/');
		await expect(page).toHaveTitle(/Fermento Vivo/);
		await expect(page.locator('.fv-faixa')).toContainText('Frete grátis');
		await expect(page.locator('.storefront-product-categories li.product')).toHaveCount(4);
		await expect(page.locator('.fv-parcelas').first()).toContainText('sem juros');
		await expect(page.locator('.fv-rodape-legal')).toContainText('CNPJ');
		await expect(page.getByText('Compre por marca')).toHaveCount(0);
	});

	test('categoria lista só os produtos dela', async ({ page }) => {
		await page.goto('/categoria/panelas-e-assadeiras/');
		await expect(page.locator('ul.products li.product')).toHaveCount(2);
	});

	test('produto esgotado não pode ser comprado', async ({ page }) => {
		await page.goto('/produto/banneton-oval-de-vime-25-cm/');
		await expect(page.locator('.stock.out-of-stock')).toBeVisible();
		await expect(page.locator('button.single_add_to_cart_button')).toHaveCount(0);
	});

	// Obs.: no servidor (MySQL) a busca também ignora acentos ("lamina" acha "Lâmina").
	// O ambiente local usa SQLite, que diferencia acentos; por isso o teste usa um termo sem acento.
	test('busca encontra produto', async ({ page }) => {
		await page.goto('/?s=banneton&post_type=product');
		await expect(page.locator('ul.products li.product')).toHaveCount(3);
	});
});

test.describe('Compra', () => {
	test('pessoa física: carrinho, CEP, frete, Pix e pedido recebido', async ({ page }) => {
		await adicionarBanneton22(page);
		await adicionarSimples(page, '/produto/lamina-para-pestana-com-5-refis/');

		await page.goto('/carrinho/');
		await expect(page.locator('.cart_item')).toHaveCount(2);
		await expect(page.locator('.fv-falta-frete')).toContainText('Faltam');
		// 89,90 + 39,90 = 129,80
		await expect(page.locator('.cart-subtotal')).toContainText('129,80');

		await page.goto('/finalizar-compra/');
		await expect(page.locator('#billing_persontype')).toHaveValue('1');
		await expect(page.locator('#billing_cnpj_field')).toBeHidden();
		await preencherEndereco(page);

		// CPF inválido é recusado com mensagem clara.
		await page.fill('#billing_cpf', '111.111.111-11');
		await finalizar(page);
		await expect(page.locator('ul.woocommerce-error')).toContainText('CPF');

		// Máscara: digitado só com números, aparece formatado.
		await page.fill('#billing_cpf', '');
		await page.locator('#billing_cpf').pressSequentially(CPF_VALIDO.replace(/\D/g, ''));
		await expect(page.locator('#billing_cpf')).toHaveValue(CPF_VALIDO);

		// Frete: entrega padrão (paga), sem frete grátis abaixo de R$ 299.
		await esperarCheckout(page);
		await expect(page.locator('#shipping_method')).toContainText('Entrega padrão');
		await expect(page.locator('#shipping_method')).not.toContainText('Frete grátis');
		await escolherFrete(page, 'flat_rate');
		// 129,80 + 24,90 = 154,70
		await expect(page.locator('.order-total')).toContainText('154,70');

		await finalizar(page);
		await page.waitForURL(/pedido-recebido/, { timeout: 60_000 });
		await expect(page.locator('.woocommerce-order-overview__order')).toBeVisible();
		await expect(page.locator('.woocommerce-order-overview__total')).toContainText('154,70');
		await expect(page.locator('.woocommerce-bacs-bank-details, .woocommerce-order')).toContainText('Pix');
		await expect(page.locator('.woocommerce-customer-details')).toContainText('1578');
		await expect(page.locator('.woocommerce-customer-details')).toContainText('Bela Vista');
	});

	test('pessoa jurídica com CNPJ alfanumérico e frete grátis acima de R$ 299', async ({ page }) => {
		await adicionarSimples(page, '/produto/panela-de-ferro-fundido-esmaltada-24-cm/');

		await page.goto('/finalizar-compra/');
		await page.locator('#billing_persontype').selectOption('2');
		await expect(page.locator('#billing_cpf_field')).toBeHidden();
		await expect(page.locator('#billing_cnpj_field')).toBeVisible();
		await preencherEndereco(page);

		// Sem razão social: recusado.
		await page.locator('#billing_cnpj').pressSequentially('12abc34501de35');
		await expect(page.locator('#billing_cnpj')).toHaveValue(CNPJ_ALFANUMERICO);
		await finalizar(page);
		await expect(page.locator('ul.woocommerce-error')).toContainText('Razão social');

		await page.fill('#billing_company', 'Padaria Exemplo Ltda.');
		await esperarCheckout(page);
		// Pedido de R$ 399,90: frete grátis aparece e a entrega padrão paga some.
		await expect(page.locator('#shipping_method')).toContainText('Frete grátis');
		await expect(page.locator('input.shipping_method[value^="flat_rate"]')).toHaveCount(0);
		await escolherFrete(page, 'free_shipping');
		await expect(page.locator('.order-total')).toContainText('399,90');

		await finalizar(page);
		await page.waitForURL(/pedido-recebido/, { timeout: 60_000 });
		await expect(page.locator('.woocommerce-order-overview__total')).toContainText('399,90');
		await expect(page.locator('.woocommerce-customer-details')).toContainText('Padaria Exemplo');
	});

	test('retirada na loja não cobra frete', async ({ page }) => {
		await adicionarSimples(page, '/produto/raspador-de-massa-em-aco-inox/');
		await page.goto('/finalizar-compra/');
		await preencherEndereco(page);
		await page.locator('#billing_cpf').pressSequentially(CPF_VALIDO.replace(/\D/g, ''));
		await escolherFrete(page, 'local_pickup');
		await expect(page.locator('.order-total')).toContainText('34,90');
		await finalizar(page);
		await page.waitForURL(/pedido-recebido/, { timeout: 60_000 });
	});
});

test.describe('Contato, SEO e segurança', () => {
	test('formulário de contato envia e confirma', async ({ page }) => {
		await page.goto('/contato/');
		await page.fill('#fv_nome', 'Maria Teste');
		await page.fill('#fv_email', 'maria@example.com');
		await page.fill('#fv_mensagem', 'Qual banneton serve para 800 g de massa?');
		await page.check('#fv_aceite');
		await page.waitForTimeout(3500); // o formulário recusa envios em menos de 3 s (robôs)
		await page.click('button[name="fv_contato_enviar"]');
		await expect(page.locator('.fv-contato .woocommerce-message')).toContainText('Mensagem enviada');
	});

	test('produto tem descrição, Open Graph e dados estruturados', async ({ page }) => {
		await page.goto('/produto/panela-de-ferro-fundido-esmaltada-24-cm/');
		await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', /4,2 litros/);
		await expect(page.locator('meta[property="og:image"]')).toHaveCount(1);
		await expect(page.locator('meta[property="product:price:amount"]')).toHaveAttribute('content', '399.90');
		const ld = await page.locator('script[type="application/ld+json"]').allTextContents();
		expect(ld.join('')).toContain('"@type":"Product"');
	});

	test('robots.txt esconde carrinho e checkout do Google', async ({ request }) => {
		const txt = await (await request.get('/robots.txt')).text();
		expect(txt).toContain('Disallow: /carrinho/');
		expect(txt).toContain('Disallow: /finalizar-compra/');
	});

	test('não expõe usuários nem a versão do WordPress', async ({ request, page }) => {
		const usuarios = await request.get('/wp-json/wp/v2/users');
		expect(usuarios.status()).toBe(404);
		await page.goto('/?author=1');
		await expect(page).toHaveURL(/\/$/);
		await expect(page.locator('meta[name="generator"][content^="WordPress"]')).toHaveCount(0);
	});
});
