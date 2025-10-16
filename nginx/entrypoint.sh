#!/bin/sh

# Entrypoint script para Nginx - Gera .htpasswd automaticamente
# Se as variáveis NGINX_AUTH_USER e NGINX_AUTH_PASS estiverem definidas

echo "🚀 Iniciando Nginx..."

# Verifica se as variáveis de auth estão definidas
if [ -n "$NGINX_AUTH_USER" ] && [ -n "$NGINX_AUTH_PASS" ]; then
    echo "🔐 Configurando autenticação HTTP..."
    
    # Gera hash da senha
    PASSWORD_HASH=$(echo "$NGINX_AUTH_PASS" | openssl passwd -apr1 -stdin)
    
    # Cria arquivo .htpasswd
    echo "$NGINX_AUTH_USER:$PASSWORD_HASH" > /etc/nginx/.htpasswd
    
    echo "✅ Autenticação HTTP configurada para usuário: $NGINX_AUTH_USER"
    echo "💡 Para ativar, descomente as seções em nginx/default.conf"
else
    echo "ℹ️  Variáveis NGINX_AUTH_USER/NGINX_AUTH_PASS não definidas"
    echo "💡 Defina no .env para ativar autenticação HTTP extra"
fi

# Inicia nginx normalmente
echo "▶️  Iniciando servidor Nginx..."
exec nginx -g "daemon off;"
