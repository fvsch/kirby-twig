Options documentation
=====================


Basic options
-------------

```php
// REQUIRED: activate Twig plugin
c::set('twig', true);

// Should we use .php templates as fallback when .twig
// templates don't exist? Set to false to only allow Twig templates
c::set('twig.usephp', true);
```


Customizing the Twig environment
--------------------------------

```php
// Define a directory as a Twig namespace, that can be used as:
//   {% include '@mynamespace/something.twig' %}
c::set('twig.namespace.mynamespace', kirby()->roots()->index().'/mydirectory');

// List of additional functions that should be available in templates
c::set('twig.env.functions', ['myCustomFunction']);

// List of additional functions that should be available as Twig filters
c::set('twig.env.filters', ['myCustomFilter']);
```


Advanced
--------

```php
// Use Twig’s PHP cache?
// Disabled by default (starting from 2.2).
// Enabling Twig's cache can give a speed boost to pages with changing
// content (e.g. a search result page), because Twig will use a compiled
// version of the template when building the response.
// But if you have static text content in your Twig templates, you won’t
// see content changes until you manually remove the `site/cache/twig` folder.
c::set('twig.cache', false);

// Disable autoescaping or specify autoescaping type
// http://twig.sensiolabs.org/doc/api.html#environment-options
c::set('twig.autoescape', true);

// Should Twig throw errors when using undefined variables or methods?
// Defaults to the value of the 'debug' option
c::set('twig.strict', c::get('debug', false));
```
