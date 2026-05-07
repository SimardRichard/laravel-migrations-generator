# Skill : Module Encryption

## Quand utiliser

Utiliser ce skill pour créer ou modifier tout composant du module `Encryption` :
services de chiffrement, gestion des clés, casts Eloquent, commandes artisan,
événements, ou toute logique liée au chiffrement des données.

## Structure du module

```
app/Modules/Encryption/
├── Contracts/
│   ├── EncryptionServiceInterface.php    # Chiffrer / déchiffrer des données
│   ├── KeyManagerInterface.php           # Gérer KEK et DEK
│   └── BlobStorageInterface.php          # Stocker / récupérer des BLOBs chiffrés
├── Services/
│   ├── AesGcmEncryptionService.php       # Implémentation AES-256-GCM
│   ├── EnvelopeKeyManager.php            # Envelope encryption KEK/DEK
│   ├── EncryptedBlobService.php          # CRUD BLOB chiffré
│   └── KeyRotationService.php            # Rotation automatique des clés
├── Models/
│   ├── AgentKey.php                      # DEK chiffrée par agent
│   ├── EncryptedBlob.php                 # Fichiers/images en BD
│   └── KeyRotationLog.php                # Historique des rotations
├── Casts/
│   └── EncryptedCast.php                 # Cast Eloquent transparent
├── Middleware/
│   └── DecryptResponseMiddleware.php     # Déchiffrement si nécessaire
├── Console/
│   ├── RotateKeysCommand.php             # php artisan encryption:rotate
│   └── AuditKeysCommand.php              # php artisan encryption:audit
├── Events/
│   ├── KeyRotated.php
│   ├── DataDecrypted.php
│   ├── DataEncrypted.php
│   └── UnauthorizedDecryptAttempt.php
├── Listeners/
│   └── LogDecryptionAccess.php           # Envoie vers le module AuditTrail
├── Exceptions/
│   ├── DecryptionFailedException.php
│   ├── KeyNotFoundException.php
│   └── IntegrityCheckFailedException.php
├── Migrations/
│   ├── xxxx_create_agent_keys_table.php
│   ├── xxxx_create_encrypted_blobs_table.php
│   └── xxxx_create_key_rotation_logs_table.php
├── Config/
│   └── encryption-module.php             # Config publiable
├── Routes/
│   └── api.php
├── Tests/
│   ├── Unit/
│   │   ├── AesGcmEncryptionServiceTest.php
│   │   ├── EnvelopeKeyManagerTest.php
│   │   └── EncryptedCastTest.php
│   └── Feature/
│       ├── BlobStorageTest.php
│       ├── KeyRotationTest.php
│       └── InterAgentAccessTest.php
└── EncryptionServiceProvider.php
```

## Contrats (Interfaces)

### EncryptionServiceInterface

```php
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
     * @param string $aad       Additional Authenticated Data (optionnel)
     * @return array{ciphertext: string, iv: string, tag: string}
     */
    public function encrypt(string $plaintext, string $key, string $aad = ''): array;

    /**
     * Déchiffre des données chiffrées avec AES-256-GCM.
     *
     * @param string $ciphertext Données chiffrées
     * @param string $key        Clé de chiffrement (DEK)
     * @param string $iv         Vecteur d'initialisation
     * @param string $tag        Tag d'authentification GCM
     * @param string $aad        Additional Authenticated Data (optionnel)
     * @return string Données en clair
     *
     * @throws DecryptionFailedException Si le tag est invalide ou la clé incorrecte
     */
    public function decrypt(string $ciphertext, string $key, string $iv, string $tag, string $aad = ''): string;
}
```

### KeyManagerInterface

```php
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

    /** Récupère une version spécifique de la DEK (pour déchiffrement rétroactif). */
    public function getAgentKeyByVersion(int $agentId, int $version): string;

    /** Rotation de la KEK : re-chiffre toutes les DEK. */
    public function rotateKek(string $newKek): void;
}
```

### BlobStorageInterface

```php
<?php

declare(strict_types=1);

namespace App\Modules\Encryption\Contracts;

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
     *
     * @throws DecryptionFailedException
     * @throws IntegrityCheckFailedException Si le checksum ne correspond pas
     */
    public function retrieve(int $blobId): array;

    /** Supprime un blob (soft-delete avec audit). */
    public function delete(int $blobId): void;

    /** Liste les blobs d'un agent. */
    public function listByAgent(int $agentId): \Illuminate\Support\Collection;
}
```

## Règles d'implémentation

### AES-256-GCM

```php
// ✅ CORRECT
$iv = random_bytes(16);  // 128 bits, TOUJOURS aléatoire
$ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);

// ❌ INTERDIT
$iv = str_repeat("\0", 16);           // IV statique
$iv = substr(md5($agentId), 0, 16);   // IV déterministe
openssl_encrypt($data, 'aes-256-cbc', ...);  // CBC interdit
```

### Enregistrement du ServiceProvider

```php
// bootstrap/providers.php
return [
    // ...
    App\Modules\Encryption\EncryptionServiceProvider::class,
];
```

### ServiceProvider — bindings

```php
public function register(): void
{
    $this->app->singleton(EncryptionServiceInterface::class, AesGcmEncryptionService::class);
    $this->app->singleton(KeyManagerInterface::class, EnvelopeKeyManager::class);
    $this->app->singleton(BlobStorageInterface::class, EncryptedBlobService::class);
}
```

### EncryptedCast — comportement

Le cast doit :
1. Récupérer la DEK de l'agent via `KeyManagerInterface`
2. Chiffrer à l'écriture, déchiffrer à la lecture
3. Émettre `DataDecrypted` à chaque lecture
4. Stocker le résultat en format : `version:iv_base64:tag_base64:ciphertext_base64`
5. Parser ce format au déchiffrement pour supporter la rotation

```php
// Format stocké en BD (une seule colonne TEXT)
// "1:base64(iv):base64(tag):base64(ciphertext)"
// Le préfixe "1" est la key_version
```

## Commandes artisan

### `encryption:rotate`

```bash
php artisan encryption:rotate              # Rotation de tous les agents
php artisan encryption:rotate --agent=42   # Rotation d'un seul agent
php artisan encryption:rotate --dry-run    # Simulation sans modification
```

### `encryption:audit`

```bash
php artisan encryption:audit               # Vérifie l'intégrité de toutes les clés
php artisan encryption:audit --agent=42    # Vérifie un seul agent
php artisan encryption:audit --blobs       # Vérifie aussi les checksums des BLOBs
```

## Tests obligatoires

Chaque PR touchant au module Encryption doit inclure des tests pour :

- [ ] Chiffrement/déchiffrement round-trip (données identiques)
- [ ] IV unique par opération (deux chiffrements du même texte → résultats différents)
- [ ] Tag GCM invalide → exception `DecryptionFailedException`
- [ ] Clé incorrecte → exception `DecryptionFailedException`
- [ ] Rotation de clé → données toujours accessibles
- [ ] Checksum BLOB → `IntegrityCheckFailedException` si altéré
- [ ] Événement `DataDecrypted` émis à chaque lecture
- [ ] Événement `UnauthorizedDecryptAttempt` émis si accès refusé
- [ ] Aucune donnée en clair en BD (vérification directe par requête SQL)
