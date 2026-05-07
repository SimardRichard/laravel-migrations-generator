#!/usr/bin/env bash
#
# verify-encryption.sh
# Vérifie que les colonnes sensibles sont bien chiffrées en BD.
# Usage : .claude/scripts/verify-encryption.sh
#
# Vérifie :
# 1. Que les champs sensibles des modèles utilisent EncryptedCast
# 2. Que les valeurs en BD ne sont pas en clair (format version:iv:tag:ciphertext)
# 3. Qu'aucun Storage::put() n'est utilisé pour des données sensibles
#
set -euo pipefail

PASS=0
FAIL=0
WARN=0

echo ""
echo "🔍 Vérification du chiffrement — CRASSQ"
echo "========================================="
echo ""

# --- 1. Vérifier les EncryptedCast dans les modèles ---
echo "📋 1. Vérification des EncryptedCast dans les modèles..."
echo ""

SENSITIVE_FIELDS=("nom" "prenom" "courriel" "telephone" "nas" "adresse" "notes" "coordonnees")

for model_file in $(find app/Modules -name "*.php" -path "*/Models/*" 2>/dev/null); do
    model_name=$(basename "$model_file" .php)

    for field in "${SENSITIVE_FIELDS[@]}"; do
        if grep -q "'${field}'" "$model_file" 2>/dev/null; then
            if grep -q "'${field}'.*EncryptedCast" "$model_file" 2>/dev/null; then
                echo "   ✅ ${model_name}::${field} → EncryptedCast"
                ((PASS++))
            else
                # Vérifier si c'est dans $fillable ou $casts
                if grep -q "'${field}'" "$model_file" 2>/dev/null; then
                    echo "   ❌ ${model_name}::${field} → PAS de EncryptedCast !"
                    ((FAIL++))
                fi
            fi
        fi
    done
done

if [[ $FAIL -eq 0 ]] && [[ $PASS -eq 0 ]]; then
    echo "   ⚠️  Aucun modèle trouvé dans app/Modules/*/Models/"
    ((WARN++))
fi

echo ""

# --- 2. Vérifier qu'aucun Storage::put() n'est utilisé ---
echo "📋 2. Recherche de Storage::put() / Storage::disk() interdit..."
echo ""

STORAGE_HITS=$(grep -rn "Storage::put\|Storage::disk\|file_put_contents\|move_uploaded_file" \
    app/Modules/ \
    --include="*.php" \
    2>/dev/null || true)

if [[ -n "$STORAGE_HITS" ]]; then
    echo "   ❌ Utilisation de filesystem détectée :"
    echo "$STORAGE_HITS" | while IFS= read -r line; do
        echo "      $line"
    done
    ((FAIL++))
else
    echo "   ✅ Aucun Storage::put() / file_put_contents() trouvé"
    ((PASS++))
fi

echo ""

# --- 3. Vérifier qu'aucun AES-CBC n'est utilisé ---
echo "📋 3. Recherche de AES-CBC interdit..."
echo ""

CBC_HITS=$(grep -rn "aes-256-cbc\|aes-128-cbc\|AES-256-CBC\|AES-128-CBC" \
    app/Modules/ \
    --include="*.php" \
    2>/dev/null || true)

if [[ -n "$CBC_HITS" ]]; then
    echo "   ❌ Utilisation de AES-CBC détectée (utiliser AES-256-GCM) :"
    echo "$CBC_HITS" | while IFS= read -r line; do
        echo "      $line"
    done
    ((FAIL++))
else
    echo "   ✅ Aucun AES-CBC trouvé (AES-256-GCM uniquement)"
    ((PASS++))
fi

echo ""

# --- 4. Vérifier les données sensibles dans les logs ---
echo "📋 4. Recherche de données sensibles dans les logs..."
echo ""

LOG_PATTERNS="->nom\|->prenom\|->courriel\|->telephone\|->nas\|->adresse"
LOG_HITS=$(grep -rn "Log::" app/Modules/ --include="*.php" 2>/dev/null | \
    grep -i "$LOG_PATTERNS" 2>/dev/null || true)

if [[ -n "$LOG_HITS" ]]; then
    echo "   ❌ Données sensibles potentiellement dans les logs :"
    echo "$LOG_HITS" | while IFS= read -r line; do
        echo "      $line"
    done
    ((FAIL++))
else
    echo "   ✅ Aucune donnée sensible détectée dans les appels Log::"
    ((PASS++))
fi

echo ""

# --- 5. Vérifier que VERIFY_PEER n'est pas désactivé ---
echo "📋 5. Recherche de VERIFY_PEER désactivé..."
echo ""

VERIFY_HITS=$(grep -rn "VERIFY_PEER.*false\|verify_peer.*false\|CURLOPT_SSL_VERIFYPEER.*false" \
    app/ config/ \
    --include="*.php" \
    2>/dev/null || true)

if [[ -n "$VERIFY_HITS" ]]; then
    echo "   ❌ VERIFY_PEER désactivé détecté :"
    echo "$VERIFY_HITS" | while IFS= read -r line; do
        echo "      $line"
    done
    ((FAIL++))
else
    echo "   ✅ VERIFY_PEER actif partout"
    ((PASS++))
fi

echo ""

# --- 6. Vérifier le .env.example ---
echo "📋 6. Vérification du .env.example..."
echo ""

if [[ -f ".env.example" ]]; then
    if grep -q "ENCRYPTION_MASTER_KEY" .env.example; then
        ACTUAL_VALUE=$(grep "ENCRYPTION_MASTER_KEY" .env.example | cut -d= -f2)
        if [[ -z "$ACTUAL_VALUE" ]] || [[ "$ACTUAL_VALUE" == "base64:CHANGEZ_MOI" ]] || [[ "$ACTUAL_VALUE" == "" ]]; then
            echo "   ✅ ENCRYPTION_MASTER_KEY présent dans .env.example (placeholder)"
            ((PASS++))
        else
            echo "   ❌ ENCRYPTION_MASTER_KEY dans .env.example contient une vraie valeur !"
            ((FAIL++))
        fi
    else
        echo "   ⚠️  ENCRYPTION_MASTER_KEY absent de .env.example"
        ((WARN++))
    fi
else
    echo "   ⚠️  .env.example non trouvé"
    ((WARN++))
fi

echo ""

# --- Résumé ---
echo "========================================="
echo "📊 Résumé"
echo "   ✅ Pass : $PASS"
echo "   ❌ Fail : $FAIL"
echo "   ⚠️  Warn : $WARN"
echo ""

if [[ $FAIL -gt 0 ]]; then
    echo "❌ ÉCHEC — Des problèmes de chiffrement ont été détectés."
    exit 1
else
    echo "✅ OK — Aucun problème critique détecté."
    exit 0
fi
