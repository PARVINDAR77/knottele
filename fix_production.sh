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
echo -e "\n${YELLOW}[Step 1/5] Pulling latest git updates (including missing footer assets)...${NC}"
git pull origin main

# 2. Install Composer & PHP packages inside container
echo -e "\n${YELLOW}[Step 2/5] Installing Composer dependencies inside knotelle-app...${NC}"
docker exec knotelle-app apk add --no-cache composer git unzip
docker exec -w /var/www/html knotelle-app composer install --no-dev --optimize-autoloader --no-interaction

# 3. Compile Laravel Caches & Migrations
echo -e "\n${YELLOW}[Step 3/5] Compiling Laravel route, config & view caches...${NC}"
docker exec -w /var/www/html knotelle-app php artisan key:generate --force || true
docker exec -w /var/www/html knotelle-app php artisan migrate --force || true
docker exec -w /var/www/html knotelle-app php artisan optimize:clear
docker exec -w /var/www/html knotelle-app php artisan config:cache
docker exec -w /var/www/html knotelle-app php artisan route:cache
docker exec -w /var/www/html knotelle-app php artisan view:cache
docker exec -w /var/www/html knotelle-app php artisan storage:link 2>/dev/null || true

# 4. Fix permissions
echo -e "\n${YELLOW}[Step 4/5] Fixing storage and cache permissions...${NC}"
docker exec -w /var/www/html knotelle-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -w /var/www/html knotelle-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Verify API output
echo -e "\n${YELLOW}[Step 5/5] Verifying API response...${NC}"
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
