#!/bin/bash
# Script de inicialização do MariaDB para WordPress

# Carrega variáveis do .env
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

PROJECT_NAME=${PROJECT_NAME:-wp-site}
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-dev_root_123}
MYSQL_DATABASE=${MYSQL_DATABASE:-wp-site-local}
MYSQL_USER=${MYSQL_USER:-wp-user-local}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-dev_password_123}

echo "🗄️ Configurando MariaDB para WordPress..."

# Detecta ambiente (local ou produção)
if [ -f /usr/local/bin/wp-stack ]; then
    CONTAINER_SUFFIX=""
else
    CONTAINER_SUFFIX="_local"
fi

# Aguarda MariaDB estar pronto
until docker exec ${PROJECT_NAME}_mariadb${CONTAINER_SUFFIX} mariadb-admin ping -h localhost -u root -p${MYSQL_ROOT_PASSWORD} --silent; do
    echo "Aguardando MariaDB..."
    sleep 2
done

echo "✅ MariaDB está pronto!"

# Cria usuário e banco se não existir
docker exec ${PROJECT_NAME}_mariadb${CONTAINER_SUFFIX} mariadb -u root -p${MYSQL_ROOT_PASSWORD} -e "
CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
"

echo "✅ Usuário e banco WordPress configurados!"
