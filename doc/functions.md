Using your own functions in templates
=====================================


If you need to expose PHP functions (or static class methods) to your Twig templates, you can list them with those options:

- `twig.function.[…]`
- `twig.filter.[…]`

As with any option in Kirby, you should define these options in your `site/config/config.php`. Let’s show how each option works.


Exposing a function
-------------------

The expected syntax for these configuration options is:

```php
// In site/config/config.php:
c::set('twig.function.myFunctionName', $someFunction);
```

Where:

-   `myFunctionName` is any name you want (only letters and underscores), and is the name that will be available in your Twig templates.
-   `$someFunction` can be a string, or a Closure.

Let’s use more tangible examples.

### Using a function name (string)

If you have a custom function defined in a plugin file (e.g. `site/plugins/myplugin.php`):

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
c::set('twig.function.sayHello', 'sayHello');
```

```twig
{# Prints 'Hello Jane' #}
{{ sayHello('Jane') }}
```

Or you could expose it as a Twig filter:

```php
c::set('twig.filter.sayHello', 'sayHello');
```

```twig
{# Prints 'Hello Jane' #}
{{ 'Jane'|sayHello }}
```

I recommend sticking to the Twig function syntax, and only using Twig’s built-in filters. Of course, you should do what you like best.

### Using an anonymous function

The `twig.function.[…]` and `twig.filter.[…]` configs accept anonymous functions (called closures in PHP):

```php
c::set('twig.function.sayHello', function($who='') {
    return 'Hello' . (is_string($who) ? ' ' . $who : '');
}
```

### Exposing static methods

You can also expose static methods, using the string syntax:

```php
c::set('twig.function.setCookie', 'Cookie::set');
c::set('twig.function.getCookie', 'Cookie::get');
```

```twig
{% do setCookie('test', 'real value') %}

{# Prints 'real value' #}
{{ getCookie('test', 'fallback') }}
```

### Marking a function’s output as safe

By default, Twig escapes strings returned by functions, to avoid security attacks such as cross-site scripting. This is why you often need to ask Twig to ouptut a raw, unescaped string:

```twig
{{ page.text.markdown | raw }}
```

Alternatively, when declaring a Twig function you can mark it as safe for HTML output by adding a `*` before its name, like this:

```php
c::set('twig.function.*sayHello', 'sayHello');
```


Exposing and using classes
--------------------------

**Breaking change:** Previous versions of Kirby Twig allowed instantiating PHP classes with a `new()` Twig function. This feature was removed in Kirby Twig 3.0.

If you need to use PHP classes in your templates, I recommend two approaches:

1. Do it in a controller instead, and feed the resulting content to your templates. ([Kirby documentation: Controllers](https://getkirby.com/docs/developer-guide/advanced/controllers).)
2. Write a custom function that returns a class instance.

Let’s look at an example of that second solution:

```php
// site/plugins/coolplugin/src/verycoolthing.php
class VeryCoolThing
{
  // class implementation
}

// site/config/config.php
c::set('twig.function.getCoolStuff', function(){
  return new VeryCoolThing();
});
```

Then in your templates, you can use that function to get a class instance:

```twig
{% set coolThing = getCoolStuff() %}
```

This example is simplistic; in practice, you might need to pass some parameters around to instanciate your class.

Alternatively, you could define and expose a generic function that allows instantiating any (known) PHP class:

```php
// site/config/config.php

/**
 * Make a class instance for the provided class name and parameters
 * Giving this function the name 'new' in Twig templates, for
 * backwards compatibility with Kirby Twig 2.x.
 */
c::set('twig.function.new', function($name) {
  if (!class_exists($name)) {
    throw new Twig_Error_Runtime("Unknown class \"$name\"");
  }
  $args = array_slice(func_get_args(), 1);
  if (count($args) > 0) {
    $reflected = new ReflectionClass($name);
    return $reflected->newInstanceArgs($args);
  }
  return new $name;
});
```

Then in Twig templates:

```twig
{% set coolThing = new('VeryCoolThing') %}
```
