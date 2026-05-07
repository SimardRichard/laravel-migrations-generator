<?php

declare(strict_types=1);

namespace App\Modules\Migration\Modules\Migration\Modules\Generator\Drivers\Resolvers;

/**
 * Convertit une valeur par défaut SQL brute en valeur PHP typée.
 *
 * Stratégie :
 * 1. NULL → null PHP.
 * 2. CURRENT_TIMESTAMP / NOW() → RawDefault('CURRENT_TIMESTAMP') (canonisé).
 * 3. Toute autre expression entre parenthèses → RawDefault.
 * 4. Chaîne entre quotes simples ou doubles → string PHP (sans les quotes).
 * 5. true/false littéraux → bool PHP.
 * 6. Entier ou flottant numérique → int|float PHP.
 * 7. Fallback → string brute trimée.
 */
final class DefaultValueResolver
{
    /** @var list<string> */
    private const TIMESTAMP_FORMS = ['CURRENT_TIMESTAMP', 'NOW()', 'CURRENT_TIMESTAMP()'];

    public function resolve(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        if (in_array(strtoupper($trimmed), self::TIMESTAMP_FORMS, true)) {
            return new RawDefault('CURRENT_TIMESTAMP');
        }

        if (str_contains($trimmed, '(') && str_ends_with($trimmed, ')')) {
            return new RawDefault($trimmed);
        }

        if (preg_match("/^'(.*)'$/", $trimmed, $m) === 1
            || preg_match('/^"(.*)"$/', $trimmed, $m) === 1) {
            return $m[1];
        }

        $lower = strtolower($trimmed);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $trimmed) === 1) {
            return (int) $trimmed;
        }
        if (preg_match('/^-?\d+\.\d+$/', $trimmed) === 1) {
            return (float) $trimmed;
        }

        return $trimmed;
    }
}
