<?

// REQUIRED: activate Twig plugin
c::set('plugin.twig.enabled', true);

// Optional: use Twig’s PHP cache in addition to Kirby’s HTML cache,
// (Only works when Kirby’s cache is active.) Defaults to false.
c::set('plugin.twig.cache', true);

// Optional: disable or specify autoescaping. Defaults to 'html'
// http://twig.sensiolabs.org/doc/api.html#environment-options
c::set('plugin.twig.autoescape', false);
