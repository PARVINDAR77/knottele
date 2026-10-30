#!/bin/bash
# ==============================================================================
# KNOTELLE INDIA — FAST PRODUCTION HOTFIX & COMPOSER DEPENDENCY RESTORATION
# ==============================================================================
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}====================================================================${NC}"
echo -e "${YELLOW}       UPDATING PRODUCTION .ENV & COMPILING LARAVEL CACHES          ${NC}"
echo -e "${YELLOW}====================================================================${NC}"

cd /var/www/knottele-fullstack

# 1. Pull latest assets and fixes from git
echo -e "\n${YELLOW}[Step 1/6] Pulling latest git updates...${NC}"
git pull origin main

# 2. Write exact production environment variables to backend/.env
echo -e "\n${YELLOW}[Step 2/6] Writing verified production .env...${NC}"
cat << 'EOF' > backend/.env
APP_NAME=KNOTELLE
APP_ENV=production
APP_KEY=base64:VnrUSV6b7OqcSVFwFdOIVNTUJNyqlqYHsrfnKvtOWls=
APP_DEBUG=false
APP_URL=https://knotelleindia.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=knottele
DB_USERNAME=knottele_user
DB_PASSWORD=KnotelleLive2026!

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=nexora@vardaansmartsolutions.com
MAIL_PASSWORD=Cz?f;R5!d
MAIL_ENCRYPTION=ssl

MAIL_FROM_ADDRESS=nexora@vardaansmartsolutions.com
MAIL_FROM_NAME="KNOTELLE"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

RAZORPAY_KEY_ID=rzp_live_Tu2n0eQvOns3qd
RAZORPAY_KEY_SECRET=Zg25hn2EJogstsbOTBucdX6r
EOF

# 3. Point /usr/bin/php to the official Docker PHP (which has PDO & pdo_mysql)
echo -e "\n${YELLOW}[Step 3/6] Linking /usr/local/bin/php to /usr/bin/php...${NC}"
docker exec knotelle-app ln -sf /usr/local/bin/php /usr/bin/php

# 4. Generate autoloader with official PHP runtime
echo -e "\n${YELLOW}[Step 4/6] Generating optimized autoloader & discovering packages...${NC}"
docker exec -w /var/www/html knotelle-app /usr/local/bin/php -d memory_limit=-1 /usr/bin/composer dump-autoload --optimize --no-scripts
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan package:discover --ansi

# 5. Compile Laravel Caches & Migrations
echo -e "\n${YELLOW}[Step 5/6] Compiling Laravel route, config & view caches...${NC}"
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan migrate --force || true
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan optimize:clear
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan config:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan route:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan view:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan storage:link 2>/dev/null || true

# 6. Fix permissions and restart queue worker
echo -e "\n${YELLOW}[Step 6/6] Fixing storage permissions and restarting workers...${NC}"
docker exec -w /var/www/html knotelle-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -w /var/www/html knotelle-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker exec knotelle-app supervisorctl restart laravel-worker:* 2>/dev/null || true

# Verify API output
echo -e "\n${YELLOW}Verifying API responses...${NC}"
MEDIA_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8090/api/customer/media || true)
ORDER_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST http://127.0.0.1:8090/api/customer/payment/razorpay/order -H "Content-Type: application/json" -H "Accept: application/json" -d '{"amount":100}' || true)

echo -e "Media API HTTP Status: ${GREEN}$MEDIA_STATUS${NC}"
echo -e "Razorpay Order API HTTP Status: ${GREEN}$ORDER_STATUS${NC}"

if [ "$MEDIA_STATUS" = "200" ] && [ "$ORDER_STATUS" = "200" ]; then
    echo -e "\n${GREEN}====================================================================${NC}"
    echo -e "${GREEN}   ALL FIXES APPLIED SUCCESSFULLY! CHECKOUT IS NOW FULLY LIVE!      ${NC}"
    echo -e "${GREEN}====================================================================${NC}"
else
    echo -e "\n${YELLOW}Notice: One or more APIs returned status: Media=$MEDIA_STATUS, Order=$ORDER_STATUS${NC}"
    docker exec knotelle-app tail -n 20 /var/www/html/storage/logs/laravel.log 2>/dev/null || true
fi
