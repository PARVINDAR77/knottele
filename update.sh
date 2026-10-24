#!/bin/bash
set -e

echo "🚀 Updating KNOTELLE on live server..."

cd /var/www/knottele-fullstack

# 1. Fetch and reset git to latest main commit
echo "📥 Pulling latest code from GitHub..."
git fetch origin main
git reset --hard origin/main

# 2. Stop old container and rebuild/start with fresh files and volumes
echo "🐳 Recreating Docker container with latest build..."
docker compose down || true
docker compose up -d --build --force-recreate

# 3. Fix storage and cache permissions
echo "🔒 Fixing file and directory permissions..."
chmod -R 777 /var/www/knottele-fullstack/backend/storage /var/www/knottele-fullstack/backend/bootstrap/cache 2>/dev/null || true
docker exec knotelle-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
docker exec knotelle-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# 4. Run database migrations and seed default admin user
echo "📦 Running database migrations and ensuring Admin exists..."
docker exec knotelle-app php artisan migrate --force || true
docker exec knotelle-app php artisan db:seed --class=AdminSeeder --force || true

# 5. Clear and refresh Laravel caches
echo "⚡ Clearing Laravel caches..."
docker exec knotelle-app php artisan route:clear || true
docker exec knotelle-app php artisan cache:clear || true
docker exec knotelle-app php artisan config:clear || true
docker exec knotelle-app php artisan view:clear || true

echo "=========================================="
echo "✅ KNOTELLE Updated Successfully!"
echo "=========================================="
