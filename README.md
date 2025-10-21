# 🚀 WP Docker TurboStack

TurboStack is a modern, production-ready WordPress stack designed for maximum performance, security and scalability. It combines NGINX, Rocket-NGINX, MariaDB, Redis, and custom adjustments into a single, seamless Docker environment.

With deep integration, TurboStack delivers instant static caching, edge delivery, robust security, and zero-hassle management. It is tuned for real-world scale, reliability, and developer experience.

---

## 📦 Stack Components

| Component        | Version   | Role/Notes                                 |
|------------------|-----------|--------------------------------------------|
| WordPress (FPM)  | latest    | Main app, PHP-FPM, custom configs/plugins  |
| NGINX            | 1.28      | Web server, static/cache, security         |
| Rocket-NGINX     | 3.1.1     | Advanced static cache for WP Rocket        |
| MariaDB          | 11.8      | Database, persistent storage               |
| Redis            | 8.2       | Object cache for WordPress                 |
| Adminer          | latest    | DB management UI                           |
| File Browser     | latest    | Web file manager                           |

---

## 🎯 Target Deployment

* **Platform-Optimized:** Designed for deployment on Docker-native platforms like **Dokploy** and **Coolify**, integrating smoothly with their Traefik-based environments.
* **Cloudflare:** Built to be fully proxied by **Cloudflare**, leveraging its CDN, WAF, and Zero Trust services for essential security and performance.

---

## 🥇 Core Advantages

* **Multi-Layer Caching:** Cloudflare (Edge), NGINX + Rocket-NGINX (Static HTML), WP Rocket (Page), and Redis (Object).
* **Extreme Performance:** NGINX/Rocket-NGINX serve cached pages instantly, bypassing PHP execution.
* **Hardened Security:** Tuned NGINX configs (headers, rate limiting) plus Cloudflare's DDoS/WAF proxy.
* **Smart Automation:** Fast setup, auto-init scripts for MariaDB, and a reliable external cron system.
* **Docker-Ready:** Includes essential Must-Use plugins (like `wordpress-docker-bridge.php`) to fix loopbacks and ensure correct NGINX/Cloudflare integration.
* **Extra Tools:** Adminer and File Browser to manage files and database.

---

## 🚦 Quick Start

### Local Development
1. **Clone the repository**
   ```zsh
   git clone <repo-url>
   cd wp-docker-turbostack
   ```
2. **Start local stack**
   ```zsh
   docker-compose -f docker-compose.local.yml up -d
   ./scripts/setup-mariadb.sh  # Configure database
   ```

### Production Deploy
1. **Upload to your Docker platform** (Dokploy, Coolify, etc.)
2. **Configure environment variables** (see below)
3. **Start stack**
   ```zsh
   docker-compose up -d
   ```

---

## 🌐 Access URLs

**Local Development:**
- WordPress: http://localhost (Nginx with FastCGI Cache)
- Adminer: http://localhost:8081
- File Browser: http://localhost:8082 (check logs for password)

**Production:**
- WordPress: https://yourdomain.com
- Adminer: https://adminer.yourdomain.com (BasicAuth required)
- Files: https://files.yourdomain.com (BasicAuth required)

---

## ⏰ WordPress Cron

WordPress cron is **DISABLED** for performance (doesn't run on every visit).

# Add to system crontab (every 3 hours)
0 */3 * * * /usr/local/bin/wp-cron-external.sh >> /var/log/wp-cron.log 2>&1
```

---

## ⚙️ Environment Configuration

Set these in your `.env` file:

```env
# Database Settings (CHANGE ALL PASSWORDS)
MYSQL_ROOT_PASSWORD=your-root-password-here
MYSQL_DATABASE=wp-site
MYSQL_USER=wp-user
MYSQL_PASSWORD=your-password-here

# Extra settings
TZ=your-timezone
```

---

## 📝 Usage & Best Practices

- **`.env` Setup:** Copy `.env.example` to `.env` and fill in all secrets. Never commit `.env` to version control.
- **Cloudflare Integration:** This stack is designed to run with Cloudflare. Always enable the **Cloudflare Proxy** (orange-cloud) for performance, caching, and to activate the **WAF** for threat protection.
- **Secure Access (Zero Trust):** Use **Cloudflare Zero Trust** to protect all sensitive login pages. This is the recommended method for securing `wp-admin`, **Adminer**, and **File Browser** instead of exposing them to the public internet or relying on Basic Auth.
- **Local vs. Production:** Use `docker-compose.local.yml` for local development only (e.g., `docker-compose -f docker-compose.yml -f docker-compose.local.yml up`). Never use local overrides in production.
- **Must-Use Plugins:** The auto-mounted plugins (`turbostack-optimizations.php`, `wordpress-docker-bridge.php`) are critical for performance, security, and a correct Docker/Cloudflare/NGINX integration.
- **Volumes:** All persistent data (DB, uploads, configs) is stored in named Docker volumes. Ensure you have a regular backup strategy for them.

---

## 🧮 Resources adjustments

This stack is pre-configured with optimized resource limits to enable deploying multiple WordPress sites on a single server. Adjust resource values in docker-compose.yml to match your host's capacity.

---

## ⚖️ Trademarks & Disclaimer

All product names, logos, and brands are property of their respective owners.

All company, product and service names used in this project are for identification and compatibility purposes only. Use of these names, logos, and brands does not imply endorsement or official partnership.

This project is not affiliated with, endorsed by, or sponsored by Cloudflare, Inc., Dokploy, Coolify, Automattic (WordPress), WP Rocket, or any other trademark holder mentioned.

---

**TurboStack: The fastest, most secure, and most scalable way to run WordPress on Docker.**