# Classification des données — CRASSQ

## Principe fondamental

**Toute donnée est classifiée avant d'être modélisée.** La classification détermine
le niveau de protection, le type de stockage et les obligations de conformité.

## Niveaux de classification

### 🔴 CRITIQUE — Chiffrement obligatoire + audit complet

Données dont la fuite causerait un préjudice grave à un individu.

| Catégorie | Exemples |
|---|---|
| Identité légale | NAS, numéro de permis, date de naissance |
| Authentification | Mots de passe (hash uniquement), tokens API, clés de session |
| Données financières | Numéro de compte, informations bancaires |
| Données judiciaires | Casier, antécédents, rapports d'enquête |

**Exigences :**
- Chiffrement AES-256-GCM avec DEK per-agent
- Hash irréversible pour les mots de passe (argon2id)
- Audit trail sur chaque lecture et écriture
- Accès restreint au rôle minimum nécessaire
- Jamais dans les logs, URLs, cache, cookies

### 🟠 SENSIBLE — Chiffrement obligatoire + audit

Données personnelles dont la fuite causerait un préjudice modéré.

| Catégorie | Exemples |
|---|---|
| PII de contact | Nom, prénom, courriel, téléphone, adresse postale |
| Localisation | Coordonnées GPS, historique de positions, secteurs assignés |
| Professionnel | Notes d'intervention, rapports de terrain, évaluations |
| Médias | Photos de profil, captures terrain, preuves photographiques |
| Documents | Pièces jointes, formulaires, rapports PDF |

**Exigences :**
- Chiffrement AES-256-GCM avec DEK per-agent
- Stockage BLOB chiffré en BD pour les fichiers
- Audit trail sur chaque accès
- Visibilité inter-agents configurable par module

### 🟡 INTERNE — Protection standard

Données opérationnelles internes sans PII directe.

| Catégorie | Exemples |
|---|---|
| Configuration | Paramètres de modules, préférences d'affichage |
| Métadonnées techniques | Types MIME, tailles de fichiers, versions de clés |
| Données agrégées | Statistiques anonymisées, compteurs |
| Structure | Noms de modules, identifiants de rôles |

**Exigences :**
- Pas de chiffrement applicatif requis (protégé par MySQL TDE)
- Contrôle d'accès par tenant
- Pas d'audit individuel requis

### 🟢 PUBLIC — Aucune restriction

Données destinées à être publiques.

| Catégorie | Exemples |
|---|---|
| Référence | Noms de régions publiques, codes postaux génériques |
| Application | Libellés d'interface, messages d'erreur génériques |

**Exigences :**
- Aucune protection particulière

## Règles de classification

### Avant de créer un modèle ou une migration

1. **Identifier chaque champ** et sa classification (🔴🟠🟡🟢)
2. **Documenter** la classification dans un commentaire PHPDoc sur le modèle
3. **Appliquer** le `EncryptedCast` sur tous les champs 🔴 et 🟠
4. **Vérifier** que le type de colonne est `TEXT` ou `BLOB` pour les champs chiffrés

```php
/**
 * Agent de surveillance.
 *
 * Classification des données :
 * 🔴 CRITIQUE : nas
 * 🟠 SENSIBLE : nom, prenom, courriel, telephone, adresse
 * 🟡 INTERNE  : role_id, tenant_id, is_active
 * 🟢 PUBLIC   : (aucun)
 */
class Agent extends Model
{
    protected function casts(): array
    {
        return [
            'nas'       => EncryptedCast::class,  // 🔴
            'nom'       => EncryptedCast::class,  // 🟠
            'prenom'    => EncryptedCast::class,  // 🟠
            'courriel'  => EncryptedCast::class,  // 🟠
            'telephone' => EncryptedCast::class,  // 🟠
            'adresse'   => EncryptedCast::class,  // 🟠
        ];
    }
}
```

### Doute sur la classification

**En cas de doute, classifier au niveau supérieur.** Un champ qu'on pense 🟡
mais qui pourrait être 🟠 doit être traité comme 🟠.

### Données combinées

Si la combinaison de deux champs 🟡 permet d'identifier un individu, les deux
champs deviennent 🟠. Exemple : `secteur` (🟡) + `horaire_patrouille` (🟡) =
identification possible d'un agent → les deux passent à 🟠.

### Minimisation (Loi 25 art. 4 / PIPEDA principe 4.4)

- Ne collecter que les données **nécessaires** à la finalité déclarée
- Ne pas stocker « au cas où » — si un champ n'a pas de finalité claire, ne pas le créer
- Documenter la finalité de chaque champ 🔴 et 🟠 dans le PHPDoc du modèle
- Prévoir une politique de suppression / anonymisation quand la finalité est atteinte
