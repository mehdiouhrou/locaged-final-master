# Quick Start: Offline Deployment Package

## 📋 Summary

You need to package your LocaGed application from `/var/www/locaged-final-master/` for deployment to a **client server with NO INTERNET ACCESS**.

---

## 🎯 What You Have Now

**[create-offline-package.sh](file:///c:/Users/faisa/OneDrive/Desktop/M%20C/Latest/locaged-final-master/create-offline-package.sh)** - Complete packaging script

---

## 🚀 Step-by-Step Instructions

### On Your Current Server

1. **Upload the script to your server:**
```bash
# Upload create-offline-package.sh to your server
scp create-offline-package.sh user@your-server:/tmp/
```

2. **SSH into your server and run it:**
```bash
ssh user@your-server

# Make it executable
chmod +x /tmp/create-offline-package.sh

# Run the script (takes 10-30 minutes)
sudo /tmp/create-offline-package.sh
```

3. **Download the package:**
```bash
# The script creates: /tmp/locaged-offline-package.tar.gz (~1.8-2GB)
# Download it to your local machine
scp user@your-server:/tmp/locaged-offline-package.tar.gz ~/Desktop/
```

### On Client Server (Air-Gapped, NO Internet)

1. **Transfer the package** (USB drive, network copy, etc.)

2. **Extract and install:**
```bash
# Extract
tar -xzf locaged-offline-package.tar.gz
cd locaged-offline-package

# Run offline installer
sudo bash install-offline.sh
```

3. **Follow post-installation steps** (shown by the script):
   - Create MySQL database
   - Configure `.env` file
   - Run `php artisan migrate`
   - Setup cron job
   - Restart services

---

## 📦 What Gets Packaged

| Component | Details |
|-----------|---------|
| **Application Code** | Complete Laravel app WITH vendor/ and node_modules/ |
| **System Packages** | All .deb files for PHP 8.2, MySQL, Redis, Nginx, Supervisor |
| **Typesense** | Full installer |
| **Tesseract OCR** | With French, Arabic, English language packs |
| **Configurations** | Nginx and Supervisor configs from your server |
| **Tools** | Composer installer |
| **Total Size** | ~1.8-2 GB |

---

## ✅ What Gets Installed on Client

The offline installer script installs:
- ✅ PHP 8.2 + 10 extensions (imagick, gd, mysql, redis, etc.)
- ✅ MySQL 8.0
- ✅ Redis
- ✅ Typesense
- ✅ Tesseract OCR (3 languages)
- ✅ Nginx
- ✅ Supervisor
- ✅ ImageMagick + Poppler Utils
- ✅ Application deployed to `/var/www/locaged`

**NO INTERNET REQUIRED!**

---

## ⚠️ Important Notes

- Package is Ubuntu 22.04 specific - client server MUST use same version
- Package includes ALL dependencies pre-downloaded as .deb files
- Client will need to manually configure database and `.env` file
- Estimated time: 10-30 minutes to create package, 15-20 minutes to install

---

## 📞 Quick Commands

**Create package:**
```bash
sudo /tmp/create-offline-package.sh
```

**Download package:**
```bash
scp user@server:/tmp/locaged-offline-package.tar.gz ~/Desktop/
```

**Install on client:**
```bash
tar -xzf locaged-offline-package.tar.gz
cd locaged-offline-package
sudo bash install-offline.sh
```

---

## 📚 Documentation Files

- **[create-offline-package.sh](file:///c:/Users/faisa/OneDrive/Desktop/M%20C/Latest/locaged-final-master/create-offline-package.sh)** - Packaging script (run on your server)
- **[offline_deployment_guide.md](file:///C:/Users/faisa/.gemini/antigravity/brain/95f5d599-645e-40b8-af79-b05a5e531fd7/offline_deployment_guide.md)** - Detailed guide

---

**Ready to create your offline package? Upload `create-offline-package.sh` to your server and run it!**
