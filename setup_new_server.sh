#!/usr/bin/env bash
# ==============================================================================
# KNOTELLE INDIA - COMPLETE NEW VPS SETUP & PROVISIONING SCRIPT
# Target Server IP: 95.111.242.173
# Automatically installs Docker, MySQL/MariaDB, Nginx, imports DB, and starts KNOTELLE
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}===================================================================${NC}"
echo -e "${BLUE}       KNOTELLE INDIA - NEW SERVER PROVISIONING & DEPLOYMENT       ${NC}"
echo -e "${BLUE}===================================================================${NC}"

# 1. Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    OS="unknown"
fi

echo -e "\n${YELLOW}[Step 1/7] Installing System Prerequisites on ${OS}...${NC}"
if [ "$OS" = "ubuntu" ] || [ "$OS" = "debian" ]; then
    apt-get update -y
    apt-get install -y curl wget git mariadb-server mariadb-client nginx certbot python3-certbot-nginx
    
    # Install Docker if not present
    if ! command -v docker &> /dev/null; then
        echo "Installing Docker..."
        curl -fsSL https://get.docker.com | sh
        systemctl enable --now docker
    fi
elif [ "$OS" = "almalinux" ] || [ "$OS" = "rocky" ] || [ "$OS" = "centos" ] || [ "$OS" = "rhel" ]; then
    dnf install -y epel-release || yum install -y epel-release
    dnf install -y curl wget git mariadb-server mariadb nginx certbot python3-certbot-nginx || yum install -y curl wget git mariadb-server mariadb nginx certbot python3-certbot-nginx
    
    # Install Docker if not present
    if ! command -v docker &> /dev/null; then
        echo "Installing Docker..."
        curl -fsSL https://get.docker.com | sh
        systemctl enable --now docker
    fi
fi

# Ensure docker-compose plugin is available
if ! docker compose version &> /dev/null; then
    echo "Installing Docker Compose plugin..."
    mkdir -p /usr/local/lib/docker/cli-plugins
    curl -SL "https://github.com/docker/compose/releases/download/v2.24.5/docker-compose-linux-$(uname -m)" -o /usr/local/lib/docker/cli-plugins/docker-compose
    chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
fi

echo -e "\n${YELLOW}[Step 2/7] Configuring MySQL/MariaDB with skip-name-resolve...${NC}"
systemctl enable mariadb || systemctl enable mysql || true
systemctl start mariadb || systemctl start mysql || true

CNF_FILE=""
if [ -f /etc/my.cnf ]; then
    CNF_FILE="/etc/my.cnf"
elif [ -f /etc/mysql/my.cnf ]; then
    CNF_FILE="/etc/mysql/my.cnf"
elif [ -f /etc/my.cnf.d/server.cnf ]; then
    CNF_FILE="/etc/my.cnf.d/server.cnf"
elif [ -f /etc/mysql/mariadb.conf.d/50-server.cnf ]; then
    CNF_FILE="/etc/mysql/mariadb.conf.d/50-server.cnf"
fi

if [ -n "$CNF_FILE" ]; then
    if ! grep -q "skip-name-resolve" "$CNF_FILE"; then
        if grep -q "\[mysqld\]" "$CNF_FILE"; then
            sed -i '/\[mysqld\]/a skip-name-resolve' "$CNF_FILE"
        else
            echo -e "\n[mysqld]\nskip-name-resolve" >> "$CNF_FILE"
        fi
        echo "Added skip-name-resolve to $CNF_FILE"
        systemctl restart mariadb 2>/dev/null || systemctl restart mysql 2>/dev/null || true
    fi
fi

# Create Database and User
echo "Setting up knottele database and user permissions..."
DB_PASS="KnotelleLive2026!"
mysql -e "
CREATE DATABASE IF NOT EXISTS knottele CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'knottele_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'knottele_user'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'knottele_user'@'%' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'localhost';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'%';
FLUSH PRIVILEGES;
" 2>/dev/null || mysql -u root -e "
CREATE DATABASE IF NOT EXISTS knottele CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'knottele_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'knottele_user'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'knottele_user'@'%' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'knottele_user'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'localhost';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON knottele.* TO 'knottele_user'@'%';
FLUSH PRIVILEGES;
" 2>/dev/null || true

# Import backup if available
if [ -f "/root/knottele_full_backup.sql" ]; then
    echo "Found /root/knottele_full_backup.sql! Importing database..."
    mysql knottele < /root/knottele_full_backup.sql
    echo -e "${GREEN}Database imported successfully.${NC}"
elif [ -f "/root/backup.sql" ]; then
    echo "Found /root/backup.sql! Importing database..."
    mysql knottele < /root/backup.sql
    echo -e "${GREEN}Database imported successfully.${NC}"
else
    echo -e "${YELLOW}Notice: No /root/knottele_full_backup.sql found. Fresh database initialized.${NC}"
fi

echo -e "\n${YELLOW}[Step 3/7] Cloning or Updating KNOTELLE Repository...${NC}"
mkdir -p /var/www
if [ ! -d "/var/www/knottele-fullstack" ]; then
    cd /var/www
    git clone https://github.com/PARVINDAR77/knottele.git knottele-fullstack
fi

cd /var/www/knottele-fullstack
git fetch origin main
git checkout main
git pull origin main

echo -e "\n${YELLOW}[Step 4/7] Configuring Environment (.env) & Permissions...${NC}"
if [ ! -f "backend/.env" ]; then
    cp backend/.env.example backend/.env
fi

# Ensure database config in .env matches
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' backend/.env
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' backend/.env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' backend/.env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=knottele/' backend/.env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=knottele_user/' backend/.env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=KnotelleLive2026!/' backend/.env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' backend/.env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' backend/.env
sed -i 's|^APP_URL=.*|APP_URL=https://knotelleindia.com|' backend/.env

chmod -R 775 backend/storage backend/bootstrap/cache

echo -e "\n${YELLOW}[Step 5/7] Building and Launching knotelle-app Container...${NC}"
docker compose down 2>/dev/null || true
docker compose up -d --build

sleep 5

echo -e "\n${YELLOW}[Step 6/7] Compiling Laravel Production Caches & Linking Storage...${NC}"
docker exec knotelle-app php artisan migrate --force || true
docker exec knotelle-app php artisan optimize:clear
docker exec knotelle-app php artisan config:cache
docker exec knotelle-app php artisan route:cache
docker exec knotelle-app php artisan view:cache
docker exec knotelle-app php artisan storage:link 2>/dev/null || true

echo -e "\n${YELLOW}[Step 7/7] Configuring Host Nginx Reverse Proxy (Port 8090)...${NC}"
NGINX_CONF_DIR="/etc/nginx/conf.d"
if [ ! -d "$NGINX_CONF_DIR" ]; then
    NGINX_CONF_DIR="/etc/nginx/sites-available"
fi

cat <<'EOF' > /tmp/knotelle_proxy.conf
server {
    listen 80;
    listen [::]:80;
    server_name knotelleindia.com www.knotelleindia.com 95.111.242.173;

    client_max_body_size 256M;

    location / {
        proxy_pass http://127.0.0.1:8090;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 60s;
    }
}
EOF

if [ -d "/etc/nginx/conf.d" ]; then
    cp /tmp/knotelle_proxy.conf /etc/nginx/conf.d/knotelleindia.com.conf
fi
if [ -d "/etc/nginx/sites-available" ]; then
    cp /tmp/knotelle_proxy.conf /etc/nginx/sites-available/knotelleindia.com.conf
    ln -sf /etc/nginx/sites-available/knotelleindia.com.conf /etc/nginx/sites-enabled/knotelleindia.com.conf 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
fi

systemctl enable nginx || true
systemctl restart nginx || nginx -s reload || true

echo -e "\n${GREEN}===================================================================${NC}"
echo -e "${GREEN}    NEW SERVER SETUP COMPLETED! KNOTELLE IS LIVE ON 95.111.242.173 ${NC}"
echo -e "${GREEN}===================================================================${NC}"
echo "You can now test http://95.111.242.173 directly in your browser."
echo "Once DNS is pointed to 95.111.242.173, run this command for free HTTPS SSL:"
echo "certbot --nginx -d knotelleindia.com -d www.knotelleindia.com"
