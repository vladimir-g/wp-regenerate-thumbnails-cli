You might be looking for the documentation of the `built-in wp-cli command <https://developer.wordpress.org/cli/commands/media/regenerate/>`_.

========================================
Regenerate Wordpress thumbnails from CLI
========================================

This is very simple php script/library for regenerating Wordpress
thumbnails from command line.  It can be used as standalone command
line script or in third-party code.

Code isn't perfect and not well-tested, use at your own risk.

License: WTFPLv2

CLI usage
=========

Put wp-regenerate-thumbnails-cli.php somewhere and run::

  php wp-regenerate-thumbnails-cli.php -p /path/to/wp/root/

Options:

- -p|--path -- path to Wordpress root directory (required)
- -h|--help -- show usage info
- -s|--silent -- don't print messages (except php errors/warnings)
- -r|--remove -- remove old images (warning, experimental)
- -d|--debug -- don't really remove images, just print filenames

Removing old images must work correctly, but better to do a debug run
(-d) before it.

Using in the code
=================

*wp-load.php*, *wp-admin/includes/image.php* and
*wp-admin/includes/file.php* must be included before. They are
included automatically when used in Wordpress environment.

Example::

  require_once('/path/to/wp/wp-load.php');
  require_once('/path/to/wp/wp-admin/includes/image.php');
  require_once('/path/to/wp/wp-admin/includes/file.php');

  require_once('/path/to/scrip/wp-regenerate-thumbnails-cli.php');

  $r = new WpRegenerateThumbnailsCli\Regenerator(false, false, false);
  $r->run(); // Will take long

Constructor looks like *Regenerator($remove=false, $debug=false,
$silent=true)*, options are same as CLI args.
