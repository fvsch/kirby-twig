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
class TwigComponent extends Kirby\Component\Template
{

	/**
	 * Kirby Helper functions to expose as simple Twig functions
	 * This is purposefuly a subset of https://getkirby.com/docs/cheatsheet#helpers
	 * and a even stricter subset of https://getkirby.com/docs/toolkit/api#helpers
	 * It’s limited to things helpful for HTML templating. (For example the csrf() helper is
	 * not used, since checking for cross-site reequest forgery is better left to a controller!)
	 *
	 * @var array
	 */
	private $helpersList = [
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
	 * Renders the template by page with the additional data
	 *
	 * @param Page|string $template
	 * @param array $data
	 * @param boolean $return
	 * @return string
	 * @throws Exception
	 */
	public function render($template, $data = [], $return = true) {

		// We have to reimplement all of the logic of $page->templateFile()
		// and sadly we can’t fix $page->hasTemplate() (which hardcodes looking
		// for a .php file).
		$dir = $this->kirby->roots()->templates();

		// List of template paths we're going to render, by priority
		$paths = [
			'twig:intended' => null,
			'php:intended'  => null,
			'twig:default'  => $dir . DS . 'default.twig',
			'php:default'   => $dir . DS . 'default.php'
		];

		if($template instanceof Page) {
			$page = $template;
			$name = $page->intendedTemplate();
			$paths['twig:intended'] = $dir . DS . $name . '.twig';
			$paths['php:intended']  = $dir . DS . $name . '.php';
			$data = $this->data($page, $data);
		}
		// I couldn't find any code path that calls the render function
		// with the path of a PHP file instead of a Page object.
		// Still keeping this for compatibility with Kirby\Component\Template
		elseif (substr($template, -4) === '.php') {
			$paths['twig:intended'] = str_replace('.php', '.twig', $template);
			$paths['php:intended'] = $template;
			$data = $this->data(null, $data);
		}

		// Let's see if we have an intended or default template
		$found = null;
		foreach ($paths as $key => $path) {
			if (is_file($path)) {
				$found = [ 'type' => explode(':', $key)[0], 'path' => $path ];
				break;
			}
		}
		if ($found == null) {
			throw new Exception('The template could not be found');
		}

		// Merge and register the template data globally
		tpl::$data = array_merge(tpl::$data, $data);

		// Render using Twig or Kirby's default PHP rendering
		if ($found['type'] === 'twig') {
			return $this->renderTwig($found['path'], $return);
		}
		else {
			return tpl::load($found['path'], null, $return);
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

		$tplDir   = $this->kirby->roots()->templates();
		$cacheDir = $this->kirby->roots()->cache() . DS . 'twig';
		$options  = [
			'debug' => c::get('debug', false),
			'strict_variables' => c::get('debug', false)
		];

		// Use Twig cache if using Kirby cache PLUS asking for Twig cache
		if (c::get('cache', false) and c::get('plugin.twig.cache', false)) {
			$options['cache'] = $cacheDir;
		}

		// Use escaping by default, unless set otherwise
		$options['autoescape'] = c::get('plugin.twig.autoescape', true);

		// Start up Twig
		$twig = new Twig_Environment(new Twig_Loader_Filesystem($tplDir), $options);

		// Plug in our selected list of helper functions
		foreach ($this->helpersList as $name) {
			$twig->addFunction(new Twig_SimpleFunction($name, $name));
		}

		// Add the main variables (page, pages, site, kirby + controller stuff)
		foreach (tpl::$data as $key=>$item) {
			$twig->addGlobal($key, $item);
		}

		// Enable Twig’s dump function
		$twig->addExtension(new Twig_Extension_Debug());

		// Render the template (should we catch Twig_Error?)
		$content = $twig->render( str_replace($tplDir, '', $file) );
		if($return) return $content;
		echo $content;
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
