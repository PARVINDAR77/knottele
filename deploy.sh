#!/bin/bash
set -e

echo "🚀 Starting KNOTELLE Deployment..."

# 1. Move to project root
cd "$(dirname "$0")"
PROJECT_ROOT="$(pwd)"

# Ensure backend is brought out of maintenance mode even if an unexpected error occurs
trap 'if [ -d "$PROJECT_ROOT/backend" ]; then cd "$PROJECT_ROOT/backend" && php artisan up 2>/dev/null || true; fi' EXIT

# 2. Ensure application is running smoothly (zero downtime deploy)
if [ -d "$PROJECT_ROOT/backend" ]; then
    cd "$PROJECT_ROOT/backend"
    php artisan up 2>/dev/null || true
fi

# 3. Pull latest code from Git
echo "📥 Pulling latest code..."
git fetch origin main
git reset --hard origin/main
rm -rf "$PROJECT_ROOT/backend/public/admin" "$PROJECT_ROOT/backend/public/knottele/admin"

# Only set fallback IP address if APP_URL is not configured with a custom domain (e.g. knotelleindia.com)
if [ -f "$PROJECT_ROOT/backend/.env" ]; then
    if ! grep -qE '^APP_URL=https?://[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}' "$PROJECT_ROOT/backend/.env"; then
        sed -i -E 's|^APP_URL=.*|APP_URL=http://187.127.158.24/knottele|g' "$PROJECT_ROOT/backend/.env"
        sed -i -E 's|^ASSET_URL=.*|ASSET_URL=http://187.127.158.24/knottele|g' "$PROJECT_ROOT/backend/.env" 2>/dev/null || true
    fi
fi

# 4. Install Composer dependencies
if [ -d "$PROJECT_ROOT/backend" ]; then
    echo "📦 Installing Composer dependencies..."
    cd "$PROJECT_ROOT/backend"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 4b. Export all live product & category slugs to frontend
if [ -d "$PROJECT_ROOT/backend" ]; then
    echo "📋 Exporting live database slugs to frontend..."
    cd "$PROJECT_ROOT/backend"
    php artisan tinker --execute="file_put_contents('$PROJECT_ROOT/frontend/src/data/db-slugs.json', json_encode(['products' => \App\Models\Product::pluck('slug')->filter()->values(), 'categories' => \App\Models\Category::pluck('slug')->filter()->values()]));" 2>/dev/null || true
fi

# 5. Build Next.js customer frontend and sync to Laravel public
if [ -d "$PROJECT_ROOT/frontend" ]; then
    echo "🛍️ Building Frontend (Next.js -> Laravel public)..."
    cd "$PROJECT_ROOT/frontend"
    export INTERNAL_API_URL="http://127.0.0.1/knottele/api"
    npm install
    npm run build:laravel
fi

# 6. Build Blade Admin Vite assets
if [ -d "$PROJECT_ROOT/backend" ]; then
    cd "$PROJECT_ROOT/backend"
    if [ -f "package.json" ]; then
        echo "⚙️ Building Admin assets..."
        npm install
        npm run build
    fi

    # Ensure static public/admin directory never shadows Laravel admin routes
    rm -rf public/admin public/knottele/admin

    # Copy icons to /var/www/html if present to prevent root 404s
    if [ -d "/var/www/html" ]; then
        mkdir -p /var/www/html/images/logo 2>/dev/null || true
        cp -f "$PROJECT_ROOT/backend/public/icon.png" /var/www/html/ 2>/dev/null || true
        cp -f "$PROJECT_ROOT/backend/public/favicon.ico" /var/www/html/ 2>/dev/null || true
        cp -f "$PROJECT_ROOT/backend/public/images/logo/Logo_1.png" /var/www/html/images/logo/ 2>/dev/null || true
    fi

    # 7. Database Migrations
    echo "🗄️ Running Migrations..."
    php artisan migrate --force

    # 8. Storage Symlink
    echo "🔗 Linking Storage..."
    php artisan storage:link || true

    # 9. Clear and rebuild Laravel caches
    echo "⚡ Optimizing Laravel Caches..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    # 10. Restart queue workers
    echo "🔄 Restarting Queue..."
    php artisan queue:restart || true

    # 11. Folder permissions & storage/image/video directories
    echo "🔒 Updating permissions..."
    mkdir -p storage/logs public/images/products public/images/hero public/images/categories public/images/footer public/images/reels public/videos/reels public/js/admin
    touch storage/logs/laravel.log && chmod 666 storage/logs/laravel.log || true
    chmod -R 777 storage bootstrap/cache public/images public/videos || true
    chown -R nginx:nginx storage bootstrap/cache public/images public/videos 2>/dev/null || chown -R apache:apache storage bootstrap/cache public/images public/videos 2>/dev/null || chown -R www-data:www-data storage bootstrap/cache public/images public/videos 2>/dev/null || true
    chcon -R -t httpd_sys_rw_content_t storage bootstrap/cache public/images public/videos 2>/dev/null || true

    # Ensure PHP upload limits support video uploads (128M)
    if [ -d "/etc/php" ]; then
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 128M/' /etc/php/*/*/php.ini 2>/dev/null || true
        sed -i 's/^post_max_size = .*/post_max_size = 128M/' /etc/php/*/*/php.ini 2>/dev/null || true
        systemctl reload php*-fpm 2>/dev/null || systemctl restart php*-fpm 2>/dev/null || true
    fi

    # 12. Bring application out of maintenance mode
    php artisan up
fi

# 13. Restart PM2 & Nginx & PHP-FPM
if command -v pm2 >/dev/null 2>&1; then
    echo "🔄 Restarting PM2 processes..."
    pm2 restart all --update-env 2>/dev/null || pm2 reload all --update-env 2>/dev/null || true
fi

if command -v systemctl >/dev/null 2>&1; then
    echo "🔄 Reloading Nginx and PHP-FPM..."
    systemctl reload nginx 2>/dev/null || true
    systemctl reload php-fpm 2>/dev/null || systemctl restart php-fpm 2>/dev/null || true
fi

echo "=========================================="
echo "✅ KNOTELLE Deployment Completed Successfully!"
echo "=========================================="
