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

# 3. Clear Laravel internal caches
echo "⚡ Clearing Laravel caches..."
docker exec knotelle-app php artisan route:clear || true
docker exec knotelle-app php artisan cache:clear || true
docker exec knotelle-app php artisan config:clear || true
docker exec knotelle-app php artisan view:clear || true

echo "=========================================="
echo "✅ KNOTELLE Updated Successfully!"
echo "=========================================="
