// Gera blueprint-demo.json: a mesma loja do ambiente local, mas lendo os arquivos direto do GitHub.
// É o que o botão "Ver demo ao vivo" abre no WordPress Playground (roda no navegador, sem servidor).
// Rode depois de mudar arquivos do tema, do mu-plugin ou do setup: node ferramentas/gerar-blueprint-demo.mjs
import fs from 'node:fs';
import path from 'node:path';

const REPO = process.env.REPO || 'VitoriaMir/loja-woocommerce';
const BRANCH = process.env.BRANCH || 'main';
const raw = (p) => `https://raw.githubusercontent.com/${REPO}/${BRANCH}/${p}`;

// pasta do projeto → pasta dentro do WordPress
const pastas = {
	'wp-content/themes/fermento-vivo': '/wordpress/wp-content/themes/fermento-vivo',
	'wp-content/mu-plugins': '/wordpress/wp-content/mu-plugins',
	setup: '/wordpress/setup',
};

function listar(dir) {
	return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
		const p = path.posix.join(dir, e.name);
		return e.isDirectory() ? listar(p) : [p];
	});
}

const arquivos = [];
const diretorios = new Set();
for (const [origem, destino] of Object.entries(pastas)) {
	for (const arq of listar(origem)) {
		const alvo = destino + arq.slice(origem.length);
		diretorios.add(path.posix.dirname(alvo));
		arquivos.push({ step: 'writeFile', path: alvo, data: { resource: 'url', url: raw(arq) } });
	}
}

const blueprint = {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	meta: {
		title: 'Loja Fermento Vivo · demo',
		author: 'VitoriaMir',
		description: 'Loja WooCommerce com checkout brasileiro, Mercado Pago, Melhor Envio e produtos de exemplo, rodando no navegador.',
	},
	landingPage: '/',
	preferredVersions: { php: '8.3', wp: 'latest' },
	features: { networking: true },
	login: true,
	steps: [
		{ step: 'installPlugin', pluginData: { resource: 'wordpress.org/plugins', slug: 'woocommerce' }, options: { activate: true } },
		// O plugin do Mercado Pago dá erro fatal no Playground do navegador (no ambiente local funciona).
		// Na demo o pagamento é o "Pix manual", já que o Mercado Pago precisaria de credenciais reais.
		{ step: 'installPlugin', pluginData: { resource: 'wordpress.org/plugins', slug: 'melhor-envio-cotacao' }, options: { activate: true } },
		{ step: 'installTheme', themeData: { resource: 'wordpress.org/themes', slug: 'storefront' }, options: { activate: false } },
		...[...diretorios].sort().map((d) => ({ step: 'mkdir', path: d })),
		...arquivos,
		{ step: 'activateTheme', themeFolderName: 'fermento-vivo' },
		{ step: 'setSiteLanguage', language: 'pt_BR' },
		{ step: 'wp-cli', command: 'wp eval-file /wordpress/setup/configurar-loja.php local' },
		{ step: 'wp-cli', command: 'wp rewrite flush' },
	],
};

fs.writeFileSync('blueprint-demo.json', JSON.stringify(blueprint, null, '\t') + '\n');
const url = `https://playground.wordpress.net/?blueprint-url=${encodeURIComponent(raw('blueprint-demo.json'))}`;
console.log(`blueprint-demo.json com ${arquivos.length} arquivos.\nDemo: ${url}`);
