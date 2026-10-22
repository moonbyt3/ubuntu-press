<?php
/**
 * Dashboard background: a preset gradient or an image from the Media Library.
 * Shared by the settings page, the Profile section and the Dashboard cards screen.
 *
 * @package UbuntuPress
 */

namespace UbuntuPress\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background {

	/**
	 * Choice value for a Media Library image.
	 */
	const IMAGE = 'image';

	/**
	 * Preset backgrounds, keyed by setting value: two Yaru-coloured glows and the dark-style base
	 * colour beneath them. The light style draws them on white.
	 */
	const PRESETS = array(
		'aubergine' => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #e95420 0%, rgba(233, 84, 32, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #77216f 0%, rgba(119, 33, 111, 0) 70%)',
			'base'   => '#2c001e',
		),
		'sunset'    => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #da3450 0%, rgba(218, 52, 80, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #e95420 0%, rgba(233, 84, 32, 0) 70%)',
			'base'   => '#2a0b0b',
		),
		'lavender'  => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #b34cb3 0%, rgba(179, 76, 179, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #7764d8 0%, rgba(119, 100, 216, 0) 70%)',
			'base'   => '#170f2e',
		),
		'ocean'     => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #0073e5 0%, rgba(0, 115, 229, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #308280 0%, rgba(48, 130, 128, 0) 70%)',
			'base'   => '#04172b',
		),
		'forest'    => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #4b8501 0%, rgba(75, 133, 1, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #03875b 0%, rgba(3, 135, 91, 0) 70%)',
			'base'   => '#06190f',
		),
		'graphite'  => array(
			'layers' => 'radial-gradient(ellipse 70% 60% at 85% 100%, #787859 0%, rgba(120, 120, 89, 0) 70%), radial-gradient(ellipse 80% 70% at 10% 0%, #5e5e5e 0%, rgba(94, 94, 94, 0) 70%)',
			'base'   => '#1d1d1d',
		),
	);

	/**
	 * Base colour beneath an image while it loads, and around one that doesn't cover the screen.
	 */
	const IMAGE_BASE = '#1d1d1d';

	/**
	 * @return string[] Preset names, keyed by setting value.
	 */
	public static function labels() {
		return array(
			'aubergine' => __( 'Aubergine', 'ubuntupress' ),
			'sunset'    => __( 'Sunset', 'ubuntupress' ),
			'lavender'  => __( 'Lavender', 'ubuntupress' ),
			'ocean'     => __( 'Ocean', 'ubuntupress' ),
			'forest'    => __( 'Forest', 'ubuntupress' ),
			'graphite'  => __( 'Graphite', 'ubuntupress' ),
		);
	}

	/**
	 * @param mixed $choice Submitted choice.
	 * @return bool
	 */
	public static function is_choice( $choice ) {
		return self::IMAGE === $choice || isset( self::PRESETS[ $choice ] );
	}

	/**
	 * Keeps an attachment ID only if it is an image the current user may pick: anyone who can
	 * open the Media Library, or anyone keeping the site's own image.
	 *
	 * @param mixed $id Submitted attachment ID.
	 * @return int Attachment ID, or 0.
	 */
	public static function sanitize_image( $id ) {
		$id = absint( $id );
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return 0;
		}

		$site = Settings::site_values();
		return current_user_can( 'upload_files' ) || (int) $site['background_image'] === $id ? $id : 0;
	}

	/**
	 * CSS background layers for an attachment.
	 *
	 * @param int    $id   Attachment ID.
	 * @param string $size Image size.
	 * @return string Empty when the attachment has no image.
	 */
	private static function image_layer( $id, $size ) {
		$url = $id ? wp_get_attachment_image_url( $id, $size ) : false;
		if ( ! $url ) {
			return '';
		}

		// Percent-encode what could end the CSS string; esc_url_raw() already drops "<".
		$url = str_replace( array( '"', "'", '(', ')', '\\' ), array( '%22', '%27', '%28', '%29', '%5C' ), esc_url_raw( $url ) );
		return 'url("' . $url . '") center / cover no-repeat';
	}

	/**
	 * The current user's background. A missing image falls back to the default preset.
	 *
	 * @return array Keys: layers, base, image (bool).
	 */
	public static function current() {
		$choice = Settings::get( 'background' );

		if ( self::IMAGE === $choice ) {
			$layers = self::image_layer( (int) Settings::get( 'background_image' ), 'full' );
			if ( $layers ) {
				return array(
					'layers' => $layers,
					'base'   => self::IMAGE_BASE,
					'image'  => true,
				);
			}
		}

		$preset = isset( self::PRESETS[ $choice ] ) ? self::PRESETS[ $choice ] : self::PRESETS[ Settings::defaults()['background'] ];
		return $preset + array( 'image' => false );
	}

	/**
	 * Custom properties read by dashboard.css.
	 *
	 * @return string
	 */
	public static function css() {
		$background = self::current();
		return sprintf(
			'body.ubuntupress-admin--dashboard{--ubuntupress-background:%1$s;--ubuntupress-background-base:%2$s;}',
			$background['layers'],
			$background['base']
		);
	}

	/**
	 * Loads the media modal for the "Choose Image" button.
	 */
	public static function enqueue_media() {
		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Outputs the picker: a thumbnail radio per preset, one for the chosen image, and a button
	 * that opens the Media Library. The caller wraps it in a labelled group.
	 *
	 * @param string $name     Form field name the values are submitted under.
	 * @param string $choice   Current choice: a preset or IMAGE.
	 * @param int    $image_id Current image attachment ID.
	 */
	public static function render_picker( $name, $choice, $image_id ) {
		$image = self::image_layer( (int) $image_id, 'medium' );
		?>
		<div class="ubuntupress-backgrounds">
			<?php foreach ( self::labels() as $preset => $label ) : ?>
				<input
					type="radio"
					class="ubuntupress-background"
					name="<?php echo esc_attr( $name . '[background]' ); ?>"
					value="<?php echo esc_attr( $preset ); ?>"
					style="<?php echo esc_attr( '--ubuntupress-background: ' . self::PRESETS[ $preset ]['layers'] . '; --ubuntupress-background-base: ' . self::PRESETS[ $preset ]['base'] ); ?>"
					aria-label="<?php echo esc_attr( $label ); ?>"
					title="<?php echo esc_attr( $label ); ?>"
					<?php checked( $choice, $preset ); ?>
				/>
			<?php endforeach; ?>
			<input
				type="radio"
				class="ubuntupress-background ubuntupress-background--image"
				name="<?php echo esc_attr( $name . '[background]' ); ?>"
				value="<?php echo esc_attr( self::IMAGE ); ?>"
				style="<?php echo esc_attr( $image ? '--ubuntupress-background: ' . $image : '' ); ?>"
				aria-label="<?php esc_attr_e( 'Image', 'ubuntupress' ); ?>"
				title="<?php esc_attr_e( 'Image', 'ubuntupress' ); ?>"
				<?php checked( $image && self::IMAGE === $choice ); ?>
				<?php echo $image ? '' : 'hidden'; ?>
			/>
			<input type="hidden" class="ubuntupress-backgrounds__image" name="<?php echo esc_attr( $name . '[background_image]' ); ?>" value="<?php echo esc_attr( $image ? (int) $image_id : 0 ); ?>" />
			<?php if ( current_user_can( 'upload_files' ) ) : ?>
				<button
					type="button"
					class="button ubuntupress-backgrounds__choose"
					data-title="<?php esc_attr_e( 'Choose Image', 'ubuntupress' ); ?>"
					data-button="<?php esc_attr_e( 'Set as background', 'ubuntupress' ); ?>"
				><?php esc_html_e( 'Choose Image', 'ubuntupress' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}
}
