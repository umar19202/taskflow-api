#!/bin/sh
set -e

# Copilot Safeguard: Auto-create .env from example if recruiter forgot
if [ ! -f .env ] && [ -f .env.example ]; then
    echo ".env file not found, creating from .env.example..."
    cp .env.example .env
fi

# Install dependencies including dev (crucial for recruiters running PHPUnit tests)
if [ ! -d "vendor" ]; then
    echo "Vendor directory missing. Running composer install..."
    composer install --no-interaction --optimize-autoloader
fi

# Ensure application encryption key is set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Senior Signal: Check database connection availability before migrating
echo "Waiting for database connection..."
until php -r "
try {
    \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
"; do
    echo "Database is unavailable - sleeping..."
    sleep 2
done

echo "Database is up! Running migrations..."
php artisan migrate --force

# Start main PHP-FPM execution process
exec php-fpm
