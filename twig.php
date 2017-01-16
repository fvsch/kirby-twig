<?php

/**
 * @package Kirby Twig Plugin
 * @file Main plugin file when installed manually or with Kirby’s CLI; not used with Composer.
 */

load([
    'kirby\twig\plugin'        => __DIR__.'/src/Plugin.php',
    'kirby\twig\twigcomponent' => __DIR__.'/src/TwigComponent.php',
    'kirby\twig\twigenv'       => __DIR__.'/src/TwigEnv.php'
]);

if (c::get('twig', false)) {
    Kirby\Twig\Plugin::register();
}
