// Testes do fluxo de compra. Rode com a loja local no ar (npm run loja) e depois: npm run teste
// Para testar outro endereço: LOJA_URL=https://homologacao.minhaloja.com.br npm run teste
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: './tests',
	testMatch: '*.spec.js',
	timeout: 120_000,
	expect: { timeout: 15_000 },
	fullyParallel: false,
	workers: 1,
	retries: 0,
	reporter: [['list'], ['html', { open: 'never', outputFolder: 'tests/relatorio' }]],
	use: {
		baseURL: process.env.LOJA_URL || 'http://127.0.0.1:9400',
		locale: 'pt-BR',
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
	},
	projects: [
		{ name: 'computador', use: { ...devices['Desktop Chrome'] } },
		{ name: 'celular', use: { ...devices['Pixel 7'] } },
	],
});
