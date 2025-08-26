#!/bin/sh
set -e

cd /var/www/html

# Ensure required Laravel directories exist
mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

# Set safe permissions
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Optional: Backup database (MySQL)
if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ] && [ -n "$DB_HOST" ]; then
  BACKUP_FILE="/var/www/backup_$(date +'%Y%m%d_%H%M%S').sql"

  # Vérifier que mysqldump est disponible
  if command -v mysqldump >/dev/null 2>&1; then
    # Exécuter la sauvegarde
    mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" -h"$DB_HOST" "$DB_DATABASE" > "$BACKUP_FILE" || true
  fi
fi

# Run Laravel maintenance commands
php83 artisan migrate --force || true
php83 artisan storage:link || true

# Clear and cache configurations
php83 artisan config:clear || true
php83 artisan route:clear || true
php83 artisan view:clear || true
php83 artisan cache:clear || true

php83 artisan optimize || true

# Run the default command
exec "$@"