#!/bin/bash
# =============================================================
#  Payin — Deployment Script
#  Run this on your production server: bash deploy.sh
# =============================================================

set -e

PROJECT_DIR="/var/www/payment"
SERVICES=("auth-service" "payment-service" "transaction-service" "wallet-service" "settlement-service")

echo "========================================"
echo "  Payin — Deploying all services"
echo "========================================"

cd "$PROJECT_DIR"

# Pull latest code
echo ""
echo ">> Pulling latest code from GitHub..."
git pull origin main

# Deploy each service
for SERVICE in "${SERVICES[@]}"; do
    echo ""
    echo "----------------------------------------"
    echo "  Deploying: $SERVICE"
    echo "----------------------------------------"

    cd "$PROJECT_DIR/$SERVICE"

    # Install PHP dependencies (no dev packages in production)
    echo ">> Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction

    # Run migrations
    echo ">> Running migrations..."
    php artisan migrate --force

    # Clear and rebuild caches
    echo ">> Optimizing..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Set permissions
    echo ">> Setting permissions..."
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache

    echo ">> $SERVICE deployed successfully."
done

# Restart PHP-FPM to pick up changes
echo ""
echo ">> Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm 2>/dev/null || sudo systemctl restart php-fpm 2>/dev/null || true

# Reload Nginx
echo ">> Reloading Nginx..."
sudo systemctl reload nginx

echo ""
echo "========================================"
echo "  All services deployed successfully!"
echo "========================================"
