# 🚀 WordPress Docker TurboStack

Production-ready WordPress Docker stack with multi-layer caching (Varnish + Redis), optimized for high performance and designed for deployment platforms like Dokploy, Coolify and similar.

## ⚡ Components

| Service | Version | Function |
|---------|---------|----------|
| **Varnish** | latest | HTTP Cache Layer |
| **Nginx** | 1.28-alpine | Web Server + PHP-FPM |
| **WordPress** | fpm-alpine | CMS Core |
| **Redis** | 8.2.2-alpine | Object Cache |
| **MariaDB** | 11.8 | Database |
| **Adminer** | latest | Database Management |
| **File Browser** | latest | File Management |

## 🎯 Key Features

- ✅ **Multi-layer Caching**: Varnish (HTTP) + Redis (Objects)
- ✅ **WP Rocket Compatible**: Automatic purging and optimized headers
- ✅ **Production Ready**: Based on WordPress.org best practices
- ✅ **Traefik Integration**: SSL automation with any cert resolver
- ✅ **Security Hardened**: HSTS, CSP, XSS Protection, file blocking
- ✅ **Zero Maintenance**: Single setup, automatic operation

## 🚀 Quick Setup

### 1. Clone and Configure
```bash
git clone <repo> my-client-site
cd my-client-site
cp .env.example .env
# Edit .env: PROJECT_NAME, DOMAIN, and all passwords
```

### 2. Local Development
```bash
docker-compose -f docker-compose.local.yml up -d
./scripts/setup-mariadb.sh  # Configure database
```

### 3. Production Deploy
Upload to your Docker platform (Dokploy, Coolify, etc.) and configure environment variables.

## 🌐 Access URLs

**Local Development:**
- WordPress: http://localhost (Varnish) | http://localhost:8080 (direct Nginx)
- Adminer: http://localhost:8081
- File Browser: http://localhost:8082 (check logs for password)

**Production:**
- WordPress: https://yourdomain.com
- Adminer: https://adminer.yourdomain.com (BasicAuth required)
- Files: https://files.yourdomain.com (BasicAuth required)

## ⚙️ Environment Configuration

```env
# Project
PROJECT_NAME=mysite
DOMAIN=yourdomain.com

# Database (CHANGE ALL PASSWORDS)
MYSQL_ROOT_PASSWORD=secure_root_password
MYSQL_DATABASE=wordpress
MYSQL_USER=wordpress
MYSQL_PASSWORD=secure_wp_password

# Admin Tools Auth (generate: htpasswd -nbB admin password)
ADMINER_AUTH=admin:$2y$10$hash...
FILES_AUTH=admin:$2y$10$hash...
```

### Generate Auth Hash
```bash
docker run --rm httpd:2.4-alpine htpasswd -nbB admin yourpassword
```

## 🎯 Technical Optimizations

### **Varnish (HTTP Cache)**
- **WP Rocket Compatible**: Automatic purging with `X-Purge-Method: regex` headers
- **Query String Normalization**: `std.querysort()` for better cache efficiency
- **Static Files**: 1-day cache for CSS/JS/images
- **Mobile Detection**: Separate cache hash for mobile/desktop
- **Debug Headers**: `X-Varnish-Cache: HIT/MISS` and `X-Cache-Hits`

### **Nginx (Web Server)**  
- **WordPress.org Compliant**: Configuration based on official documentation
- **Security Headers**: HSTS, CSP, XSS Protection, X-Frame-Options
- **Static Files**: 1-year expires + `Cache-Control: immutable`  
- **PHP-FPM Optimized**: Tuned buffers and timeouts
- **WordPress Security**: Block `.php` in uploads and hidden files

### **Performance Stack**
- **Redis Object Cache**: Database queries cached in memory
- **PHP OPcache**: Bytecode caching enabled  
- **MariaDB Tuned**: Optimized buffers and query cache
- **Gzip Compression**: Automatic text compression

## 📁 Project Structure

```
wp-docker-turbostack/
├── docker-compose.yml          # Production (Traefik integration)
├── docker-compose.local.yml    # Local development  
├── .env.example               # Configuration template
├── nginx/                     # Optimized Nginx configs
├── varnish/
│   └── default.vcl           # Varnish config (WP Rocket ready)
├── wordpress/
│   ├── wp-config-optimizations.php  # WordPress optimization settings
│   └── [other PHP configs]    # Optimized PHP-FPM configs
├── scripts/                  # Utility scripts
│   ├── wp-cron-external.sh   # External WP-Cron runner
│   └── setup-mariadb.sh      # MariaDB setup (local only)
└── filebrowser.json         # File Browser configuration
```

## ⏰ WordPress Cron

WordPress cron is **DISABLED** for performance (doesn't run on every visit).

### Server Setup:
```bash
# Copy script to server
scp scripts/wp-cron-external.sh root@server:/usr/local/bin/
chmod +x /usr/local/bin/wp-cron-external.sh

# Add to system crontab (every 3 hours)
0 */3 * * * /usr/local/bin/wp-cron-external.sh >> /var/log/wp-cron.log 2>&1
```

See `scripts/README.md` for detailed script documentation.

## � WordPress Optimizations

The stack includes `wordpress/wp-config-optimizations.php` with performance and security settings:

- **Redis Object Cache**: Automatic Redis integration
- **Disabled WP-Cron**: Uses external cron for better performance  
- **Security Hardening**: Disabled file editing, forced SSL admin
- **Performance**: Optimized memory limits, reduced post revisions
- **Varnish Compatibility**: Proper proxy and SSL detection

**Usage**: Include in your `wp-config.php`:
```php
require_once(__DIR__ . '/wp-config-optimizations.php');
```

## 🔒 Security Features

- ✅ Nginx security headers (XSS, Clickjacking protection)
- ✅ Blocked sensitive files (.htaccess, .log, etc.)
- ✅ Protected wp-admin and wp-login endpoints  
- ✅ Disabled xmlrpc.php for security
- ✅ BasicAuth protection for admin tools
- ✅ Disabled WP-Cron (performance improvement)

## 🎯 Plugin Compatibility

**Optimized for WP Rocket + Cloudflare**: Automatic Varnish and CloudFlare integration with purging support and performance capabilities.

**Redis Object Cache**: Install [Redis Object Cache](https://wordpress.org/plugins/redis-cache/) plugin to enable object caching with Redis.

**Compatible with other cache plugins** that support Varnish and cloudflare integration.

---

**Production-ready WordPress stack with enterprise-grade performance! 🚀**
