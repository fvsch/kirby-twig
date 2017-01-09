<?php

namespace Kirby\Twig;

use C;
use Exception;
use Kirby\Component\Template;
use Page;
use Tpl;

/**
 * Twig Template Component for Kirby
 *
 * This component class extends Kirby’s built-in Kirby\Component\Template
 * class and implements custom file() and render() methods. When rendering
 * a Twig template, instead of calling Tpl::load, we call:
 * Kirby\Twig\TwigEnv::renderPath
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class TwigComponent extends Template
{
    /**
     * Returns a template file path by name
     * @param string $name
     * @return string
     */
    public function file($name)
    {
        $usephp = c::get('twig.usephp', true);
        $base = str_replace('\\', '/', $this->kirby->roots()->templates().'/'.$name);
        $twig = $base . '.twig';
        $php  = $base . '.php';

        // only check existing files if PHP template support is active
        if ($usephp and !is_file($twig) and is_file($php)) {
            return $php;
        } else {
            return $twig;
        }
    }

    /**
     * Renders the template by page with the additional data
     * @param Page|string $template
     * @param array $data
     * @param boolean $return
     * @return string
     * @throws Exception
     */
    public function render($template, $data = [], $return = true)
    {
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
        $startData = Tpl::$data;
        Tpl::$data = array_merge(Tpl::$data, $data);

        // load the template
        if (pathinfo($file, PATHINFO_EXTENSION) === 'twig') {
            $twig = TwigEnv::instance();
            $result = $twig->renderPath($file, Tpl::$data, $return, true);
        } else {
            $result = Tpl::load($file, [], $return);
        }

        // reset the template data
        Tpl::$data = $startData;

        return $result;
    }
}
