#!/usr/bin/env bash
#
# new-audit-module.sh
# Scaffolding complet du module AuditTrail pour CRASSQ.
# Usage : .claude/scripts/new-audit-module.sh
#
set -euo pipefail

MODULE_PATH="app/Modules/AuditTrail"

if [[ -d "$MODULE_PATH" ]]; then
    echo "❌ Le module AuditTrail existe déjà dans $MODULE_PATH"
    echo "   Supprimez-le d'abord si vous voulez regénérer."
    exit 1
fi

echo "📋 Création du module AuditTrail..."

# --- Arborescence ---
mkdir -p "$MODULE_PATH"/{Contracts,Services,Models,Listeners,Jobs,Console,Config,Routes,Tests/{Unit,Feature}}

# --- Contrat ---
cat > "$MODULE_PATH/Contracts/AuditLoggerInterface.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Contracts;

interface AuditLoggerInterface
{
    /**
     * Journalise un événement d'audit (asynchrone via queue).
     *
     * @param array{
     *     event_type: string,
     *     user_id: int,
     *     agent_id: ?int,
     *     resource_type: ?string,
     *     resource_id: ?int,
     *     field_name: ?string,
     *     result: 'success'|'denied'|'error',
     *     ip_address: string,
     *     user_agent: ?string,
     *     metadata: ?array<string, mixed>,
     * } $data
     */
    public function log(array $data): void;

    /** Raccourci pour journaliser un accès à une donnée chiffrée. */
    public function logDataAccess(
        int $userId,
        int $agentId,
        string $resourceType,
        int $resourceId,
        string $fieldName,
        string $ipAddress,
    ): void;

    /** Raccourci pour journaliser un accès refusé. */
    public function logAccessDenied(
        int $userId,
        string $resourceType,
        int $resourceId,
        string $reason,
        string $ipAddress,
    ): void;
}
PHP

# --- Service ---
cat > "$MODULE_PATH/Services/AuditLoggerService.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Services;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\AuditTrail\Jobs\WriteAuditLogJob;
use App\Modules\AuditTrail\Models\AuditLog;

class AuditLoggerService implements AuditLoggerInterface
{
    public function log(array $data): void
    {
        $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()?->tenant_id;

        WriteAuditLogJob::dispatch($data);
    }

    public function logDataAccess(
        int $userId,
        int $agentId,
        string $resourceType,
        int $resourceId,
        string $fieldName,
        string $ipAddress,
    ): void {
        $this->log([
            'event_type'    => AuditLog::EVENT_DATA_READ,
            'user_id'       => $userId,
            'agent_id'      => $agentId,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'field_name'    => $fieldName,
            'result'        => 'success',
            'ip_address'    => $ipAddress,
            'user_agent'    => request()->userAgent(),
            'metadata'      => null,
        ]);
    }

    public function logAccessDenied(
        int $userId,
        string $resourceType,
        int $resourceId,
        string $reason,
        string $ipAddress,
    ): void {
        $this->log([
            'event_type'    => AuditLog::EVENT_ACCESS_DENIED,
            'user_id'       => $userId,
            'agent_id'      => null,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'field_name'    => null,
            'result'        => 'denied',
            'ip_address'    => $ipAddress,
            'user_agent'    => request()->userAgent(),
            'metadata'      => ['reason' => $reason],
        ]);
    }
}
PHP

cat > "$MODULE_PATH/Services/AuditQueryService.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Services;

use App\Modules\AuditTrail\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditQueryService
{
    /**
     * @param array{
     *     tenant_id?: int,
     *     event_type?: string,
     *     user_id?: int,
     *     agent_id?: int,
     *     result?: string,
     *     from?: string,
     *     to?: string,
     * } $filters
     */
    public function query(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
PHP

# --- Model ---
cat > "$MODULE_PATH/Models/AuditLog.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log d'audit immuable.
 *
 * INSERT et SELECT uniquement. Aucun UPDATE, aucun DELETE.
 * Rétention minimum : 7 ans.
 */
class AuditLog extends Model
{
    // Données
    public const EVENT_DATA_READ = 'data.read';
    public const EVENT_DATA_WRITE = 'data.write';
    public const EVENT_DATA_DELETE = 'data.delete';

    // BLOBs
    public const EVENT_BLOB_UPLOAD = 'blob.upload';
    public const EVENT_BLOB_DOWNLOAD = 'blob.download';

    // Accès
    public const EVENT_ACCESS_GRANTED = 'access.granted';
    public const EVENT_ACCESS_DENIED = 'access.denied';

    // Authentification
    public const EVENT_AUTH_LOGIN = 'auth.login';
    public const EVENT_AUTH_LOGOUT = 'auth.logout';
    public const EVENT_AUTH_FAILED = 'auth.failed';

    // Clés
    public const EVENT_KEY_ROTATED = 'key.rotated';

    // Intégrité
    public const EVENT_INTEGRITY_CHECKSUM = 'integrity.checksum_failed';
    public const EVENT_INTEGRITY_DECRYPTION = 'integrity.decryption_failed';

    /** Immuable : pas de updated_at. */
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $guarded = ['id', 'created_at'];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Les logs d\'audit sont immuables.'));
        static::deleting(fn () => throw new \RuntimeException('Les logs d\'audit ne peuvent pas être supprimés.'));
    }
}
PHP

# --- Job ---
cat > "$MODULE_PATH/Jobs/WriteAuditLogJob.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Jobs;

use App\Modules\AuditTrail\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WriteAuditLogJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {
        $this->onQueue('audit');
    }

    public function handle(): void
    {
        AuditLog::create($this->data);
    }

    public function tries(): int
    {
        return 10;
    }

    /**
     * @return array<int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }
}
PHP

# --- Listeners ---
cat > "$MODULE_PATH/Listeners/LogDataDecrypted.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Listeners;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\Encryption\Events\DataDecrypted;

class LogDataDecrypted
{
    public function __construct(
        private readonly AuditLoggerInterface $logger,
    ) {}

    public function handle(DataDecrypted $event): void
    {
        $this->logger->logDataAccess(
            userId: $event->accessedBy,
            agentId: $event->agentId,
            resourceType: $event->resourceType,
            resourceId: $event->resourceId,
            fieldName: $event->field,
            ipAddress: $event->ip,
        );
    }
}
PHP

cat > "$MODULE_PATH/Listeners/LogKeyRotated.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Listeners;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\AuditTrail\Models\AuditLog;
use App\Modules\Encryption\Events\KeyRotated;

class LogKeyRotated
{
    public function __construct(
        private readonly AuditLoggerInterface $logger,
    ) {}

    public function handle(KeyRotated $event): void
    {
        $this->logger->log([
            'event_type'    => AuditLog::EVENT_KEY_ROTATED,
            'user_id'       => 0, // Système
            'agent_id'      => $event->agentId,
            'resource_type' => 'AgentKey',
            'resource_id'   => $event->agentId,
            'field_name'    => null,
            'result'        => 'success',
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'artisan/encryption:rotate',
            'metadata'      => [
                'old_version' => $event->oldVersion,
                'new_version' => $event->newVersion,
            ],
        ]);
    }
}
PHP

cat > "$MODULE_PATH/Listeners/LogUnauthorizedAccess.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Listeners;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\Encryption\Events\UnauthorizedDecryptAttempt;

class LogUnauthorizedAccess
{
    public function __construct(
        private readonly AuditLoggerInterface $logger,
    ) {}

    public function handle(UnauthorizedDecryptAttempt $event): void
    {
        $this->logger->logAccessDenied(
            userId: $event->userId,
            resourceType: $event->resourceType,
            resourceId: $event->resourceId,
            reason: $event->reason,
            ipAddress: $event->ip,
        );
    }
}
PHP

cat > "$MODULE_PATH/Listeners/LogAuthEvent.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Listeners;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\AuditTrail\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthEvent
{
    public function __construct(
        private readonly AuditLoggerInterface $logger,
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->logger->log([
            'event_type'    => AuditLog::EVENT_AUTH_LOGIN,
            'user_id'       => $event->user->getAuthIdentifier(),
            'agent_id'      => null,
            'resource_type' => 'User',
            'resource_id'   => $event->user->getAuthIdentifier(),
            'field_name'    => null,
            'result'        => 'success',
            'ip_address'    => request()->ip() ?? '0.0.0.0',
            'user_agent'    => request()->userAgent(),
            'metadata'      => ['guard' => $event->guard],
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        $this->logger->log([
            'event_type'    => AuditLog::EVENT_AUTH_LOGOUT,
            'user_id'       => $event->user->getAuthIdentifier(),
            'agent_id'      => null,
            'resource_type' => 'User',
            'resource_id'   => $event->user->getAuthIdentifier(),
            'field_name'    => null,
            'result'        => 'success',
            'ip_address'    => request()->ip() ?? '0.0.0.0',
            'user_agent'    => request()->userAgent(),
            'metadata'      => null,
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        $this->logger->log([
            'event_type'    => AuditLog::EVENT_AUTH_FAILED,
            'user_id'       => 0,
            'agent_id'      => null,
            'resource_type' => 'User',
            'resource_id'   => null,
            'field_name'    => null,
            'result'        => 'denied',
            'ip_address'    => request()->ip() ?? '0.0.0.0',
            'user_agent'    => request()->userAgent(),
            'metadata'      => ['guard' => $event->guard],
        ]);
    }
}
PHP

# --- Console commands ---
cat > "$MODULE_PATH/Console/AuditReportCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Console;

use Illuminate\Console\Command;

class AuditReportCommand extends Command
{
    protected $signature = 'audit:report
                            {--from= : Date de début (YYYY-MM-DD)}
                            {--to= : Date de fin (YYYY-MM-DD)}
                            {--type= : Filtrer par event_type}
                            {--format=table : Format de sortie (table, csv, json)}';

    protected $description = 'Génère un rapport d\'audit des accès aux données sensibles';

    public function handle(): int
    {
        // TODO: Implémenter
        $this->info('audit:report — à implémenter');

        return self::SUCCESS;
    }
}
PHP

cat > "$MODULE_PATH/Console/AuditPurgeExpiredCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Console;

use Illuminate\Console\Command;

class AuditPurgeExpiredCommand extends Command
{
    protected $signature = 'audit:purge
                            {--force : Sans confirmation (pour cron)}';

    protected $description = 'Purge les logs d\'audit de plus de 7 ans';

    public function handle(): int
    {
        // TODO: Implémenter
        // IMPORTANT: Vérifier que les logs ont PLUS de 7 ans
        // Utiliser DROP PARTITION pour performance
        $this->info('audit:purge — à implémenter');

        return self::SUCCESS;
    }
}
PHP

# --- Config ---
cat > "$MODULE_PATH/Config/audit-trail.php" << 'PHP'
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Queue dédiée pour les logs d'audit
    |--------------------------------------------------------------------------
    */
    'queue' => env('AUDIT_QUEUE', 'audit'),

    /*
    |--------------------------------------------------------------------------
    | Rétention des logs (en années)
    |--------------------------------------------------------------------------
    | Minimum 7 ans pour conformité Loi 25 / ISO 27001.
    */
    'retention_years' => 7,

    /*
    |--------------------------------------------------------------------------
    | Activer l'audit (désactivable en test uniquement)
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AUDIT_ENABLED', true),
];
PHP

# --- Routes placeholder ---
cat > "$MODULE_PATH/Routes/api.php" << 'PHP'
<?php

declare(strict_types=1);

// Routes du module AuditTrail
// Consultation réservée aux administrateurs.

// TODO: Implémenter les routes
// Route::middleware(['auth:sanctum', 'role:admin'])->prefix('audit')->group(function (): void {
//     Route::get('/logs', [AuditLogController::class, 'index']);
//     Route::get('/logs/agent/{agent}', [AuditLogController::class, 'byAgent']);
//     Route::get('/logs/user/{user}', [AuditLogController::class, 'byUser']);
//     Route::get('/report', [AuditReportController::class, 'generate']);
// });
PHP

# --- ServiceProvider ---
cat > "$MODULE_PATH/AuditTrailServiceProvider.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail;

use App\Modules\AuditTrail\Contracts\AuditLoggerInterface;
use App\Modules\AuditTrail\Listeners\LogAuthEvent;
use App\Modules\AuditTrail\Listeners\LogDataDecrypted;
use App\Modules\AuditTrail\Listeners\LogKeyRotated;
use App\Modules\AuditTrail\Listeners\LogUnauthorizedAccess;
use App\Modules\AuditTrail\Services\AuditLoggerService;
use App\Modules\Encryption\Events\DataDecrypted;
use App\Modules\Encryption\Events\KeyRotated;
use App\Modules\Encryption\Events\UnauthorizedDecryptAttempt;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditTrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLoggerInterface::class, AuditLoggerService::class);

        $this->mergeConfigFrom(__DIR__ . '/Config/audit-trail.php', 'audit-trail');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        // Événements de chiffrement
        Event::listen(DataDecrypted::class, LogDataDecrypted::class);
        Event::listen(KeyRotated::class, LogKeyRotated::class);
        Event::listen(UnauthorizedDecryptAttempt::class, LogUnauthorizedAccess::class);

        // Événements d'authentification
        Event::listen(Login::class, [LogAuthEvent::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthEvent::class, 'handleLogout']);
        Event::listen(Failed::class, [LogAuthEvent::class, 'handleFailed']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\AuditReportCommand::class,
                Console\AuditPurgeExpiredCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/Config/audit-trail.php' => config_path('audit-trail.php'),
            ], 'audit-config');
        }
    }
}
PHP

# --- Comptage ---
FILE_COUNT=$(find "$MODULE_PATH" -type f | wc -l)

echo ""
echo "✅ Module AuditTrail créé avec succès !"
echo "   📁 $MODULE_PATH"
echo "   📄 $FILE_COUNT fichiers générés"
echo ""
echo "   Prochaines étapes :"
echo "   1. Ajouter le ServiceProvider dans bootstrap/providers.php :"
echo "      App\Modules\AuditTrail\AuditTrailServiceProvider::class"
echo "   2. Créer la migration pour audit_logs"
echo "   3. Configurer un worker dédié pour la queue 'audit'"
echo "   4. Lancer les tests : php artisan test --filter=AuditTrail"
