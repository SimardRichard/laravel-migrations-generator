# Skill : Module AuditTrail

## Quand utiliser

Utiliser ce skill pour créer ou modifier le module `AuditTrail` : journalisation
des accès aux données sensibles, événements de sécurité, requêtes de consultation
des logs, et rapports de conformité.

## Structure du module

```
app/Modules/AuditTrail/
├── Contracts/
│   └── AuditLoggerInterface.php          # Interface de journalisation
├── Services/
│   ├── AuditLoggerService.php            # Implémentation principale
│   └── AuditQueryService.php             # Requêtes de consultation
├── Models/
│   └── AuditLog.php                      # Modèle Eloquent (INSERT + SELECT only)
├── Listeners/
│   ├── LogDataDecrypted.php              # Écoute DataDecrypted
│   ├── LogDataEncrypted.php              # Écoute DataEncrypted
│   ├── LogKeyRotated.php                 # Écoute KeyRotated
│   ├── LogUnauthorizedAccess.php         # Écoute UnauthorizedDecryptAttempt
│   ├── LogBlobAccess.php                 # Écoute blob upload/download
│   └── LogAuthEvent.php                  # Écoute login/logout/failed
├── Jobs/
│   └── WriteAuditLogJob.php              # Écriture asynchrone (queue)
├── Console/
│   ├── AuditReportCommand.php            # php artisan audit:report
│   └── AuditPurgeExpiredCommand.php      # php artisan audit:purge (> 7 ans seulement)
├── Migrations/
│   └── xxxx_create_audit_logs_table.php
├── Config/
│   └── audit-trail.php
├── Routes/
│   └── api.php                           # Endpoints de consultation (admin only)
├── Tests/
│   ├── Unit/
│   │   └── AuditLoggerServiceTest.php
│   └── Feature/
│       ├── AuditLoggingTest.php
│       ├── AuditQueryTest.php
│       └── AuditImmutabilityTest.php
└── AuditTrailServiceProvider.php
```

## Schéma de table

```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    event_type      VARCHAR(50) NOT NULL,              -- 'data.read', 'access.denied', etc.
    user_id         BIGINT UNSIGNED NOT NULL,           -- qui a fait l'action
    agent_id        BIGINT UNSIGNED NULL,               -- agent cible (si applicable)
    resource_type   VARCHAR(100) NULL,                  -- 'Agent', 'EncryptedBlob', etc.
    resource_id     BIGINT UNSIGNED NULL,
    field_name      VARCHAR(100) NULL,                  -- champ spécifique accédé
    result          ENUM('success', 'denied', 'error') NOT NULL,
    ip_address      VARCHAR(45) NOT NULL,               -- IPv4 ou IPv6
    user_agent      VARCHAR(500) NULL,
    metadata        JSON NULL,                          -- détails additionnels
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Pas de updated_at : immuable
    -- Pas de deleted_at : jamais supprimé avant 7 ans

    INDEX idx_tenant_event (tenant_id, event_type),
    INDEX idx_tenant_user (tenant_id, user_id),
    INDEX idx_tenant_agent (tenant_id, agent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_result (result)
) ENGINE=InnoDB ENCRYPTION='Y'
  PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    -- Partitions par trimestre, à créer dynamiquement
    -- Facilite la purge après 7 ans et améliore les performances
);
```

**Permissions MySQL pour l'utilisateur applicatif :**
```sql
GRANT INSERT, SELECT ON crassq.audit_logs TO 'app_user'@'%';
-- PAS de UPDATE, PAS de DELETE
```

## Contrat

```php
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Contracts;

interface AuditLoggerInterface
{
    /**
     * Journalise un événement d'audit.
     * Exécuté de manière asynchrone via queue.
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

    /**
     * Journalise un accès à une donnée chiffrée.
     * Raccourci pour les cas les plus fréquents.
     */
    public function logDataAccess(
        int $userId,
        int $agentId,
        string $resourceType,
        int $resourceId,
        string $fieldName,
        string $ipAddress,
    ): void;

    /**
     * Journalise un accès refusé.
     */
    public function logAccessDenied(
        int $userId,
        string $resourceType,
        int $resourceId,
        string $reason,
        string $ipAddress,
    ): void;
}
```

## Taxonomie event_type

Constantes dans le modèle `AuditLog` :

```php
<?php

declare(strict_types=1);

namespace App\Modules\AuditTrail\Models;

use Illuminate\Database\Eloquent\Model;

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

    /** Immuable : pas de updated_at */
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

    /**
     * Empêche toute modification d'un log existant.
     */
    public static function booted(): void
    {
        static::updating(function (): bool {
            throw new \RuntimeException('Les logs d\'audit sont immuables.');
        });

        static::deleting(function (): bool {
            throw new \RuntimeException('Les logs d\'audit ne peuvent pas être supprimés.');
        });
    }
}
```

## Listeners — pattern standard

```php
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
```

## Écriture asynchrone

Les logs d'audit sont écrits via un job en queue pour ne pas ralentir les requêtes :

```php
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

    /**
     * Ne pas perdre de logs : retry illimité avec backoff.
     */
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
```

**Queue dédiée `audit`** : configurer un worker séparé pour l'audit afin de
ne pas mélanger avec les jobs métier.

## Endpoints de consultation (admin only)

```php
// Routes API — admin uniquement
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('audit')->group(function (): void {
    Route::get('/logs', [AuditLogController::class, 'index']);       // Liste paginée
    Route::get('/logs/agent/{agent}', [AuditLogController::class, 'byAgent']);
    Route::get('/logs/user/{user}', [AuditLogController::class, 'byUser']);
    Route::get('/report', [AuditReportController::class, 'generate']);
});
```

## Commandes artisan

### `audit:report`

```bash
php artisan audit:report                          # Rapport du dernier mois
php artisan audit:report --from=2026-01-01        # Depuis une date
php artisan audit:report --type=access.denied     # Filtrer par type
php artisan audit:report --format=csv             # Export CSV
```

### `audit:purge`

```bash
php artisan audit:purge                           # Purge > 7 ans (avec confirmation)
php artisan audit:purge --force                   # Sans confirmation (cron)
```

**Sécurité :** la commande `purge` vérifie que les logs ont **plus de 7 ans**
avant de supprimer. Elle utilise le partitionnement MySQL pour un `DROP PARTITION`
rapide plutôt qu'un `DELETE` massif.

## Interdictions

- ❌ Valeur déchiffrée dans les logs (jamais le contenu PII réel)
- ❌ `UPDATE` ou `DELETE` sur `audit_logs` (sauf purge > 7 ans)
- ❌ Désactivation de l'audit en production
- ❌ Écriture synchrone dans le request cycle (toujours via queue)
- ❌ Mots de passe, tokens, clés dans le champ `metadata`
- ❌ Log dans le filesystem seul (BD obligatoire comme source de vérité)

## Tests obligatoires

- [ ] Chaque événement de chiffrement génère un log d'audit
- [ ] `AuditLog::update()` lève une exception
- [ ] `AuditLog::delete()` lève une exception
- [ ] Aucune valeur sensible dans les logs (scan automatisé)
- [ ] Les logs contiennent tous les champs requis
- [ ] Le job `WriteAuditLogJob` retry correctement en cas d'échec
- [ ] L'endpoint de consultation nécessite le rôle admin
- [ ] La purge refuse de supprimer des logs < 7 ans
- [ ] Les filtres de consultation fonctionnent (par agent, user, type, date)
