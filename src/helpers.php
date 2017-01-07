<?php

/**
 * Renders a Twig template string or template file
 * Can be used in Kirby controllers and PHP templates
 *
 * Example usage:
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
function twig($template='', $userData=[]) {
    if (!is_string($template)) return '';
    $path = strlen($template) <= 256 ? trim($template) : '';
    $data = array_merge(Tpl::$data, is_array($userData) ? $userData : []);
    $twig = Kirby\Plugin\Twig\TwigRenderer::instance();

    // treat template as a path only if it *looks like* a Twig template path
    if (Str::startsWith($path, '@') || Str::endsWith(strtolower($path), '.twig')) {
        return $twig->render($path, $data);
    }
    return $twig->renderString($template, $data);
}
