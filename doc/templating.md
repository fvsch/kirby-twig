kirby-twig: Twig templating guide
=================================

This is a short guide of how to use Twig templates with Kirby CMS. It supposes that you have installed and enabled the Twig Plugin already.


Twig basics
-----------

A few basic examples of Twig syntax:

```twig
{# This is a comment #}

{# Output a variable as text #}
<title>{{ myTitle }}</title>

{# The dot notation in 'page.title' works for
   arrays, object properties and object methods. #}
<h1>{{ page.title }}</h1>

{% if page.abstract.isNotEmpty %}
<p>{{ page.abstract }}</p>
{% endif %}

{# If the 'list' variable is an array or iterable
   object, we can loop on it, yay! #}
<ul>
{% for item in list %}
    <li><a href="{{ item.url }}">{{ item.title }}</li>
{% endfor %}
</ul>
```

If you don’t know Twig already, you should read [Twig for Template Designers](http://twig.sensiolabs.org/doc/templates.html).


Tips and tricks
---------------

### Undefined variables

Trying to use a variable that doesn’t exist will result in an error. If you’re not sure the variable will be defined, you can use the `default` filter:

```twig
{# Can be risky: #}
{% if description %}
    <meta name="description" value="{{ description }}">
{% endif %}

{# Safer: #}
{% if description|default('') %}
    <meta name="description" value="{{ description }}">
{% endif %}

{# You can also use this syntax: #}
{% if description ?? '' %}
    <meta name="description" value="{{ description }}">
{% endif %}
```

### HTML escaping

By default, Twig will escape HTML tags and entities (to help prevent [cross-site scripting](https://en.wikipedia.org/wiki/Cross-site_scripting) attacks). When that’s not ideal, you can use the `raw` filter.

```twig
{# Oops, we may end up with text that looks like:
   &lt;p&gt;This is a paragraph.&lt;/p&gt; #}
{{ page.text.kirbytext }}

{# We know it’s going to be HTML, it’s okay: #}
{{ page.text.kirbytext | raw }}
```


Kirby-specific variables and functions
--------------------------------------

### Objects

The following variables are always available in templates (note that we’re using Twig notation for variables and object methods):

-   `page` (page object)
-   `site` (site object)
-   `kirby` (Kirby instance)
-   `pages` (page collection with the site’s top-level pages, same as `site.children`)

For instance we could work with the `site` object to retrieve all child pages of a `"blog"` page:

```twig
{# site/templates/blog.twig #}

{% set posts = site
    .find('blog')
    .children
    .filterBy('status', 'published')
    .sortBy('date', 'desc') %}

<h1>{{ page.title }}</h1>

{% if posts.count %}
    <ul>
    {% for post in posts %}
        <li><a href="{{ post.url }}">{{ post.title }}</a></li>
    {% endfor %}
    </ul>
{% endif %}
```

You can use all of [Kirby’s chaining API](https://getkirby.com/docs/templates/api) for `$page`, `$site`, etc. in your Twig templates, and often that’s all you will ever need.

### Helper functions

Almost all of [Kirby’s helper functions](https://getkirby.com/docs/cheatsheet#helpers) are available in your Twig templates. This includes things like the `css()`, `js()` or `snippet()` functions.

The few exceptions are:

-   helpers related to sending emails or writing to files, such as `email`, `upload`, `structure` and `textfile`;
-   and the `ecco` and `r` helpers, which are trivial to do with Twig syntax (for instance: `{{ condition ? 'yes' : 'no' }}` is the same as Kirby’s `<?php ecco(condition, 'yes', 'no') ?>`.

### Getting config values

-   Use the `c(configName, defaultValue)` function in Twig templates to get config values (shortcut for `c::get`).
-   Use the `l(configName, defaultValue)` funciton in Twig templates to get language-specific config values or translation strings (shortcut for `l::get`).

### Kirby Toolkit

The [Kirby Toolkit API](https://getkirby.com/docs/toolkit/api) is not available in Twig templates. Some methods, such as string comparisons, can be done directly with Twig syntax. Other things, like reading from a database, should probably be done in a controller instead.

### Plugin functions

Functions or classes defined by Kirby plugins will not be picked up in Twig templates. If you do want to use them in your templates, see: [Exposing functions and classes to Twig templates](functions.md).


Using controllers
-----------------

You can send more data to templates by [writing a Controller](https://getkirby.com/docs/developer-guide/advanced/controllers). Don’t worry, it’s really easy.

In the previous example, the part where we defined the `posts` variable could go in a controller file:

```php
<?php // site/controllers/blog.php

return function($site, $pages, $page) {
    $data = [];
    $data['posts'] = $site->find('blog')->children()
        ->filterBy('status', 'published')
        ->sortBy('date', 'desc');
    return $data;
};
```

And in our template:

```twig
{# site/templates/blog.twig #}

<h1>{{ page.title }}</h1>

{% if posts.count %}
    <ul>
    {% for post in posts %}
        <li><a href="{{ post.url }}">{{ post.title }}</a></li>
    {% endfor %}
    </ul>
{% endif %}
```

Ain’t that better? Well, you decide. :) I like separating the “logic” from the HTML markup, but if you just want to write a template and be done, do what you like best.
