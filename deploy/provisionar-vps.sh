#!/usr/bin/env bash
# =============================================================================
# Instala a loja Fermento Vivo num VPS Ubuntu 24.04 limpo.
#
#   Nginx + PHP 8.3-FPM (OPcache) + MariaDB + Redis + HTTPS (Let's Encrypt)
#   + firewall (UFW) + fail2ban + cache de página + backup diário.
#
# Uso (como root, dentro da pasta do projeto já copiada para o servidor):
#   DOMINIO=minhaloja.com.br EMAIL_ADMIN=voce@email.com bash deploy/provisionar-vps.sh
#
# Antes: aponte o DNS do domínio (registro A de @ e de www) para o IP do VPS.
# Pode rodar de novo sem estragar nada: cada etapa confere se já foi feita.
# =============================================================================
set -euo pipefail

DOMINIO="${DOMINIO:?Informe o domínio: DOMINIO=minhaloja.com.br}"
EMAIL_ADMIN="${EMAIL_ADMIN:?Informe o e-mail do administrador: EMAIL_ADMIN=voce@email.com}"
USUARIO_ADMIN="${USUARIO_ADMIN:-gestor}"
PHP_VER="${PHP_VER:-8.3}"
RAIZ="/var/www/${DOMINIO}"
SITE="${RAIZ}/public"
PROJETO="$(cd "$(dirname "$0")/.." && pwd)"
SEGREDOS="/root/.loja-${DOMINIO}.env"

verde() { printf '\n\033[1;32m== %s\033[0m\n' "$*"; }
[ "$(id -u)" -eq 0 ] || { echo "Rode como root (sudo -i)."; exit 1; }

# -----------------------------------------------------------------------------
verde "1/10 Pacotes do sistema"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q nginx mariadb-server redis-server certbot python3-certbot-nginx \
	ufw fail2ban unzip curl ghostscript \
	"php${PHP_VER}-fpm" "php${PHP_VER}-mysql" "php${PHP_VER}-curl" "php${PHP_VER}-gd" "php${PHP_VER}-intl" \
	"php${PHP_VER}-mbstring" "php${PHP_VER}-xml" "php${PHP_VER}-zip" "php${PHP_VER}-bcmath" "php${PHP_VER}-soap" \
	"php${PHP_VER}-imagick" "php${PHP_VER}-redis" "php${PHP_VER}-opcache"
apt-get install -y -q unattended-upgrades
dpkg-reconfigure -f noninteractive unattended-upgrades

if ! command -v wp >/dev/null; then
	curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x /usr/local/bin/wp
fi
WP="sudo -u www-data wp --path=${SITE}"

# -----------------------------------------------------------------------------
verde "2/10 Segredos (senhas geradas uma vez e guardadas em ${SEGREDOS})"
if [ ! -f "$SEGREDOS" ]; then
	umask 077
	cat > "$SEGREDOS" <<EOF
DB_NOME=loja_$(tr -dc a-z0-9 </dev/urandom | head -c 6)
DB_USUARIO=loja_$(tr -dc a-z0-9 </dev/urandom | head -c 6)
DB_SENHA=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)
ADMIN_SENHA=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)
EOF
fi
# shellcheck disable=SC1090
source "$SEGREDOS"

# -----------------------------------------------------------------------------
verde "3/10 Banco de dados"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NOME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USUARIO}'@'localhost' IDENTIFIED BY '${DB_SENHA}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NOME}\`.* TO '${DB_USUARIO}'@'localhost'; FLUSH PRIVILEGES;"

# -----------------------------------------------------------------------------
verde "4/10 PHP (OPcache, limites e pool dedicado)"
install -m 644 "$PROJETO/deploy/php/loja.ini" "/etc/php/${PHP_VER}/fpm/conf.d/90-loja.ini"
install -m 644 "$PROJETO/deploy/php/loja.ini" "/etc/php/${PHP_VER}/cli/conf.d/90-loja.ini"
sed "s/{{DOMINIO}}/${DOMINIO}/g; s/{{PHP_VER}}/${PHP_VER}/g" "$PROJETO/deploy/php/pool.conf" > "/etc/php/${PHP_VER}/fpm/pool.d/loja.conf"
rm -f "/etc/php/${PHP_VER}/fpm/pool.d/www.conf"
systemctl restart "php${PHP_VER}-fpm"

# -----------------------------------------------------------------------------
verde "5/10 WordPress"
mkdir -p "$SITE" "$RAIZ/backups" "$RAIZ/logs"
chown -R www-data:www-data "$RAIZ"

if [ ! -f "$SITE/wp-load.php" ]; then
	$WP core download --locale=pt_BR
fi
if [ ! -f "$SITE/wp-config.php" ]; then
	$WP config create --dbname="$DB_NOME" --dbuser="$DB_USUARIO" --dbpass="$DB_SENHA" --dbcharset=utf8mb4 --locale=pt_BR --skip-check
	$WP config set WP_ENVIRONMENT_TYPE production
	$WP config set DISALLOW_FILE_EDIT true --raw        # sem editor de código no painel
	$WP config set FORCE_SSL_ADMIN true --raw
	$WP config set WP_POST_REVISIONS 5 --raw
	$WP config set WP_MEMORY_LIMIT 256M
	$WP config set WP_MAX_MEMORY_LIMIT 512M
	$WP config set DISABLE_WP_CRON true --raw           # o cron roda pelo sistema (etapa 9)
	$WP config set WP_REDIS_HOST 127.0.0.1
	$WP config set WP_REDIS_PREFIX "${DOMINIO}:"
	$WP config set WP_CACHE_KEY_SALT "${DOMINIO}:"
	$WP config set WP_DEBUG false --raw
	$WP config set WP_DEBUG_LOG false --raw
	$WP config shuffle-salts
	chmod 640 "$SITE/wp-config.php"
fi
if ! $WP core is-installed 2>/dev/null; then
	$WP core install --url="https://${DOMINIO}" --title="Fermento Vivo" \
		--admin_user="$USUARIO_ADMIN" --admin_password="$ADMIN_SENHA" --admin_email="$EMAIL_ADMIN" --skip-email
fi

# -----------------------------------------------------------------------------
verde "6/10 Tema, plugins e código da loja"
rsync -a --delete "$PROJETO/wp-content/themes/fermento-vivo/" "$SITE/wp-content/themes/fermento-vivo/"
mkdir -p "$SITE/wp-content/mu-plugins"
rsync -a "$PROJETO/wp-content/mu-plugins/" "$SITE/wp-content/mu-plugins/"
rsync -a --delete "$PROJETO/setup/" "$RAIZ/setup/"
chown -R www-data:www-data "$SITE/wp-content" "$RAIZ/setup"

$WP theme install storefront
$WP theme activate fermento-vivo
$WP plugin install woocommerce woocommerce-mercadopago melhor-envio-cotacao redis-cache --activate
$WP plugin delete hello akismet 2>/dev/null || true
$WP theme delete twentytwentythree twentytwentyfour 2>/dev/null || true
$WP language core install pt_BR --activate
$WP language plugin install --all pt_BR
$WP language theme install --all pt_BR

# -----------------------------------------------------------------------------
verde "7/10 Nginx (HTTP primeiro, para o certificado)"
sed "s/{{DOMINIO}}/${DOMINIO}/g; s/{{PHP_VER}}/${PHP_VER}/g" "$PROJETO/deploy/nginx/loja.conf" > "/etc/nginx/sites-available/${DOMINIO}.conf"
install -m 644 "$PROJETO/deploy/nginx/cache-http.conf" /etc/nginx/conf.d/loja-cache.conf
mkdir -p /var/cache/nginx/loja && chown www-data:www-data /var/cache/nginx/loja
ln -sf "/etc/nginx/sites-available/${DOMINIO}.conf" "/etc/nginx/sites-enabled/${DOMINIO}.conf"
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# -----------------------------------------------------------------------------
verde "8/10 HTTPS (Let's Encrypt) e firewall"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
if [ ! -d "/etc/letsencrypt/live/${DOMINIO}" ]; then
	certbot --nginx -d "$DOMINIO" -d "www.${DOMINIO}" -m "$EMAIL_ADMIN" --agree-tos --no-eff-email --redirect -n
fi
nginx -t && systemctl reload nginx

install -m 644 "$PROJETO/deploy/fail2ban/wordpress.conf" /etc/fail2ban/filter.d/wordpress-login.conf
sed "s#{{LOG}}#${RAIZ}/logs/acesso.log#g" "$PROJETO/deploy/fail2ban/jail.local" > /etc/fail2ban/jail.d/loja.local
systemctl restart fail2ban

# -----------------------------------------------------------------------------
verde "9/10 Configuração da loja, cache e rotinas"
$WP eval-file "$RAIZ/setup/configurar-loja.php" producao
$WP rewrite flush
$WP option update home "https://${DOMINIO}"
$WP option update siteurl "https://${DOMINIO}"
$WP redis enable || true

cat > /etc/cron.d/loja-fermento-vivo <<EOF
# Cron do WordPress (pedidos, e-mails, estoque) a cada minuto.
* * * * * www-data wp --path=${SITE} cron event run --due-now --quiet
# Backup do banco e dos uploads todo dia às 3h.
0 3 * * * root bash ${PROJETO}/deploy/backup.sh ${DOMINIO} >> ${RAIZ}/logs/backup.log 2>&1
EOF

# -----------------------------------------------------------------------------
verde "10/10 Permissões"
find "$SITE" -type d -exec chmod 755 {} +
find "$SITE" -type f -exec chmod 644 {} +
chmod 640 "$SITE/wp-config.php"
chown -R www-data:www-data "$SITE"

cat <<EOF

Loja no ar: https://${DOMINIO}
Painel:     https://${DOMINIO}/wp-admin
Usuário:    ${USUARIO_ADMIN}
Senha:      está em ${SEGREDOS} (troque no primeiro acesso)

Falta fazer no painel:
  1. WooCommerce > Mercado Pago: colar as credenciais de produção.
  2. Melhor Envio > Configurações: conectar a conta e escolher as transportadoras.
  3. Configurações > Dados da loja: WhatsApp, CNPJ, endereço.
  4. Fazer uma compra real de valor baixo e estornar.
EOF
