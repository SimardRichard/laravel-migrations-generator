#!/usr/bin/env bash
#
# new-encryption-module.sh
# Scaffolding complet du module Encryption pour CRASSQ.
# Usage : .claude/scripts/new-encryption-module.sh
#
# Crée la structure complète du module avec tous les fichiers de base :
# contrats, services, modèles, casts, commandes, événements, migrations, tests.
#
set -euo pipefail

MODULE_PATH="app/Modules/Encryption"

if [[ -d "$MODULE_PATH" ]]; then
    echo "❌ Le module Encryption existe déjà dans $MODULE_PATH"
    echo "   Supprimez-le d'abord si vous voulez regénérer."
    exit 1
fi

echo "🔐 Création du module Encryption..."

# --- Arborescence ---
mkdir -p "$MODULE_PATH"/{Contracts,Services,Models,Casts,Middleware,Console,Events,Listeners,Exceptions,Migrations,Config,Routes,Tests/{Unit,Feature}}

# --- Contrats ---
cat > "$MODULE_PATH/Contracts/EncryptionServiceInterface.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Contracts;

interface EncryptionServiceInterface
{
    /**
     * Chiffre des données avec AES-256-GCM.
     *
     * @param string $plaintext Données en clair
     * @param string $key       Clé de chiffrement (DEK)
     * @param string $aad       Additional Authenticated Data
     * @return array{ciphertext: string, iv: string, tag: string}
     */
    public function encrypt(string $plaintext, string $key, string $aad = ''): array;

    /**
     * Déchiffre des données chiffrées avec AES-256-GCM.
     *
     * @throws \App\Modules\Encryption\Exceptions\DecryptionFailedException
     */
    public function decrypt(string $ciphertext, string $key, string $iv, string $tag, string $aad = ''): string;
}
PHP

cat > "$MODULE_PATH/Contracts/KeyManagerInterface.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Contracts;

interface KeyManagerInterface
{
    /** Récupère la DEK déchiffrée pour un agent. */
    public function getAgentKey(int $agentId): string;

    /** Crée une nouvelle DEK pour un agent. */
    public function createAgentKey(int $agentId): void;

    /** Rotation : génère une nouvelle DEK, retourne l'ancienne version. */
    public function rotateAgentKey(int $agentId): int;

    /** Récupère une version spécifique de la DEK. */
    public function getAgentKeyByVersion(int $agentId, int $version): string;

    /** Rotation de la KEK : re-chiffre toutes les DEK. */
    public function rotateKek(string $newKek): void;
}
PHP

cat > "$MODULE_PATH/Contracts/BlobStorageInterface.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Contracts;

use Illuminate\Support\Collection;

interface BlobStorageInterface
{
    /**
     * Stocke un fichier chiffré en BD.
     *
     * @return int ID du blob créé
     */
    public function store(int $agentId, string $content, string $filename, string $mimeType): int;

    /**
     * Récupère et déchiffre un fichier.
     *
     * @return array{content: string, filename: string, mime_type: string, size: int}
     */
    public function retrieve(int $blobId): array;

    /** Supprime un blob (soft-delete avec audit). */
    public function delete(int $blobId): void;

    /** Liste les blobs d'un agent. */
    public function listByAgent(int $agentId): Collection;
}
PHP

# --- Exceptions ---
cat > "$MODULE_PATH/Exceptions/DecryptionFailedException.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Exceptions;

use RuntimeException;

class DecryptionFailedException extends RuntimeException
{
    public function __construct(string $message = 'Échec du déchiffrement.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
PHP

cat > "$MODULE_PATH/Exceptions/KeyNotFoundException.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Exceptions;

use RuntimeException;

class KeyNotFoundException extends RuntimeException
{
    public function __construct(int $agentId, ?int $version = null)
    {
        $message = $version !== null
            ? "Clé version {$version} introuvable pour l'agent {$agentId}."
            : "Aucune clé trouvée pour l'agent {$agentId}.";

        parent::__construct($message);
    }
}
PHP

cat > "$MODULE_PATH/Exceptions/IntegrityCheckFailedException.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Exceptions;

use RuntimeException;

class IntegrityCheckFailedException extends RuntimeException
{
    public function __construct(int $blobId, string $expected, string $actual)
    {
        parent::__construct(
            "Échec de vérification d'intégrité pour le blob {$blobId}. " .
            "Checksum attendu: {$expected}, calculé: {$actual}"
        );
    }
}
PHP

# --- Events ---
cat > "$MODULE_PATH/Events/DataDecrypted.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DataDecrypted
{
    use Dispatchable;

    public function __construct(
        public readonly int $agentId,
        public readonly string $resourceType,
        public readonly int $resourceId,
        public readonly string $field,
        public readonly int $accessedBy,
        public readonly string $ip,
    ) {}
}
PHP

cat > "$MODULE_PATH/Events/DataEncrypted.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DataEncrypted
{
    use Dispatchable;

    public function __construct(
        public readonly int $agentId,
        public readonly string $resourceType,
        public readonly int $resourceId,
        public readonly string $field,
        public readonly int $performedBy,
        public readonly string $ip,
    ) {}
}
PHP

cat > "$MODULE_PATH/Events/KeyRotated.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Events;

use Illuminate\Foundation\Events\Dispatchable;

class KeyRotated
{
    use Dispatchable;

    public function __construct(
        public readonly int $agentId,
        public readonly int $oldVersion,
        public readonly int $newVersion,
    ) {}
}
PHP

cat > "$MODULE_PATH/Events/UnauthorizedDecryptAttempt.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Events;

use Illuminate\Foundation\Events\Dispatchable;

class UnauthorizedDecryptAttempt
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $resourceType,
        public readonly int $resourceId,
        public readonly string $reason,
        public readonly string $ip,
    ) {}
}
PHP

# --- Casts ---
cat > "$MODULE_PATH/Casts/EncryptedCast.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Casts;

use App\Modules\Encryption\Contracts\EncryptionServiceInterface;
use App\Modules\Encryption\Contracts\KeyManagerInterface;
use App\Modules\Encryption\Events\DataDecrypted;
use App\Modules\Encryption\Exceptions\DecryptionFailedException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast Eloquent pour chiffrement/déchiffrement transparent AES-256-GCM.
 *
 * Format stocké en BD : "version:iv_base64:tag_base64:ciphertext_base64"
 *
 * @implements CastsAttributes<string, string>
 */
class EncryptedCast implements CastsAttributes
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // TODO: Implémenter le déchiffrement
        // 1. Parser "version:iv:tag:ciphertext"
        // 2. Récupérer la DEK via KeyManagerInterface
        // 3. Déchiffrer via EncryptionServiceInterface
        // 4. Émettre DataDecrypted
        throw new \RuntimeException('EncryptedCast::get() — à implémenter');
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // TODO: Implémenter le chiffrement
        // 1. Récupérer la DEK via KeyManagerInterface
        // 2. Chiffrer via EncryptionServiceInterface
        // 3. Retourner "version:iv_base64:tag_base64:ciphertext_base64"
        throw new \RuntimeException('EncryptedCast::set() — à implémenter');
    }
}
PHP

# --- ServiceProvider ---
cat > "$MODULE_PATH/EncryptionServiceProvider.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption;

use App\Modules\Encryption\Contracts\BlobStorageInterface;
use App\Modules\Encryption\Contracts\EncryptionServiceInterface;
use App\Modules\Encryption\Contracts\KeyManagerInterface;
use App\Modules\Encryption\Services\AesGcmEncryptionService;
use App\Modules\Encryption\Services\EncryptedBlobService;
use App\Modules\Encryption\Services\EnvelopeKeyManager;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionServiceInterface::class, AesGcmEncryptionService::class);
        $this->app->singleton(KeyManagerInterface::class, EnvelopeKeyManager::class);
        $this->app->singleton(BlobStorageInterface::class, EncryptedBlobService::class);

        $this->mergeConfigFrom(__DIR__ . '/Config/encryption-module.php', 'encryption-module');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\RotateKeysCommand::class,
                Console\AuditKeysCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/Config/encryption-module.php' => config_path('encryption-module.php'),
            ], 'encryption-config');
        }
    }
}
PHP

# --- Config ---
cat > "$MODULE_PATH/Config/encryption-module.php" << 'PHP'
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Algorithme de chiffrement
    |--------------------------------------------------------------------------
    | AES-256-GCM uniquement. Ne pas modifier.
    */
    'cipher' => 'aes-256-gcm',

    /*
    |--------------------------------------------------------------------------
    | Longueur de l'IV (en octets)
    |--------------------------------------------------------------------------
    */
    'iv_length' => 16,

    /*
    |--------------------------------------------------------------------------
    | Longueur du tag GCM (en octets)
    |--------------------------------------------------------------------------
    */
    'tag_length' => 16,

    /*
    |--------------------------------------------------------------------------
    | Rotation automatique des DEK (en jours)
    |--------------------------------------------------------------------------
    */
    'rotation_interval_days' => 90,

    /*
    |--------------------------------------------------------------------------
    | Taille max d'upload de BLOB (en octets) — 50 Mo
    |--------------------------------------------------------------------------
    */
    'max_blob_size' => 50 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | MIME types autorisés pour les BLOBs
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
        'application/zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Source de la KEK
    |--------------------------------------------------------------------------
    | 'env'   → ENCRYPTION_MASTER_KEY dans .env
    | 'vault' → HashiCorp Vault (VAULT_ADDR, VAULT_TOKEN, VAULT_KEY_PATH)
    */
    'kek_source' => env('ENCRYPTION_KEK_SOURCE', 'env'),
];
PHP

# --- Routes placeholder ---
cat > "$MODULE_PATH/Routes/api.php" << 'PHP'
<?php

declare(strict_types=1);

// Routes du module Encryption
// Les endpoints de gestion des clés sont réservés aux administrateurs.

// TODO: Implémenter les routes
// Route::middleware(['auth:sanctum', 'role:admin'])->prefix('encryption')->group(function (): void {
//     Route::post('/rotate', [KeyRotationController::class, 'rotate']);
//     Route::get('/audit', [KeyAuditController::class, 'index']);
// });
PHP

# --- Console commands placeholder ---
cat > "$MODULE_PATH/Console/RotateKeysCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Console;

use Illuminate\Console\Command;

class RotateKeysCommand extends Command
{
    protected $signature = 'encryption:rotate
                            {--agent= : ID d\'un agent spécifique}
                            {--dry-run : Simulation sans modification}';

    protected $description = 'Rotation des clés de chiffrement (DEK) des agents';

    public function handle(): int
    {
        // TODO: Implémenter la rotation
        $this->info('encryption:rotate — à implémenter');

        return self::SUCCESS;
    }
}
PHP

cat > "$MODULE_PATH/Console/AuditKeysCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Console;

use Illuminate\Console\Command;

class AuditKeysCommand extends Command
{
    protected $signature = 'encryption:audit
                            {--agent= : ID d\'un agent spécifique}
                            {--blobs : Vérifier aussi les checksums des BLOBs}';

    protected $description = 'Vérifie l\'intégrité des clés de chiffrement et des BLOBs';

    public function handle(): int
    {
        // TODO: Implémenter l'audit
        $this->info('encryption:audit — à implémenter');

        return self::SUCCESS;
    }
}
PHP

# --- Services placeholder ---
cat > "$MODULE_PATH/Services/AesGcmEncryptionService.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Services;

use App\Modules\Encryption\Contracts\EncryptionServiceInterface;
use App\Modules\Encryption\Exceptions\DecryptionFailedException;

class AesGcmEncryptionService implements EncryptionServiceInterface
{
    public function encrypt(string $plaintext, string $key, string $aad = ''): array
    {
        // TODO: Implémenter
        // 1. Générer un IV aléatoire de 16 octets
        // 2. openssl_encrypt avec aes-256-gcm
        // 3. Retourner [ciphertext, iv, tag]
        throw new \RuntimeException('AesGcmEncryptionService::encrypt() — à implémenter');
    }

    public function decrypt(string $ciphertext, string $key, string $iv, string $tag, string $aad = ''): string
    {
        // TODO: Implémenter
        // 1. openssl_decrypt avec aes-256-gcm
        // 2. Vérifier le résultat (false = tag invalide)
        // 3. Lever DecryptionFailedException si échec
        throw new \RuntimeException('AesGcmEncryptionService::decrypt() — à implémenter');
    }
}
PHP

cat > "$MODULE_PATH/Services/EnvelopeKeyManager.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Services;

use App\Modules\Encryption\Contracts\KeyManagerInterface;

class EnvelopeKeyManager implements KeyManagerInterface
{
    public function getAgentKey(int $agentId): string
    {
        // TODO: Implémenter — récupérer DEK chiffrée, déchiffrer avec KEK
        throw new \RuntimeException('EnvelopeKeyManager::getAgentKey() — à implémenter');
    }

    public function createAgentKey(int $agentId): void
    {
        // TODO: Implémenter — générer DEK, chiffrer avec KEK, stocker
        throw new \RuntimeException('EnvelopeKeyManager::createAgentKey() — à implémenter');
    }

    public function rotateAgentKey(int $agentId): int
    {
        // TODO: Implémenter — nouvelle DEK, re-chiffrer données, incrémenter version
        throw new \RuntimeException('EnvelopeKeyManager::rotateAgentKey() — à implémenter');
    }

    public function getAgentKeyByVersion(int $agentId, int $version): string
    {
        // TODO: Implémenter — récupérer une version spécifique
        throw new \RuntimeException('EnvelopeKeyManager::getAgentKeyByVersion() — à implémenter');
    }

    public function rotateKek(string $newKek): void
    {
        // TODO: Implémenter — re-chiffrer toutes les DEK avec la nouvelle KEK
        throw new \RuntimeException('EnvelopeKeyManager::rotateKek() — à implémenter');
    }
}
PHP

cat > "$MODULE_PATH/Services/EncryptedBlobService.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Services;

use App\Modules\Encryption\Contracts\BlobStorageInterface;
use Illuminate\Support\Collection;

class EncryptedBlobService implements BlobStorageInterface
{
    public function store(int $agentId, string $content, string $filename, string $mimeType): int
    {
        // TODO: Implémenter
        // 1. hash('sha256', $content) pour le checksum
        // 2. Récupérer la DEK de l'agent
        // 3. Chiffrer le contenu
        // 4. Chiffrer le filename
        // 5. INSERT dans encrypted_blobs
        // 6. Émettre événement d'audit
        throw new \RuntimeException('EncryptedBlobService::store() — à implémenter');
    }

    public function retrieve(int $blobId): array
    {
        // TODO: Implémenter
        // 1. SELECT le blob
        // 2. Vérifier les permissions
        // 3. Déchiffrer avec la DEK (version correcte)
        // 4. Vérifier le checksum
        // 5. Émettre événement d'audit
        throw new \RuntimeException('EncryptedBlobService::retrieve() — à implémenter');
    }

    public function delete(int $blobId): void
    {
        // TODO: Implémenter — soft-delete + audit
        throw new \RuntimeException('EncryptedBlobService::delete() — à implémenter');
    }

    public function listByAgent(int $agentId): Collection
    {
        // TODO: Implémenter — liste sans déchiffrer le contenu
        throw new \RuntimeException('EncryptedBlobService::listByAgent() — à implémenter');
    }
}
PHP

cat > "$MODULE_PATH/Services/KeyRotationService.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Services;

class KeyRotationService
{
    // TODO: Implémenter
    // - Rotation d'un agent spécifique
    // - Rotation de tous les agents
    // - Re-chiffrement des BLOBs par batch (100/batch)
    // - Journalisation via événements
}
PHP

# --- Models placeholder ---
cat > "$MODULE_PATH/Models/AgentKey.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DEK chiffrée par agent.
 *
 * Classification : 🔴 CRITIQUE
 * La colonne encrypted_key contient la DEK chiffrée par la KEK.
 */
class AgentKey extends Model
{
    protected $table = 'agent_keys';

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'encrypted_key',
        'key_version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'key_version' => 'integer',
            'is_active'   => 'boolean',
        ];
    }
}
PHP

cat > "$MODULE_PATH/Models/EncryptedBlob.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fichier/image stocké chiffré en BD.
 *
 * Classification : 🟠 SENSIBLE (contenu) / 🔴 CRITIQUE (selon le fichier)
 */
class EncryptedBlob extends Model
{
    use SoftDeletes;

    protected $table = 'encrypted_blobs';

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'filename',
        'mime_type',
        'blob_data',
        'blob_size',
        'checksum_sha256',
        'iv',
        'tag',
        'key_version',
    ];

    protected function casts(): array
    {
        return [
            'blob_size'   => 'integer',
            'key_version' => 'integer',
        ];
    }
}
PHP

cat > "$MODULE_PATH/Models/KeyRotationLog.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historique des rotations de clés.
 * Immuable : pas de update ni delete.
 */
class KeyRotationLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'key_rotation_logs';

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'old_version',
        'new_version',
        'blobs_rotated',
        'fields_rotated',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'old_version'    => 'integer',
            'new_version'    => 'integer',
            'blobs_rotated'  => 'integer',
            'fields_rotated' => 'integer',
            'duration_ms'    => 'integer',
        ];
    }

    public static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Les logs de rotation sont immuables.'));
        static::deleting(fn () => throw new \RuntimeException('Les logs de rotation ne peuvent pas être supprimés.'));
    }
}
PHP

# --- Listeners placeholder ---
cat > "$MODULE_PATH/Listeners/LogDecryptionAccess.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Listeners;

use App\Modules\Encryption\Events\DataDecrypted;

class LogDecryptionAccess
{
    public function handle(DataDecrypted $event): void
    {
        // TODO: Déléguer au module AuditTrail via AuditLoggerInterface
    }
}
PHP

# --- Middleware placeholder ---
cat > "$MODULE_PATH/Middleware/DecryptResponseMiddleware.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Implémenter si nécessaire
        return $next($request);
    }
}
PHP

# --- Comptage ---
FILE_COUNT=$(find "$MODULE_PATH" -type f | wc -l)

echo ""
echo "✅ Module Encryption créé avec succès !"
echo "   📁 $MODULE_PATH"
echo "   📄 $FILE_COUNT fichiers générés"
echo ""
echo "   Prochaines étapes :"
echo "   1. Ajouter le ServiceProvider dans bootstrap/providers.php :"
echo "      App\Modules\Encryption\EncryptionServiceProvider::class"
echo "   2. Créer les migrations : php artisan make:migration --path=app/Modules/Encryption/Migrations"
echo "   3. Configurer ENCRYPTION_MASTER_KEY dans .env"
echo "   4. Implémenter les services (TODO dans chaque fichier)"
echo "   5. Lancer les tests : php artisan test --filter=Encryption"
