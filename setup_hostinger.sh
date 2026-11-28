#!/bin/bash
set -e

echo "=========================================================="
echo "🚀 Setting up KNOTELLE on Hostinger Mumbai VPS"
echo "=========================================================="

# 1. Require root privileges
if [ "$(id -u)" -ne 0 ]; then
    echo "❌ This setup script must be run as root or with sudo."
    echo "Run: sudo bash setup_hostinger.sh"
    exit 1
fi

PROJECT_DIR="/var/www/knottele-fullstack"
REPO_URL="https://github.com/PARVINDAR77/knottele.git"

# 2. Update System Packages
echo "📦 Updating OS packages..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    curl \
    git \
    ufw \
    nginx \
    certbot \
    python3-certbot-nginx \
    unzip \
    tar \
    software-properties-common

# 3. Install Docker & Docker Compose if missing
if ! command -v docker &>/dev/null; then
    echo "🐳 Installing Docker..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    sh /tmp/get-docker.sh
    rm -f /tmp/get-docker.sh
    systemctl enable docker
    systemctl start docker
fi

# 4. Clone or Pull Latest Project Code
mkdir -p /var/www
if [ ! -d "$PROJECT_DIR/.git" ]; then
    echo "📥 Cloning project from GitHub..."
    rm -rf "$PROJECT_DIR"
    git clone "$REPO_URL" "$PROJECT_DIR"
else
    echo "🔄 Pulling latest changes from GitHub..."
    cd "$PROJECT_DIR"
    git fetch origin main
    git reset --hard origin/main
fi

cd "$PROJECT_DIR"

# 5. Ensure .env file exists and has correct database/app configuration
if [ ! -f "backend/.env" ]; then
    echo "⚙️ Creating backend/.env configuration..."
    cat << 'EOF' > backend/.env
APP_NAME=KNOTELLE
APP_ENV=production
APP_KEY=base64:VnrUSV6b7OqcSVFwFdOIVNTUJNyqlqYHsrfnKvtOWls=
APP_DEBUG=false
APP_URL=https://knotelleindia.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
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
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=nexora@vardaansmartsolutions.com
MAIL_PASSWORD="Cz?f;R5!d"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=nexora@vardaansmartsolutions.com
MAIL_FROM_NAME="KNOTELLE"

RAZORPAY_KEY_ID=rzp_live_U4i6HxVEFQi0Ly
RAZORPAY_KEY_SECRET=RmpbZYsqsWqkW7kHr2GeSRMQ

DLT_SMS_URI=http://allsmssolution.in/sms-panel/api/http/index.php
DLT_SMS_USERNAME=EMPEROR
DLT_SMS_APIKEY=62CEF-9A446
DLT_SMS_SENDER_ID=TXXTOO
DLT_SMS_TEMPLATE_ID=1607100000000313742
DLT_SMS_ENTITY_ID=1401805350000060162
DLT_SMS_ROUTE=TRANS
DLT_SMS_APIREQUEST=Text
DLT_SMS_FORMAT=JSON
DLT_SMS_VAR3="Team Emperor"
EOF
fi

# Ensure storage directories and permissions
mkdir -p backend/storage/framework/{sessions,views,cache} backend/storage/logs backend/public/storage
chmod -R 777 backend/storage backend/bootstrap/cache backend/public/storage 2>/dev/null || true

# 6. Start Docker Containers (MariaDB & Laravel/Next app on port 8095)
echo "🐳 Building & starting Docker containers..."
docker compose down 2>/dev/null || true
docker compose up -d --build

# Wait for MariaDB to accept connections
echo "⏳ Waiting for Database to be ready..."
for i in {1..30}; do
    if docker exec knotelle-db mysqladmin ping -h localhost -u root -pKnotelleLive2026! --silent 2>/dev/null; then
        echo "✅ Database is online!"
        break
    fi
    sleep 2
done

# 7. Run Database Migrations
echo "🔄 Running database migrations..."
docker exec knotelle-app php artisan migrate --force
docker exec knotelle-app php artisan config:clear 2>/dev/null || true
docker exec knotelle-app php artisan cache:clear 2>/dev/null || true
docker exec knotelle-app php artisan view:clear 2>/dev/null || true
docker exec knotelle-app php artisan route:clear 2>/dev/null || true
docker exec knotelle-app php artisan storage:link 2>/dev/null || true

# 8. Configure Host Nginx Reverse Proxy with Full CSP
echo "🌐 Configuring Host Nginx reverse proxy..."
cat << 'EOF' > /etc/nginx/sites-available/knotelleindia.com
server {
    listen 80;
    listen [::]:80;
    server_name knotelleindia.com www.knotelleindia.com;

    client_max_body_size 256M;

    # Security Headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Permitted-Cross-Domain-Policies "none" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com https://connect.facebook.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https: blob: https://www.facebook.com; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com https://checkout.razorpay.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com https://connect.facebook.net https://www.facebook.com; frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com; object-src 'none'; base-uri 'self';" always;

    # Proxy to Docker container on port 8095
    location / {
        proxy_pass http://127.0.0.1:8095;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }
}
EOF

# Enable Knotelle site and disable default
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/knotelleindia.com /etc/nginx/sites-enabled/knotelleindia.com

nginx -t
systemctl reload nginx

# 9. Setup Free SSL with Certbot
echo "🔒 Requesting SSL Certificate from Let's Encrypt..."
certbot --nginx -d knotelleindia.com -d www.knotelleindia.com --non-interactive --agree-tos -m panjabiparvindar77@gmail.com --redirect || true

echo "=========================================================="
echo "✅ KNOTELLE Deployment on Hostinger Mumbai VPS Complete!"
echo "🌐 Site: https://knotelleindia.com"
echo "=========================================================="
