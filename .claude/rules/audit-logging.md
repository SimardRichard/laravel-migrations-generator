# Règles de journalisation d'audit — CRASSQ

## Principe fondamental

**Tout accès, modification ou tentative d'accès à des données sensibles est
journalisé de manière immuable.** Les logs d'audit servent à la conformité
(Loi 25, PIPEDA, ISO 27001, SOC 2) et à la détection d'incidents.

## Quoi journaliser

### Événements obligatoires

| Événement | Détails requis |
|---|---|
| Lecture de donnée chiffrée | `user_id`, `agent_id` cible, champ(s), IP, timestamp |
| Création de donnée sensible | `user_id`, `agent_id`, champ(s), IP, timestamp |
| Modification de donnée sensible | `user_id`, `agent_id`, champ(s), IP, timestamp |
| Suppression de donnée | `user_id`, `agent_id`, type, IP, timestamp |
| Accès inter-agent | demandeur, cible, permission, résultat (accordé/refusé) |
| Tentative d'accès refusée | `user_id`, ressource, raison, IP, timestamp |
| Upload / download de BLOB | `user_id`, `blob_id`, `agent_id`, action, IP, user-agent |
| Rotation de clé | `agent_id`, ancienne version, nouvelle version, timestamp |
| Connexion / déconnexion | `user_id`, IP, user-agent, résultat |
| Échec d'authentification | identifiant tenté, IP, user-agent, timestamp |

### Événements d'intégrité

| Événement | Détails requis |
|---|---|
| Échec de vérification checksum | `blob_id`, checksum attendu vs calculé |
| Échec de déchiffrement (tag GCM invalide) | `blob_id` ou champ, `agent_id` |
| Clé introuvable | `agent_id`, `key_version` demandée |

## Format des logs

```php
// Structure d'un enregistrement d'audit
[
    'id'            => /* BIGINT AUTO_INCREMENT */,
    'tenant_id'     => /* BIGINT UNSIGNED */,
    'event_type'    => /* VARCHAR(50) — ex: 'data.read', 'data.write', 'access.denied' */,
    'user_id'       => /* BIGINT UNSIGNED — qui a fait l'action */,
    'agent_id'      => /* BIGINT UNSIGNED NULLABLE — agent cible */,
    'resource_type' => /* VARCHAR(100) — ex: 'Agent', 'EncryptedBlob' */,
    'resource_id'   => /* BIGINT UNSIGNED NULLABLE */,
    'field_name'    => /* VARCHAR(100) NULLABLE — champ spécifique */,
    'result'        => /* ENUM('success', 'denied', 'error') */,
    'ip_address'    => /* VARCHAR(45) — IPv4 ou IPv6 */,
    'user_agent'    => /* VARCHAR(500) NULLABLE */,
    'metadata'      => /* JSON NULLABLE — détails additionnels */,
    'created_at'    => /* TIMESTAMP — jamais modifiable */,
]
```

## Taxonomie des event_type

Utiliser la notation pointée, toujours en minuscules :

- `data.read` — lecture de donnée chiffrée
- `data.write` — création ou modification
- `data.delete` — suppression
- `blob.upload` — upload de fichier/image
- `blob.download` — téléchargement de fichier/image
- `access.granted` — accès inter-agent accordé
- `access.denied` — accès refusé
- `auth.login` — connexion réussie
- `auth.logout` — déconnexion
- `auth.failed` — échec d'authentification
- `key.rotated` — rotation de clé
- `integrity.checksum_failed` — échec de checksum
- `integrity.decryption_failed` — échec de déchiffrement

## Règles d'immuabilité

- **INSERT only** : aucun UPDATE, aucun DELETE sur la table `audit_logs`
- L'utilisateur MySQL applicatif n'a que `INSERT` et `SELECT` sur `audit_logs`
- Aucun soft-delete, aucun archivage qui supprime
- Rétention minimum : **7 ans**
- Partitionnement par mois recommandé pour les performances

## Interdictions absolues

- ❌ **Jamais** de valeur déchiffrée dans les logs (pas de contenu PII)
- ❌ **Jamais** de `UPDATE` ou `DELETE` sur `audit_logs`
- ❌ **Jamais** de log d'audit dans le filesystem seul (BD obligatoire)
- ❌ **Jamais** de désactivation de l'audit en environnement de production
- ❌ **Jamais** de mots de passe ou tokens dans les logs
- ❌ **Jamais** de troncation automatique des logs avant 7 ans

## Implémentation

- L'audit est déclenché par des **Events Laravel** (`DataDecrypted`, `DataWritten`, etc.)
- Les listeners sont asynchrones (queue) pour ne pas impacter les performances
- Le module `AuditTrail` gère toute la logique
- Les autres modules émettent les événements, jamais d'écriture directe dans `audit_logs`

## Tests

- Tester que chaque accès à un champ chiffré génère un log
- Tester que les tentatives refusées sont journalisées
- Tester qu'aucune valeur sensible n'apparaît dans les logs
- Tester l'immuabilité : toute tentative d'UPDATE/DELETE échoue
- Tester le format : tous les champs requis sont présents
