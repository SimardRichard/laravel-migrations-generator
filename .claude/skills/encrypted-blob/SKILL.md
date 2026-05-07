# Skill : Stockage BLOB chiffré

## Quand utiliser

Utiliser ce skill pour tout ce qui concerne le stockage, la récupération, la validation
et la gestion de fichiers (images, documents, PDF, etc.) en base de données sous forme
de BLOB chiffré. Aucun fichier ne doit être stocké sur le filesystem.

## Principe

Tout fichier uploadé par un agent est :
1. Lu en mémoire (contenu binaire)
2. Hashé (SHA-256 du contenu original)
3. Chiffré avec la DEK de l'agent (AES-256-GCM)
4. Inséré dans `encrypted_blobs` avec IV, tag, checksum et key_version
5. Journalisé dans l'audit trail

## Schéma de table

```sql
CREATE TABLE encrypted_blobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    agent_id        BIGINT UNSIGNED NOT NULL,
    filename        TEXT NOT NULL,                    -- chiffré AES-256-GCM
    mime_type       VARCHAR(127) NOT NULL,
    blob_data       LONGBLOB NOT NULL,                -- contenu chiffré
    blob_size       BIGINT UNSIGNED NOT NULL,          -- taille originale en octets
    checksum_sha256 CHAR(64) NOT NULL,                 -- hash du contenu AVANT chiffrement
    iv              VARBINARY(16) NOT NULL,             -- vecteur d'initialisation
    tag             VARBINARY(16) NOT NULL,             -- tag d'authentification GCM
    key_version     INT UNSIGNED NOT NULL DEFAULT 1,    -- version de la DEK
    deleted_at      TIMESTAMP NULL DEFAULT NULL,        -- soft delete
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tenant_agent (tenant_id, agent_id),
    INDEX idx_key_version (key_version),
    INDEX idx_mime_type (mime_type),
    CONSTRAINT fk_blob_agent FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE RESTRICT
) ENGINE=InnoDB ENCRYPTION='Y';
```

**Notes :**
- `filename` est chiffré au niveau applicatif (type `TEXT` pour accommoder la sortie chiffrée)
- `mime_type` n'est PAS chiffré (nécessaire pour le Content-Type en réponse)
- `blob_size` est la taille originale, pas la taille chiffrée
- `ON DELETE RESTRICT` : un agent ne peut pas être supprimé tant qu'il a des blobs

## Upload — flux complet

```php
<?php

declare(strict_types=1);

// Dans le contrôleur du module concerné
// POST /api/v1/agents/{agent}/documents

public function store(StoreDocumentRequest $request, Agent $agent): JsonResponse
{
    $file = $request->file('document');

    // Validation déjà faite par StoreDocumentRequest :
    // - mime types autorisés
    // - taille max (ex. 50 Mo)
    // - extension cohérente avec le mime type

    $blobId = app(BlobStorageInterface::class)->store(
        agentId: $agent->id,
        content: $file->getContent(),
        filename: $file->getClientOriginalName(),
        mimeType: $file->getMimeType(),
    );

    return response()->json(['blob_id' => $blobId], 201);
}
```

## Download — flux complet

```php
<?php

declare(strict_types=1);

// GET /api/v1/blobs/{blob}

public function show(int $blobId): Response
{
    $result = app(BlobStorageInterface::class)->retrieve($blobId);

    // L'audit est déjà journalisé par le BlobStorageService

    return response($result['content'])
        ->header('Content-Type', $result['mime_type'])
        ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
        ->header('Content-Length', (string) $result['size'])
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
}
```

**Headers de sécurité obligatoires :**
- `X-Content-Type-Options: nosniff` — empêche le sniffing MIME
- `Cache-Control: no-store` — jamais de cache pour des données sensibles

## Validation à l'upload

```php
// StoreDocumentRequest
public function rules(): array
{
    return [
        'document' => [
            'required',
            'file',
            'max:51200', // 50 Mo
            'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt,zip',
        ],
    ];
}
```

**Règles supplémentaires côté service :**
- Vérifier le magic number du fichier (pas juste l'extension)
- Scanner les archives (ZIP) pour contenu malveillant si applicable
- Rejeter les fichiers exécutables même déguisés

## Vérification d'intégrité

```php
// À chaque retrieve()
$calculatedChecksum = hash('sha256', $decryptedContent);

if ($calculatedChecksum !== $blob->checksum_sha256) {
    event(new IntegrityCheckFailed($blob->id, $blob->checksum_sha256, $calculatedChecksum));
    throw new IntegrityCheckFailedException(
        "Checksum mismatch for blob {$blob->id}"
    );
}
```

## Rotation de clé sur les BLOBs

Lors de la rotation de la DEK d'un agent, tous ses BLOBs doivent être re-chiffrés :

1. Déchiffrer avec l'ancienne DEK
2. Vérifier le checksum (intégrité)
3. Re-chiffrer avec la nouvelle DEK (nouveau IV, nouveau tag)
4. Mettre à jour `iv`, `tag`, `key_version` en BD
5. Journaliser la rotation

```php
// Le KeyRotationService gère ça automatiquement
// Traitement par batch pour les gros volumes (100 blobs par batch)
// Queue job pour éviter les timeouts
```

## Accès inter-agents

Quand un agent B a permission de voir un document de l'agent A :

1. Vérifier la permission via le module `AccessControl`
2. Déchiffrer avec la DEK de l'agent A
3. Journaliser l'accès inter-agent (`access.granted`)
4. Servir le contenu déchiffré

**Ne PAS re-chiffrer pour le simple affichage.** Le re-chiffrement est réservé au
cas où l'agent B reçoit une copie permanente du document.

## Interdictions

- ❌ `Storage::put()` / `file_put_contents()` / `move_uploaded_file()`
- ❌ Stocker le fichier temporairement sur disque (travailler en mémoire)
- ❌ URL publique vers un BLOB (toujours passer par le contrôleur authentifié)
- ❌ Cache du contenu déchiffré (Redis, fichier, mémoire partagée)
- ❌ Thumbnails non chiffrés
- ❌ Preview sans contrôle d'accès

## Limites et considérations

- **MySQL `max_allowed_packet`** : doit être configuré >= taille max d'upload + overhead chiffrement
- **Mémoire PHP** : `memory_limit` doit accommoder la taille du fichier × 2 (original + chiffré)
- **LONGBLOB** : max 4 Go — largement suffisant pour les cas d'usage courants
- **Streaming** : pour les fichiers très volumineux, envisager le chiffrement par chunks
  (hors scope initial, à implémenter si un besoin > 100 Mo se manifeste)

## Tests obligatoires

- [ ] Upload → stockage → retrieve → contenu identique
- [ ] Checksum vérifié après retrieve
- [ ] Altération du ciphertext → `IntegrityCheckFailedException`
- [ ] MIME type incorrect rejeté à l'upload
- [ ] Fichier exécutable déguisé rejeté
- [ ] Accès par agent non autorisé → 403 + audit
- [ ] Headers de sécurité présents sur le download
- [ ] Rotation de clé → BLOBs toujours accessibles
- [ ] Soft-delete → BLOB non accessible mais pas supprimé physiquement
