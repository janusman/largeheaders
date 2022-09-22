# Large Headers

Dumps large headers for debugging/troubleshooting. Related to https://www.drupal.org/project/drupal/issues/2844620 where Drupal or modules can output too much HTTP header data (like Cache Tag debugging, multilingual 'link' headers, etc.) which can cause issues at Apache, Varnish, reverse proxies, or other services.

Turn it on and visit /admin/config/services/largeheaders to configure the thresholds.

The complete headers will also be logged to a file called `largeheader.log` on the current temporary folder set by Drupal (you can find the temp folder by running: drush st --fields=temp). If this file grows too large (around 0.5MB) it will be rotated into a `largeheaders.log.1` file, and `largeheaders.log` will be emptied.

Each request logged on Drupal watchdog will have a UUID value. You can use this UUID to look for the request in the above file(s).

Example drupal-watchdog messages:

```
Found large header(s): request GET /myview ; UUID 12345678901234567890123 ; reasons [large_combined_header_size]
```

```
Found large header(s): request GET / ; UUID 12345678901234567890123 ; reasons [one_or_more_large_headers,too_many_headers]
```
