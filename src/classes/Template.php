<?php

namespace fvsch\Twig;

use Exception;
use Kirby\Cms\App;
use Kirby\Toolkit\F;

/**
 * Twig Template Component for Kirby
 *
 * This component class extends Kirby’s built-in Kirby\Cms\Template
 * class and implements custom file() and render() methods. When rendering
 * a Twig template, instead of calling Tpl::load, we call:
 * Kirby\Twig\TwigEnv::renderPath
 *
 * @package  Kirby Twig Plugin
 * @author   Florens Verschelde <florens@fvsch.com>
 */
class Template extends \Kirby\Cms\Template
{
    protected $twig;
    protected $kirby;

    public function __construct(App $kirby, string $name, string $type = 'html')
    {
        parent::__construct($name, $type);
        $viewPath    = dirname($this->file());
        $this->twig = new Environment($viewPath);
        $this->kirby = $kirby;
    }
    
    public function extension(): string
    {
        return 'twig';
    }

    public function isTwig(): bool
    {
        $length = strlen($this->extension());
        return substr($this->file(), -$length) === $this->extension();
    }

    /**
     * Returns a template file path by name
     * @param string $name
     * @return string
     */
    // public function file($name)
    public function file(): ?string
    {
        $usephp = option('fvsch.twig.usephp', true);
        $type = $this->type();
        $name = $type !== null && $type !== 'html' ? $this->name() . '.' . $type : $this->name();

        $base = $this->root() . '/' . $name;
        $twig = $base . '.twig';
        $php  = $base . '.php';

        // only check existing files if PHP template support is active
        if ($usephp and !is_file($twig) and is_file($php)) {
            return F::realpath($php, $this->root());
        } else {
            try {
                return F::realpath($twig, $this->root());
            } catch (Exception $e) {
                return App::instance()->extension('templates', $name);
            }
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
    // public function render($template, $data = [], $return = true)
    public function render(array $data = []): string
    {
        if ($this->isTwig()) {
            return $this->twig->renderPath($this->name() . '.' . $this->extension(), $data, true);
        }
        return parent::render($data);
    }
}
