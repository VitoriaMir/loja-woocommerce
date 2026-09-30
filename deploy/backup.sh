#!/usr/bin/env bash
# Backup diário: banco de dados + uploads, guardando os últimos 14 dias.
# Uso: bash deploy/backup.sh minhaloja.com.br
#
# Recomendado: copie também para fora do VPS (ex.: rclone para Google Drive ou S3),
# porque um backup no mesmo servidor não protege contra perda do servidor.
set -euo pipefail

DOMINIO="${1:?Informe o domínio}"
RAIZ="/var/www/${DOMINIO}"
DESTINO="${RAIZ}/backups"
DATA="$(date +%Y-%m-%d_%H%M)"
WP="sudo -u www-data wp --path=${RAIZ}/public"

mkdir -p "$DESTINO"
$WP db export - --single-transaction --quick | gzip > "${DESTINO}/banco_${DATA}.sql.gz"
tar -czf "${DESTINO}/uploads_${DATA}.tar.gz" -C "${RAIZ}/public/wp-content" uploads
find "$DESTINO" -type f -mtime +14 -delete
chmod 600 "${DESTINO}"/*
echo "$(date '+%d/%m/%Y %H:%M') backup ok: banco_${DATA}.sql.gz, uploads_${DATA}.tar.gz"
