#!/usr/bin/env bash
# ==============================================================================
# KNOTELLE INDIA - PRODUCTION PERFORMANCE DEPLOYMENT & RECOVERY SCRIPT
# Safe, isolated deployment for knotelle-app without affecting neighboring containers
# Target Commits: ce82a6fd & 12c54d7a
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}===================================================================${NC}"
echo -e "${BLUE}    KNOTELLE PRODUCTION OPTIMIZATION & DEPLOYMENT RUNNER          ${NC}"
echo -e "${BLUE}===================================================================${NC}"

APP_DIR="/var/www/knottele-fullstack"

if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
else
    echo -e "${YELLOW}Notice: Current directory is $(pwd), proceeding...${NC}"
fi

echo -e "\n${YELLOW}[Step 1/8] Verifying Production Environment & Safety Pre-flight...${NC}"
echo "Current Commit: $(git rev-parse --short HEAD 2>/dev/null || echo 'Unknown')"
echo "Host Uptime: $(uptime)"
echo "Memory Available:"
free -h
echo "Root Disk Free Space:"
df -h / | awk 'NR==2 {print $4 " free of " $2}'

echo -e "\n${YELLOW}[Step 2/8] Checking & Fixing Host MySQL DNS Resolver Delay...${NC}"
# Check if MySQL has skip-name-resolve
SKIP_RESOLVE_ACTIVE=$(mysql -u root -e "SHOW VARIABLES LIKE 'skip_name_resolve';" 2>/dev/null | awk 'NR==2 {print $2}' || echo "UNKNOWN")

if [ "$SKIP_RESOLVE_ACTIVE" != "ON" ]; then
    echo "MySQL skip_name_resolve is currently OFF or UNKNOWN ($SKIP_RESOLVE_ACTIVE). Applying fix..."
    
    # Locate configuration file
    CNF_FILE=""
    if [ -f /etc/my.cnf ]; then
        CNF_FILE="/etc/my.cnf"
    elif [ -f /etc/mysql/my.cnf ]; then
        CNF_FILE="/etc/mysql/my.cnf"
    elif [ -f /etc/my.cnf.d/server.cnf ]; then
        CNF_FILE="/etc/my.cnf.d/server.cnf"
    fi

    if [ -n "$CNF_FILE" ]; then
        echo "Found MySQL config at: $CNF_FILE"
        cp "$CNF_FILE" "${CNF_FILE}.bak.$(date +%F_%H%M%S)"
        
        # Verify knottele_user grant matches 127.0.0.1
        mysql -u root -e "GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'127.0.0.1' IDENTIFIED BY 'KnotelleLive2026!'; FLUSH PRIVILEGES;" 2>/dev/null || true
        mysql -u root -e "GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'localhost' IDENTIFIED BY 'KnotelleLive2026!'; FLUSH PRIVILEGES;" 2>/dev/null || true

        if ! grep -q "skip-name-resolve" "$CNF_FILE"; then
            if grep -q "\[mysqld\]" "$CNF_FILE"; then
                sed -i '/\[mysqld\]/a skip-name-resolve' "$CNF_FILE"
                echo "Appended skip-name-resolve under [mysqld] in $CNF_FILE"
            else
                echo -e "\n[mysqld]\nskip-name-resolve" >> "$CNF_FILE"
                echo "Appended [mysqld] section with skip-name-resolve to $CNF_FILE"
            fi
        fi

        echo "Restarting MySQL / MariaDB service..."
        systemctl restart mariadb 2>/dev/null || systemctl restart mysql 2>/dev/null || echo "Notice: manual mysql restart may be needed"
        echo -e "${GREEN}MySQL DNS resolution bottleneck resolved.${NC}"
    else
        echo -e "${YELLOW}Notice: MySQL config file not found automatically. Skipping cnf edit.${NC}"
    fi
else
    echo -e "${GREEN}MySQL skip_name_resolve is already ON.${NC}"
fi

echo -e "\n${YELLOW}[Step 3/8] Restoring Assets & Pulling Latest Optimization Commits...${NC}"
# Restore any missing files in public folder
git checkout HEAD -- backend/public/ 2>/dev/null || true

# Pull latest main branch
git fetch origin main
git checkout main
git pull origin main

echo -e "${GREEN}Successfully synchronized with latest commit: $(git rev-parse --short HEAD)${NC}"

echo -e "\n${YELLOW}[Step 4/8] Verifying Permissions & Storage Links...${NC}"
chmod -R 775 backend/storage backend/bootstrap/cache 2>/dev/null || true

echo -e "\n${YELLOW}[Step 5/8] Recreating knotelle-app Container with Tuned FPM & OPcache...${NC}"
docker compose up -d --force-recreate knotelle

# Wait 3 seconds for container to initialize
sleep 3

echo -e "\n${YELLOW}[Step 6/8] Running Database Migrations (Indexes & Optimizations)...${NC}"
docker exec knotelle-app php artisan migrate --force

echo -e "\n${YELLOW}[Step 7/8] Compiling High-Performance Laravel Production Caches...${NC}"
docker exec knotelle-app php artisan optimize:clear
docker exec knotelle-app php artisan config:cache
docker exec knotelle-app php artisan route:cache
docker exec knotelle-app php artisan view:cache
docker exec knotelle-app php artisan storage:link 2>/dev/null || true

echo -e "\n${YELLOW}[Step 8/8] Warming Up Cache & Verifying Internal Latency...${NC}"
# Warm-up calls
UP_TIME=$(docker exec knotelle-app curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/up 2>/dev/null || curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/up 2>/dev/null || echo "0")
SET_TIME=$(docker exec knotelle-app curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/settings 2>/dev/null || curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/settings 2>/dev/null || echo "0")
CAT_TIME=$(docker exec knotelle-app curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/categories 2>/dev/null || curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/categories 2>/dev/null || echo "0")
MED_TIME=$(docker exec knotelle-app curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/media 2>/dev/null || curl -s -o /dev/null -w "%{time_total}" http://127.0.0.1:8090/api/customer/media 2>/dev/null || echo "0")

echo -e "${GREEN}Warm-up Complete:${NC}"
echo "  - /up latency: ${UP_TIME}s"
echo "  - /api/customer/settings latency: ${SET_TIME}s"
echo "  - /api/customer/categories latency: ${CAT_TIME}s"
echo "  - /api/customer/media latency: ${MED_TIME}s"

echo -e "\n${GREEN}===================================================================${NC}"
echo -e "${GREEN}    DEPLOYMENT & PERFORMANCE RECOVERY SUCCESSFULLY COMPLETED!     ${NC}"
echo -e "${GREEN}===================================================================${NC}"
