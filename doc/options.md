kirby-twig: Options documentation
=================================

```php
// REQUIRED: activate Twig plugin
c::set('twig', true);

// Should we use .php templates as fallback when .twig
// templates don't exist? Set to false to only allow Twig templates
c::set('twig.usephp', true);

// Kirby URI of a page to render when there is a Twig error in production
// For instance 'error/system'. Falls back to c::get('error').
c::set('twig.error', '');

// List of additional functions that should be available in templates
c::set('twig.env.functions', ['myCustomFunction']);

// List of classes that can be instantiated from templates (with the `new()` function)
c::set('twig.env.classes', ['SomeClass']);
```

## Advanced

```
// Use Twig’s PHP cache?
// Enabled by default. Note that Kirby has its own HTML cache for pages,
// so this might be extra work, but for pages which are often not cached
// (e.g. search pages with a specific query string) this could be useful.
c::set('twig.cache', true);

// Disable autoescaping or specify autoescaping type
// http://twig.sensiolabs.org/doc/api.html#environment-options
c::set('twig.autoescape', true);

// Should Twig throw errors when using undefined variables or methods?
// Defaults to the value of the 'debug' option
c::set('twig.strict', c::get('debug', false));
```
