Twig templating in Kirby
========================

This is a short guide of how to use Twig templates with Kirby CMS. It supposes that you have installed and configured the Twig Plugin already.


## Twig basics

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


## Twig tips and tricks

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


## What variables can I use?

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


## Using controllers

You can send more information to templates by [writing a Controller](https://getkirby.com/docs/developer-guide/advanced/controllers). Don’t worry, it’s really easy.

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


## Using custom PHP functions in Twig

Unlike with PHP templates, you won’t have access to any function or class defined in a plugin file. Twig needs every function to be passed explicitely to the template, which can be a bit bothersome.

### You might not need it?

I’ve found that it’s not a big deal in Kirby because:

-   For important logic, writing a controller and returning some data to the template is better than doing everything in the template anyway.
-   It’s possible to extend the `$page` and `$site` objects with your own methods, which covers a lot of ground.

If you still need a way to pass a function or PHP class to your templates, please [open an issue](https://github.com/fvsch/kirby-twig/issues).

### Kirby helper functions

Kirby provides [many helper functions](https://getkirby.com/docs/cheatsheet#helpers), plus the [Kirby Toolkit](https://getkirby.com/docs/toolkit/api), that can be used in templates (and in controllers and plugins).

Only some of them are made vailable to Twig templates:

-   Generating HTML tags: `css()`, `js()`, `kirbytag()`
-   Service-specific tags: `youtube()`, `vimeo()`, `twitter()`, `gist()`
-   URL and request stuff: `get()`, `thisUrl()`, `param()`, `params()`
-   Getting Kirby pages: `page()`, `pages()`
-   Getting a config value: `config()` (alias for `c::get()`)

Some functions are made as Twig filters:

-   Text transformations: `markdown`, `smartypants`, `kirbytext`, `multiline`, `excerpt`
-   String escaping: `html`, `xml`
-   String enhancement: `url`, `gravatar`

If you think something *should* be added, please [open an issue](https://github.com/fvsch/kirby-twig/issues).
