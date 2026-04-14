# LocaGed Deployment - Quick Start Guide

## 📦 Creating the Deployment Package

You have a **PowerShell script** ready to create the deployment package for you:

```powershell
# Run this command in PowerShell
.\create-package.ps1
```

This script will:
- ✅ Copy all necessary application files
- ✅ Exclude `vendor/` and `node_modules/` (will be installed fresh on server)
- ✅ Exclude `.env` file (contains sensitive data)
- ✅ Create proper `storage/` directory structure
- ✅ Create a ZIP file: `locaged-package.zip` in the parent directory

**Output**: `c:\Users\faisa\OneDrive\Desktop\M C\Latest\locaged-package.zip`

---

## 🖥️ What the Client Server Needs

### System Requirements

| Component | Version | Purpose |
|-----------|---------|---------|
| Ubuntu | 22.04 LTS | Operating System |
| PHP | 8.2+ | Runtime |
| MySQL | 8.0+ | Database |
| Redis | 6.0+ | Cache & Queues |
| Typesense | 0.25+ | Search Engine |
| Tesseract OCR | 4.1+ | OCR Processing |
| Nginx | 1.18+ | Web Server |
| Supervisor | 4.2+ | Queue Workers |

### Key Dependencies

**Tesseract OCR Languages:**
- French (`tesseract-ocr-fra`)
- Arabic (`tesseract-ocr-ara`)
- English (`tesseract-ocr-eng`)

**PHP Extensions:**
```
php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl php8.2-gd 
php8.2-imagick php8.2-mbstring php8.2-xml php8.2-zip 
php8.2-bcmath php8.2-intl
```

**Image Processing:**
- ImageMagick (for PDF to image conversion)
- Poppler Utils (for PDF processing)

---

## 🚀 Installation on Client Server

### Automated Installation (Recommended)

```bash
# 1. Upload the package to server
scp locaged-package.zip user@server:/tmp/

# 2. On the server, extract
sudo unzip /tmp/locaged-package.zip -d /var/www/locaged

# 3. Run the automated installer
cd /var/www/locaged
sudo bash install.sh
```

The `install.sh` script automatically installs **ALL** required software and configures the server.

### What install.sh Does

- ✅ Installs PHP 8.2 + all extensions
- ✅ Installs MySQL, Redis, Typesense
- ✅ Installs Tesseract OCR with language packs
- ✅ Installs Nginx and configures virtual host
- ✅ Installs Supervisor for queue workers
- ✅ Runs `composer install` (PHP dependencies)
- ✅ Runs `php artisan migrate` (database setup)
- ✅ Sets up Laravel scheduler cron job
- ✅ Configures proper file permissions

---

## ⚙️ Post-Installation Configuration

### 1. Database Setup

```bash
sudo mysql -u root -p

CREATE DATABASE locaged CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'locaged_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON locaged.* TO 'locaged_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Configure .env File

```bash
sudo nano /var/www/locaged/.env
```

**Critical settings:**

```env
APP_DEBUG=false                    # MUST be false in production
APP_URL=https://your-domain.com

DB_DATABASE=locaged
DB_USERNAME=locaged_user
DB_PASSWORD=STRONG_PASSWORD

SESSION_DRIVER=redis               # Use Redis for production
CACHE_STORE=redis
QUEUE_CONNECTION=redis

SCOUT_DRIVER=typesense
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108

# Configure SMTP for email
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
```

### 3. Build Frontend Assets

```bash
cd /var/www/locaged
npm install
npm run build
```

### 4. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Application loads without errors: `curl http://localhost`
- [ ] Database connection works
- [ ] Redis is running: `redis-cli ping`
- [ ] Typesense is running: `curl http://localhost:8108/health`
- [ ] Queue workers are active: `sudo supervisorctl status`
- [ ] Cron job is configured: `crontab -l`
- [ ] Tesseract languages installed: `tesseract --list-langs`
- [ ] Upload and OCR a test PDF
- [ ] Email sending works

---

## 📋 Key Features That Need Testing

### 1. OCR Processing
- Upload a PDF document
- Verify OCR text extraction works for French, Arabic, English

### 2. Full-Text Search
- Upload multiple documents
- Test Typesense search functionality

### 3. Queue Workers
- Verify background jobs are processed (document uploads, OCR)
- Check: `sudo supervisorctl status`

### 4. Scheduled Tasks
- Daily backups (runs at 02:00)
- Document expiration checks
- Backup cleanup

---

## 🔧 Troubleshooting

### Queue Workers Not Running
```bash
sudo supervisorctl restart locaged-worker:*
```

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/locaged
sudo chmod -R 775 /var/www/locaged/storage
sudo chmod -R 775 /var/www/locaged/bootstrap/cache
```

### Check Logs
```bash
# Laravel logs
tail -f /var/www/locaged/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log

# Queue worker logs
sudo tail -f /var/www/locaged/storage/logs/worker.log
```

---

## 📚 Documentation Files

- **[README.md](README.md)** - Full project documentation
- **[install.sh](install.sh)** - Automated installation script
- **[restore.sh](restore.sh)** - Backup restoration script
- **.env.example** - Environment configuration template

---

## 🎯 Quick Command Reference

```bash
# Restart services
sudo systemctl restart nginx php8.2-fpm redis-server typesense supervisor

# Clear caches
php artisan cache:clear && php artisan config:clear

# Rebuild caches
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Check queue workers
sudo supervisorctl status

# Restart queue workers
sudo supervisorctl restart locaged-worker:*

# Run manual backup
php artisan backup:run

# Import to Typesense
php artisan scout:import "App\Models\DocumentVersion"
```

---

## 🔐 Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password set
- [ ] SSL certificate installed (HTTPS)
- [ ] Firewall configured (allow only 80, 443, 22)
- [ ] Regular backups scheduled
- [ ] `.env` file permissions restricted (600)

---

**Ready to Deploy?**

1. ✅ Run `.\create-package.ps1` on your local machine
2. ✅ Transfer `locaged-package.zip` to client server
3. ✅ Extract and run `sudo bash install.sh`
4. ✅ Configure `.env` file
5. ✅ Run verification checklist

**For detailed instructions, see the full deployment guide.**
