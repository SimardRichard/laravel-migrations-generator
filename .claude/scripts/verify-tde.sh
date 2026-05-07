#!/usr/bin/env bash
#
# verify-tde.sh
# Vérifie que MySQL TDE (Transparent Data Encryption) est actif.
# Usage : .claude/scripts/verify-tde.sh
#
# Vérifie :
# 1. Le plugin keyring est chargé
# 2. Les tables sensibles ont ENCRYPTION='Y'
# 3. Les connexions SSL sont actives
#
# Prérequis : mysql CLI configuré (via .my.cnf ou variables d'env)
#
set -euo pipefail

# Charger les variables d'environnement Laravel si disponible
if [[ -f ".env" ]]; then
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d= -f2)
    DB_PORT=$(grep "^DB_PORT=" .env | cut -d= -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-crassq}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

MYSQL_CMD="mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME}"
if [[ -n "$DB_PASSWORD" ]]; then
    MYSQL_CMD="${MYSQL_CMD} -p${DB_PASSWORD}"
fi

PASS=0
FAIL=0
WARN=0

echo ""
echo "🔍 Vérification MySQL TDE — CRASSQ"
echo "======================================"
echo ""

# --- 1. Vérifier le plugin keyring ---
echo "📋 1. Plugin keyring..."
echo ""

KEYRING_STATUS=$($MYSQL_CMD -N -e "
    SELECT PLUGIN_NAME, PLUGIN_STATUS
    FROM INFORMATION_SCHEMA.PLUGINS
    WHERE PLUGIN_NAME LIKE 'keyring%'
    ORDER BY PLUGIN_NAME;" 2>/dev/null || echo "ERREUR")

if [[ "$KEYRING_STATUS" == "ERREUR" ]]; then
    echo "   ❌ Impossible de se connecter à MySQL"
    ((FAIL++))
elif [[ -z "$KEYRING_STATUS" ]]; then
    echo "   ❌ Aucun plugin keyring chargé — TDE impossible"
    ((FAIL++))
else
    echo "   ✅ Plugin(s) keyring détecté(s) :"
    echo "$KEYRING_STATUS" | while IFS=$'\t' read -r name status; do
        echo "      ${name} → ${status}"
    done
    ((PASS++))
fi

echo ""

# --- 2. Vérifier le chiffrement des tables ---
echo "📋 2. Chiffrement des tables sensibles..."
echo ""

TABLES_TO_CHECK=("agents" "agent_keys" "encrypted_blobs" "key_rotation_logs" "audit_logs")

for table in "${TABLES_TO_CHECK[@]}"; do
    ENCRYPTION=$($MYSQL_CMD -N -e "
        SELECT CREATE_OPTIONS
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA='${DB_DATABASE}' AND TABLE_NAME='${table}';" 2>/dev/null || echo "ERREUR")

    if [[ "$ENCRYPTION" == "ERREUR" ]]; then
        echo "   ⚠️  ${table} → impossible de vérifier"
        ((WARN++))
    elif [[ -z "$ENCRYPTION" ]]; then
        echo "   ⚠️  ${table} → table inexistante"
        ((WARN++))
    elif echo "$ENCRYPTION" | grep -qi "ENCRYPTION='Y'\|ENCRYPTION=Y"; then
        echo "   ✅ ${table} → TDE actif"
        ((PASS++))
    else
        echo "   ❌ ${table} → TDE INACTIF (CREATE_OPTIONS: ${ENCRYPTION})"
        echo "      Fix: ALTER TABLE ${table} ENCRYPTION='Y';"
        ((FAIL++))
    fi
done

echo ""

# --- 3. Vérifier les connexions SSL ---
echo "📋 3. Connexions SSL..."
echo ""

SSL_STATUS=$($MYSQL_CMD -N -e "SHOW STATUS LIKE 'Ssl_cipher';" 2>/dev/null || echo "ERREUR")

if [[ "$SSL_STATUS" == "ERREUR" ]]; then
    echo "   ⚠️  Impossible de vérifier le SSL"
    ((WARN++))
elif echo "$SSL_STATUS" | grep -q "Ssl_cipher"; then
    CIPHER=$(echo "$SSL_STATUS" | awk '{print $2}')
    if [[ -z "$CIPHER" ]]; then
        echo "   ❌ Connexion MySQL NON chiffrée (pas de SSL)"
        ((FAIL++))
    else
        echo "   ✅ Connexion SSL active (cipher: ${CIPHER})"
        ((PASS++))
    fi
fi

echo ""

# --- 4. Vérifier les REQUIRE SSL sur les utilisateurs applicatifs ---
echo "📋 4. REQUIRE SSL sur l'utilisateur applicatif..."
echo ""

SSL_REQUIRED=$($MYSQL_CMD -N -e "
    SELECT ssl_type
    FROM mysql.user
    WHERE User='${DB_USERNAME}' AND Host='%';" 2>/dev/null || echo "ERREUR")

if [[ "$SSL_REQUIRED" == "ERREUR" ]]; then
    echo "   ⚠️  Impossible de vérifier (accès mysql.user requis)"
    ((WARN++))
elif [[ "$SSL_REQUIRED" == "ANY" ]] || [[ "$SSL_REQUIRED" == "X509" ]]; then
    echo "   ✅ REQUIRE SSL actif pour l'utilisateur ${DB_USERNAME}"
    ((PASS++))
elif [[ -z "$SSL_REQUIRED" ]]; then
    echo "   ❌ Aucun REQUIRE SSL sur l'utilisateur ${DB_USERNAME}"
    echo "      Fix: ALTER USER '${DB_USERNAME}'@'%' REQUIRE SSL;"
    ((FAIL++))
else
    echo "   ⚠️  ssl_type: ${SSL_REQUIRED}"
    ((WARN++))
fi

echo ""

# --- 5. Vérifier la version TLS ---
echo "📋 5. Version TLS..."
echo ""

TLS_VERSION=$($MYSQL_CMD -N -e "SHOW STATUS LIKE 'Ssl_version';" 2>/dev/null || echo "ERREUR")

if [[ "$TLS_VERSION" == "ERREUR" ]]; then
    echo "   ⚠️  Impossible de vérifier"
    ((WARN++))
else
    VERSION=$(echo "$TLS_VERSION" | awk '{print $2}')
    if [[ "$VERSION" == "TLSv1.3" ]]; then
        echo "   ✅ TLS 1.3"
        ((PASS++))
    elif [[ "$VERSION" == "TLSv1.2" ]]; then
        echo "   ⚠️  TLS 1.2 (recommandé : TLS 1.3)"
        ((WARN++))
    else
        echo "   ❌ Version TLS insuffisante : ${VERSION}"
        ((FAIL++))
    fi
fi

echo ""

# --- Résumé ---
echo "======================================"
echo "📊 Résumé"
echo "   ✅ Pass : $PASS"
echo "   ❌ Fail : $FAIL"
echo "   ⚠️  Warn : $WARN"
echo ""

if [[ $FAIL -gt 0 ]]; then
    echo "❌ ÉCHEC — Des problèmes TDE/SSL ont été détectés."
    exit 1
else
    echo "✅ OK — MySQL TDE et SSL correctement configurés."
    exit 0
fi
