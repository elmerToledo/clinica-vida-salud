#!/bin/sh
# Arranque del contenedor en producción (Render).
set -e
PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Datos de demostración (usuarios de prueba): solo si SEED_DEMO=true
if [ "${SEED_DEMO}" = "true" ]; then
  php artisan db:seed --class=ClinicaSeeder --force
fi

exec apache2-foreground
