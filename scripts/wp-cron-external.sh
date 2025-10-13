#!/bin/bash

# =============================================================================
# WP-CRON RUNNER - Executa WP-Cron via HTTP (sem WP-CLI)
# =============================================================================
# Para instalar no servidor Dokploy:
# 1. Copie este script: scp wp-cron-external.sh root@servidor:/usr/local/bin/
# 2. Configure cron: 0 */3 * * * /usr/local/bin/wp-cron-external.sh >> /var/log/wp-cron.log 2>&1

set -e

# Configurações
LOG_FILE="/var/log/wp-cron-runner.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Função para log
log() {
    echo "[$DATE] $1" | tee -a "$LOG_FILE"
}

log "=== Iniciando execução do WP-Cron externo ==="

# Encontra todos os containers WordPress ativos
CONTAINERS=$(docker ps --filter "label=wp.cron.job=true" --format "{{.Names}}" 2>/dev/null)

if [ -z "$CONTAINERS" ]; then
    log "❌ Nenhum container WordPress encontrado com label 'wp.cron.job=true'"
    log "💡 Certifique-se que containers estão rodando com a label correta"
    exit 0
fi

log "📦 Containers encontrados: $(echo "$CONTAINERS" | wc -l)"

# Contador de sucessos e falhas
SUCCESS_COUNT=0
FAILED_COUNT=0

# Executa wp-cron para cada container via HTTP
for CONTAINER_NAME in $CONTAINERS; do
    # Extrai informações do container
    PROJECT_LABEL=$(docker inspect --format='{{index .Config.Labels "wp.project"}}' "$CONTAINER_NAME" 2>/dev/null)
    
    log "🚀 Executando cron para: $CONTAINER_NAME [Projeto: $PROJECT_LABEL]"
    
    # Método 1: Tentar via curl dentro do container
    if docker exec "$CONTAINER_NAME" curl -s -f http://localhost/wp-cron.php > /dev/null 2>&1; then
        log "✅ Cron executado com sucesso para $CONTAINER_NAME (método interno)"
        ((SUCCESS_COUNT++))
    else
        # Método 2: Tentar via domain externo (se disponível)
        DOMAIN=$(docker inspect --format='{{index .Config.Labels "traefik.http.routers.*.rule"}}' "$CONTAINER_NAME" 2>/dev/null | grep -o 'Host(`[^`]*`)' | cut -d'`' -f2 || echo "")
        
        if [ -n "$DOMAIN" ] && curl -s -f "https://$DOMAIN/wp-cron.php" > /dev/null 2>&1; then
            log "✅ Cron executado com sucesso para $CONTAINER_NAME via $DOMAIN"
            ((SUCCESS_COUNT++))
        else
            log "❌ Erro ao executar cron para $CONTAINER_NAME"
            ((FAILED_COUNT++))
        fi
    fi
done

# Relatório final
log "=== Relatório Final ==="
log "✅ Sucessos: $SUCCESS_COUNT"
log "❌ Falhas: $FAILED_COUNT"
log "📊 Total processado: $((SUCCESS_COUNT + FAILED_COUNT))"

if [ $FAILED_COUNT -gt 0 ]; then
    log "⚠️  Alguns containers tiveram falhas. Verifique os logs acima."
    exit 1
else
    log "🎉 Todos os containers processados com sucesso!"
    exit 0
fi
