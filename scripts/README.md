# Scripts Documentation

Utility scripts for WordPress Docker stack management.

## Available Scripts

### `setup-mariadb.sh`
**MariaDB connection test for local development**

Tests MariaDB connection and creates WordPress user/database if needed.

```bash
./scripts/setup-mariadb.sh
```

**Usage**: Local development only - validates database connectivity and setup.

### `wp-cron-external.sh`
**External WordPress Cron runner for production**

Executes WordPress cron via HTTP for all running WordPress containers.

```bash
# 1. Copy to server
scp scripts/wp-cron-external.sh root@server:/usr/local/bin/
chmod +x /usr/local/bin/wp-cron-external.sh

# 2. Add to system crontab (every 3 hours)
sudo crontab -e
0 */3 * * * /usr/local/bin/wp-cron-external.sh >> /var/log/wp-cron.log 2>&1

# 3. Monitor logs
tail -f /var/log/wp-cron.log
```

**How it works:**
- Finds containers with label `wp.cron.job=true`
- Executes `wp-cron.php` via HTTP for each container
- Logs all executions and results
- Compatible with any Docker platform (Dokploy, Coolify, etc.)
