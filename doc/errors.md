kirby-twig: How errors are displayed (or not)
=============================================

With PHP templates, most errors are shown directly in the page. Things are a bit different with Twig: if an error is not suppressed, the template will *not* be rendered at all, and you end up with an error page.

This plugin uses the value of the `debug` option (`c::get('debug')`) to know how strict it should be with errors and how much information to display.

## In debug mode

If `c::get('debug')` is true:

- Undefined variables and methods raise an error (see the config section if you want to change that).
- A nice error page is shown, with an excerpt of the faulty template code.

<figure>
    <img src="doc/errorpage.png" width="770" alt="">
</figure>

## In production

If `c::get('debug')` is false:

1. Undefined variables are ignored, so they don’t raise an error.
2. For other errors, an error page will be shown, and it will have very little information about the source of the error (it doesn’t mention Twig, template names, etc.). We will show the site’s error page (whose content URI is defined by `c::get('error')`) if it exists, or a very short message otherwise.
