# WPSkin

Object-oriented base plugin for WordPress (PHP 7.4+, WP 6.0+).

## Structure

```
wpskin.php              Bootstrap: constants, autoloader, (de)activation hooks, boot on plugins_loaded
uninstall.php           Deletes plugin options
src/
  Autoloader.php        PSR-4: WPSkin\Foo\Bar -> src/Foo/Bar.php
  Plugin.php            Singleton; instantiates services and runs the Loader
  Loader.php            Queues actions/filters, registers them on run()
  Contracts/Hookable.php  Interface every service implements
  I18n.php              Text domain
  Activator.php / Deactivator.php
  Admin/Admin.php       Admin assets + "Settings" link on Plugins screen
  Admin/Settings.php    Settings > WPSkin page (Settings API)
assets/                 css/ and js/ for the admin
languages/
```

## Adding a feature

1. Create a class under `src/` implementing `WPSkin\Contracts\Hookable`:

```php
namespace WPSkin\Frontend;

use WPSkin\Contracts\Hookable;
use WPSkin\Loader;

class Shortcode implements Hookable {
	public function register_hooks( Loader $loader ) {
		$loader->add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		add_shortcode( 'wpskin', function () { return 'Hello'; } );
	}
}
```

2. Add it to `Plugin::get_service_classes()` (or from outside via the `wpskin_services` filter).

## API

- `wpskin()` — plugin instance
- `wpskin()->get( SomeClass::class )` — booted service instance
- `WPSkin\Admin\Settings::get( 'dashboard_cards' )` — read a setting
- Action `wpskin_booted` fires after all services are registered
