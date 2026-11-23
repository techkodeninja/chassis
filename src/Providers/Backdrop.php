<?php
/**
 * Backdrop service provider, for ClassicPress themes.
 *
 * Register in the theme's `app/framework.php`:
 *
 *     $theme->provider( Techkodeninja\Chassis\Providers\Backdrop::class );
 *
 * This is what turns Chassis on. It loads the CSS and JavaScript for you, adds
 * the theme's `chassis.json` settings after the CSS, and
 * `Backdrop\App::resolve( 'chassis' )` gives you the helpers.
 *
 * Requires backdrop-dev/framework, which the theme already has.
 *
 * @package Techkodeninja\Chassis
 */

namespace Techkodeninja\Chassis\Providers;

use Backdrop\Core\ServiceProvider;
use Techkodeninja\Chassis\Chassis;

class Backdrop extends ServiceProvider
{
	/**
	 * Binds the UI helpers to the container, and records the platform.
	 */
	public function register(): void
	{
		$this->app->singleton( Chassis::class, function() {
			$chassis = new Chassis( 'backdrop', [ $this, 'url' ] );

			return $chassis->designFiles( array_unique( [
				get_template_directory() . '/chassis.json',
				get_stylesheet_directory() . '/chassis.json',
			] ) );
		} );
		$this->app->alias( Chassis::class, 'chassis' );
	}

	/**
	 * Loads the CSS and JavaScript on the front end.
	 */
	public function boot(): void
	{
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ], 5 );
	}

	/**
	 * Adds the stylesheets, the `chassis.json` settings, and the script. They
	 * load early, so the theme's own stylesheet comes after them. The
	 * ClassicPress add-on only loads when the core stylesheet did: without it
	 * the add-on would enqueue with an unregistered dependency.
	 */
	public function enqueue(): void
	{
		$chassis = $this->app->resolve( Chassis::class );

		$core = false;

		if ( $url = $chassis->assetUrl( 'chassis.css' ) ) {
			wp_enqueue_style( 'chassis', $url, [], $this->version( $chassis, 'chassis.css' ) );
			$core = true;
		}

		if ( $core && ( $url = $chassis->assetUrl( 'chassis-classicpress.css' ) ) ) {
			wp_enqueue_style( 'chassis-classicpress', $url, [ 'chassis' ], $this->version( $chassis, 'chassis-classicpress.css' ) );
		}

		// Values from code, such as the customizer, win over `chassis.json`: a theme or a
		// package returns `[ 'styles.color.accent' => '#cc3300' ]` from `chassis/overrides`.
		$chassis->overrides( (array) apply_filters( 'chassis/overrides', [] ) );

		if ( wp_style_is( 'chassis', 'enqueued' ) && ( $design = $chassis->designCss() ) ) {
			wp_add_inline_style( 'chassis', $design );
		}

		$url = $chassis->assetUrl( 'chassis.js' );

		if ( $url ) {
			wp_enqueue_script( 'chassis', $url, [], $this->version( $chassis, 'chassis.js' ), true );
		}
	}

	/**
	 * Returns a version for a file's URL (`?ver=…`) that changes whenever the
	 * file does, so browsers never keep an old copy after an update. It's a
	 * short hash of the file, like the names the Novaris provider uses.
	 */
	protected function version( Chassis $chassis, string $file ): string
	{
		$path = $chassis->distPath( $file );

		return is_file( $path ) ? substr( md5_file( $path ), 0, 8 ) : Chassis::VERSION;
	}

	/**
	 * Returns the URL of a file in `dist/`. The package sits inside the theme's
	 * `vendor` folder, so its URL comes from the theme's URL. Returns an empty
	 * string — with a warning — when the file can't be mapped to a theme
	 * folder, so a site doesn't load with no Chassis CSS and no diagnostic.
	 */
	public function url( string $file ): string
	{
		$path = wp_normalize_path( $this->app->resolve( Chassis::class )->distPath( $file ) );

		foreach ( [ get_stylesheet_directory() => get_stylesheet_directory_uri(), get_template_directory() => get_template_directory_uri() ] as $dir => $uri ) {
			$dir = trailingslashit( wp_normalize_path( $dir ) );

			if ( 0 === strpos( $path, $dir ) ) {
				return trailingslashit( $uri ) . substr( $path, strlen( $dir ) );
			}
		}

		trigger_error( sprintf( 'Chassis could not map %s to a theme URL: the package may be installed outside the theme.', $file ), E_USER_WARNING );

		return '';
	}
}
