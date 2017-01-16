Displaying Twig errors
======================


Let’s say something is wrong in your template. The syntax is bad, or you used a Twig tag that doesn’t exist. What will happen now?

We try to only show informative errors when it’s safe to do so. If you don’t ask this plugin to show you error information, you might get *nothing*, or a standard error page (maybe your own 404 page).


Switching to debug mode
-----------------------

To be precise, we only show an error with relevant technical information when the `debug` option is on. You can set it in your config like this:

```php
<?php /* site/config/config.localhost.php */
c::set('debug', true);
```

Note that you should avoid enabling the `debug` option on a production website. You can use domain-specific config files with Kirby:

```
site/
    config/
        config.php
        config.localhost.php
        config.www.yourdomain.com.php
```

If you’re testing your website on `http://localhost/`, then Kirby will load the `site/config/config.localhost.php` file. Replace "localhost" with the domain name you’re using for development.


What errors look like
---------------------

We’re showing different things depending on (A) if debug mode is active and (B) if we’re rendering a full page using Twig, or just a fragment with the `twig()` helper function.

<table>
  <tr>
    <th scope="col">Debug mode</th>
    <th scope="col">Full page</th>
    <th scope="col">twig($template, $data)</th>
  </tr>
  <tr>
    <th scope="row">Off (debug=false)</th>
    <td>
        <strong>Shows the site’s error page</strong><br>
        By default, it’s the page whose URI is <code>'error'</code>.<br>
        If this doesn’t work, we’re letting Kirby handle the error; depending on your version and settings, this can mean seeing a completely white page (no content), or a white page with a basic error message.</td>
    <td>
      <strong>Returns nothing</strong><br>
      The <code>twig()</code> function will return an empty string. No content, no error, no nothing.
    </td>
  </tr>
  <tr>
    <th scope="row">On (debug=true)</th>
    <td>
      <strong>Shows a custom error page</strong><br>
      Complete with an error message and an extract of the faulty code. So much better for debugging!
    </td>
    <td>
      <strong>Returns a short error message</strong><br>
      A few lines of text, with an error message and a shorter extract of the faulty code.
    </td>
  </tr>
</table>

*Note for Kirby 2.4+ users: we are not using the Whoops error reporting page, with its full stack trace explorer, because it’s not really useful here — it shows PHP code instead of the faulty Twig code.*
