// Gera capturas de tela da loja no computador e no celular, incluindo carrinho e checkout.
// Uso: node tests/capturas.mjs [pasta] [url-base]
import { chromium, devices } from '@playwright/test';

const pasta = process.argv[2] || 'tests/capturas';
const base = process.argv[3] || 'http://127.0.0.1:9400';
const paginas = {
	inicio: '/',
	loja: '/loja/',
	produto: '/produto/banneton-de-vime-redondo/',
	contato: '/contato/',
	carrinho: '/carrinho/',
	checkout: '/finalizar-compra/',
};

const browser = await chromium.launch();
for (const [tamanho, opcoes] of Object.entries({ pc: { viewport: { width: 1280, height: 900 } }, cel: devices['Pixel 7'] })) {
	const ctx = await browser.newContext({ ...opcoes, locale: 'pt-BR' });
	await ctx.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: base }]);
	const page = await ctx.newPage();
	for (const [nome, url] of Object.entries(paginas)) {
		if (nome === 'carrinho') {
			await page.goto(base + '/produto/lamina-para-pestana-com-5-refis/');
			await page.click('button.single_add_to_cart_button');
			await page.waitForLoadState('networkidle');
		}
		await page.goto(base + url, { waitUntil: 'networkidle' });
		await page.screenshot({ path: `${pasta}/${nome}-${tamanho}.png`, fullPage: true });
	}
	await ctx.close();
}
await browser.close();
console.log('Capturas salvas em', pasta);
