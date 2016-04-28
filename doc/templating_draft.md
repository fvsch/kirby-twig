# Twig templating tips for Kirby

This is a short guide of how to use Twig templates with Kirby CMS. It supposes that you have installed and configured the Twig Plugin already.


## Twig basics

If you don’t know Twig already, you should read [Twig for Template Designers](http://twig.sensiolabs.org/doc/templates.html).


## Using Kirby’s chaining API

Kirby’s API for `$page`, `$site`, etc. works well with Twig. See this example:

```twig
{% set posts = site
    .find('blog')
    .children
    .filterBy('status', 'published')
    .sortBy('date', 'desc') %}

{% if posts.count %}
    <ul>
    {% for post in posts %}
        <li><a href="{{ post.url }}">{{ post.title }}</a></li>
    {% endfor %}
    </ul>
{% endif %}
```


## Extending and includes

An important feature of Twig is extending a “parent” template. For instance you could have a `_base.twig` template:

```twig
<!doctype html>
<html lang="">
```


## Kirby helper functions

*To be written later, sorry.*

(Added a `config()` function that aliases `c::get()`.)


## Kirby Toolkit API

Potentially useful:

    a (array helpers)
    c::get (not c::set and c::remove)
    string
    url
    v (validators)

