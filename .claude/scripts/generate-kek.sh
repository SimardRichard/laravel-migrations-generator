#!/usr/bin/env bash
#
# generate-kek.sh
# Génère une KEK (Key Encryption Key) sécurisée pour CRASSQ.
# Usage : .claude/scripts/generate-kek.sh [--env] [--length=32]
#
# Options :
#   --env       Affiche la ligne à ajouter dans .env
#   --length=N  Longueur de la clé en octets (défaut: 32 = 256 bits)
#
set -euo pipefail

LENGTH=32
FORMAT="raw"

for arg in "$@"; do
    case $arg in
        --env)
            FORMAT="env"
            ;;
        --length=*)
            LENGTH="${arg#*=}"
            ;;
        *)
            echo "Usage: $0 [--env] [--length=32]"
            exit 1
            ;;
    esac
done

# Vérifier que openssl est disponible
if ! command -v openssl &> /dev/null; then
    echo "❌ openssl est requis mais n'est pas installé."
    exit 1
fi

# Générer la clé
RAW_KEY=$(openssl rand -base64 "$LENGTH")

echo ""
echo "🔐 KEK générée (AES-256, ${LENGTH} octets / $((LENGTH * 8)) bits)"
echo ""

if [[ "$FORMAT" == "env" ]]; then
    echo "   Ajoutez cette ligne dans votre .env :"
    echo ""
    echo "   ENCRYPTION_MASTER_KEY=base64:${RAW_KEY}"
    echo ""
else
    echo "   Base64 : ${RAW_KEY}"
    echo "   Format .env : ENCRYPTION_MASTER_KEY=base64:${RAW_KEY}"
    echo ""
fi

echo "   ⚠️  IMPORTANT :"
echo "   - Ne commitez JAMAIS cette clé dans le dépôt"
echo "   - Stockez-la dans un vault sécurisé (HashiCorp Vault, AWS KMS, etc.)"
echo "   - Gardez une copie de secours dans un endroit sûr et séparé"
echo "   - La perte de cette clé rend TOUTES les données inaccessibles"
echo ""
