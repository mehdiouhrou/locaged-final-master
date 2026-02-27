#!/bin/bash

#===============================================================================
# LocaGed - Offline Deployment Package Creator
# Creates a complete package for air-gapped server deployment
# Run this on your current server at /var/www/locaged-final-master/
#===============================================================================

set -e

PACKAGE_DIR="/tmp/locaged-offline-package"
APP_SOURCE="/var/www/locaged-final-master"

echo "════════════════════════════════════════════════════════"
echo " Creating Offline Deployment Package"
echo "════════════════════════════════════════════════════════"

# Clean and create package directory
rm -rf "$PACKAGE_DIR"
mkdir -p "$PACKAGE_DIR"
cd "$PACKAGE_DIR"

# ============================================================================
# 1. Copy Application Code (WITH vendor and node_modules)
# ============================================================================
echo "[1/6] Copying application code..."
mkdir -p application
sudo rsync -av --progress \
  --exclude 'storage/app/*' \
  --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude '.env' \
  --exclude '.git' \
  "$APP_SOURCE/" \
  application/

# Create storage structure
mkdir -p application/storage/{app/public,framework/{cache,sessions,testing,views},logs}

# ============================================================================
# 2. Download ALL System Packages (.deb files)
# ============================================================================
echo "[2/6] Downloading system packages..."
mkdir -p packages
cd packages

# Update package lists
sudo apt update

# Download packages with all dependencies
echo "  → Downloading PHP 8.2 and extensions..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-curl \
  php8.2-gd php8.2-imagick php8.2-mbstring php8.2-xml php8.2-zip \
  php8.2-bcmath php8.2-intl | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading MySQL..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  mysql-server mysql-client | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading Redis..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  redis-server | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading Nginx..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  nginx | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading Supervisor..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  supervisor | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading Tesseract OCR..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  tesseract-ocr tesseract-ocr-fra tesseract-ocr-ara tesseract-ocr-eng \
  poppler-utils imagemagick ghostscript | grep "^\w" | sort -u) 2>/dev/null || true

echo "  → Downloading Java (for Elasticsearch)..."
apt download $(apt-cache depends --recurse --no-recommends --no-suggests \
  --no-conflicts --no-breaks --no-replaces --no-enhances \
  openjdk-17-jdk openjdk-17-jre | grep "^\w" | sort -u) 2>/dev/null || true

cd "$PACKAGE_DIR"

# ============================================================================
# 3. Download Elasticsearch (separate, as it's from external repo)
# ============================================================================
echo "[3/6] Downloading Elasticsearch..."
mkdir -p elasticsearch
cd elasticsearch

# Download Elasticsearch .deb directly
wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-8.12.0-amd64.deb

cd "$PACKAGE_DIR"

# ============================================================================
# 4. Download Composer installer
# ============================================================================
echo "[4/6] Downloading Composer..."
mkdir -p tools
wget -O tools/composer-installer.php https://getcomposer.org/installer

# ============================================================================
# 5. Copy Configurations
# ============================================================================
echo "[5/6] Copying configurations..."
mkdir -p configs

# Nginx config
if [ -f /etc/nginx/sites-available/locaged ]; then
  sudo cp /etc/nginx/sites-available/locaged configs/nginx-locaged.conf
elif [ -f /etc/nginx/sites-available/locaged-final-master ]; then
  sudo cp /etc/nginx/sites-available/locaged-final-master configs/nginx-locaged.conf
else
  echo "  ⚠ WARNING: Nginx config not found at standard locations"
  echo "  Please manually copy your Nginx config to configs/nginx-locaged.conf"
fi

# Supervisor config
if [ -f /etc/supervisor/conf.d/locaged-worker.conf ]; then
  sudo cp /etc/supervisor/conf.d/locaged-worker.conf configs/supervisor-worker.conf
elif [ -f /etc/supervisor/conf.d/locaged.conf ]; then
  sudo cp /etc/supervisor/conf.d/locaged.conf configs/supervisor-worker.conf
else
  echo "  ⚠ WARNING: Supervisor config not found at standard locations"
fi

# Create .env template
cp application/.env.example configs/env-template 2>/dev/null || echo "APP_NAME=LocaGed" > configs/env-template

# ============================================================================
# 6. Create Offline Installation Script
# ============================================================================
echo "[6/6] Creating installation script..."

cat > install-offline.sh << 'EOFINSTALL'
#!/bin/bash

#===============================================================================
# LocaGed - OFFLINE Installation Script
# For air-gapped servers with NO internet connection
#===============================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

APP_DIR="/var/www/locaged"
PHP_VERSION="8.2"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       LocaGed - Offline Installation                          ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Error: This script must be run as root${NC}"
   exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Install all .deb packages
echo -e "${YELLOW}[1/7] Installing system packages from .deb files...${NC}"
cd "$SCRIPT_DIR/packages"
echo "  This may take several minutes..."
dpkg -i *.deb 2>/dev/null || apt-get -f install -y

# Install Elasticsearch
echo -e "${YELLOW}[2/7] Installing Elasticsearch...${NC}"
cd "$SCRIPT_DIR/elasticsearch"
dpkg -i elasticsearch-*.deb || apt-get -f install -y

# Configure Elasticsearch
cat > /etc/elasticsearch/elasticsearch.yml << 'EOF'
cluster.name: locaged-cluster
node.name: node-1
network.host: 127.0.0.1
http.port: 9200
discovery.type: single-node
xpack.security.enabled: false
EOF

systemctl enable elasticsearch
systemctl start elasticsearch

# Install Composer
echo -e "${YELLOW}[3/7] Installing Composer...${NC}"
php "$SCRIPT_DIR/tools/composer-installer.php" --install-dir=/usr/local/bin --filename=composer

# Deploy application
echo -e "${YELLOW}[4/7] Deploying application...${NC}"
mkdir -p "$APP_DIR"
cp -r "$SCRIPT_DIR/application/"* "$APP_DIR/"

# Set permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# Deploy Nginx config
echo -e "${YELLOW}[5/7] Configuring Nginx...${NC}"
if [ -f "$SCRIPT_DIR/configs/nginx-locaged.conf" ]; then
  cp "$SCRIPT_DIR/configs/nginx-locaged.conf" /etc/nginx/sites-available/locaged
  ln -sf /etc/nginx/sites-available/locaged /etc/nginx/sites-enabled/
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl restart nginx
fi

# Deploy Supervisor config
echo -e "${YELLOW}[6/7] Configuring Supervisor...${NC}"
if [ -f "$SCRIPT_DIR/configs/supervisor-worker.conf" ]; then
  cp "$SCRIPT_DIR/configs/supervisor-worker.conf" /etc/supervisor/conf.d/locaged-worker.conf
  # Update path to correct directory
  sed -i "s|/var/www/locaged-final-master|$APP_DIR|g" /etc/supervisor/conf.d/locaged-worker.conf
  supervisorctl reread
  supervisorctl update
fi

# Configure ImageMagick for PDF processing
sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml 2>/dev/null || true

# Copy environment template
echo -e "${YELLOW}[7/7] Setting up environment...${NC}"
cd "$APP_DIR"
cp "$SCRIPT_DIR/configs/env-template" .env

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                 Installation Complete!                        ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT: Complete these manual steps:${NC}"
echo ""
echo "1. Create MySQL database:"
echo "   mysql -u root -p"
echo "   CREATE DATABASE locaged CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "   CREATE USER 'locaged'@'localhost' IDENTIFIED BY 'your_password';"
echo "   GRANT ALL ON locaged.* TO 'locaged'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo ""
echo "2. Configure .env file:"
echo "   nano $APP_DIR/.env"
echo "   Update: DB_PASSWORD, APP_URL, etc."
echo ""
echo "3. Run Laravel setup:"
echo "   cd $APP_DIR"
echo "   php artisan key:generate"
echo "   php artisan migrate"
echo "   php artisan config:cache"
echo "   php artisan route:cache"
echo "   php artisan view:cache"
echo ""
echo "4. Setup cron job:"
echo "   crontab -e"
echo "   Add: * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "5. Restart services:"
echo "   systemctl restart nginx php${PHP_VERSION}-fpm supervisor"
echo ""

EOFINSTALL

chmod +x install-offline.sh

# Create README
cat > README.md << 'EOFREADME'
# LocaGed - Offline Deployment Package

## Package Contents

- `application/` - Complete Laravel application WITH vendor/ and node_modules/
- `packages/` - All .deb packages for PHP, MySQL, Redis, Nginx, etc.
- `elasticsearch/` - Elasticsearch .deb installer
- `tools/` - Composer installer
- `configs/` - Nginx and Supervisor configurations
- `install-offline.sh` - Automated offline installation script

## System Requirements

- Ubuntu 22.04 LTS (MUST match the version this was packaged on)
- NO internet connection required
- At least 4GB RAM
- 30GB+ disk space

## Installation Steps

1. Transfer this package to client server (USB drive, network copy, etc.)
2. Extract: `tar -xzf locaged-offline-package.tar.gz`
3. Run: `cd locaged-offline-package && sudo bash install-offline.sh`
4. Follow post-installation steps shown by script

## What Gets Installed

- PHP 8.2 + all extensions
- MySQL 8.0
- Redis
- Elasticsearch 8.x
- Tesseract OCR (French, Arabic, English)
- Nginx
- Supervisor
- ImageMagick, Poppler Utils

Everything is installed from bundled .deb packages - NO internet needed!

EOFREADME

echo ""
echo "Creating compressed archive..."
cd /tmp
tar -czf locaged-offline-package.tar.gz locaged-offline-package/

FILE_SIZE=$(du -h locaged-offline-package.tar.gz | cut -f1)

echo ""
echo "════════════════════════════════════════════════════════"
echo " Package Created Successfully!"
echo "════════════════════════════════════════════════════════"
echo ""
echo "Location: /tmp/locaged-offline-package.tar.gz"
echo "Size: $FILE_SIZE"
echo ""
echo "Next steps:"
echo "1. Download this file from your server"
echo "2. Transfer to client server"
echo "3. Extract and run install-offline.sh"
echo ""
