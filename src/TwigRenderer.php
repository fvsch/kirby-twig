<?php

namespace Kirby\Plugin\Twig;

use C;
use Escape;
use F;
use Kirby;
use ReflectionClass;
use Response;
use Str;
use Tpl;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;
use Twig_Extension_Debug;
use Twig_Error;
use Twig_Error_Runtime;

/**
 * Twig Template Renderer for Kirby
 *
 * @package   Kirby Twig Plugin
 * @author    Florens Verschelde <florens@fvsch.com>
 */
class TwigRenderer
{
    /** @var TwigRenderer */
    private static $instance = null;

    /** @var Twig_Environment */
    private $twigEnv = null;

    /** @var boolean */
    private $debug = false;

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
    private $functions = [
        '*attr',
        '*brick',
        // Get config value
        'c::get',
        // Skipping: call - Allows calling any PHP function
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
        // Get locale-specific config (or translation)
        'l::get',
        '*js',
        'kirby',
        '*kirbytag',
        '*kirbytext',
        // Skipping: l - We're adding it manually
        '*markdown',
        'memory',
        '*multiline',
        'page',
        'pages',
        'param',
        'params',
        // From the Patterns plugin - similar to snippet
        '*pattern',
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
        '*youtube',
    ];

    /**
     * Names of classes that can be instantiated from Twig templates using
     * our custom `new('MyClass')` function.
     * @var array
     */
    private $classes = null;

    /**
     * Cache of $kirby->roots()->templates()
     * @var string
     */
    private $templateDir = null;

    /**
     * Return a new instance or the cached instance if it exists
     * @return TwigRenderer
     */
    static public function instance() {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Prepare the Twig environment
     * @throws Twig_Error_Runtime
     */
    public function __construct()
    {
        $this->debug = c::get('debug', false);

        $kirby = Kirby::instance();
        $templateDir = $kirby->roots()->templates();
        $cacheDir = $kirby->roots()->cache() . '/twig';

        $options  = [
            'debug' => $this->debug,
            'strict_variables' => c::get('twig.strict', $this->debug),
            'cache' => c::get('twig.cache', false) ? $cacheDir : false,
            'autoescape' => c::get('twig.autoescape', true)
        ];

        // Set up loader
        $loader = new Twig_Loader_Filesystem($templateDir);

        // Add namespaces
        $namespaces = [
            'templates' => $templateDir,
            'snippets' => $kirby->roots->snippets(),
            'plugins' => $kirby->roots->plugins(),
            'assets' => $kirby->roots->assets()
        ];
        foreach (array_keys($kirby->options()) as $key) {
            if (strpos($key, 'twig.namespace.') === 0) {
                $name = str_replace('twig.namespace.', '', $key);
                $path = $kirby->option($key);
                if (is_string($path)) $namespaces[$name] = $path;
            }
        }
        foreach ($namespaces as $name => $path) {
            $loader->addPath($path, $name);
        }

        // Start up Twig
        $twig = new Twig_Environment($loader, $options);

        // Enable Twig’s dump function
        $twig->addExtension(new Twig_Extension_Debug());

        // Plug in our selected list of helper functions
        $functions = array_merge($this->functions, c::get('twig.env.functions', []));
        foreach (array_filter($functions, 'is_string') as $name) {
            $callName = trim($name, '* ');
            if (!is_callable($callName)) continue;
            $twigName = str_replace('::', '__', $callName);
            $params = strpos($name, '*') !== false ? ['is_safe' => ['html']] : [];
            $twig->addFunction(new Twig_SimpleFunction($twigName, $callName, $params));
        }

        // Add the 'new' function that allows instantiating a whitelist of classes
        $this->classes = array_filter(c::get('twig.env.classes', []), 'is_string');
        $twig->addFunction(new Twig_SimpleFunction('new', [$this, 'makeClassInstance']));

        // And we're done
        $this->templateDir = $templateDir;
        $this->twigEnv = $twig;

        // Make sure the instance is stored / overwritten
        static::$instance = $this;
    }

    /**
     * Render a Twig template from a file path,
     * similarly to how Tpl::load renders a PHP template
     * @param string $filePath
     * @param array  $tplData
     * @param bool   $return
     * @param bool   $isPage
     * @return string|null
     */
    public function render($filePath='', $tplData=[], $return=true, $isPage=false)
    {
        // Remove the start of the templates path, since Twig asks
        // for a path starting from one of the registered directories.
        $path = ltrim(str_replace($this->templateDir, '',
            preg_replace('#[\\\/]+#', '/', $filePath)), '/');

        try {
            $content = $this->twigEnv->render($path, $tplData);
        }
        catch (Twig_Error $err) {
            $content = $this->error($err, $isPage);
        }

        // Mimicking the API of Tpl::load and how it's called by
        // Kirby\Component\Template::render.
        if ($return) return $content;
        echo $content;
        return null;
    }

    /**
     * Render a Twig template from a string
     * @param $tplString
     * @param $tplData
     * @return string
     */
    public function renderString($tplString, $tplData) {
        try {
            return $this->twigEnv->createTemplate($tplString)->render($tplData);
        }
        catch (Twig_Error $err) {
            return $this->error($err);
        }
    }

    /**
     * Handle Twig errors, with different scenarios depending on if we're
     * rendering a full page or a fragment (e.g. when using the `twig` helper),
     * and if we're in debug mode or not.
     *
     *        | Page mode           | Fragment mode
     * -------|---------------------| --------------
     * Debug: | Custom error page   | Error message
     * -------|---------------------| --------------
     * Prod:  | Standard error page | Empty string
     *
     * @param Twig_Error $err
     * @param boolean $isPage
     * @return string|Response
     * @throws Twig_Error
     */
    private function error(Twig_Error $err, $isPage=false)
    {
        // When returning a HTML fragment
        if (!$isPage) {
            if (!$this->debug) return '';
            return implode(' ', [
                '<b>Error:</b> ' . get_class($err) . ',',
                'line ' . $err->getTemplateLine(),
                'of ' . $err->getTemplateName(),
                '<br>' . $err->getRawMessage()
            ]);
        }

        // When returning a page
        // Debug mode off: show the site's error page
        if (!$this->debug) {
            try {
                $kirby = Kirby::instance();
                $page = $kirby->site()->page($kirby->get('option', 'error'));
                if ($page) return $kirby->render($page);
            }
            catch (Twig_Error $err2) {
            }
            // Still there? Let Whoops catch the initial error
            if (c::get('whoops', false)) {
                throw $err;
            }
            // or mimic Kirby 2.4's fatal error page, with less info
            return new Response(
                '<title>Error</title>'."\n".
                '<p style="text-align:center;margin:10%;">'.
                'This page is currently offline due to an unexpected error.</p>',
                'html', 500
            );
        }

        // Debug mode on: make a custom error page
        // Note for Kirby 2.4+: we don't use the Whoops error page because
        // it's not possible to surface Twig source code in it's stack trace
        // and code viewer. Whoops will only show the PHP method calls going
        // in in the Twig library. That's a know — but unresolved — issue.
        // https://github.com/filp/whoops/issues/167
        // https://github.com/twigphp/Twig/issues/1347
        // So we roll our own.
        $line = $err->getTemplateLine();
        $message = $err->getRawMessage();
        // TODO: we need a better way to get at the actual file content
        // e.g. for the main templates we might get 'article.twig' and
        // try to find site/templates/article.twig, but when using namespaces
        // like '@snippets/header.twig' getting to the right file will require
        // some work. Also if we dump $err we do get the real file path and
        // the template's content! Just not sure which part of the Twig API
        // we should use to get at this info.
        $file = $err->getTemplateName();
        $subtitle = 'Line ' . $line . ' of ' . $file;
        if (str::startsWith($file, '@') == false) {
            $subtitle = 'Line ' . $line . ' of ' . '@templates/'.$file;
            $file = $this->templateDir . '/' . $file;
        }

        // Get a few lines of code from the buggy template
        $excerpt = [];
        if (f::isReadable($file)) {
            $plus  = 6;
            $twig  = Escape::html(f::read($file));
            $lines = preg_split("/(\r\n|\n|\r)/", $twig);
            $start = max(1, $line - $plus);
            $limit = min(count($lines), $line + $plus);
            for ($i = $start - 1; $i < $limit; $i++) {
                $attr = 'data-line="'.($i+1).'"';
                if ($i === $line - 1) $excerpt[] = "<mark $attr>$lines[$i]</mark>";
                else $excerpt[] = "<span $attr>$lines[$i]</span>";
            }
        }

        // Error page template
        $html = Tpl::load(dirname(__DIR__) . '/templates/errorpage.php', [
            'title' => get_class($err),
            'message' => $message,
            'subtitle' => $subtitle,
            'code' => implode("\n", $excerpt)
        ]);

        echo new Response($html, 'html', 500);
        exit;
    }

    /**
     * Function used as `new('ClassName')` in Twig templates
     * Returns a class instance for the provided class name, provided that
     * this name has been whitelisted in the `twig.env.classes` config.
     * @param $name
     * @return mixed
     * @throws Twig_Error_Runtime
     */
    public function makeClassInstance($name)
    {
        $args = array_slice(func_get_args(), 1);
        if (!is_string($name)) {
            throw new Twig_Error_Runtime("Function \"new\" needs a class name (string) as first parameter");
        }
        if (!in_array($name, $this->classes)) {
            throw new Twig_Error_Runtime("Class \"$name\" is not allowed in option \"twig.env.classes\"");
        }
        if (!class_exists($name)) {
            throw new Twig_Error_Runtime("Unknown class \"$name\"");
        }
        if (count($args) > 0) {
            $reflected = new ReflectionClass($name);
            return $reflected->newInstanceArgs($args);
        }
        return new $name;
    }

}
