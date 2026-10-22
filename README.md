# UbuntuPress

UbuntuPress restyles the WordPress admin after the Ubuntu desktop (GNOME with Yaru colours). Each feature can be switched on or off on one settings page that looks like GNOME Settings.

Requires WordPress 6.0+ and PHP 7.4+.

## Features

- **Light and dark styles.** A colour theme for the whole admin. Dark mode also covers the block editor, the editor canvas and the Customizer, including the block-based widgets editor.
- **Dashboard cards.** The usual Dashboard is replaced by a grid of large app icons built from your admin menu. Icons use the Yaru accent colours, and update and comment counts show as badges. Submenus become app folders with a 2×2 preview, and you can type to search everything at once. The classic Dashboard is still available from its own card.
- **Hidden sidebar.** You can hide the admin sidebar on every screen, on the Dashboard only, or never. When it's hidden, a "Dashboard" link appears in the toolbar so you can still get around. The sidebar only hides while Dashboard cards are on, so you're never left without navigation.
- **Notification center.** Admin notices no longer pile up at the top of the page. They're collected in a popup behind a bell icon in the toolbar, with a counter. On by default.
- **Ubuntu-style Settings screens.** Optionally, General, Writing, Reading, Discussion, Media and Permalinks get the same look as the UbuntuPress settings page.

Deleting the plugin removes its settings.

## Structure

```
ubuntupress.php         Bootstrap: constants, autoloader, (de)activation hooks, boot on plugins_loaded
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
  Admin/Theme.php       Light / dark style for the admin, block editor and Customizer
  Admin/DashboardCards.php  Card-based Dashboard built from the admin menu
  Admin/Sidebar.php     Hides the admin sidebar, adds a toolbar "Dashboard" link
  Admin/Notifications.php  Collects admin notices into a toolbar popup
  Admin/SettingsPages.php  Ubuntu styling for core Settings screens
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
