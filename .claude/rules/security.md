# Règles de sécurité

## Principe fondamental

Traite des données sensibles d'agents de surveillance. Toute décision
technique doit prioriser la sécurité. Ce projet vise la conformité avec la
**Loi 25**, **PIPEDA**, **ISO 27001** et **SOC 2**.

## Chiffrement et protection des données

Voir les règles détaillées dans :

- `.claude/rules/encryption.md` — règles de chiffrement AES-256-GCM + TDE
- `.claude/rules/audit-logging.md` — journalisation immuable de tous les accès
- `.claude/rules/data-classification.md` — classification 🔴🟠🟡🟢

Voir les skills d'implémentation dans :

- `.claude/skills/encryption-module/SKILL.md` — module Encryption
- `.claude/skills/encrypted-blob/SKILL.md` — stockage BLOB chiffré
- `.claude/skills/audit-trail/SKILL.md` — module AuditTrail

## Validation des entrées

- **Toujours** valider côté serveur, jamais faire confiance au client
- Utiliser les Form Requests Laravel pour chaque endpoint
- Valider les types, longueurs, formats, plages de valeurs
- Valider les fichiers uploadés : MIME type réel (magic number), pas juste l'extension
- Rejeter les fichiers exécutables même déguisés (`.php`, `.exe`, `.sh`, `.bat`, `.cmd`)
- Sanitiser les noms de fichiers avant stockage

## Autorisation

- Utiliser les **Policies** Laravel pour chaque modèle
- Vérifier le tenant sur chaque requête (isolation multi-tenant)
- Un agent ne peut accéder qu'à ses propres données sauf permission explicite
- L'accès inter-agent est géré par le module `AccessControl` avec audit
- Appliquer le principe du **moindre privilège**
- Utiliser Sanctum pour l'authentification API avec tokens à durée limitée

## Protection contre les injections

### SQL Injection

- **Toujours** utiliser Eloquent ou le Query Builder avec bindings
- **Jamais** de concaténation de chaînes dans les requêtes SQL
- **Jamais** de `DB::raw()` avec des entrées utilisateur non échappées

```php
// ✅ CORRECT
Agent::where('tenant_id', $tenantId)->get();

// ❌ INTERDIT
DB::select("SELECT * FROM agents WHERE tenant_id = {$tenantId}");
```

### XSS (Cross-Site Scripting)

- Bien que l'aplication soit API-first, les réponses JSON doivent être propres
- Échapper toute donnée affichée si un frontend est ajouté
- Headers de sécurité sur chaque réponse (voir section Headers)

### Mass Assignment

- Définir `$fillable` ou `$guarded` sur chaque modèle
- **Jamais** de `$guarded = []` (tout permis)
- Utiliser les Form Requests, pas `$request->all()`

```php
// ✅ CORRECT
Agent::create($request->validated());

// ❌ INTERDIT
Agent::create($request->all());
```

## Headers de sécurité

Configurer via middleware global :

```php
'X-Content-Type-Options'  => 'nosniff',
'X-Frame-Options'         => 'DENY',
'X-XSS-Protection'        => '0',  // Désactivé (CSP est plus fiable)
'Referrer-Policy'          => 'strict-origin-when-cross-origin',
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
'Cache-Control'            => 'no-store',  // Pour les endpoints sensibles
```

## Gestion des erreurs

- **Jamais** de stack trace en production (`APP_DEBUG=false`)
- **Jamais** de donnée sensible dans les messages d'erreur
- Logger les erreurs avec contexte technique, sans PII
- Retourner des messages d'erreur génériques au client

```php
// ✅ CORRECT — message générique, log détaillé
Log::error('Échec de déchiffrement', ['blob_id' => $blobId, 'agent_id' => $agentId]);
return response()->json(['error' => 'Ressource inaccessible.'], 403);

// ❌ INTERDIT — fuite d'information
return response()->json(['error' => "Clé de déchiffrement invalide pour l'agent {$agent->nom}"], 500);
```

## Logging sécurisé

- **Jamais** de PII dans les logs (`Log::info`, `Log::error`, etc.)
- Utiliser des identifiants techniques (IDs) pas des noms/courriels
- Configurer la rétention des logs applicatifs (distinct de l'audit trail)
- Les logs d'audit (accès aux données) passent par le module `AuditTrail`, pas par `Log::`

```php
// ✅ CORRECT
Log::info('Agent mis à jour', ['agent_id' => $agent->id, 'fields' => ['nom', 'telephone']]);

// ❌ INTERDIT
Log::info("Agent {$agent->nom} ({$agent->courriel}) mis à jour");
```

## Connexion à la base de données

- **TLS obligatoire** sur toutes les connexions MySQL (`REQUIRE SSL`)
- Configurer `DB_SSL_CA`, `DB_SSL_CERT`, `DB_SSL_KEY` dans `.env`
- **Jamais** de `VERIFY_PEER = false` en production
- Utilisateur MySQL applicatif avec permissions minimales :
  - `SELECT, INSERT, UPDATE, DELETE` sur les tables métier
  - `INSERT, SELECT` seulement sur `audit_logs`
  - Pas de `DROP`, `ALTER`, `CREATE` (réservé aux migrations)

## Gestion des sessions et tokens

- Tokens Sanctum avec durée de vie limitée
- Invalider les tokens à la déconnexion
- Rate limiting sur les endpoints d'authentification
- Verrouillage de compte après N tentatives échouées (configurable)
- Journaliser toutes les tentatives d'authentification (module AuditTrail)

## Dépendances

- Auditer régulièrement les dépendances (`composer audit`)
- Mettre à jour les patchs de sécurité rapidement
- Vérifier les advisories avant chaque mise en production

## CORS (si applicable)

- Configurer les origines autorisées explicitement
- **Jamais** de `Access-Control-Allow-Origin: *` en production
- Limiter les méthodes et headers autorisés

## Variables d'environnement sensibles

Les variables suivantes ne doivent **jamais** être commitées :

```
APP_KEY
ENCRYPTION_MASTER_KEY
DB_PASSWORD
DB_SSL_CA / DB_SSL_CERT / DB_SSL_KEY
VAULT_TOKEN
SANCTUM_TOKEN_*
MAIL_PASSWORD
```

Utiliser `.env.example` avec des valeurs placeholder uniquement.
