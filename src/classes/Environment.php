<?php

namespace fvsch\Twig;

use Closure;
use Kirby;
use Response;
use Kirby\Cms\App;
use Kirby\Toolkit\Html;
use Kirby\Toolkit\Tpl;

use Twig_Environment;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use Twig_Extension_Debug;
use Twig_Error;
use Twig_Error_Loader;


/**
 * Wrapper for the Twig_Environment class, setting up an instance
 * with Kirby-specific configuration.
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class Environment
{
    /** @var Twig_Environment */
    public $twig = null;

    /** @var boolean */
    public $debug = false;

    /** @var TwigEnv */
    private static $instance = null;

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
    private $defaultFunctions = [
        '*attr',
        // Get config value
        'option',
        // Skipping: call - Allows calling any PHP function
        '*csrf',
        '*csrf_field',
        '*honeypot_field',
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

    private $templateDir = null;

    /**
     * Prepare the Twig environment
     * @throws Twig_Error_Loader
     */
    public function __construct($viewPath)
    {
        $kirby = Kirby::instance();
        $this->debug = option('debug');
        $this->templateDir = $viewPath;

        // Get environment & user config
        $options = [
            'core' => [
                'debug' => $this->debug,
                'strict_variables' => option('fvsch.twig.strict', $this->debug),
                'autoescape' => option('fvsch.twig.autoescape', 'html'),
                'cache' => false
            ],
            'namespace' => [
                'templates' => $this->templateDir,
                'snippets' => kirby()->roots()->snippets(),
                'plugins' => kirby()->roots()->plugins(),
                'assets' => kirby()->roots()->assets()
            ],
            'paths' => [],
            'function' => $this->cleanNames(array_merge(
                $this->defaultFunctions,
                option('fvsch.twig.env.functions', [])
            )),
            'filter' => $this->cleanNames(option('fvsch.twig.env.filters', []))
        ];

        // Set cache directory
        if (option('fvsch.twig.cache')) {
            $options['core']['cache'] = kirby()->roots()->cache() . '/twig';
        }

        // Look at 'twig.abc.xYz' options to find namespaces, functions & filters
        foreach (array_keys(App::instance()->options()) as $key) {
            $p = '/^fvsch.twig\.(env\.)?([a-z]+)\.(\*?[a-zA-Z][a-zA-Z0-9_\-]*)$/';
            if (preg_match($p, $key, $m) === 1 && array_key_exists($m[2], $options)) {
                $options[ $m[2] ][ $m[3] ] = option($key);
            }
        }
        
        // Set up the template loader
        $loader = new LoaderFilesystem($this->templateDir);
        $canSkip = ['snippets', 'plugins', 'assets'];   
        foreach ($options['namespace'] as $key=>$path) {
            if (!is_string($path)) continue;
            if (in_array($key, $canSkip) && !file_exists($path)) continue;
            $loader->addPath($path, $key);
        }
        
        $options['paths'] = option('fvsch.twig.paths', []);
        foreach ($options['paths'] as $path) {
            $loader->addPath($path);
        }
        
        // Start up Twig
        $this->twig = new Twig_Environment($loader, $options['core']);

        // Enable Twig’s dump function
        $this->twig->addExtension(new Twig_Extension_Debug());

        // Plug in functions and filters
        foreach ($options['function'] as $name => $func) {
            $this->addCallable('function', $name, $func);
        }
        foreach ($options['filter'] as $name => $func) {
            $this->addCallable('filter', $name, $func);
        }

        // Make sure the instance is stored / overwritten
        static::$instance = $this;
    }

    /**
     * Return a new instance or the cached instance if it exists
     * @return TwigEnv
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Render a Twig template from a file path,
     * similarly to how Tpl::load renders a PHP template
     * @param string $filePath
     * @param array $tplData
     * @param bool $return
     * @param bool $isPage
     * @return string
     * @throws Twig_Error
     */
    public function renderPath($filePath='', $tplData=[], $isPage=false)
    {
        // Remove the start of the templates path, since Twig asks
        // for a path starting from one of the registered directories.
        $path = ltrim(str_replace($this->templateDir, '',
            preg_replace('#[\\\/]+#', '/', $filePath)), '/');

        try {
            $content = $this->twig->render($path, $tplData);
        }
        catch (Twig_Error $err) {
            $content = $this->error($err, $isPage);
        }

        return $content;
    }

    /**
     * Render a Twig template from a string
     * @param  string $tplString
     * @param  array $tplData
     * @return string
     * @throws Twig_Error
     * @throws \Exception
     * @throws \Throwable
     */
    public function renderString($tplString='', $tplData=[])
    {
        try {
            return $this->twig->createTemplate($tplString)->render($tplData);
        }
        catch (Twig_Error $err) {
            return $this->error($err, false, $tplString);
        }
    }

    /**
     * Handle Twig errors, with different scenarios depending on if we're
     * rendering a full page or a fragment (e.g. when using the `twig` helper),
     * and if we're in debug mode or not.
     *
     *        | Page mode            | Fragment mode
     * -------|----------------------| --------------
     * Debug: | Custom error page    | Error message
     * -------|----------------------| --------------
     * Prod:  | Standard error page, | Empty string
     *        | or let error through |
     *
     * @param  Twig_Error $err
     * @param  boolean    $isPage
     * @param  string     $templateString
     * @return string|Response
     * @throws Twig_Error
     */
    private function error(Twig_Error $err, $isPage=false, $templateString=null)
    {
        if (!$this->debug) {
            if (!$isPage) return '';
            // Debug mode off: show the site's error page
            try {
                $kirby = Kirby::instance();
                $page = $kirby->site()->page($kirby->get('option', 'error'));
                if ($page) return $kirby->render($page);
            }
            // avoid loops
            catch (Twig_Error $err2) {
            }
            // Error page didn't exist or was buggy: rethrow the initial error
            // Can result in the 'fatal.php' white error page (in Kirby 2.4+
            // with Whoops active), or an empty response (white page).
            // That’s consistent with errors for e.g. missing base templates.
            throw $err;
        }

        // Gather information
        $sourceContext = $err->getSourceContext();
        $name = $sourceContext->getName();
        $line = $err->getTemplateLine();
        $msg  = $err->getRawMessage();
        $path = null;
        $code = $templateString ? $templateString : '';
        if (!$templateString) {
            try {
                $source = $this->twig->getLoader()->getSourceContext($name);
                $path = $source->getPath();
                $code = $source->getCode();
                
            }
            catch (Twig_Error $err2) {}
        }

        // When returning a HTML fragment
        if (!$isPage && $this->debug) {
            $info = get_class($err) . ', line ' . $line . ' of ' .
                ($templateString ? 'template string:' : $name);
            $src  = $this->getSourceExcerpt($code, $line, 1, false);
            return '<b>Error:</b> ' . $info . "\n" .
                '<pre style="margin:0">'.$src.'</pre>' . "\n" .
                '➡ ' . $msg . "<br>\n";
        }

        // When rendering a full page with Twig: make a custom error page
        // Note for Kirby 2.4+: we don't use the Whoops error page because
        // it's not possible to surface Twig source code in it's stack trace
        // and code viewer. Whoops will only show the PHP method calls going
        // in in the Twig library. That's a know — but unresolved — issue.
        // https://github.com/filp/whoops/issues/167
        // https://github.com/twigphp/Twig/issues/1347
        // So we roll our own.
        $html = Tpl::load(dirname(__DIR__) . '/errorpage.php', [
            'title' => get_class($err),
            'subtitle' => 'Line ' . $line . ' of ' . ($path ? $path : $name),
            'message' => $msg,
            'code' => $this->getSourceExcerpt($code, $line, 6, true)
        ]);
        return new Response($html, 'html', 500);
    }

    /**
     * Extract a few lines of source code from a source string
     * @param string $source
     * @param int    $line
     * @param int    $plus
     * @param bool   $format
     * @return string
     */
    private function getSourceExcerpt($source='', $line=1, $plus=1, $format=false)
    {
        $excerpt = [];
        $twig  = Html::encode($source, false);
        $lines = preg_split("/(\r\n|\n|\r)/", $twig);
        $start = max(1, $line - $plus);
        $limit = min(count($lines), $line + $plus);
        for ($i = $start - 1; $i < $limit; $i++) {
            if ($format) {
                $attr = 'data-line="'.($i+1).'"';
                if ($i === $line - 1) $excerpt[] = "<mark $attr>$lines[$i]</mark>";
                else $excerpt[] = "<span $attr>$lines[$i]</span>";
            }
            else {
                $excerpt[] = $lines[$i];
            }
        }
        return implode("\n", $excerpt);
    }

    /**
     * Clean up function names for use in Twig templates
     * Returns ['twig name' => 'callable name']
     * @param  array $source
     * @return array
     */
    private function cleanNames($source)
    {
        $names = [];
        foreach ($source as $name) {
            if (!is_string($name)) continue;
            $key = str_replace('::', '__', $name);
            $names[$key] = trim($name, '*');
        }
        return $names;
    }

    /**
     * Expose a function to the Twig environment as a function or filter
     * @param string $type
     * @param string $name
     * @param string|Closure $func
     */
    private function addCallable($type='function', $name, $func)
    {
        if (!is_string($name) || !is_callable($func)) {
            return;
        }
        $twname = trim($name, '*');
        $params = [];
        if (strpos($name, '*') === 0) {
            $params['is_safe'] = ['html'];
        }
        if ($type === 'function') {
            $this->twig->addFunction(new Twig_SimpleFunction($twname, $func, $params));
        }
        if ($type === 'filter') {
            $this->twig->addFilter(new Twig_SimpleFilter($twname, $func, $params));
        }
    }
}
