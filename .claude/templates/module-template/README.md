# Module Type

Ce dossier est un **template de module**. Pour créer un nouveau module :

1. Copiez ce dossier et renommez-le selon votre module (PascalCase).
2. Remplacez les namespaces `App\Modules\ModuleName\…` par `App\Modules\VotreModule\…`.
3. Enregistrez le `ServiceProvider` du module dans `bootstrap/providers.php`.
4. Chargez les routes, config, vues et traductions depuis le `ServiceProvider`.

La structure suit exactement l'arborescence de `app/` afin que chaque module
soit une application Laravel autonome (MVC + MCP + ressources + traductions).

Raccourci : `.claude/scripts/new-module.sh <PascalCaseName>`
