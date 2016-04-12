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
 * @version   1.0.0
 */
class TwigComponent extends Kirby\Component\Template {

	/**
	 * Kirby Helper functions to expose as simple Twig functions
	 * This is purposefuly a subset of https://getkirby.com/docs/cheatsheet#helpers
	 * and a even stricter subset of https://getkirby.com/docs/toolkit/api#helpers
	 * It’s limited to things helpful for HTML templating. (For example the csrf() helper is
	 * not used, since checking for cross-site reequest forgery is better left to a controller!)
	 *
	 * @var array
	 */
	private $toTwigFunctions = [
		// Templating essentials
		'snippet',
		// High-level text transformations
		'markdown', 'smartypants', 'kirbytext', 'multiline', 'excerpt', 'widont',
		// String escaping
		'esc', 'html', 'xml',
		// HTML tags generators
		'css', 'js', 'image',
		// Use Kirby tags
		'kirbytag',
		// Service-specific HTML generation
		'youtube', 'vimeo', 'twitter', 'gist', 'gravatar',
		// Parsing strings
		'yaml',
		// URL and request stuff
		'url', 'get', 'thisUrl', 'param', 'params',
		// Debug
		'memory'
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
		tpl::$data = array_merge(tpl::$data, $data);

		// Render using Twig or Kirby's default PHP rendering
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if ($ext === 'twig') {
			return $this->renderTwig($file, $return);
		} elseif ($ext === 'php') {
			return tpl::load($file, null, $return);
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

		// Plug in our selected list of helper functions
		foreach ($this->toTwigFunctions as $name) {
			$twig->addFunction(new Twig_SimpleFunction($name, $name));
		}

		// Add the main variables (page, pages, site, kirby + controller stuff)
		foreach (tpl::$data as $key=>$item) {
			$twig->addGlobal($key, $item);
		}

		// Enable Twig’s dump function
		if ($debug) $twig->addExtension(new Twig_Extension_Debug());

		// Render the template (should we catch Twig_Error?)
		$content = $twig->render( str_replace($dir, '', $file) );
		if ($return) return $content;
		else echo $content;
	}

}


// Only replace the Template component if Twig is installed
// and enabled in the user’s config.

if ($enabled) {
	if (file_exists(__DIR__ . DS . 'vendor' . DS . 'autoload.php')) {
		require_once (__DIR__ . DS . 'vendor' . DS . 'autoload.php');
		$kirby->set('component', 'template', 'TwigComponent');
	} else {
		throw new Exception('Twig plugin: the Twig library was not installed. Run composer install.');
	}
}
