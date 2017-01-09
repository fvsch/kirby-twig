<?php

namespace Kirby\Twig;

use Exception;
use Str;
use Tpl;


/**
 * Main Kirby Twig Plugin class, hopefully with a stable API.
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class Plugin
{
    /** @var bool - flag to make sure we only register once */
    static private $registered = false;

    /**
     * Register the template component and load the `twig` helper function
     * @return bool
     * @throws Exception
     */
    static public function register()
    {
        // only register once
        if (static::$registered === true) {
            return true;
        }
        $kirby = kirby();
        if (!class_exists('Kirby\Component\Template')) {
            throw new Exception('Kirby Twig plugin requires Kirby 2.3 or higher. Current version: ' . $kirby->version());
        }
        if (!class_exists('Twig_Environment')) {
            require_once __DIR__.'/../lib/Twig/lib/Twig/Autoloader.php';
            \Twig_Autoloader::register();
        }
        $kirby->set('component', 'template', 'Kirby\Twig\TwigComponent');
        if (is_executable('twig') === false) {
            require_once __DIR__ . '/helpers.php';
        }
        return static::$registered = true;
    }

    /**
     * Renders a Twig template string or template file
     * Can be used in Kirby controllers and PHP templates
     *
     *  * Example usage:
     *
     *     <?php echo twig('Hello {{ who }}', ['who'=>'World']) ?>
     *     <?php echo twig('@snippets/header.twig', ['title'=>'Home page']) ?>
     *
     * Note: in Twig templates, you should use the `include` tag or function instead.
     *
     * @param string $template - path or template string to render
     * @param array  $userData - data to pass as variables to the template
     * @return string
     */
    static public function render($template, $userData)
    {
        if (!is_string($template)) return '';
        $path = strlen($template) <= 256 ? trim($template) : '';
        $data = array_merge(Tpl::$data, is_array($userData) ? $userData : []);
        $twig = TwigEnv::instance();

        // treat template as a path only if it *looks like* a Twig template path
        if (Str::startsWith($path, '@') || Str::endsWith(strtolower($path), '.twig')) {
            return $twig->renderPath($path, $data);
        }
        return $twig->renderString($template, $data);
    }
}
