# Twig Plugin for Kirby CMS

-   Adds support for [Twig templates](http://twig.sensiolabs.org/) to [Kirby CMS](https://getkirby.com/).
-   Requires Kirby 2.3 (in beta as of 2016-04-12).

> I don’t like Twig!

It’s okay, using this plugin is of course optional. You can keep using PHP templates in Kirby CMS.

> I like Twig, but I don’t want to convert all my templates

Good news: you don’t have to! By default your PHP templates will keep working.

This plugin can work in two modes:

1. Default: allow both Twig and PHP templates. (If both `mytemplate.twig` and `mytemplate.php` exist, the Twig template takes precedence.)
2. Optional: allow Twig templates only.


## Installation

You will need [Composer](https://getcomposer.org/), a command-line tool, to install this plugin.

1. Download a copy of this repository and put it in your `site/plugins` folder. Rename the copied folder to `twig`.
2. Open a terminal in your `site/plugins/twig` folder and run `composer install`.
3. To activate the plugin, put `c::set('plugin.twig.enabled', true);` in your `config.php`.

You can now create `.twig` templates in your `site/templates` directory.

## Options

```php
// REQUIRED: activate Twig plugin
c::set('plugin.twig.enabled', true);

// Option: use Twig’s PHP cache in addition to Kirby’s HTML cache.
// (Only works when Kirby’s own cache is active.)
// Defaults to false
c::set('plugin.twig.cache', false);

// Option: disable or specify autoescaping type.
// http://twig.sensiolabs.org/doc/api.html#environment-options
// Defaults to true
c::set('plugin.twig.autoescape', true);

// Should we use .php templates as fallback when .twig
// templates don't exist? Set to false to only allow Twig templates.
// Defaults to true
c::set('plugin.twig.usephp', true);
```

## Using Twig

See [templating.md](templating.md) for tips and tricks for templating with Kirby and Twig.

## Known limitations

1.  Only a subset of Kirby’s functions and helpers are exposed to Twig templates (to be documented).
    -   For instance the `go()` function is not available to Twig templates. You can (and should) use it in a Controller or Page Model, of course.
    -   If there are Kirby functions that are useful for templating and which are not listed in `TwigComponent::$helpersList`, please file an issue.

2.  Likewise, normal PHP functions are not available to Twig templates. If you want full PHP power, use PHP templates, or write [Controllers that send data to your templates](https://getkirby.com/docs/templates/controllers).

3.  If a given template name has a `.twig` template but no `.php` template, Kirby’s `$page->hasTemplate()` will be false. Similarly, `$page->template()` can be wrong.

4.  By design, Twig will *not* let you include files from outside the `site/templates` directory. If you have a use care where this is a problem, please open an issue.
