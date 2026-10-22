# UbuntuPress

UbuntuPress restyles the WordPress admin after the Ubuntu desktop (GNOME with Yaru colours). Each feature can be switched on or off on one settings page that looks like GNOME Settings.

Requires WordPress 6.0+ and PHP 7.4+.

## Features

- **Dashboard cards.** The usual Dashboard is replaced by a grid of large app icons built from your admin menu. Icons use the Yaru accent colours, and update and comment counts show as badges. Submenus become app folders with a 2×2 preview, and you can *type to search* everything at once. The classic Dashboard is still available from its own card.
- **Light and dark styles.** A colour theme for the whole admin. Dark mode also covers the block editor, the editor canvas and the Customizer, including the block-based widgets editor.
<img width="1919" height="928" alt="Dashboard Cards (light)" src="https://github.com/user-attachments/assets/6e62eff6-dfee-4fda-a8ff-fc0c6a48ff73" />
<img width="1919" height="928" alt="Dashboard Cards (dark)" src="https://github.com/user-attachments/assets/a8bc0d14-d33d-4647-9af5-8f1f82d7d8de" />

- **Notification center.** Admin notices no longer pile up at the top of the page. They're collected in a popup behind a bell icon in the toolbar, with a counter. On by default.
<img width="1919" height="928" alt="Dashboard notifications" src="https://github.com/user-attachments/assets/ced5c98e-6b71-4b5d-ae64-5ee85c5adba1" />

- **Ubuntu-style Settings screens.** Optionally, General, Writing, Reading, Discussion, Media and Permalinks get the same look as the UbuntuPress settings page.
<img width="1919" height="928" alt="UbuntuPress Plugin Settings" src="https://github.com/user-attachments/assets/701bcdcd-3fec-498c-9b2f-323c50b3cea6" />


<img width="1919" height="928" alt="General Settings WordPress dashboard" src="https://github.com/user-attachments/assets/e49c9e3e-89ff-4558-b1f3-dc2c45bcddd2" />

- **Hidden sidebar.** You can hide the admin sidebar on every screen, on the Dashboard only, or never. When it's hidden, a "Dashboard" link appears in the toolbar so you can still get around. The sidebar only hides while Dashboard cards are on, so you're never left without navigation.
<img width="1919" height="928" alt="WordPress Sidebar settings" src="https://github.com/user-attachments/assets/59926e18-7baf-404d-97f4-18b4175eb70e" />

- **Dock.** An Ubuntu Dock that stays out of sight until the pointer touches the left, bottom or right edge of the screen, then slides in. You can choose which admin menu items it holds. It mirrors Ubuntu Dock's timing: a 250 ms show delay, a 200 ms ease-out slide, a 200 ms hide delay, app name labels that fade in after 300 ms, and the GNOME zoom-out effect when you launch an app. While it's hidden, a small pill marks its edge and turns orange as the dock is about to appear. With *Auto-hide the Dock* off, it stays visible and admin screens make room for it. The current screen gets the orange running dot, and update counts show as badges. Off by default; works in the block editor too.
<img width="1919" height="928" alt="WordPress Ubuntu Dock" src="https://github.com/user-attachments/assets/70078abb-c175-4c99-96e2-2087ad615e21" />


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
  Admin/AdminMenu.php   Reads the admin menu (names, URLs, icons, badges); shared by cards and dock
  Admin/DashboardCards.php  Card-based Dashboard built from the admin menu
  Admin/Dock.php        Auto-hiding Ubuntu Dock with apps picked from the admin menu
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
- Filter `ubuntupress_dock_items` — apps shown in the dock
- Action `ubuntupress_booted` fires after all services are registered
