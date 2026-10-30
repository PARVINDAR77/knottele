#!/bin/bash
# ==============================================================================
# KNOTELLE INDIA — SSL (HTTPS) AUTOMATED SETUP SCRIPT
# Run this on the VPS (95.111.242.173) as root
# ==============================================================================
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}====================================================================${NC}"
echo -e "${YELLOW}           CONFIGURING SECURE SSL (HTTPS) FOR KNOTELLE              ${NC}"
echo -e "${YELLOW}====================================================================${NC}"

# 1. Install Certbot and Nginx plugin if not present
echo -e "\n${YELLOW}[Step 1/5] Checking Certbot installation...${NC}"
if ! command -v certbot &> /dev/null; then
    apt-get update -y
    apt-get install -y certbot python3-certbot-nginx
fi

# 2. Configure Host Nginx configuration for knotelleindia.com
echo -e "\n${YELLOW}[Step 2/5] Setting up Nginx virtual host...${NC}"
mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled

cat <<'EOF' > /etc/nginx/sites-available/knotelleindia.com
server {
    listen 80;
    listen [::]:80;
    server_name knotelleindia.com www.knotelleindia.com;

    client_max_body_size 256M;

    location /.well-known/acme-challenge/ {
        root /var/www/html;
        allow all;
    }

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

ln -sf /etc/nginx/sites-available/knotelleindia.com /etc/nginx/sites-enabled/knotelleindia.com

# Also ensure /etc/nginx/conf.d has it if sites-enabled is not included
if [ -d "/etc/nginx/conf.d" ]; then
    cp /etc/nginx/sites-available/knotelleindia.com /etc/nginx/conf.d/knotelleindia.com.conf
fi

mkdir -p /var/www/html

# Test Nginx syntax
nginx -t
systemctl reload nginx

# 3. Check if www.knotelleindia.com resolves to avoid Certbot failure
echo -e "\n${YELLOW}[Step 3/5] Testing DNS resolution...${NC}"
DOMAINS="-d knotelleindia.com"
if host www.knotelleindia.com &>/dev/null || ping -c 1 -W 2 www.knotelleindia.com &>/dev/null; then
    echo -e "${GREEN}Both knotelleindia.com and www.knotelleindia.com resolve!${NC}"
    DOMAINS="-d knotelleindia.com -d www.knotelleindia.com"
else
    echo -e "${YELLOW}Notice: www.knotelleindia.com does not have a DNS record yet. Certifying knotelleindia.com first.${NC}"
    DOMAINS="-d knotelleindia.com"
fi

# 4. Request and configure SSL certificate via Certbot
echo -e "\n${YELLOW}[Step 4/5] Obtaining Let's Encrypt SSL Certificate...${NC}"
certbot --nginx $DOMAINS --non-interactive --agree-tos --register-unsafely-without-email --redirect --keep-until-expiring

# 5. Reload Nginx and verify
echo -e "\n${YELLOW}[Step 5/5] Reloading Nginx with new SSL certificate...${NC}"
systemctl reload nginx

echo -e "\n${GREEN}====================================================================${NC}"
echo -e "${GREEN}   SUCCESS! SSL CERTIFICATE INSTALLED & HTTPS IS NOW FULLY ACTIVE   ${NC}"
echo -e "${GREEN}====================================================================${NC}"
echo -e "You can now safely open in your browser:"
echo -e "${GREEN}https://knotelleindia.com${NC}"
