#!/bin/bash
set -e

# Support Render custom dynamic PORT
RENDER_PORT="${PORT:-80}"
sed -i "s/80/$RENDER_PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Ensure required directories exist
mkdir -p /var/www/html/database /var/www/html/storage/uploads /var/www/html/storage/logs

# Auto-seed database if app.db doesn't exist
if [ ! -f /var/www/html/database/app.db ]; then
    echo "⚙️ Database app.db belum ditemukan. Menjalankan migrasi dan seeder awal..."
    php /var/www/html/scripts/reseed_all.php || true
    echo "✅ Database berhasil diinisialisasi."
fi

# Ensure correct file permissions for web server
chown -R www-data:www-data /var/www/html/database /var/www/html/storage
chmod -R 775 /var/www/html/database /var/www/html/storage

echo "🚀 Menjalankan Apache di port $RENDER_PORT..."
exec apache2-foreground
