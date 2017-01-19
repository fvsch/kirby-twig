Kirby Twig Plugin: Change log
=============================

v3.0.0
------

**Breaking changes:**

- Removed the `twig` boolean option. The plugin is now active if it’s installed (classical installation). For Composer installs, there is a separate registering step.
- Removed the `twig.env.classes` option and `new()` Twig function.
- Twig’s template cache is now disabled by default (enable with `c::set('twig.cache', true);`).
- Error reporting: the `twig.error` config key is now ignored. Instead, the site’s main error page (whose URI is `error` by default) will be used in some specific situations. See `doc/errors.md` for details.
- Namespace and class names (and sometimes methods) have changed (again); there is now a `Kirby\Twig\Plugin` class which will act as a stable API, while other implementation details may change.

Deprecated (still working):

- `twig.env.functions` in favor of `twig.function.myFunction`;
- `twig.env.filters` in favor of `twig.filter.myFilter`;
- `twig.env.namespace.xyz` in favor of `twig.namespace.xyz`.

v2.x
----

See on GitHub:

- [v2.1.2](https://github.com/fvsch/kirby-twig/releases/tag/v2.1.2)
- [v2.1.1](https://github.com/fvsch/kirby-twig/releases/tag/v2.1.1)
- [v2.1.0](https://github.com/fvsch/kirby-twig/releases/tag/v2.1.0)
- [v2.0.2](https://github.com/fvsch/kirby-twig/releases/tag/v2.0.2)
- [v2.0.1](https://github.com/fvsch/kirby-twig/releases/tag/v2.0.1)
- [v2.0.0](https://github.com/fvsch/kirby-twig/releases/tag/v2.0.0)

v1.x
----

- [v1.3.0](https://github.com/fvsch/kirby-twig/releases/tag/v1.3.0)
- [v1.2.0](https://github.com/fvsch/kirby-twig/releases/tag/v1.2.0)
- [v1.0.0](https://github.com/fvsch/kirby-twig/releases/tag/v1.0.0)
