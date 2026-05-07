# Règle #1 : TOUT EST MODULAIRE

## Décision
Tout code métier, sans exception, vit dans `app/Modules/{ModuleName}/`.

## Comment décider où va un fichier ?
```
┌─────────────────────────────────────────────────────────┐
│  Est-ce du code métier (feature, endpoint, model, …) ?  │
└────────────────────┬────────────────────────────────────┘
                     │
              ┌──────┴──────┐
              │             │
             OUI           NON
              │             │
              ▼             ▼
   app/Modules/{Name}/    Est-ce utilisé par PLUSIEURS modules
                          comme infrastructure partagée ?
                               │
                        ┌──────┴──────┐
                        │             │
                       OUI           NON
                        │             │
                        ▼             ▼
                     app/       Retour à : c'est un module
                                (crée-le si nécessaire)
```

## Exemples concrets
| Besoin                                | Destination                                          |
| ------------------------------------- | ---------------------------------------------------- |
| Endpoint POST /api/invoices           | `app/Modules/Invoicing/Http/Controllers/…`           |
| Model Invoice                         | `app/Modules/Invoicing/Models/Invoice.php`           |
| Service calcul de taxes               | `app/Modules/Taxation/Services/TaxCalculator.php`    |
| MCP Tool "get_invoice"                | `app/Modules/Invoicing/Mcp/Tools/GetInvoiceTool.php` |
| Job d'envoi d'email                   | `app/Modules/Notifications/Jobs/SendEmailJob.php`    |
| Middleware "EnsureUserIsAdmin"        | `app/Http/Middleware/` (transversal)                 |
| Base Controller                       | `app/Http/Controllers/Controller.php` (framework)    |
| Handler d'exception global            | `app/Exceptions/Handler.php` (framework)             |
| Serveur MCP racine qui agrège modules | `app/Mcp/Servers/`                                   |

## Critères pour créer un nouveau module
Créer un module dès que **l'une** de ces conditions est vraie :
- Nouveau domaine métier (facturation, stock, RH, …)
- Nouveau sous-domaine avec 3+ endpoints
- Ensemble cohérent de 2+ models avec leurs règles métier
- Intégration externe (passerelle de paiement, SSO, API tierce)

## Anti-patterns qui bloquent un merge
- Controller métier dans `app/Http/Controllers/`
- Model métier dans `app/Models/`
- Service métier dans `app/Services/`
- Policy dans `app/Policies/` (sauf policies transversales explicites)
- Route métier dans `routes/api.php` racine
- Migration métier dans `database/migrations/` racine

## Quand un agent/développeur écrit du code
**Avant** d'ouvrir un fichier en écriture :
1. Identifier le module cible (existant ou à créer)
2. Vérifier que le chemin commence bien par `app/Modules/{Name}/`
3. Si c'est vraiment transversal, justifier explicitement dans le commit
