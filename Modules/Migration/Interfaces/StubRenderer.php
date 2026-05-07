<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Interfaces;

/**
 * Contrat d'un moteur de rendu de stubs (templates de migrations).
 *
 * Vit au niveau 2 (core) car partagé par Generator et potentiellement
 * Extract/Import dans le futur (templates d'export par exemple).
 */
interface StubRenderer
{
    /** @param array<string, string> $placeholders */
    public function render(string $stubName, array $placeholders): string;
}
