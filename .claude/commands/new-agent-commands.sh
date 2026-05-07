#!/usr/bin/env bash
#
# new-agent-commands.sh
# Génère les commandes artisan CRUD pour le module Agent.
# Usage : .claude/scripts/new-agent-commands.sh
#
# Prérequis : le module Agent doit exister dans app/Modules/Agent/
#
set -euo pipefail

MODULE_PATH="app/Modules/Agent"
CONSOLE_PATH="${MODULE_PATH}/Console"

if [[ ! -d "$MODULE_PATH" ]]; then
    echo "❌ Le module Agent n'existe pas dans $MODULE_PATH"
    echo "   Créez-le d'abord avec : .claude/scripts/new-module.sh Agent"
    exit 1
fi

mkdir -p "$CONSOLE_PATH"

echo "👤 Création des commandes artisan pour le module Agent..."

# --- agent:create ---
cat > "$CONSOLE_PATH/CreateAgentCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use App\Modules\Encryption\Contracts\KeyManagerInterface;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAgentCommand extends Command
{
    protected $signature = 'agent:create
                            {--tenant= : ID du tenant}';

    protected $description = 'Créer un nouvel agent de surveillance (interactif)';

    public function handle(KeyManagerInterface $keyManager): int
    {
        $tenantId = $this->option('tenant') ?? text(
            label: 'ID du tenant',
            required: true,
        );

        $nom = text(label: 'Nom de famille', required: true);
        $prenom = text(label: 'Prénom', required: true);
        $courriel = text(label: 'Courriel', required: true);
        $telephone = text(label: 'Téléphone');
        $nas = password(label: 'NAS (sera chiffré)');

        $agent = Agent::create([
            'tenant_id' => (int) $tenantId,
            'nom'       => $nom,
            'prenom'    => $prenom,
            'courriel'  => $courriel,
            'telephone' => $telephone,
            'nas'       => $nas,
            'is_active' => true,
        ]);

        // Générer la DEK pour l'agent
        $keyManager->createAgentKey($agent->id);

        $this->info("✅ Agent #{$agent->id} créé avec succès.");
        $this->info("   DEK générée et chiffrée par la KEK.");

        return self::SUCCESS;
    }
}
PHP

# --- agent:list ---
cat > "$CONSOLE_PATH/ListAgentsCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use Illuminate\Console\Command;

class ListAgentsCommand extends Command
{
    protected $signature = 'agent:list
                            {--tenant= : Filtrer par tenant}
                            {--inactive : Inclure les agents inactifs}';

    protected $description = 'Lister les agents de surveillance';

    public function handle(): int
    {
        $query = Agent::query();

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        if (! $this->option('inactive')) {
            $query->where('is_active', true);
        }

        $agents = $query->get();

        if ($agents->isEmpty()) {
            $this->warn('Aucun agent trouvé.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Tenant', 'Nom', 'Prénom', 'Courriel', 'Actif'],
            $agents->map(fn (Agent $agent): array => [
                $agent->id,
                $agent->tenant_id,
                $agent->nom,       // Déchiffré automatiquement via EncryptedCast
                $agent->prenom,
                $agent->courriel,
                $agent->is_active ? '✅' : '❌',
            ]),
        );

        $this->info("Total : {$agents->count()} agent(s)");

        return self::SUCCESS;
    }
}
PHP

# --- agent:show ---
cat > "$CONSOLE_PATH/ShowAgentCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use App\Modules\Encryption\Models\AgentKey;
use App\Modules\Encryption\Models\EncryptedBlob;
use Illuminate\Console\Command;

class ShowAgentCommand extends Command
{
    protected $signature = 'agent:show {id : ID de l\'agent}';

    protected $description = 'Afficher les détails d\'un agent (données déchiffrées)';

    public function handle(): int
    {
        $agent = Agent::find($this->argument('id'));

        if ($agent === null) {
            $this->error("Agent #{$this->argument('id')} introuvable.");

            return self::FAILURE;
        }

        $this->info("Agent #{$agent->id}");
        $this->newLine();

        // Données déchiffrées via EncryptedCast
        $this->table(['Champ', 'Valeur'], [
            ['Tenant ID', $agent->tenant_id],
            ['Nom', $agent->nom],
            ['Prénom', $agent->prenom],
            ['Courriel', $agent->courriel],
            ['Téléphone', $agent->telephone ?? '—'],
            ['NAS', $agent->nas ? '***-***-***' : '—'], // Masqué même en CLI
            ['Actif', $agent->is_active ? 'Oui' : 'Non'],
            ['Créé le', $agent->created_at?->format('Y-m-d H:i')],
            ['Modifié le', $agent->updated_at?->format('Y-m-d H:i')],
        ]);

        // Info clé de chiffrement
        $key = AgentKey::where('agent_id', $agent->id)
            ->where('is_active', true)
            ->first();

        if ($key !== null) {
            $this->info("🔐 Clé de chiffrement : version {$key->key_version} (active)");
        } else {
            $this->warn("⚠️  Aucune clé de chiffrement active !");
        }

        // Nombre de BLOBs
        $blobCount = EncryptedBlob::where('agent_id', $agent->id)->count();
        $this->info("📎 Documents/images : {$blobCount} fichier(s) chiffré(s)");

        return self::SUCCESS;
    }
}
PHP

# --- agent:deactivate ---
cat > "$CONSOLE_PATH/DeactivateAgentCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class DeactivateAgentCommand extends Command
{
    protected $signature = 'agent:deactivate {id : ID de l\'agent}';

    protected $description = 'Désactiver un agent (ne supprime pas les données)';

    public function handle(): int
    {
        $agent = Agent::find($this->argument('id'));

        if ($agent === null) {
            $this->error("Agent #{$this->argument('id')} introuvable.");

            return self::FAILURE;
        }

        if (! $agent->is_active) {
            $this->warn("L'agent #{$agent->id} est déjà inactif.");

            return self::SUCCESS;
        }

        $this->warn("Agent : {$agent->prenom} {$agent->nom} (#{$agent->id})");

        if (! confirm('Confirmer la désactivation ?')) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $agent->update(['is_active' => false]);

        $this->info("✅ Agent #{$agent->id} désactivé.");
        $this->info("   Les données et la DEK sont conservées (chiffrées).");
        $this->info("   Pour réactiver : UPDATE agents SET is_active = 1 WHERE id = {$agent->id}");

        return self::SUCCESS;
    }
}
PHP

# --- agent:export ---
cat > "$CONSOLE_PATH/ExportAgentsCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class ExportAgentsCommand extends Command
{
    protected $signature = 'agent:export
                            {--tenant= : Filtrer par tenant}
                            {--format=csv : Format de sortie (csv, json)}
                            {--output= : Chemin du fichier de sortie}';

    protected $description = 'Exporter la liste des agents (données déchiffrées)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->warn('⚠️  ATTENTION : Vous exportez des données sensibles déchiffrées en production.');

            if (! confirm('Confirmer l\'export ?')) {
                $this->info('Annulé.');

                return self::SUCCESS;
            }
        }

        $query = Agent::where('is_active', true);

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        $agents = $query->get();
        $format = $this->option('format');
        $output = $this->option('output') ?? "agents-export-" . now()->format('Y-m-d-His') . ".{$format}";

        if ($format === 'csv') {
            $this->exportCsv($agents, $output);
        } elseif ($format === 'json') {
            $this->exportJson($agents, $output);
        } else {
            $this->error("Format '{$format}' non supporté. Utilisez csv ou json.");

            return self::FAILURE;
        }

        $this->info("✅ {$agents->count()} agent(s) exporté(s) vers {$output}");
        $this->warn("🔐 Ce fichier contient des données sensibles déchiffrées.");
        $this->warn("   Supprimez-le après utilisation.");

        return self::SUCCESS;
    }

    private function exportCsv($agents, string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['ID', 'Tenant', 'Nom', 'Prénom', 'Courriel', 'Téléphone']);

        foreach ($agents as $agent) {
            fputcsv($handle, [
                $agent->id,
                $agent->tenant_id,
                $agent->nom,
                $agent->prenom,
                $agent->courriel,
                $agent->telephone ?? '',
            ]);
        }

        fclose($handle);
    }

    private function exportJson($agents, string $path): void
    {
        $data = $agents->map(fn ($agent): array => [
            'id'        => $agent->id,
            'tenant_id' => $agent->tenant_id,
            'nom'       => $agent->nom,
            'prenom'    => $agent->prenom,
            'courriel'  => $agent->courriel,
            'telephone' => $agent->telephone,
        ]);

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
PHP

# --- agent:generate-key ---
cat > "$CONSOLE_PATH/GenerateAgentKeyCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use App\Modules\Encryption\Contracts\KeyManagerInterface;
use App\Modules\Encryption\Models\AgentKey;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class GenerateAgentKeyCommand extends Command
{
    protected $signature = 'agent:generate-key
                            {id : ID de l\'agent}
                            {--force : Remplacer si une clé existe déjà}';

    protected $description = 'Générer la DEK (Data Encryption Key) pour un agent';

    public function handle(KeyManagerInterface $keyManager): int
    {
        $agent = Agent::find($this->argument('id'));

        if ($agent === null) {
            $this->error("Agent #{$this->argument('id')} introuvable.");

            return self::FAILURE;
        }

        $existingKey = AgentKey::where('agent_id', $agent->id)
            ->where('is_active', true)
            ->first();

        if ($existingKey !== null && ! $this->option('force')) {
            $this->warn("L'agent #{$agent->id} a déjà une clé active (version {$existingKey->key_version}).");
            $this->info("Utilisez --force pour générer une nouvelle clé (rotation).");

            return self::FAILURE;
        }

        if ($existingKey !== null) {
            if (! confirm("Rotation de la clé pour l'agent #{$agent->id}. Les données seront re-chiffrées. Continuer ?")) {
                $this->info('Annulé.');

                return self::SUCCESS;
            }

            $oldVersion = $keyManager->rotateAgentKey($agent->id);
            $this->info("✅ Clé rotée pour l'agent #{$agent->id}.");
            $this->info("   Ancienne version : {$oldVersion}");
        } else {
            $keyManager->createAgentKey($agent->id);
            $this->info("✅ DEK générée pour l'agent #{$agent->id}.");
        }

        $this->info("   Chiffrée par la KEK et stockée dans agent_keys.");

        return self::SUCCESS;
    }
}
PHP

# --- agent:verify-keys ---
cat > "$CONSOLE_PATH/VerifyAgentKeysCommand.php" << 'PHP'
<?php

declare(strict_types=1);

namespace App\Modules\Agent\Console;

use App\Modules\Agent\Models\Agent;
use App\Modules\Encryption\Contracts\KeyManagerInterface;
use App\Modules\Encryption\Exceptions\DecryptionFailedException;
use App\Modules\Encryption\Exceptions\KeyNotFoundException;
use App\Modules\Encryption\Models\AgentKey;
use Illuminate\Console\Command;

class VerifyAgentKeysCommand extends Command
{
    protected $signature = 'agent:verify-keys
                            {--tenant= : Filtrer par tenant}';

    protected $description = 'Vérifier l\'intégrité des DEK de tous les agents';

    public function handle(KeyManagerInterface $keyManager): int
    {
        $query = Agent::where('is_active', true);

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        $agents = $query->get();
        $pass = 0;
        $fail = 0;

        $this->info("🔐 Vérification des DEK pour {$agents->count()} agent(s)...");
        $this->newLine();

        foreach ($agents as $agent) {
            try {
                // Tenter de récupérer et déchiffrer la DEK
                $keyManager->getAgentKey($agent->id);

                $activeKey = AgentKey::where('agent_id', $agent->id)
                    ->where('is_active', true)
                    ->first();

                $this->line("   ✅ Agent #{$agent->id} — version {$activeKey?->key_version}");
                $pass++;
            } catch (KeyNotFoundException $e) {
                $this->error("   ❌ Agent #{$agent->id} — Aucune clé trouvée");
                $fail++;
            } catch (DecryptionFailedException $e) {
                $this->error("   ❌ Agent #{$agent->id} — Échec de déchiffrement de la DEK");
                $fail++;
            } catch (\Throwable $e) {
                $this->error("   ❌ Agent #{$agent->id} — Erreur : {$e->getMessage()}");
                $fail++;
            }
        }

        $this->newLine();
        $this->info("📊 Résultat : {$pass} OK, {$fail} ÉCHEC");

        if ($fail > 0) {
            $this->error("⚠️  {$fail} agent(s) avec des problèmes de clé !");
            $this->info("   Utilisez agent:generate-key {id} pour régénérer les clés manquantes.");

            return self::FAILURE;
        }

        $this->info("✅ Toutes les clés sont valides.");

        return self::SUCCESS;
    }
}
PHP

FILE_COUNT=$(find "$CONSOLE_PATH" -name "*.php" -type f | wc -l)

echo ""
echo "✅ Commandes artisan Agent créées avec succès !"
echo "   📁 $CONSOLE_PATH"
echo "   📄 $FILE_COUNT commandes générées"
echo ""
echo "   Commandes disponibles :"
echo "   - php artisan agent:create"
echo "   - php artisan agent:list"
echo "   - php artisan agent:show {id}"
echo "   - php artisan agent:deactivate {id}"
echo "   - php artisan agent:export --format=csv"
echo "   - php artisan agent:generate-key {id}"
echo "   - php artisan agent:verify-keys"
echo ""
echo "   N'oubliez pas d'enregistrer les commandes dans le ServiceProvider du module Agent."
