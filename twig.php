<?php

if (C::get('twig', false)) {

    if (!class_exists('Kirby\Component\Template')) {
        throw new Exception('Kirby Twig plugin requires Kirby 2.3 or higher. Current version: ' . kirby()->version());
    }
    if (!class_exists('Twig_Environment')) {
        require_once __DIR__.'/lib/Twig/Twig/Autoloader.php';
        Twig_Autoloader::register();
    }

    require_once __DIR__.'/src/TwigTemplate.php';
    require_once __DIR__.'/src/TwigRenderer.php';

    kirby()->set('component', 'template', 'Kirby\Plugin\Twig\TwigTemplate');

}
