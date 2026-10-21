# UbuntuPress

Object-oriented base plugin for WordPress (PHP 7.4+, WP 6.0+).

## Structure

```
ubuntupress.php              Bootstrap: constants, autoloader, (de)activation hooks, boot on plugins_loaded
uninstall.php           Deletes plugin options
src/
  Autoloader.php        PSR-4: UbuntuPress\Foo\Bar -> src/Foo/Bar.php
  Plugin.php            Singleton; instantiates services and runs the Loader
  Loader.php            Queues actions/filters, registers them on run()
  Contracts/Hookable.php  Interface every service implements
  I18n.php              Text domain
  Activator.php / Deactivator.php
  Admin/Admin.php       Admin assets + "Settings" link on Plugins screen
  Admin/Settings.php    UbuntuPress settings page, last in the admin menu (Settings API)
assets/                 css/ and js/ for the admin
bin/build-editor-dark.py  Regenerates assets/css/editor-dark.css and customize-dark.css from core's editor / Customizer CSS
languages/
```

## Adding a feature

1. Create a class under `src/` implementing `UbuntuPress\Contracts\Hookable`:

```php
namespace UbuntuPress\Frontend;

use UbuntuPress\Contracts\Hookable;
use UbuntuPress\Loader;

class Shortcode implements Hookable {
	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		add_shortcode( 'ubuntupress', function () { return 'Hello'; } );
	}
}
```

2. Add it to `Plugin::get_service_classes()` (or from outside via the `ubuntupress_services` filter).

## API

- `ubuntupress()` — plugin instance
- `ubuntupress()->get( SomeClass::class )` — booted service instance
- `UbuntuPress\Admin\Settings::get( 'dashboard_cards' )` — read a setting
- Action `ubuntupress_booted` fires after all services are registered
