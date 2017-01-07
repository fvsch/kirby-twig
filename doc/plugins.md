Twig support in Kirby plugins
=============================

## Modules and Patterns plugins

Using Twig templates with the Patterns or Modules plugins requires a few steps.

First, you should declare Twig namespaces for the `patterns` and `modules` folders, for example:

```php
// site/config/config.php
c::set('twig.namespace.modules',  dirname(__DIR__) . '/modules');
c::set('twig.namespace.patterns', dirname(__DIR__) . '/patterns');
```

Now let’s say you’re using the Patterns plugin. You could have a file structure like this:

```
site/
    patterns/
        mypattern/
            mypattern.html.php
            mypattern.html.twig
```

In the `mypattern.html.php`, use the `twig()` helper function:

```php
echo twig('@patterns/mypattern/mypattern.html.twig');
```

The Twig template will have access to the `kirby`, `site` and `page` variables, and to any variable defined in the page’s controller. But if you defined other variables for your Module or Pattern, they won’t be automatically passed to the Twig template. Two solutions:

```php
// 1. Explicitly pass variables to the Twig template.
// Shortcut for array('myVar'=>$myVar, ...)
$data = compact('myVar', 'myOtherVar');
echo twig('@patterns/mypattern/mypattern.html.twig', $data);

// 2. Nuclear option: pass everything in the current scope
echo twig('@patterns/mypattern/mypattern.html.twig', get_defined_vars());
```

## Using Twig in plugin code

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
