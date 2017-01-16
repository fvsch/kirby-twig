Using your own functions in templates
=====================================

If you need to expose PHP functions or classes to your Twig templates, you can list them with those options:

- `twig.env.functions`
- `twig.env.filters`
- `twig.env.classes`

As with any option in Kirby, you should define these options in your `site/config/config.php`. Let’s show how each option works.


Exposing a function
-------------------

For example if you have a custom function defined in your own plugin file (`site/plugins/myplugin.php`):

```php
<?php
/**
 * Returns a welcoming message
 * @param  string $who
 * @return string
 */
function sayHello($who='') {
    return 'Hello' . (is_string($who) ? ' ' . $who : '');
}
```

You can make it available as a Twig function:

```php
c::set('twig.env.functions', ['sayHello']);
```

```twig
{# Prints 'Hello Jane' #}
{{ sayHello('Jane') }}
```

Or you could expose it as a Twig filter:

```php
c::set('twig.env.filters', ['sayHello']);
```

```twig
{# Prints 'Hello Jane' #}
{{ 'Jane'|sayHello }}
```

I recommend sticking to the Twig function syntax, and only using Twig’s built-in filters. Of course, you should do what you like best.


Exposing static methods
-----------------------

If you just need a couple static methods, you can use the same solution:

```php
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

Finally, note that you probably *should not need to use classes* in templates. If you have a lot of programming-like work to do in a template, try to do that work in a controller instead.
