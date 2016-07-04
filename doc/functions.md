Exposing functions and classes to Twig templates
================================================


If you need to expose more PHP functions or classes to your Twig templates, you can list them with those two options:

-   `twig.env.functions` (for functions or static methods of classes)
-   `twig.env.classes` (for classes, which must be instantiated with a `new()` Twig function)


Exposing a function
-------------------

For example if you have a custom function defined in your own plugin file:

```php
<?php // site/plugins/myplugin.php

function myFunction() {
    return 'Hello';
}
```

You could tell the Twig plugin to make it available in your templates:

```php
<?php // site/config/config.php
c::set('twig.env.functions', ['myFunction']);
```

```twig
{# Prints 'Hello' #}
{{ myFunction() }}
```


Exposing static methods
-----------------------

If you just need a couple static methods, you can use the same solution:

```php
<?php // site/config/config.php
c::set('twig.env.functions', ['cookie::set', 'cookie::get']);
```

Note that the `::` will be replaced by two underscores (`__`).

```twig
{% do cookie__set('test', 'real value') %}

{# Prints 'real value' #}
{{ cookie__get('test', 'fallback') }}
```


Exposing and using classes
--------------------------

First you need to whitelist the class(es) you want to be able to instantiate:

```php
<?php // site/config/config.php
c::set('twig.env.classes', ['cookie', 'str']);
```

You can now use the `new()` *function* to instantiate a class.

```twig
{% set cookie = new('cookie') %}
{% do cookie.set('test', 'real value') %}

{# Prints 'real value' #}
{{ cookie.get('test', 'fallback') }}

{# Prints 'salut-ca-va' #}
{{ new('str').slug('Salut ça va?') }}
```

If the class constructor takes parameters, you can provide them after the first parameter:

```twig
{% set something = new('something', param1, param2) %}
```

Finally, note that you probably *should not need to use classes* in templates. If you have a lot of programming-like work to do in a template, you should probably do that work in a controller instead.
