---
name: mcp-tool
description: Crée un outil MCP (Model Context Protocol) avec laravel/mcp. Utiliser pour "créer un tool MCP", "ajouter un outil MCP", "nouveau serveur MCP".
---

# Créer un outil MCP

## Emplacement
- Outils globaux : `app/Mcp/Tools/{Nom}Tool.php`
- Outils d'un module : `app/Modules/{Module}/Mcp/Tools/{Nom}Tool.php`

## Structure minimale
Voir `.claude/templates/mcp-tool.stub` pour le squelette.

## Enregistrement
Dans le `McpServiceProvider` du module (ou global) :
```php
Mcp::server('gsti')
    ->tool(MonTool::class)
    ->prompt(MonPrompt::class)
    ->resource(MaResource::class);
```

## Bonnes pratiques
- Un tool = une action atomique (create/update/fetch). Pas de "mega-tool".
- Schéma d'entrée **strictement typé** via `inputSchema()`.
- Retour : toujours un `ToolResult` avec contenu structuré (pas de string libre).
- Gestion d'erreur : lancer `McpException` avec code approprié.
- Tests dans `tests/Feature/Mcp/{Nom}ToolTest.php`.
