<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Stub;

use App\Modules\Migration\Modules\Migration\Exceptions\StubNotFoundException;
use App\Modules\Migration\Modules\Migration\Interfaces\StubRenderer;

/**
 * Implémentation par défaut : remplace `{{placeholder}}` par les valeurs
 * fournies. Lance StubNotFoundException si le fichier `.stub` n'existe pas.
 *
 * Les placeholders non fournis restent en clair dans la sortie (utile en
 * pipeline multi-passes).
 */
final class DefaultStubRenderer implements StubRenderer
{
    public function __construct(private readonly string $stubDir) {}

    public function render(string $stubName, array $placeholders): string
    {
        $path = $this->stubDir.'/'.$stubName.'.stub';

        if (! is_file($path)) {
            throw StubNotFoundException::for($path);
        }

        $content = (string) file_get_contents($path);

        foreach ($placeholders as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return $content;
    }
}
