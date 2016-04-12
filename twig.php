<?php

$enabled = c::get('plugin.twig.enabled', false);

// Check compatible version
if ($enabled) {
	$version = explode('.', kirby()->version());
	if ($version[0] < 2 or $version[1] < 3) {
		throw new Exception('Twig plugin requires Kirby 2.3 or higher. Current version: ' . kirby()->version());
	}
}


/**
 * Twig Template Builder Component for Kirby
 *
 * This component class extends Kirby’s built-in Kirby\Component\Template
 * class and replaces it’s render method.
 *
 * @package   Kirby CMS Twig Plugin
 * @author    Florens Verschelde <florens@fvsch.com>
 * @version   1.0.1
 */
class TwigComponent extends Kirby\Component\Template {

	/**
	 * Kirby Helper functions to expose as simple Twig functions
	 *
	 * This is purposefuly a subset of https://getkirby.com/docs/cheatsheet#helpers
	 * and a even stricter subset of https://getkirby.com/docs/toolkit/api#helpers
	 * It’s limited to things helpful for HTML templating. (For example the csrf() helper is
	 * not used, since checking for cross-site reequest forgery is better left to a controller!)
	 *
	 * Use Twig functions for: helpers that build a HTML tag or retrieve some data.
	 *
	 * @var array
	 */
	private $toTwigFunctions = [
		// HTML tags generators
		'css', 'js', 'kirbytag',
		// Service-specific HTML generation
		'youtube', 'vimeo', 'twitter', 'gist',
		// URL and request stuff
		'get', 'thisUrl', 'param', 'params',
		// Parsing strings
		'yaml',
		// Debug
		'memory'
	];

	/**
	 * Kirby Helper functions to expose as simple Twig filters
	 *
	 * Use Twig filters for: helpers that transforms a string to a string
	 *
	 * @var array
	 */
	private $toTwigFilters = [
		// High-level text transformations
		'markdown', 'smartypants', 'kirbytext', 'multiline', 'excerpt',
		// String escaping (note that Twig as its own |escape filter)
		'html', 'xml',
		// String building
		'url', 'gravatar'
	];

	/**
	 * Returns a template file path by name
	 *
	 * @param string $name
	 * @return string
	 */
	public function file($name) {
		$usephp = c::get('plugin.twig.usephp', true);
		$base = $this->kirby->roots()->templates() . DS . str_replace('/', DS, $name);
		$twig = $base . '.twig';
		$php  = $base . '.php';
		// Only check existing files if PHP template support is active
		if ($usephp and !is_file($twig) and is_file($php)) {
			return $php;
		} else {
			return $twig;
		}
	}

	/**
	 * Renders the template by page with the additional data
	 *
	 * @param Page|string $template
	 * @param array $data
	 * @param boolean $return
	 * @return string
	 * @throws Exception
	 */
	public function render($template, $data = [], $return = true) {

		if ($template instanceof Page) {
			$page = $template;
			$file = $page->templateFile();
			$data = $this->data($page, $data);
		} else {
			$file = $template;
			$data = $this->data(null, $data);
		}

		// check for an existing template
		if (!file_exists($file)) {
			throw new Exception('The template could not be found');
		}

		// merge and register the template data globally
		Tpl::$data = array_merge(Tpl::$data, $data);

		// Render using Twig or Kirby's default PHP rendering
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if ($ext === 'twig') {
			return $this->renderTwig($file, $return);
		} elseif ($ext === 'php') {
			return Tpl::load($file, null, $return);
		} else {
			throw new Exception('Invalid template path: ' . basename($file));
		}
	}

	/**
	 * Render a Twig template, similarly to how Tpl::load renders a PHP template
	 *
	 * @param $file
	 * @param bool $return
	 * @return string
	 */
	private function renderTwig($file, $return = true) {

		$debug = c::get('debug', false);
		$dir   = $this->kirby->roots()->templates();
		$cache = c::get('cache', false) and c::get('plugin.twig.cache', false) ?
			     $this->kirby->roots()->cache() . DS . 'twig' : false;

		$options  = [
			'debug' => $debug,
			'strict_variables' => $debug,
			'cache' => $cache,
			'autoescape' => c::get('plugin.twig.autoescape', true)
		];

		// Start up Twig
		$twig = new Twig_Environment(new Twig_Loader_Filesystem($dir), $options);

		// Add the snippet helper and mark it as safe for HTML output
		$twig->addFunction(new Twig_SimpleFunction('snippet', 'snippet', ['is_safe' => ['html']]));

		// Plug in our selected list of helper functions
		foreach ($this->toTwigFunctions as $name) {
			$twig->addFunction(new Twig_SimpleFunction($name, $name));
		}
		foreach ($this->toTwigFilters as $name) {
			$twig->addFilter(new Twig_SimpleFilter($name, $name));
		}

		// Enable Twig’s dump function
		if ($debug) $twig->addExtension(new Twig_Extension_Debug());

		// Render the template (should we catch Twig_Error?)
		$content = $twig->render( str_replace($dir, '', $file), Tpl::get() );
		if ($return) return $content;
		else echo $content;
	}

}


// Only replace the Template component if Twig is installed
// and enabled in the user’s config.

if ($enabled) {
	// Check the outside Twig already loaded.
	if (! class_exists('Twig_Environment')) {
		// Check the plugin composer autoload.
		if (! file_exists(__DIR__ . DS . 'vendor' . DS . 'autoload.php')) {
			throw new Exception('Twig plugin: the Twig library was not installed. Run composer install.');
		}
		
		// Load the composer autoload.
		require_once (__DIR__ . DS . 'vendor' . DS . 'autoload.php');
		
		// Check the composer autoload for Twig library installed correctly.
		if (! class_exists('Twig_Environment')) {
			throw new Exception('Twig plugin: the Twig library cannot be load. Please check the composer installation.');
		}
	}
	
	$kirby->set('component', 'template', 'TwigComponent');
}
