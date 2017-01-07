Twig Plugin for Kirby CMS
=========================

<figure>
  <img src="doc/img/kirby-plus-twig.png" width="200" alt="">
</figure>

-   Adds support for [Twig templates](http://twig.sensiolabs.org/) to [Kirby CMS](https://getkirby.com/) (2.3+).
-   PHP templates still work, you don’t have to rewrite them if you don’t want to.


What it looks like
------------------

Before:

```php
<?php /* site/templates/hello.php */ ?>
<h1><?php echo $page->title() ?></h1>
<?php echo $page->text()->markdown() ?>
```

After:

```twig
{# site/templates/hello.twig #}
<h1>{{ page.title }}</h1>
{{ page.text.markdown | raw }}
```


Installation
------------

If you’re using [Kirby’s CLI](https://github.com/getkirby/cli), you can install with:

```
kirby plugin:install fvsch/kirby-twig
```

For manual installation:

1. Download [the latest release](https://github.com/fvsch/kirby-twig/releases) and put it in your `site/plugins` folder.
2. Rename the copied folder to `twig` (it should be named `site/plugins/twig`).
3. To activate the plugin, put `c::set('twig', true);` in your `site/config/config.php`.


Usage
-----

**TODO: shortly describe usage with page template, including a snippet, and twig helper.**


More documentation
------------------

Recommended reads:

-   [Twig templating guide for Kirby](doc/templating.md)
-   [Using your own functions in templates](doc/functions.md)
-   [Complete options documentation](doc/options.md)

Other topics:

-   [How errors are displayed (or not)](doc/errors.md)
-   [Twig support in Kirby plugins](doc/plugins.md)


Credits
-------

-   Twig library: Fabien Potencier and contributors / [New BSD License]([lib/Twig/LICENSE](lib/Twig/LICENSE))
-   Twig plugin for Kirby: Florens Verschelde / [MIT License](LICENSE)
