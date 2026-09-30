// Abre a demo ao vivo no WordPress Playground e confere se a loja carregou.
// Uso: node ferramentas/verificar-demo.mjs
import { chromium } from '@playwright/test';

const REPO = process.env.REPO || 'VitoriaMir/loja-woocommerce';
const url = 'https://playground.wordpress.net/?blueprint-url=' + encodeURIComponent(`https://raw.githubusercontent.com/${REPO}/main/blueprint-demo.json`);
const browser = await chromium.launch();
const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' })).newPage();
await page.goto(url);

const inicio = Date.now();
let loja = null;
while (!loja && Date.now() - inicio < 360_000) {
	for (const f of page.frames()) {
		const texto = await f.evaluate(() => document.body?.innerText || '').catch(() => '');
		if (texto.includes('Pão de verdade começa')) { loja = f; break; }
		if (texto.includes('Blueprint execution failed') || texto.includes('critical error')) {
			console.log('A demo falhou:\n' + texto.slice(0, 800));
			await browser.close();
			process.exit(1);
		}
	}
	if (!loja) await page.waitForTimeout(5000);
}
if (!loja) {
	console.log('A demo não carregou em 6 minutos.');
	await browser.close();
	process.exit(1);
}
console.log(`Demo carregou em ${Math.round((Date.now() - inicio) / 1000)} s.`);
await page.screenshot({ path: 'tests/demo-playground.png' });
await browser.close();
