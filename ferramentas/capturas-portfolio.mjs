// Capturas usadas na página do portfólio (docs/). Rode com a loja local no ar: node ferramentas/capturas-portfolio.mjs
import { chromium, devices } from '@playwright/test';

const base = process.env.LOJA_URL || 'http://127.0.0.1:9400';
const pasta = 'docs/img';
const browser = await chromium.launch();

async function contexto(opcoes) {
	const ctx = await browser.newContext({ ...opcoes, locale: 'pt-BR' });
	await ctx.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: base }]);
	return ctx;
}
async function abrir(page, url) {
	await page.goto(base + url, { waitUntil: 'networkidle' });
	await page.evaluate(() => document.querySelectorAll('img[loading="lazy"]').forEach((i) => (i.loading = 'eager')));
	await page.waitForLoadState('networkidle');
	await page.evaluate(() => document.querySelector('.fv-whatsapp')?.remove());
}
const foto = (page, nome, extra = {}) => page.screenshot({ path: `${pasta}/${nome}.jpg`, type: 'jpeg', quality: 82, ...extra });

// Computador
const pc = await contexto({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
let page = await pc.newPage();
await abrir(page, '/');
await foto(page, 'inicio-pc');
await abrir(page, '/produto/panela-de-ferro-fundido-esmaltada-24-cm/');
await foto(page, 'produto-pc');

// Compra completa até "pedido recebido"
await page.click('button.single_add_to_cart_button');
await abrir(page, '/produto/lamina-para-pestana-com-5-refis/');
await page.click('button.single_add_to_cart_button');
await abrir(page, '/carrinho/');
await foto(page, 'carrinho-pc');
await abrir(page, '/finalizar-compra/');
await page.fill('#billing_first_name', 'Maria');
await page.fill('#billing_last_name', 'Oliveira');
await page.locator('#billing_cpf').pressSequentially('52998224725');
await page.locator('#billing_phone').pressSequentially('11987654321');
await page.fill('#billing_email', 'maria@example.com');
await page.locator('#billing_postcode').pressSequentially('01310100');
await page.waitForFunction(() => document.querySelector('#billing_city').value === 'São Paulo');
await page.fill('#billing_number', '1578');
await page.waitForTimeout(1500);
await page.waitForFunction(() => !document.querySelector('.blockUI.blockOverlay'));
await page.evaluate(() => window.scrollTo(0, document.querySelector('#customer_details').getBoundingClientRect().top + window.scrollY - 24));
await page.waitForTimeout(400);
await foto(page, 'checkout-pc');
await page.evaluate(() => {
	jQuery('#payment_method_bacs').prop('checked', true).trigger('click');
	document.querySelector('#terms').checked = true;
});
await page.click('#place_order');
await page.waitForURL(/pedido-recebido/, { timeout: 60000 });
await page.waitForLoadState('networkidle');
await foto(page, 'pedido-pc');
await pc.close();

// Celular
const cel = await contexto(devices['Pixel 7']);
page = await cel.newPage();
await abrir(page, '/');
await foto(page, 'inicio-cel');
await abrir(page, '/loja/');
await page.evaluate(() => window.scrollTo(0, document.querySelector('ul.products').getBoundingClientRect().top + window.scrollY - 70));
await page.waitForTimeout(500);
await foto(page, 'loja-cel');
await abrir(page, '/produto/banneton-de-vime-redondo/');
await foto(page, 'produto-cel');
await page.locator('select#pa_tamanho').selectOption({ index: 1 });
await page.click('button.single_add_to_cart_button');
await abrir(page, '/finalizar-compra/');
await page.locator('#billing_persontype').selectOption('2');
await page.locator('#billing_cnpj').pressSequentially('12abc34501de35');
await page.locator('#billing_postcode').pressSequentially('01310100');
await page.waitForFunction(() => document.querySelector('#billing_city').value === 'São Paulo');
await page.evaluate(() => window.scrollTo(0, document.querySelector('#billing_persontype_field').getBoundingClientRect().top + window.scrollY - 20));
await page.waitForTimeout(500);
await foto(page, 'checkout-cel');
await cel.close();

await browser.close();
console.log('Capturas em', pasta);
