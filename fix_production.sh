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
echo -e "${YELLOW}       FIXING COMPOSER AUTOLOAD & RESTORING MISSING ASSETS          ${NC}"
echo -e "${YELLOW}====================================================================${NC}"

cd /var/www/knottele-fullstack

# 1. Pull latest assets and fixes from git
echo -e "\n${YELLOW}[Step 1/5] Pulling latest git updates...${NC}"
git pull origin main

# 2. Point /usr/bin/php to the official Docker PHP (which has PDO & pdo_mysql)
echo -e "\n${YELLOW}[Step 2/5] Linking /usr/local/bin/php to /usr/bin/php...${NC}"
docker exec knotelle-app ln -sf /usr/local/bin/php /usr/bin/php

# Ensure composer runs using the correct PHP runtime with all extensions
echo -e "\n${YELLOW}[Step 3/5] Generating optimized autoloader & package discovery...${NC}"
docker exec -w /var/www/html knotelle-app /usr/local/bin/php -d memory_limit=-1 /usr/bin/composer dump-autoload --optimize --no-scripts
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan package:discover --ansi

# 4. Compile Laravel Caches & Migrations
echo -e "\n${YELLOW}[Step 4/5] Compiling Laravel route, config & view caches...${NC}"
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan key:generate --force || true
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan migrate --force || true
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan optimize:clear
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan config:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan route:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan view:cache
docker exec -w /var/www/html knotelle-app /usr/local/bin/php artisan storage:link 2>/dev/null || true

# 5. Fix permissions and restart queue worker
echo -e "\n${YELLOW}[Step 5/5] Fixing storage permissions and restarting workers...${NC}"
docker exec -w /var/www/html knotelle-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -w /var/www/html knotelle-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker exec knotelle-app supervisorctl restart laravel-worker:* 2>/dev/null || true

# Verify API output
echo -e "\n${YELLOW}Verifying API response...${NC}"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8090/api/media/homepage || true)
echo -e "Internal API HTTP Status: ${GREEN}$STATUS${NC}"

if [ "$STATUS" = "200" ]; then
    echo -e "\n${GREEN}====================================================================${NC}"
    echo -e "${GREEN}   ALL FIXES APPLIED SUCCESSFULLY! APIS & ASSETS ARE NOW WORKING!   ${NC}"
    echo -e "${GREEN}====================================================================${NC}"
else
    echo -e "\n${RED}API returned status $STATUS. Checking logs...${NC}"
    docker exec knotelle-app tail -n 20 /var/www/html/storage/logs/laravel.log 2>/dev/null || true
fi
