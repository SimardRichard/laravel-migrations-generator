<?php

declare(strict_types=1);

return [
    /*
    | Sous-modules à activer. Désactiver l'un n'enregistre pas son
    | ServiceProvider, ses commandes ou ses bindings.
    */
    'submodules' => [
        'generator' => true,
        'extract' => true,
        'import' => true,
    ],
];
