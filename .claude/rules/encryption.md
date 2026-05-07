# Règles de chiffrement — CRASSQ

## Principe fondamental

**Toute donnée sensible est chiffrée au niveau applicatif avec AES-256-GCM avant
d'être écrite en base de données.** MySQL TDE fournit une couche supplémentaire
at-rest, mais ne dispense jamais du chiffrement applicatif.

## Quand chiffrer

- **Toujours** : PII (nom, prénom, courriel, téléphone, NAS, adresse), coordonnées
  GPS, notes d'intervention, historiques, métadonnées de localisation
- **Toujours** : fichiers, images, documents, PDF → BLOB chiffré en BD
- **Hash, jamais chiffrer** : mots de passe (bcrypt / argon2id)
- **Ne pas chiffrer** : identifiants techniques (id, tenant_id, clés étrangères),
  timestamps système, types MIME, tailles de fichiers

## Comment chiffrer

### Champs textuels sur les modèles

```php
// Utiliser le cast EncryptedCast du module Encryption
protected function casts(): array
{
    return [
        'nom'       => EncryptedCast::class,
        'courriel'  => EncryptedCast::class,
        // ... tous les champs sensibles
    ];
}
```

- Le cast gère le chiffrement/déchiffrement transparent via Eloquent
- Le type de colonne en BD doit être `TEXT` ou `BLOB` (jamais `VARCHAR` — la
  sortie chiffrée est plus longue que l'entrée)
- Chaque opération de déchiffrement déclenche un événement `DataDecrypted`

### Fichiers et images

```php
// Utiliser le BlobStorageInterface — jamais Storage::put()
app(BlobStorageInterface::class)->store(
    agentId: $agent->id,
    content: $file->getContent(),
    filename: $file->getClientOriginalName(),
    mimeType: $file->getMimeType(),
);
```

- Stockage exclusif dans la table `encrypted_blobs`
- IV et tag GCM stockés dans des colonnes dédiées
- Checksum SHA-256 du contenu original pour validation d'intégrité
- `key_version` pour supporter la rotation

### Architecture de clés

- **KEK** (Key Encryption Key) : variable d'environnement ou vault externe, jamais en BD
- **DEK** (Data Encryption Key) : une par agent, stockée chiffrée par la KEK dans `agent_keys`
- Pour partager entre agents : re-chiffrer avec la DEK du destinataire via le module AccessControl
- Jamais de clé en clair dans le code, les logs, les configs versionnées

## Interdictions

- ❌ `Storage::put()` / `Storage::disk()` pour des données sensibles
- ❌ AES-CBC (utiliser AES-256-GCM exclusivement)
- ❌ Clé de chiffrement en dur dans le code
- ❌ `openssl_encrypt()` direct — passer par `EncryptionServiceInterface`
- ❌ Donnée sensible dans : logs, URLs, query strings, cookies, cache, réponses d'erreur
- ❌ `VERIFY_PEER = false` en production
- ❌ Backup non chiffré
- ❌ IV réutilisé (toujours générer un IV aléatoire par opération)

## Migrations

- Toute colonne contenant un champ sensible doit être de type `TEXT` ou `BLOB`
- Ajouter le commentaire `-- chiffré AES-256-GCM` dans la migration
- Toute table contenant des données sensibles doit avoir `ENCRYPTION='Y'` (TDE)
- Index sur colonnes chiffrées : utiliser un **blind index** (HMAC-SHA256 tronqué),
  jamais indexer la valeur chiffrée directement

## Tests

- Tester que les données en BD sont bien chiffrées (pas lisibles en clair)
- Tester le cycle complet : chiffrement → stockage → lecture → déchiffrement
- Tester la rotation de clé : données re-chiffrées, anciennes clés invalidées
- Tester l'accès inter-agent : permission accordée / refusée
- Tester l'intégrité : checksum SHA-256 vérifié après déchiffrement
- Tester le tag GCM : toute altération du ciphertext doit lever une exception
