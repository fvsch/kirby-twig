<?php

use Kirby\Cms\App;

Kirby::plugin('fvsch/twig', [
    'options' => [
        'usephp' => true
    ],
    'components' => [
        'template' => function (App $kirby, string $name, string $type = 'html') {
            return new fvsch\Twig\Template($kirby, $name, $type);
        }
    ]
]);

require_once __DIR__ . '/src/helpers.php';
