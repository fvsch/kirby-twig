# Installing with Composer

Kirby itself doesn’t use [Composer](https://getcomposer.org/) and many Kirby-based projects are not set up to use Composer. This is why this plugin includes [the Twig library](http://twig.sensiolabs.org/).


## Installing Twig with Composer

You can still install Twig with composer if you wish. From the root of your site:

```
composer require twig/twig
```

Make sure that your `index.php` (or `site.php`) requires the autoload script:

```php
require_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';
```

Then you can remove the `lib/Twig` folder from this plugin if you want to limit bloat.


## Installing this plugin with Composer

… is not supported yet. I’ll have to explore how it could be done, perhaps on a separate branch which doesn’t have the Twig lib files.

See also: [How to use Composer to install Kirby, Toolkit, Panel](https://forum.getkirby.com/t/how-to-use-composer-to-install-kirby-toolkit-panel/2850).
