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
	const VERSION = '0.1.8';

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
	 * Spacing names that `chassis.json` can use instead of CSS values.
	 */
	const SPACE_NAMES = [ 'xs', 's', 'm', 'l', 'l-plus', 'xl' ];

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
	 * reached.
	 */
	public function assetUrl( string $file ): string
	{
		if ( ! $this->asset || ! is_file( $this->distPath( $file ) ) ) {
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

		return $this;
	}

	/**
	 * Returns the merged settings from the `chassis.json` files.
	 */
	public function design(): array
	{
		$design = [];

		foreach ( array_unique( $this->designFiles ) as $file ) {
			if ( '' === $file || ! is_file( $file ) ) {
				continue;
			}

			$data = json_decode( (string) file_get_contents( $file ), true );

			if ( ! is_array( $data ) ) {
				trigger_error( sprintf( 'Chassis could not read %s: %s', $file, json_last_error_msg() ), E_USER_WARNING );
				continue;
			}

			$design = array_replace_recursive( $design, $data );
		}

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
	 * Returns an empty string when there's nothing to set.
	 */
	public function designCss( ?array $design = null ): string
	{
		$design     = $design ?? $this->design();
		$properties = [];

		foreach ( $this->group( $design, 'color' ) as $group => $colors ) {
			if ( ! is_array( $colors ) ) {
				continue;
			}

			$prefix = 'global' === $group ? 'color-' : "color-{$group}-";

			foreach ( $colors as $name => $value ) {
				$properties[ $prefix . $name ] = $value;
			}
		}

		foreach ( $this->group( $design, 'font' ) as $name => $value ) {
			$properties[ "font-family-{$name}" ] = $value;
		}

		foreach ( $this->group( $design, 'spacing' ) as $name => $value ) {
			$properties[ $name ] = is_string( $value ) ? $this->spaceValue( $value ) : $value;
		}

		// `width` is the older name for `layout`. Both work, `layout` last so it wins.
		foreach ( array_replace( $this->group( $design, 'width' ), $this->group( $design, 'layout' ) ) as $name => $value ) {
			$properties[ "width-{$name}" ] = $value;
		}

		$menu   = $this->group( $design, 'menu' );
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
		foreach ( $this->group( $menu, 'design' ) as $name => $value ) {
			$name = 'text' === $name ? 'color' : $name;

			$properties[ "menu-toggle-{$name}" ] = 'padding' === $name && is_string( $value ) ? $this->spaceValue( $value ) : $value;
		}

		// Last, so a theme's own settings win over the `menu.toggle` presets.
		foreach ( $this->group( $design, 'design' ) as $name => $value ) {
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

		foreach ( $borders as $path => list( $property, $width, $style, $color ) ) {
			$border = $this->setting( $design, $path );

			if ( is_string( $border ) ) {
				$properties[ $property ] = $border;
			} elseif ( is_array( $border ) ) {
				$properties[ $property ] = sprintf(
					'%s %s %s',
					$border['width'] ?? $width,
					$border['style'] ?? $style,
					$this->paletteValue( $border['color'] ?? $color )
				);
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
	 * Other values are kept as they are.
	 */
	protected function paletteValue( $value )
	{
		return is_string( $value ) && 0 === strpos( trim( $value ), '@' ) ? 'var( --palette-' . substr( trim( $value ), 1 ) . ' )' : $value;
	}

	/**
	 * Returns one top-level group from the settings, or an empty array.
	 */
	protected function group( array $design, string $key ): array
	{
		return isset( $design[ $key ] ) && is_array( $design[ $key ] ) ? $design[ $key ] : [];
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
			$this->escape( $url ),
			$this->escape( $text )
		);
	}

	/**
	 * Builds a BEM class list: `button` + [ 'primary' ] → `button button--primary`.
	 *
	 * @param string   $block     Block class.
	 * @param string[] $modifiers Modifier names.
	 */
	public function classes( string $block, array $modifiers = [] ): string
	{
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
