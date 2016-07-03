<?php

namespace Kirby\Plugin\Twig;

use C;
use Escape;
use Exception;
use F;
use Kirby\Component\Template;
use Page;
use Response;
use Tpl;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;
use Twig_Extension_Debug;
use Twig_Error;


$enabled = C::get('plugin.twig.enabled', false);

if ($enabled) {
	if (!class_exists('Kirby\Component\Template')) {
		throw new Exception('Twig plugin requires Kirby 2.3 or higher. Current version: ' . kirby()->version());
	}
	if (!class_exists('Twig_Environment')) {
		if (file_exists($loader = __DIR__.DS.'lib'.DS.'Twig'.DS.'Autoloader.php')) {
			require_once $loader;
			\Twig_Autoloader::register();
		} else {
			throw new Exception('Twig plugin: cannot find the Twig library');
		}
	}
}


/**
 * Twig Template Builder Component for Kirby
 *
 * This component class extends Kirby’s built-in Kirby\Component\Template
 * class and replaces it’s render method.
 *
 * @package   Kirby Twig Plugin
 * @author    Florens Verschelde <florens@fvsch.com>
 * @version   1.3.0
 */
class TwigComponent extends Template {

	/**
	 * How many times we have tried to render a page through Kirby
	 * (hence possibly through Twig) from the renderTwigError method.
	 */
	private $renderTwigErrorCount = 0;

	/**
	 * Kirby helper functions to expose as simple Twig functions
	 *
	 * We're exposing all helper functions documented in
	 * https://getkirby.com/docs/cheatsheet#helpers
	 * with just a few exceptions (sending email, saving files…)
	 *
	 * Prefix the function name with '*' to mark the
	 * function's output as safe (avoiding HTML escaping).
	 *
	 * @var array
	 */
	private $exposedHelpers = [
		'*attr',
		'*brick',
		'csrf',
		'*css',
		// Skipping: dump - Twig has one, and its ouput seems buggy anyway (prints the result twice?)
		// Skipping: e, ecco - Twig syntax is simple: {{ condition ? 'a' : 'b' }}
		// Skipping: email - Send emails from controllers, not templates
		'*esc',
		'*excerpt',
		'get',
		'*gist',
		'go',
		'gravatar',
		'*h', '*html',
		'*image',
		'invalid',
		'*js',
		'kirby',
		'*kirbytag',
		'*kirbytext',
		'*l',
		'*markdown',
		'memory',
		'*multiline',
		'page',
		'pages',
		'param',
		'params',
		// Skipping: r - Same reason as for ecco/e
		'site',
		'size',
		'*smartypants',
		'*snippet',
		// Skipping: structure - For writing data to pages, not for display
		// Skipping: textfile - For making content file names
		'thisUrl',
		'*thumb',
		'*twitter',
		// Skipping: upload - Manage uploading from a controller
		'u', 'url',
		'*vimeo',
		'*widont',
		'*xml',
		'yaml',
		'*youtube'
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
		$cache = $this->kirby->roots()->cache() . DS . 'twig';

		$options  = [
			'debug' => $debug,
			'strict_variables' => c::get('plugin.twig.strict', $debug),
			'cache' => c::get('plugin.twig.cache', false) ? $cache : false,
			'autoescape' => c::get('plugin.twig.autoescape', true)
		];

		// Start up Twig
		$twig = new Twig_Environment(new Twig_Loader_Filesystem($dir), $options);

		// Add functions to retrieve config keys
		$twig->addFunction(new Twig_SimpleFunction('c', 'c::get'));
		$twig->addFunction(new Twig_SimpleFunction('l', 'l::get'));

		// Plug in our selected list of helper functions
		foreach ($this->exposedHelpers as $name) {
			$clean = trim($name, '* ');
			$param = strpos($name, '*') !== false ? ['is_safe' => ['html']] : [];
			if (is_callable($clean)) {
				$twig->addFunction(new Twig_SimpleFunction($clean, $clean, $param));
			}
		}

		// Enable Twig’s dump function
		if ($debug) $twig->addExtension(new Twig_Extension_Debug());

		// Render the template
		try {
			$path = str_replace($dir, '', $file);
			$path = str_replace('\\', '/', $path);
			$path = str_replace('//', '/', $path);
			$path = preg_replace('/^\//', '', $path);
			$content = $twig->render($path, Tpl::get());
		}
		catch(Twig_Error $e) {
			$errorPages = [];
			$customPage = c::get('plugin.twig.errorpage', '');
			if ($customPage) $errorPages[] = $customPage;
			$errorPages[] = c::get('error', 'error');
			$content = $this->renderTwigError($e, $errorPages);
		}

		if ($return) {
			return $content;
		} else {
			echo $content;
			return null;
		}
	}

	/**
	 * Show an error page for a Twig_Error, with the faulty Twig code if we can.
	 * If not in debug mode, show the error page if it exists, or a simpler message.
	 *
	 * @param Twig_Error $e
	 * @param array $errorPages
	 * @return mixed|Response
	 */
	private function renderTwigError(Twig_Error $e, $errorPages=['error']) {
		$count = $this->renderTwigErrorCount;
		$this->renderTwigErrorCount++;
		$debug = c::get('debug', false);

		// Return an error page if we have one. May cause infinite loops if rendering
		// the error page also raises a Twig_Error, so let's check a counter.
		if (!$debug and $count == 0) {
			$errorPages = pages($errorPages);
			if ($errorPages->count()) {
				$response = new \Kirby\Component\Response($this->kirby);
				return $response->make($errorPages->first());
			}
		}

		// Or make a custom error page (with more information in debug mode)
		$title = $debug ? get_class($e) : 'Error';
		$message = $debug ? $e->getMessage() : 'An error occurred while rendering the template for this page.<br>Turn on the "debug" option for more information.';
		$file = '';
		$code = '';

		// Get a few lines of code from the buggy template
		if ($debug) {
			$file = $this->kirby->roots->templates() . DS . $e->getTemplateFile();
			if (F::isReadable($file)) {
				$line  = $e->getTemplateLine();
				$plus  = 4;
				$twig  = Escape::html(F::read($file));
				$lines = preg_split("/(\r\n|\n|\r)/", $twig);
				$start = max(1, $line - $plus);
				$limit = min(count($lines), $line + $plus);
				$excerpt = [];
				for ($i = $start - 1; $i < $limit; $i++) {
					$attr = 'data-line="'.($i+1).'"';
					if ($i === $line - 1) $excerpt[] = "<mark $attr>$lines[$i]</mark>";
					else $excerpt[] = "<span $attr>$lines[$i]</span>";
				}
				$code = implode("\n", $excerpt);
				// Small tweaks to the error message: move line number in subtitle
				$file = $file . ':' . $line;
				$message = $e->getRawMessage();
			}
		}

		// Error page template
		$html = Tpl::load(__DIR__ . DS . 'templates' . DS . 'twigerror.php', [
			'title' => $title,
			'message' => $message,
			'file' => $file,
			'code' => $code
		]);
		return new Response($html, 'html', 500);
	}

}

// Only replace the Template component if Twig is installed
// and enabled in the user’s config.

if ($enabled) {
	kirby()->set('component', 'template', 'Kirby\Plugin\Twig\TwigComponent');
}
