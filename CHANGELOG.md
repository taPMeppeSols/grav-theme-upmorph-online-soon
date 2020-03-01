# v2020.14
## 01.03.2020
1. [](#improved)
   * Modul social networks: the status check has been added.
   * Global `THEME` object: `{% set THEME = config.theme %}`.
   * Global `LANG` object: `{% set LANG = html_lang %}`.
2. [](#bugfix)
   * Modul registration: the status check has been added to simplify the deactivation of the module.


# v2020.13
## 01.03.2020
1. [](#improved)
   * languages/fr.yaml
   * languages/de.yaml

# v2020.12
## 29.02.2020
1. [](#improved)
   * *.twig: `_self` as been replaced by `import _self as macros`.
   * upmorph-online-soon.php & *.twig: The GRAV native `page->url` is now used instead of the theme custom route finding algorithm.
   * base.html.twig: `meta:color-theme` has been added.

# v2020.10-11
## 27.02.2020
1. [](#improved)
   * Minor improvements
   * Successful test on the browser: opera

# v2020.9
## 08-09.02.2020
1. [](#improved)
   * The error logger is now more versatile.
2. [](#bugfix) 
   * Backend: the registration module now properly shows that an email is already in the list when an opt-in url is used more than once.
   * Design (CSS): the representation of the modals.

# v2020.8
## 08.02.2020
1. [](#improved)
   * The *privacy policy* & *imprint* have been added at the foot of the site.
   * The email deletion action has been added the registration module.
   * The registration module has been re-written from the procedural approach to a more object oriented one.
   * The improvement above was necessary to fully embed the registration module in the GRAV life cycle.
2. [](#bugfix) 
   * Design (CSS): the representation of the site foot on mobile devices.

# v2020.6
## 25.01.2020
1. [](#bugfix) 
   * Minor bug fixes

# v2020.5
## 25.01.2020
1. [](#improved)
   * The dependency from the plugin 'admin-addon-user-manager' has become optional and no longer mandatory.
   * An internal simple buffer is now used to speed up multiple access to the list of recipients (users) & pages (routes).
2. [](#bugfix) 
   * The fields in the registration form on the side now have the same height and the text is placed in the middle.

# v2020.4
## 23.01.2020
1. [](#new)
   * First release
