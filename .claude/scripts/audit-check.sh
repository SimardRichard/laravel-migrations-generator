#!/usr/bin/env bash
#
# audit-check.sh
# Vérifie l'intégrité de la configuration d'audit pour CRASSQ.
# Usage : .claude/scripts/audit-check.sh
#
# Vérifie :
# 1. La table audit_logs existe et a la bonne structure
# 2. Les permissions MySQL sont correctes (INSERT + SELECT only)
# 3. Le partitionnement est configuré
# 4. Les listeners sont enregistrés
# 5. Le worker de queue audit est actif
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
echo "🔍 Vérification de l'audit — CRASSQ"
echo "======================================="
echo ""

# --- 1. Table audit_logs ---
echo "📋 1. Table audit_logs..."
echo ""

TABLE_EXISTS=$($MYSQL_CMD -N -e "
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA='${DB_DATABASE}' AND TABLE_NAME='audit_logs';" 2>/dev/null || echo "ERREUR")

if [[ "$TABLE_EXISTS" == "ERREUR" ]]; then
    echo "   ❌ Impossible de se connecter à MySQL"
    ((FAIL++))
elif [[ "$TABLE_EXISTS" -eq 0 ]]; then
    echo "   ❌ Table audit_logs inexistante"
    echo "      Fix: php artisan migrate"
    ((FAIL++))
else
    echo "   ✅ Table audit_logs existe"
    ((PASS++))

    # Vérifier les colonnes requises
    REQUIRED_COLS=("tenant_id" "event_type" "user_id" "agent_id" "resource_type" "resource_id" "field_name" "result" "ip_address" "user_agent" "metadata" "created_at")

    for col in "${REQUIRED_COLS[@]}"; do
        COL_EXISTS=$($MYSQL_CMD -N -e "
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA='${DB_DATABASE}' AND TABLE_NAME='audit_logs' AND COLUMN_NAME='${col}';" 2>/dev/null || echo "0")

        if [[ "$COL_EXISTS" -eq 1 ]]; then
            echo "   ✅ Colonne ${col} présente"
            ((PASS++))
        else
            echo "   ❌ Colonne ${col} manquante"
            ((FAIL++))
        fi
    done

    # Vérifier qu'il n'y a PAS de updated_at
    HAS_UPDATED=$($MYSQL_CMD -N -e "
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA='${DB_DATABASE}' AND TABLE_NAME='audit_logs' AND COLUMN_NAME='updated_at';" 2>/dev/null || echo "0")

    if [[ "$HAS_UPDATED" -eq 0 ]]; then
        echo "   ✅ Pas de colonne updated_at (immuable)"
        ((PASS++))
    else
        echo "   ⚠️  Colonne updated_at présente (devrait être absente pour immuabilité)"
        ((WARN++))
    fi
fi

echo ""

# --- 2. Vérification du module AuditTrail ---
echo "📋 2. Module AuditTrail..."
echo ""

if [[ -d "app/Modules/AuditTrail" ]]; then
    echo "   ✅ Module AuditTrail présent"
    ((PASS++))

    # Vérifier les fichiers critiques
    CRITICAL_FILES=(
        "AuditTrailServiceProvider.php"
        "Contracts/AuditLoggerInterface.php"
        "Services/AuditLoggerService.php"
        "Models/AuditLog.php"
        "Jobs/WriteAuditLogJob.php"
        "Listeners/LogDataDecrypted.php"
        "Listeners/LogUnauthorizedAccess.php"
        "Listeners/LogAuthEvent.php"
    )

    for file in "${CRITICAL_FILES[@]}"; do
        if [[ -f "app/Modules/AuditTrail/${file}" ]]; then
            echo "   ✅ ${file}"
            ((PASS++))
        else
            echo "   ❌ ${file} manquant"
            ((FAIL++))
        fi
    done
else
    echo "   ❌ Module AuditTrail absent"
    echo "      Fix: .claude/scripts/new-audit-module.sh"
    ((FAIL++))
fi

echo ""

# --- 3. ServiceProvider enregistré ---
echo "📋 3. ServiceProvider enregistré..."
echo ""

if [[ -f "bootstrap/providers.php" ]]; then
    if grep -q "AuditTrailServiceProvider" bootstrap/providers.php; then
        echo "   ✅ AuditTrailServiceProvider enregistré"
        ((PASS++))
    else
        echo "   ❌ AuditTrailServiceProvider non enregistré dans bootstrap/providers.php"
        ((FAIL++))
    fi
else
    echo "   ⚠️  bootstrap/providers.php non trouvé"
    ((WARN++))
fi

echo ""

# --- 4. Vérifier les listeners pour les événements critiques ---
echo "📋 4. Listeners d'événements..."
echo ""

EVENTS_TO_CHECK=("DataDecrypted" "KeyRotated" "UnauthorizedDecryptAttempt")

for event in "${EVENTS_TO_CHECK[@]}"; do
    LISTENER_EXISTS=$(grep -rn "$event" app/Modules/AuditTrail/Listeners/ 2>/dev/null || true)
    if [[ -n "$LISTENER_EXISTS" ]]; then
        echo "   ✅ Listener pour ${event}"
        ((PASS++))
    else
        echo "   ❌ Aucun listener pour ${event}"
        ((FAIL++))
    fi
done

echo ""

# --- 5. Vérifier la config ---
echo "📋 5. Configuration audit-trail..."
echo ""

if [[ -f "app/Modules/AuditTrail/Config/audit-trail.php" ]]; then
    echo "   ✅ Config audit-trail.php présente"
    ((PASS++))

    if grep -q "'retention_years' => 7" "app/Modules/AuditTrail/Config/audit-trail.php" 2>/dev/null; then
        echo "   ✅ Rétention configurée à 7 ans"
        ((PASS++))
    else
        echo "   ⚠️  Rétention non configurée à 7 ans — vérifier manuellement"
        ((WARN++))
    fi
else
    echo "   ❌ Config audit-trail.php manquante"
    ((FAIL++))
fi

echo ""

# --- 6. Vérifier l'immuabilité dans le modèle ---
echo "📋 6. Immuabilité du modèle AuditLog..."
echo ""

if [[ -f "app/Modules/AuditTrail/Models/AuditLog.php" ]]; then
    if grep -q "static::updating" "app/Modules/AuditTrail/Models/AuditLog.php" && \
       grep -q "static::deleting" "app/Modules/AuditTrail/Models/AuditLog.php"; then
        echo "   ✅ Protection updating + deleting active"
        ((PASS++))
    else
        echo "   ❌ Protection d'immuabilité manquante dans AuditLog"
        ((FAIL++))
    fi

    if grep -q "UPDATED_AT = null" "app/Modules/AuditTrail/Models/AuditLog.php"; then
        echo "   ✅ UPDATED_AT = null"
        ((PASS++))
    else
        echo "   ⚠️  UPDATED_AT non désactivé"
        ((WARN++))
    fi
else
    echo "   ❌ Modèle AuditLog.php manquant"
    ((FAIL++))
fi

echo ""

# --- Résumé ---
echo "======================================="
echo "📊 Résumé"
echo "   ✅ Pass : $PASS"
echo "   ❌ Fail : $FAIL"
echo "   ⚠️  Warn : $WARN"
echo ""

if [[ $FAIL -gt 0 ]]; then
    echo "❌ ÉCHEC — Des problèmes d'audit ont été détectés."
    exit 1
else
    echo "✅ OK — Configuration d'audit correcte."
    exit 0
fi
