[kirby-twig] Twig support in Kirby plugins
==========================================

If you’re developing a Kirby plugin to share it with the Kirby community, it’s probably not a great idea to make it rely on kirby-twig. You should probably provide PHP templates or snippets.

For project-specific plugins, though, if your project is using Twig, you might want to keep your plugin-related templates and snippets in your plugin’s folder. Here are a few config tricks that might help.

```php
<?php
// site/myplugin/myplugin.php

/**
 * Add a Twig namespace for our plugin's templates
 * Allows including with {% include '@myplugin/mytemplate.twig' %}
 */
c::set('twig.namespace.myplugin', __DIR__ . '/templates');

/**
 * Expose functions from our plugin to the Twig environment
 */
$twigFunctionNames = array_merge(
    c::get('twig.env.functions', []),
    ['myPluginFunction', 'myOtherFunction']
);
c::set('twig.env.functions', $twigFunctionNames);
```

See [Using your own functions in templates](functions.md) for more details about exposing functions, static methods and classes to templates.
