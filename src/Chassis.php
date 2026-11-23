<?php
/**
 * Chassis.
 *
 * Framework-agnostic helpers for the shared UI: where the package lives (so
 * build tools can find the SCSS) and small functions that print the markup
 * for its components. Works the same in ClassicPress themes (Backdrop) and
 * Novaris sites; each framework gets a thin service provider in `Providers/`.
 *
 * @package   Techkodeninja\Chassis
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/techkodeninja/chassis
 */

namespace Techkodeninja\Chassis;

class Chassis
{
	/**
	 * Package version.
	 */
	const VERSION = '0.2.3';

	/**
	 * The platform this was created for: `novaris`, `backdrop`, or `plain`.
	 *
	 * @var string
	 */
	protected $platform;

	/**
	 * Turns a file in `dist/` into a URL. Set by the service providers.
	 *
	 * @var callable|null
	 */
	protected $asset;

	/**
	 * `chassis.json` files to read, parent theme first. Later files override
	 * earlier ones.
	 *
	 * @var string[]
	 */
	protected $designFiles = [];

	/**
	 * The merged `chassis.json` settings, once read. Cleared when the files
	 * change, so `design()` doesn't re-parse them on every call.
	 *
	 * @var array|null
	 */
	protected $designCache;

	/**
	 * Values set from code, by dotted path (`styles.color.accent`). They are
	 * merged over the `chassis.json` files; see `overrides()`.
	 *
	 * @var array
	 */
	protected $overrides = [];

	/**
	 * Spacing names that `chassis.json` can use instead of CSS values.
	 */
	const SPACE_NAMES = [ 'xs', 's', 'm', 'l', 'l-plus', 'xl' ];

	/**
	 * The keys the older `chassis.json` shape knows, per group, from the
	 * schema. An unknown key warns (but is still printed: the older shape is
	 * open-ended by design, so a theme may set a custom property of its own).
	 * `menu.design` is the exception — the schema closes that list, so
	 * unknown keys there are skipped.
	 */
	const OLD_SHAPE_KEYS = [
		'color' => [
			'global'   => [ 'accent', 'accent-text', 'link', 'link-hover', 'page-background' ],
			'header'   => [ 'background', 'text' ],
			'content'  => [ 'alt', 'background', 'border', 'divider', 'heading', 'muted', 'text' ],
			'widget'   => [ 'background', 'border', 'text', 'title' ],
			'comments' => [ 'background', 'border' ],
			'footer'   => [ 'background', 'text' ],
		],
		'font'    => [ 'body', 'code', 'heading', 'meta' ],
		'spacing' => [ 'button-padding', 'comments-gap', 'comments-meta-gap', 'comments-padding', 'content-padding', 'entry-padding', 'header-padding', 'nav-link-padding', 'pagination-padding', 'sidebar-gap' ],
		'width'   => [ 'medium', 'narrow', 'sidebar', 'wide' ],
		'layout'  => [ 'medium', 'narrow', 'sidebar', 'wide' ],
		'design'  => [ 'badge-font-size', 'badge-letter-spacing', 'badge-padding', 'badge-text-transform', 'border-box', 'border-box-bottom', 'border-width', 'button-border-width', 'button-padding', 'comments-gap', 'comments-meta-gap', 'comments-padding', 'comments-shadow', 'content-padding', 'entry-padding', 'font-size-base', 'font-size-h1', 'font-size-h2', 'font-size-h3', 'font-size-h4', 'font-size-h5', 'font-size-h6', 'font-weight-heading', 'header-padding', 'header-tagline-size', 'header-title-size', 'header-title-size-large', 'line-height-base', 'line-height-heading', 'nav-link-padding', 'pagination-padding', 'radius', 'shadow', 'sidebar-gap' ],
		'menu.design' => [ 'background', 'border', 'padding', 'text' ],
	];

	/**
	 * The `settings` and `styles` shape of `chassis.json`: each setting's path, the custom
	 * property it sets, and whether it takes spacing names (`l`, `xs m`).
	 *
	 * `settings` defines things (the palette, fonts, spacing scale, widths,
	 * shapes), and `styles` applies them to the page: the whole site first,
	 * then `elements` (links, headings, buttons), then each part of the page
	 * under a friendly name. Each part's description in the schema names its
	 * BEM block, e.g. `post` is `.entry`. Inside a part, nested names are its
	 * BEM elements (`post.meta`), and keys starting with `--` its modifiers.
	 *
	 * `settings.palette`, `settings.custom`, `styles.menu-button.position`,
	 * `styles.menu-button.hide-on-scroll` and the composed borders are handled
	 * in `stylesProperties()`, not here.
	 */
	const STYLES_MAP = [
		// Settings.
		'settings.font.body'           => [ 'font-family-body', false ],
		'settings.font.heading'        => [ 'font-family-heading', false ],
		'settings.font.code'           => [ 'font-family-code', false ],
		'settings.font.meta'           => [ 'font-family-meta', false ],
		'settings.spacing.xs'          => [ 'space-xs', false ],
		'settings.spacing.s'           => [ 'space-s', false ],
		'settings.spacing.m'           => [ 'space-m', false ],
		'settings.spacing.l'           => [ 'space-l', false ],
		'settings.spacing.l-plus'      => [ 'space-l-plus', false ],
		'settings.spacing.xl'          => [ 'space-xl', false ],
		'settings.layout.width'        => [ 'width-wide', false ],
		'settings.layout.medium'       => [ 'width-medium', false ],
		'settings.layout.narrow'       => [ 'width-narrow', false ],
		'settings.layout.sidebar'      => [ 'width-sidebar', false ],
		'settings.shape.radius'        => [ 'radius', false ],
		'settings.shape.shadow'        => [ 'shadow', false ],
		'settings.shape.border-width'  => [ 'border-width', false ],

		// The whole site (`.app`).
		'styles.color.background'      => [ 'color-page-background', false ],
		'styles.color.text'            => [ 'color-content-text', false ],
		'styles.color.muted'           => [ 'color-content-muted', false ],
		'styles.color.divider'         => [ 'color-content-divider', false ],
		'styles.color.alt'             => [ 'color-content-alt', false ],
		'styles.color.accent'          => [ 'color-accent', false ],
		'styles.color.accent-text'     => [ 'color-accent-text', false ],
		'styles.typography.size'       => [ 'font-size-base', false ],
		'styles.typography.line-height' => [ 'line-height-base', false ],

		// Elements, anywhere on the page.
		'styles.elements.link.color.text'          => [ 'color-link', false ],
		'styles.elements.link.:hover.color.text'   => [ 'color-link-hover', false ],
		'styles.elements.heading.color.text'       => [ 'color-content-heading', false ],
		'styles.elements.heading.typography.weight' => [ 'font-weight-heading', false ],
		'styles.elements.heading.typography.line-height' => [ 'line-height-heading', false ],
		'styles.elements.h1.typography.size'       => [ 'font-size-h1', false ],
		'styles.elements.h2.typography.size'       => [ 'font-size-h2', false ],
		'styles.elements.h3.typography.size'       => [ 'font-size-h3', false ],
		'styles.elements.h4.typography.size'       => [ 'font-size-h4', false ],
		'styles.elements.h5.typography.size'       => [ 'font-size-h5', false ],
		'styles.elements.h6.typography.size'       => [ 'font-size-h6', false ],
		'styles.elements.button.spacing.padding'   => [ 'button-padding', true ],
		'styles.elements.button.border.width'      => [ 'button-border-width', false ],

		// `header`: `.app-header`.
		'styles.header.color.background'           => [ 'color-header-background', false ],
		'styles.header.color.text'                 => [ 'color-header-text', false ],
		'styles.header.spacing.padding'            => [ 'header-padding', true ],
		'styles.header.title.typography.size'      => [ 'header-title-size', false ],
		'styles.header.title.typography.size-large' => [ 'header-title-size-large', false ],
		'styles.header.tagline.typography.size'    => [ 'header-tagline-size', false ],

		// `primary-menu`: `.app-nav`.
		'styles.primary-menu.link.spacing.padding' => [ 'nav-link-padding', true ],

		// `social-menu`: `.social-menu`.
		'styles.social-menu.alignment'            => [ 'social-menu-alignment', false ],
		'styles.social-menu.icon.size'            => [ 'social-icon-size', true ],

		// `menu-button`: `.app-nav__toggle`.
		'styles.menu-button.color.background'      => [ 'menu-toggle-background', false ],
		'styles.menu-button.color.text'            => [ 'menu-toggle-color', false ],
		'styles.menu-button.spacing.padding'       => [ 'menu-toggle-padding', true ],

		// `content`: `.app-content`.
		'styles.content.spacing.padding'           => [ 'content-padding', true ],
		'styles.content.spacing.gap'               => [ 'sidebar-gap', true ],

		// `post`: `.entry`.
		'styles.post.color.background'             => [ 'color-content-background', false ],
		'styles.post.border.color'                 => [ 'color-content-border', false ],
		'styles.post.spacing.padding'              => [ 'entry-padding', true ],

		// `widget`: `.widget`.
		'styles.widget.color.background'           => [ 'color-widget-background', false ],
		'styles.widget.color.text'                 => [ 'color-widget-text', false ],
		'styles.widget.border.color'               => [ 'color-widget-border', false ],
		'styles.widget.title.color.text'           => [ 'color-widget-title', false ],

		// `comments`: `.comments-area` (ClassicPress).
		'styles.comments.color.background'         => [ 'color-comments-background', false ],
		'styles.comments.border.color'             => [ 'color-comments-border', false ],
		'styles.comments.spacing.padding'          => [ 'comments-padding', true ],
		'styles.comments.spacing.margin'           => [ 'comments-gap', true ],
		'styles.comments.shadow'                   => [ 'comments-shadow', false ],
		'styles.comments.meta.spacing.margin'      => [ 'comments-meta-gap', true ],

		// `pagination`: `.pagination`.
		'styles.pagination.spacing.padding'        => [ 'pagination-padding', true ],

		// `badge`: `.badge`.
		'styles.badge.typography.size'             => [ 'badge-font-size', false ],
		'styles.badge.typography.letter-spacing'   => [ 'badge-letter-spacing', false ],
		'styles.badge.typography.transform'        => [ 'badge-text-transform', false ],
		'styles.badge.spacing.padding'             => [ 'badge-padding', true ],

		// `footer`: `.app-footer`.
		'styles.footer.color.background'           => [ 'color-footer-background', false ],
		'styles.footer.color.text'                 => [ 'color-footer-text', false ],
	];

	/**
	 * Where the Menu button sits on phones, for `menu.toggle` in
	 * `chassis.json`. `bottom-left` is the default, so it sets nothing.
	 */
	const MENU_TOGGLE = [
		'bottom-left'  => [],
		'bottom-right' => [
			'menu-toggle-left'  => 'auto',
			'menu-toggle-right' => 'var( --space-l )',
		],
		'top-right'    => [
			'menu-toggle-background' => 'transparent',
			'menu-toggle-border'     => 'var( --border-width ) solid currentColor',
			'menu-toggle-bottom'     => 'auto',
			'menu-toggle-left'       => 'auto',
			'menu-toggle-padding'    => 'var( --space-xs ) var( --space-m )',
			'menu-toggle-position'   => 'static',
			'menu-panel-bottom'      => 'auto',
			'menu-panel-inset'       => '0',
			'menu-panel-max-height'  => 'calc( 100dvh - 6rem )',
			'menu-panel-position'    => 'absolute',
			'menu-panel-top'         => '100%',
			'menu-toggle-hide-offset' => 'calc( -100% - var( --space-l ) )',
		],
		'top-left'     => [
			'menu-toggle-background' => 'transparent',
			'menu-toggle-border'     => 'var( --border-width ) solid currentColor',
			'menu-toggle-bottom'     => 'auto',
			'menu-toggle-left'       => 'auto',
			'menu-toggle-padding'    => 'var( --space-xs ) var( --space-m )',
			'menu-toggle-position'   => 'static',
			'menu-panel-bottom'      => 'auto',
			'menu-panel-inset'       => '0',
			'menu-panel-max-height'  => 'calc( 100dvh - 6rem )',
			'menu-panel-position'    => 'absolute',
			'menu-panel-top'         => '100%',
			'menu-toggle-hide-offset' => 'calc( -100% - var( --space-l ) )',
			'menu-toggle-order'      => '-1',
			'menu-header-justify'    => 'flex-start',
		],
	];

	/**
	 * Sets the platform and how to reach the files in `dist/`. The service
	 * providers pass these in, and `plain` means no provider is in use.
	 */
	public function __construct( string $platform = 'plain', ?callable $asset = null )
	{
		$this->platform = $platform;
		$this->asset    = $asset;
	}

	/**
	 * Sets up Chassis for a site without Novaris or Backdrop. It copies the
	 * built files into a folder the browser can reach, and reads the design
	 * from `chassis.json`:
	 *
	 *     $chassis = Chassis::standalone( __DIR__ . '/public/chassis', '/chassis', __DIR__ . '/chassis.json' );
	 *
	 * @param string          $folder Folder the browser can reach. It's created if needed.
	 * @param string          $url    That folder's URL.
	 * @param string|string[] $design `chassis.json` files, parent first. Missing files are skipped.
	 */
	public static function standalone( string $folder, string $url, $design = [] ): self
	{
		$chassis = new static( 'plain', new Publisher( $folder, $url ) );

		return $chassis->designFiles( (array) $design );
	}

	/**
	 * Returns the platform: `novaris`, `backdrop`, or `plain`.
	 */
	public function platform(): string
	{
		return $this->platform;
	}

	/**
	 * Returns the package's root path, with an optional appended path.
	 */
	public function path( string $append = '' ): string
	{
		$path = dirname( __DIR__ );

		return $append ? $path . '/' . ltrim( $append, '/' ) : $path;
	}

	/**
	 * Returns the path to the SCSS source, e.g. for a Sass load path.
	 */
	public function scssPath( string $append = '' ): string
	{
		return $this->path( 'scss' . ( $append ? '/' . ltrim( $append, '/' ) : '' ) );
	}

	/**
	 * Returns the path to a built file in `dist/`.
	 */
	public function distPath( string $append = '' ): string
	{
		return $this->path( 'dist' . ( $append ? '/' . ltrim( $append, '/' ) : '' ) );
	}

	/**
	 * Returns the URL of a built file, or an empty string if it can't be
	 * reached. The file must stay inside `dist/`: a path that climbs out of
	 * it (for example `../composer.json`) is refused, so no other file can
	 * be copied into a web-reachable folder and handed back as a URL.
	 */
	public function assetUrl( string $file ): string
	{
		if ( ! $this->asset ) {
			return '';
		}

		$dist = realpath( $this->distPath() );
		$path = realpath( $this->distPath( $file ) );

		if ( ! $dist || ! $path || 0 !== strpos( $path, $dist . DIRECTORY_SEPARATOR ) ) {
			return '';
		}

		return (string) call_user_func( $this->asset, $file );
	}

	/**
	 * Returns the stylesheet tag. Print this in the page head, before the
	 * theme's own stylesheet.
	 */
	public function styles(): string
	{
		$url = $this->assetUrl( 'chassis.css' );

		if ( ! $url ) {
			return '';
		}

		$html   = sprintf( '<link rel="stylesheet" href="%s">', $this->escape( $url ) );
		$design = $this->designCss();

		return $design ? $html . "\n" . sprintf( '<style id="chassis-design">%s</style>', $design ) : $html;
	}

	/**
	 * Sets the `chassis.json` files to read, parent theme first. Missing files
	 * are skipped. The service providers call this with the theme's files.
	 *
	 * @param string[] $files Paths to `chassis.json` files.
	 */
	public function designFiles( array $files ): self
	{
		$this->designFiles = array_values( array_filter( $files, 'is_string' ) );
		$this->designCache = null;

		return $this;
	}

	/**
	 * Returns the settings that `chassis.json` can set, as `STYLES_MAP` has
	 * them: a dotted path (`styles.color.accent`) to `[ custom property,
	 * is spacing ]`. A customizer or settings page can use this to know which
	 * paths exist and which custom property each one sets.
	 */
	public static function stylesMap(): array
	{
		return self::STYLES_MAP;
	}

	/**
	 * Sets values from code, by the dotted paths in `stylesMap()`, for example
	 * `[ 'styles.color.accent' => '#cc3300' ]`. They are merged over the
	 * `chassis.json` files, so a customizer can change a design without
	 * editing the file. Calling this again replaces the earlier values.
	 *
	 * A path that isn't in `stylesMap()` is skipped with a warning, and so is
	 * a value that isn't a string or number. An empty value is skipped
	 * without a warning: it means "use the file's value".
	 *
	 * @param array $values Dotted path => value.
	 */
	public function overrides( array $values ): self
	{
		$this->overrides = [];

		foreach ( $values as $path => $value ) {
			if ( ! isset( self::STYLES_MAP[ $path ] ) ) {
				trigger_error( sprintf( 'Chassis skipped the override "%s": it isn\'t a setting.', $path ), E_USER_WARNING );
				continue;
			}

			if ( null === $value || '' === $value ) {
				continue;
			}

			if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
				trigger_error( sprintf( 'Chassis skipped the override "%s": use a plain value.', $path ), E_USER_WARNING );
				continue;
			}

			$this->overrides[ $path ] = (string) $value;
		}

		$this->designCache = null;

		return $this;
	}

	/**
	 * Returns the value a setting has now, from the `chassis.json` files and
	 * any `overrides()`, for the dotted path in `stylesMap()`. A palette
	 * color (`@slate`) is returned as the color it stands for. Returns null
	 * when nothing sets it, so the caller can use Chassis's own default.
	 *
	 * @param string $path Dotted path, like `styles.color.accent`.
	 * @return string|null
	 */
	public function value( string $path ): ?string
	{
		$value = $this->setting( $this->design(), $path );

		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return null;
		}

		$value = trim( (string) $value );

		if ( 0 === strpos( $value, '@' ) ) {
			$palette = $this->setting( $this->design(), 'settings.palette.' . substr( $value, 1 ) );

			return is_string( $palette ) ? $palette : null;
		}

		return $value;
	}

	/**
	 * Returns the merged settings from the `chassis.json` files. The files
	 * are read once and the result kept, so calling this (or `designCss()`)
	 * twice doesn't parse them twice.
	 */
	public function design(): array
	{
		if ( null !== $this->designCache ) {
			return $this->designCache;
		}

		$design = [];

		foreach ( array_unique( $this->designFiles ) as $file ) {
			if ( '' === $file || ! is_file( $file ) ) {
				continue;
			}

			if ( ! is_readable( $file ) ) {
				trigger_error( sprintf( 'Chassis could not read %s: the file is not readable.', $file ), E_USER_WARNING );
				continue;
			}

			$contents = file_get_contents( $file );

			if ( false === $contents ) {
				trigger_error( sprintf( 'Chassis could not read %s.', $file ), E_USER_WARNING );
				continue;
			}

			// A UTF-8 BOM, which Windows editors add, would make the JSON fail.
			if ( 0 === strpos( $contents, "\xEF\xBB\xBF" ) ) {
				$contents = substr( $contents, 3 );
			}

			$data = json_decode( $contents, true );

			if ( ! is_array( $data ) ) {
				if ( JSON_ERROR_NONE === json_last_error() ) {
					// Valid JSON, but a scalar (`"hello"`, `42`) instead of an object.
					trigger_error( sprintf( 'Chassis could not read %s: the file must contain a JSON object.', $file ), E_USER_WARNING );
				} else {
					trigger_error( sprintf( 'Chassis could not read %s: %s', $file, json_last_error_msg() ), E_USER_WARNING );
				}

				continue;
			}

			$design = array_replace_recursive( $design, $data );
		}

		// Values set from code win over the files.
		foreach ( $this->overrides as $path => $value ) {
			$design = array_replace_recursive( $design, $this->nest( $path, $value ) );
		}

		$this->designCache = $design;

		return $design;
	}

	/**
	 * Turns the `chassis.json` settings into custom properties:
	 *
	 * - `color.global.link` → `--color-link`
	 * - `color.header.text` → `--color-header-text` (any other group works the same)
	 * - `font.body` → `--font-family-body`
	 * - `spacing.content-padding` → `--content-padding`; size names such as
	 *   `l` or `xs m` become `var( --space-l )` and so on
	 * - `layout.wide` → `--width-wide` (`width` is the older name for this
	 *   group and still works; `layout` wins when both set the same width)
	 * - `design.radius` → `--radius`, for any other setting
	 * - `menu.toggle` → where the Menu button sits on phones: `bottom-left`
	 *   (the default), `bottom-right`, `top-right`, or `top-left`
	 * - `menu.design.background` → `--menu-toggle-background`, and the same
	 *   for `text` (`--menu-toggle-color`), `border`, and `padding`
	 * - `menu.hide-on-scroll` → `false` keeps the Menu button on screen while
	 *   scrolling (it hides on the way down by default)
	 *
	 * The `settings` and `styles` shape sets the same custom properties with
	 * clearer names; see `STYLES_MAP` and `stylesProperties()`. Both shapes
	 * work, and `settings` and `styles` win where both set the same thing. Any value can be a palette color, `@slate`.
	 *
	 * A setting neither shape knows is skipped with a warning, and the older
	 * shape warns about a name it doesn't style — so a typo in `chassis.json`
	 * shows up in the error log instead of failing quietly. The older shape
	 * still prints an unknown name's value, because it also sets custom
	 * properties of the theme's own; only `menu.design` is closed, so an
	 * unknown key there is skipped.
	 *
	 * Returns an empty string when there's nothing to set.
	 */
	public function designCss( ?array $design = null ): string
	{
		$design     = $design ?? $this->design();
		$properties = [];

		foreach ( $this->oldGroup( $design, 'color' ) as $group => $colors ) {
			if ( ! is_array( $colors ) ) {
				trigger_error( sprintf( 'Chassis skipped "color.%s" in chassis.json: it should be a list of colors.', $group ), E_USER_WARNING );
				continue;
			}

			$known = self::OLD_SHAPE_KEYS['color'][ $group ] ?? null;

			if ( null === $known ) {
				trigger_error( sprintf( 'Chassis doesn\'t style "color.%s" in chassis.json: check the group for a typo.', $group ), E_USER_WARNING );
			}

			$prefix = 'global' === $group ? 'color-' : "color-{$group}-";

			foreach ( $colors as $name => $value ) {
				if ( null !== $known && ! in_array( $name, $known, true ) ) {
					$this->warnUnknownOldKey( "color.{$group}.{$name}" );
				}

				$properties[ $prefix . $name ] = $value;
			}
		}

		foreach ( $this->oldGroup( $design, 'font' ) as $name => $value ) {
			if ( ! in_array( $name, self::OLD_SHAPE_KEYS['font'], true ) ) {
				$this->warnUnknownOldKey( "font.{$name}" );
			}

			$properties[ "font-family-{$name}" ] = $value;
		}

		foreach ( $this->oldGroup( $design, 'spacing' ) as $name => $value ) {
			if ( ! in_array( $name, self::OLD_SHAPE_KEYS['spacing'], true ) ) {
				$this->warnUnknownOldKey( "spacing.{$name}" );
			}

			$properties[ $name ] = is_string( $value ) ? $this->spaceValue( $value ) : $value;
		}

		// `width` is the older name for `layout`. Both work, `layout` last so it wins.
		foreach ( array_replace( $this->oldGroup( $design, 'width' ), $this->oldGroup( $design, 'layout' ) ) as $name => $value ) {
			if ( ! in_array( $name, self::OLD_SHAPE_KEYS['layout'], true ) ) {
				$this->warnUnknownOldKey( "layout.{$name}" );
			}

			$properties[ "width-{$name}" ] = $value;
		}

		$menu   = $this->oldGroup( $design, 'menu' );
		$toggle = $menu['toggle'] ?? null;

		if ( null !== $toggle ) {
			if ( is_string( $toggle ) && isset( self::MENU_TOGGLE[ $toggle ] ) ) {
				$properties = array_merge( $properties, self::MENU_TOGGLE[ $toggle ] );
			} else {
				trigger_error( sprintf( 'Chassis skipped menu.toggle in chassis.json: use %s.', implode( ', ', array_keys( self::MENU_TOGGLE ) ) ), E_USER_WARNING );
			}
		}

		if ( array_key_exists( 'hide-on-scroll', $menu ) ) {
			if ( is_bool( $menu['hide-on-scroll'] ) ) {
				$properties['menu-hide-on-scroll'] = $menu['hide-on-scroll'] ? '1' : '0';
			} else {
				trigger_error( 'Chassis skipped menu.hide-on-scroll in chassis.json: use true or false.', E_USER_WARNING );
			}
		}

		// The button's look, after the position so it wins over the presets.
		// `menu.design` is closed by the schema, so an unknown key is skipped.
		foreach ( $this->oldGroup( $menu, 'design' ) as $name => $value ) {
			if ( ! in_array( $name, self::OLD_SHAPE_KEYS['menu.design'], true ) ) {
				trigger_error( sprintf( 'Chassis skipped "menu.design.%s" in chassis.json: it isn\'t a setting.', $name ), E_USER_WARNING );
				continue;
			}

			$name = 'text' === $name ? 'color' : $name;

			$properties[ "menu-toggle-{$name}" ] = 'padding' === $name && is_string( $value ) ? $this->spaceValue( $value ) : $value;
		}

		// Last, so a theme's own settings win over the `menu.toggle` presets.
		foreach ( $this->oldGroup( $design, 'design' ) as $name => $value ) {
			if ( ! in_array( $name, self::OLD_SHAPE_KEYS['design'], true ) ) {
				$this->warnUnknownOldKey( "design.{$name}" );
			}

			$properties[ $name ] = $value;
		}

		// The `settings` and `styles` shape, after the older one so it wins
		// where both set the same thing.
		$properties = array_merge( $properties, $this->stylesProperties( $design ) );

		$palette = $this->group( $this->group( $design, 'settings' ), 'palette' );
		$css     = '';

		foreach ( $properties as $name => $value ) {
			$name = (string) $name;

			// `@slate` is the palette's `slate`.
			if ( is_string( $value ) && 0 === strpos( trim( $value ), '@' ) ) {
				$color = substr( trim( $value ), 1 );

				if ( ! array_key_exists( $color, $palette ) ) {
					trigger_error( sprintf( 'Chassis skipped the setting "%s" in chassis.json: "@%s" isn\'t in settings.palette.', $name, $color ), E_USER_WARNING );
					continue;
				}

				$value = "var( --palette-{$color} )";
			}

			if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $name ) ) {
				trigger_error( sprintf( 'Chassis skipped the setting "%s" in chassis.json: names use lowercase letters, numbers, and dashes.', $name ), E_USER_WARNING );
				continue;
			}

			if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
				trigger_error( sprintf( 'Chassis skipped the setting "%s" in chassis.json: use a plain value.', $name ), E_USER_WARNING );
				continue;
			}

			$value = trim( (string) $value );

			if ( '' === $value || preg_match( '/[{}<>;\\\\]|\/\*/', $value ) ) {
				trigger_error( sprintf( 'Chassis skipped the setting "%s" in chassis.json: its value isn\'t a plain CSS value.', $name ), E_USER_WARNING );
				continue;
			}

			$css .= "--{$name}:{$value};";
		}

		return $css ? ":root{{$css}}" : '';
	}

	/**
	 * Turns the `settings` and `styles` shape into custom properties, with the
	 * same names the older shape sets. Settings it
	 * doesn't know are skipped with a warning, so a typo doesn't fail quietly.
	 */
	protected function stylesProperties( array $design ): array
	{
		$properties = [];
		$settings   = $this->group( $design, 'settings' );
		$styles     = $this->group( $design, 'styles' );

		if ( ! $settings && ! $styles ) {
			return [];
		}

		// The palette: `slate` becomes `--palette-slate`, which `@slate` uses.
		foreach ( $this->group( $settings, 'palette' ) as $name => $value ) {
			$properties[ "palette-{$name}" ] = $value;
		}

		// Anything else, by its custom property's own name, like the older shape's `design`.
		foreach ( $this->group( $settings, 'custom' ) as $name => $value ) {
			$properties[ (string) $name ] = $value;
		}

		$menu = $this->group( $styles, 'menu-button' );

		if ( array_key_exists( 'position', $menu ) ) {
			if ( is_string( $menu['position'] ) && isset( self::MENU_TOGGLE[ $menu['position'] ] ) ) {
				$properties = array_merge( $properties, self::MENU_TOGGLE[ $menu['position'] ] );
			} else {
				trigger_error( sprintf( 'Chassis skipped styles.menu-button.position in chassis.json: use %s.', implode( ', ', array_keys( self::MENU_TOGGLE ) ) ), E_USER_WARNING );
			}
		}

		if ( array_key_exists( 'hide-on-scroll', $menu ) ) {
			if ( is_bool( $menu['hide-on-scroll'] ) ) {
				$properties['menu-hide-on-scroll'] = $menu['hide-on-scroll'] ? '1' : '0';
			} else {
				trigger_error( 'Chassis skipped styles.menu-button.hide-on-scroll in chassis.json: use true or false.', E_USER_WARNING );
			}
		}

		// Borders built from a width, a style and a color.
		$borders = [
			// The Menu button: `border` as plain CSS, or `color`, `width`, `style`.
			'styles.menu-button.border' => [ 'menu-toggle-border', 'var( --border-width )', 'solid', 'currentColor' ],
		];

		$palette = $this->group( $settings, 'palette' );

		foreach ( $borders as $path => list( $property, $width, $style, $color ) ) {
			$border = $this->setting( $design, $path );

			if ( is_string( $border ) ) {
				$properties[ $property ] = $border;
			} elseif ( is_array( $border ) ) {
				foreach ( [ 'width', 'style', 'color' ] as $part ) {
					if ( isset( $border[ $part ] ) && ! is_string( $border[ $part ] ) ) {
						trigger_error( sprintf( 'Chassis skipped "%s.%s" in chassis.json: use a plain value.', $path, $part ), E_USER_WARNING );
					}
				}

				$properties[ $property ] = sprintf(
					'%s %s %s',
					isset( $border['width'] ) && is_string( $border['width'] ) ? $border['width'] : $width,
					isset( $border['style'] ) && is_string( $border['style'] ) ? $border['style'] : $style,
					$this->paletteValue( isset( $border['color'] ) && is_string( $border['color'] ) ? $border['color'] : $color, $palette, $color )
				);
			} elseif ( null !== $border ) {
				trigger_error( sprintf( 'Chassis skipped "%s" in chassis.json: use a plain value, or a width, style, and color.', $path ), E_USER_WARNING );
			}
		}

		// The post box's border styles, around `--color-content-border`.
		$post = $this->group( $this->group( $styles, 'post' ), 'border' );

		foreach ( [ 'style' => 'border-box', 'bottom-style' => 'border-box-bottom' ] as $key => $property ) {
			if ( isset( $post[ $key ] ) && is_string( $post[ $key ] ) ) {
				$properties[ $property ] = "var( --border-width ) {$post[ $key ]} var( --color-content-border )";
			}
		}

		// Everything in the map.
		$handled = [ 'settings.palette', 'settings.custom', 'styles.menu-button.position', 'styles.menu-button.hide-on-scroll', 'styles.menu-button.border', 'styles.post.border.style', 'styles.post.border.bottom-style' ];

		foreach ( $this->flatten( [ 'settings' => $settings, 'styles' => $styles ] ) as $path => $value ) {
			if ( isset( self::STYLES_MAP[ $path ] ) ) {
				list( $property, $space ) = self::STYLES_MAP[ $path ];

				$properties[ $property ] = $space && is_string( $value ) ? $this->spaceValue( $value ) : $value;
				continue;
			}

			foreach ( $handled as $prefix ) {
				if ( $path === $prefix || 0 === strpos( $path, "{$prefix}." ) ) {
					continue 2;
				}
			}

			trigger_error( sprintf( 'Chassis skipped %s in chassis.json: it isn\'t a setting.', $path ), E_USER_WARNING );
		}

		return $properties;
	}

	/**
	 * Turns a dotted path and a value into nested arrays:
	 * `styles.color.accent` becomes `[ 'styles' => [ 'color' => [ 'accent' => $value ] ] ]`.
	 */
	protected function nest( string $path, $value ): array
	{
		$nested = $value;

		foreach ( array_reverse( explode( '.', $path ) ) as $key ) {
			$nested = [ $key => $nested ];
		}

		return $nested;
	}

	/**
	 * Flattens nested settings into dotted paths: `styles.post.color.background`.
	 * Empty groups (`{}`) produce nothing.
	 */
	protected function flatten( array $values, string $prefix = '' ): array
	{
		$flat = [];

		foreach ( $values as $key => $value ) {
			$path = '' === $prefix ? (string) $key : "{$prefix}.{$key}";

			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $this->flatten( $value, $path ) );
			} else {
				$flat[ $path ] = $value;
			}
		}

		return $flat;
	}

	/**
	 * Returns the setting at a dotted path, or null.
	 */
	protected function setting( array $design, string $path )
	{
		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $design ) || ! array_key_exists( $key, $design ) ) {
				return null;
			}

			$design = $design[ $key ];
		}

		return $design;
	}

	/**
	 * Turns `@slate` into `var( --palette-slate )`, for values built from parts.
	 * Other values are kept as they are. An unknown `@name` warns and falls
	 * back, so a typo doesn't print a `var()` to a property that doesn't exist.
	 */
	protected function paletteValue( $value, array $palette = [], $fallback = null )
	{
		if ( ! is_string( $value ) || 0 !== strpos( trim( $value ), '@' ) ) {
			return $value;
		}

		$name = substr( trim( $value ), 1 );

		if ( ! array_key_exists( $name, $palette ) ) {
			trigger_error( sprintf( 'Chassis skipped "@%s" in chassis.json: it isn\'t in settings.palette.', $name ), E_USER_WARNING );

			return $fallback;
		}

		return 'var( --palette-' . $name . ' )';
	}

	/**
	 * Returns one top-level group from the settings, or an empty array.
	 */
	protected function group( array $design, string $key ): array
	{
		return isset( $design[ $key ] ) && is_array( $design[ $key ] ) ? $design[ $key ] : [];
	}

	/**
	 * Returns one group from the older `chassis.json` shape, or an empty
	 * array. A group that isn't a list warns instead of failing quietly.
	 */
	protected function oldGroup( array $design, string $key ): array
	{
		if ( ! isset( $design[ $key ] ) ) {
			return [];
		}

		if ( ! is_array( $design[ $key ] ) ) {
			trigger_error( sprintf( 'Chassis skipped "%s" in chassis.json: it should be a list of settings.', $key ), E_USER_WARNING );

			return [];
		}

		return $design[ $key ];
	}

	/**
	 * Warns about a key the older `chassis.json` shape doesn't style. The
	 * value is still used: the older shape is open-ended, so a theme may set
	 * a custom property of its own — the warning only flags a likely typo.
	 */
	protected function warnUnknownOldKey( string $path ): void
	{
		trigger_error( sprintf( 'Chassis doesn\'t style "%s" in chassis.json: check the name for a typo.', $path ), E_USER_WARNING );
	}

	/**
	 * Turns spacing size names into custom properties: `xs m` becomes
	 * `var( --space-xs ) var( --space-m )`. Other values are kept as they are.
	 */
	protected function spaceValue( string $value ): string
	{
		$parts = preg_split( '/\s+/', trim( $value ) );

		foreach ( $parts as &$part ) {
			if ( in_array( $part, self::SPACE_NAMES, true ) ) {
				$part = "var( --space-{$part} )";
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Returns the script tag. Print this at the end of the page.
	 */
	public function scripts(): string
	{
		$url = $this->assetUrl( 'chassis.js' );

		return $url ? sprintf( '<script src="%s"></script>', $this->escape( $url ) ) : '';
	}

	/**
	 * Returns the path to the package's JavaScript (e.g. `js/app-nav.js`).
	 */
	public function jsPath( string $append = '' ): string
	{
		return $this->path( 'js' . ( $append ? '/' . ltrim( $append, '/' ) : '' ) );
	}

	/**
	 * Returns a badge, e.g. `badge( 'Flagship Theme', [ 'large' ] )`.
	 *
	 * @param string   $text      Label text.
	 * @param string[] $modifiers Modifiers such as `large`.
	 */
	public function badge( string $text, array $modifiers = [] ): string
	{
		return sprintf(
			'<span class="%s">%s</span>',
			$this->classes( 'badge', $modifiers ),
			$this->escape( $text )
		);
	}

	/**
	 * Returns a button-styled link, e.g. `button( 'Download', $url, [ 'primary' ] )`.
	 * Only safe URL schemes are kept (`http`, `https`, `mailto`, `tel`, and
	 * relative URLs); anything else falls back to `#`, so a `javascript:` URL
	 * never prints as a live link.
	 *
	 * @param string   $text      Link text.
	 * @param string   $url       Link URL.
	 * @param string[] $modifiers Modifiers such as `primary`.
	 */
	public function button( string $text, string $url, array $modifiers = [] ): string
	{
		return sprintf(
			'<a class="%s" href="%s">%s</a>',
			$this->classes( 'button', $modifiers ),
			$this->escape( $this->safeUrl( $url ) ),
			$this->escape( $text )
		);
	}

	/**
	 * Keeps a URL that is safe to print in an `href`: `#…`, `/…`, relative
	 * URLs, and `http`, `https`, `mailto`, and `tel`. Anything else becomes
	 * `#`, with a warning.
	 */
	protected function safeUrl( string $url ): string
	{
		$url = trim( $url );

		if ( '' === $url || '#' === $url[0] || '/' === $url[0] ) {
			return $url;
		}

		if ( ! preg_match( '/^([a-zA-Z][a-zA-Z0-9+.-]*):/', $url, $matches ) ) {
			return $url;
		}

		if ( in_array( strtolower( $matches[1] ), [ 'http', 'https', 'mailto', 'tel' ], true ) ) {
			return $url;
		}

		trigger_error( sprintf( 'Chassis skipped the URL "%s" in button(): use http, https, mailto, tel, or a relative URL.', $url ), E_USER_WARNING );

		return '#';
	}

	/**
	 * Builds a BEM class list: `button` + [ 'primary' ] → `button button--primary`.
	 *
	 * @param string   $block     Block class.
	 * @param string[] $modifiers Modifier names.
	 */
	public function classes( string $block, array $modifiers = [] ): string
	{
		$block = preg_replace( '/[^a-z0-9-]/', '', strtolower( $block ) );

		if ( '' === $block ) {
			return '';
		}

		$classes = [ $block ];

		foreach ( $modifiers as $modifier ) {
			$modifier = preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $modifier ) );

			if ( '' !== $modifier ) {
				$classes[] = "{$block}--{$modifier}";
			}
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Escapes a value for HTML text or an attribute.
	 */
	protected function escape( string $value ): string
	{
		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
