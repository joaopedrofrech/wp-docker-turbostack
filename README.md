# 🚀 WP Docker TurboStack

TurboStack is a modern, production-ready WordPress stack designed for developers and teams who demand maximum performance, security, and automation. It combines the best open-source technologies—NGINX, Rocket-NGINX, Cloudflare, MariaDB, Redis, and custom scripts—into a single, seamless Docker environment.

With deep integration between all components, TurboStack delivers instant static caching, edge delivery, robust security, and zero-hassle management. Every detail is tuned for real-world scale, reliability, and developer experience.

---

## 📦 Stack Components

| Component        | Version   | Role/Notes                                 |
|------------------|-----------|--------------------------------------------|
| WordPress (FPM)  | latest    | Main app, PHP-FPM, custom configs/plugins  |
| NGINX            | 1.28      | Web server, static/cache, security         |
| Rocket-NGINX     | 3.1.1     | Advanced static cache for WP Rocket        |
| MariaDB          | 11.8      | Database, persistent storage               |
| Redis            | 8.2       | Object cache for WordPress                 |
| Adminer          | latest    | DB management UI (protected)               |
| File Browser     | latest    | Web file manager (protected)               |

---

## 🥇 Advantages

**Performance**
- Multi-layer cache: Cloudflare (edge), NGINX + Rocket-NGINX (static HTML), WP Rocket (page), Redis (object)
- NGINX and Rocket-NGINX serve cached pages instantly, bypassing PHP for max speed
- Cloudflare proxy and HTTP/3 for global, low-latency delivery
- Optimized MariaDB and Redis for fast queries and persistent storage

**Security**
- Hardened NGINX configs, strict security headers, rate limiting
- Cloudflare DDoS protection, proxy, and SSL/TLS
- Docker isolation for all services
- Adminer and File Browser protected with authentication and best practices

**Automation & Reliability**
- One-command setup for full stack
- Custom scripts for MariaDB auto-setup and external WP cron (real cron reliability)
- Must-use plugins for Docker/Nginx/Cloudflare integration and internal request handling
- All configs production-ready, but easily extensible for local development

---

## ⚡ Scripts & WP Cron

- `scripts/wp-cron-external.sh`: Runs WordPress cron jobs externally via HTTP, ensuring scheduled tasks always run (even with full static cache). Recommended for production—just add to your system crontab.
- `scripts/setup-mariadb.sh`: Automatically tests and initializes MariaDB, creates WordPress DB/user if needed. No manual DB setup required. For local test only.
- Both scripts are designed for automation, reliability, and compatibility with any Docker platform.

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

**Server Setup:**
```sh
# Copy script to server
scp scripts/wp-cron-external.sh root@server:/usr/local/bin/
chmod +x /usr/local/bin/wp-cron-external.sh

# Add to system crontab (every 3 hours)
0 */3 * * * /usr/local/bin/wp-cron-external.sh >> /var/log/wp-cron.log 2>&1
```
See `scripts/README.md` for detailed script documentation.

---

## ⚙️ Environment Configuration

Set these in your `.env` file:

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

**Generate Auth Hash:**
```sh
docker run --rm httpd:2.4-alpine htpasswd -nbB admin yourpassword
```

---

## 📝 Usage & Best Practices

- **.env setup:** Copy `.env.example` to `.env` and fill in all secrets, DB credentials, project/domain names. Never commit your real `.env` to version control.
- **Domain config:** Set `DOMAIN` and `PROJECT_NAME` in `.env` for correct routing. NGINX and Traefik use these for all service URLs and SSL.
- **Compose local:** For local development, use `docker-compose -f docker-compose.yml -f docker-compose.local.yml up` to enable local-only features, mounts, or debugging. Never mix local overrides in production.
- **Traefik/Cloudflare:** The stack is ready for Traefik as reverse proxy, with compose labels for HTTPS, domain routing, and Cloudflare DNS challenge for SSL.
- **Authentication:** Adminer and File Browser are protected with HTTP Basic Auth. Set `ADMINER_AUTH` and `FILES_AUTH` in `.env` for secure access.
- **Plugins:** Must-use plugins (`turbostack-optimizations.php`, `wordpress-docker-bridge.php`) are auto-mounted for performance, security, and Docker/Cloudflare/NGINX integration.
- **Volumes:** All persistent data (DB, cache, uploads, configs) is stored in named Docker volumes. Review and back up as needed.
- **Production tips:** Pin image tags for critical environments, review all environment variables and volumes before deploy, and always use Cloudflare proxy for best security/performance.

---

**TurboStack: The fastest, most secure, and most scalable way to run WordPress on Docker.**