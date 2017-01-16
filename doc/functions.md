Using your own functions in templates
=====================================


If you need to expose PHP functions (or static class methods) to your Twig templates, you can list them with those options:

- `twig.env.functions`
- `twig.env.filters`

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

**Breaking change:** Previous versions of Kirby Twig allowed instantiating PHP classes with a `new()` Twig function. This feature was removed in Kirby Twig 3.0.

If you need to use PHP classes in your templates, I recommend two approaches:

1. Do it in a controller instead, and feed the resulting content to your templates. (Kirby documentation: [https://getkirby.com/docs/developer-guide/advanced/controllers](Controllers).)
2. Write a custom function that returns a class instance.

Let’s look at an example of that second solution:

```php
// site/plugins/coolplugin/src/verycoolthing.php
class VeryCoolThing
{
    // class implementation
}

// site/plugins/coolplugin/helpers.php
function getCoolStuff()
{
    return new VeryCoolThing();
}

// site/config/config.php
c::set('twig.env.functions', ['getCoolStuff']);
```

Then in your templates, you can use that helper function to get a class instance:

```twig
{% set coolThing = getCoolStuff() %}
```

This example is simplistic; in practice, you might need to pass some parameters around to instanciate your class.

Alternatively, you could define and expose a generic function that allows instantiating any (known) PHP class:

```php
// site/config/config.php

/**
 * Make a class instance for the provided class name and parameters
 */
function makeInstance($name) {
  if (!class_exists($name)) {
    throw new Twig_Error_Runtime("Unknown class \"$name\"");
  }
  $args = array_slice(func_get_args(), 1);
  if (count($args) > 0) {
    $reflected = new ReflectionClass($name);
    return $reflected->newInstanceArgs($args);
  }
  return new $name;
}

c::set('twig.env.functions', ['makeInstance']);
```

Then in Twig templates:

```twig
{% set coolThing = makeInstance('VeryCoolThing') %}
```
