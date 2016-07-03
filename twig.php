<?php

if (c::get('twig', false)) {

	if (!class_exists('Kirby\Component\Template')) {
		throw new Exception('Twig plugin requires Kirby 2.3 or higher. Current version: ' . kirby()->version());
	}
	if (!class_exists('Twig_Environment')) {
		if (file_exists($loader = __DIR__.'/lib/Twig/Twig/Autoloader.php')) {
			require_once $loader;
			\Twig_Autoloader::register();
		} else {
			throw new Exception('Twig plugin: cannot find the Twig library');
		}
	}

	require_once __DIR__ . '/src/component.php';
	kirby()->set('component', 'template', 'Kirby\Plugin\Twig\TwigComponent');

}
