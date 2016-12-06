<?php

namespace Kirby\Plugin\Twig;

use C;
use Escape;
use F;
use Response;
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
    /** @var Twig_Environment */
    private $env;

    /**
     * How many times we have tried to render an error page through Kirby
     * (hence possibly through Twig) from the renderTwigError method.
     * @var integer
     */
    private $errorCount = 0;

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
    private $classes;

    /**
     * Cache of $kirby->roots()->templates()
     * @var string
     */
    private $templateDir;

    /**
     * Prepare the Twig environment
     * @throws Twig_Error_Runtime
     */
    public function __construct()
    {
        $debug = C::get('debug', false);
        $kirby = kirby();
        $templateDir = $kirby->roots()->templates();
        $cacheDir = $kirby->roots()->cache() . '/twig';

        $options  = [
            'debug' => $debug,
            'strict_variables' => C::get('twig.strict', $debug),
            'cache' => C::get('twig.cache', true) ? $cacheDir : false,
            'autoescape' => C::get('twig.autoescape', true)
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
        $functions = array_merge($this->functions, C::get('twig.env.functions', []));
        foreach (array_filter($functions, 'is_string') as $name) {
            $callName = trim($name, '* ');
            if (!is_callable($callName)) continue;
            $twigName = str_replace('::', '__', $callName);
            $params = strpos($name, '*') !== false ? ['is_safe' => ['html']] : [];
            $twig->addFunction(new Twig_SimpleFunction($twigName, $callName, $params));
        }

        // Add the 'new' function that allows instantiating a whitelist of classes
        $this->classes = array_filter(C::get('twig.env.classes', []), 'is_string');
        $twig->addFunction(new Twig_SimpleFunction('new', [$this, 'makeClassInstance']));

        // And we're done
        $this->templateDir = $templateDir;
        $this->env = $twig;
    }

    /**
     * Render a Twig template, similarly to how Tpl::load renders a PHP template
     * @param string $filePath
     * @param array  $tplData
     * @param bool   $return
     * @return string|null
     */
    public function render($filePath, $tplData=[], $return = true)
    {
        // Remove the start of the templates path, since Twig asks for a path
        // starting from one of the setup directories.
        $shortPath = str_replace($this->templateDir, '', $filePath);
        $path = ltrim( preg_replace('#[\\\/]+#', '/', $shortPath), '/');

        try {
            $content = $this->env->render($path, $tplData);
        }
        catch (Twig_Error $err) {
            $content = $this->error($err, array_filter([
                C::get('twig.error', null),
                C::get('error', 'error')
            ]));
        }

        // Mimicking the API of Tpl::load and how it's called by
        // Kirby\Component\Template::render.
        if ($return) return $content;
        echo $content;
        return null;
    }

    /**
     * Show an error page for a Twig_Error, with the faulty Twig code if we can.
     * If not in debug mode, show the error page if it exists, or a simpler message.
     * @param Twig_Error $err
     * @param array $errorPages
     * @return mixed|Response
     */
    private function error(Twig_Error $err, $errorPages=['error'])
    {
        $count = $this->errorCount++;
        $debug = C::get('debug', false);

        // Return an error page if we have one. May cause infinite loops if rendering
        // the error page also raises a Twig_Error, so let's check a counter.
        if (!$debug and $count == 0) {
            $errorPages = pages($errorPages);
            if ($errorPages->count()) {
                $response = new \Kirby\Component\Response(kirby());
                return $response->make($errorPages->first());
            }
        }

        // Or make a custom error page (with more information in debug mode)
        $title = $debug ? get_class($err) : 'Error';
        $message = $debug ? $err->getMessage() : 'An error occurred while rendering the template for this page.<br>Turn on the "debug" option for more information.';
        $file = '';
        $code = '';

        // Get a few lines of code from the buggy template
        if ($debug) {
            $file = $this->templateDir . '/' . $err->getTemplateFile();
            if (F::isReadable($file)) {
                $line  = $err->getTemplateLine();
                $plus  = 6;
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
                $message = $err->getRawMessage();
            }
        }

        // Error page template
        $html = Tpl::load(dirname(__DIR__) . '/templates/errorpage.php', [
            'title' => $title,
            'message' => $message,
            'file' => $file,
            'code' => $code
        ]);
        return new Response($html, 'html', 500);
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
