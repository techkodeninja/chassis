# Build a theme from scratch

This guide builds **Starter**, a small ClassicPress theme, using every part of Chassis. By the end you'll know which parts come from the package, which parts belong to your theme, and how they connect.

It's built on [Backdrop](https://github.com/backdrop-dev/framework), because Chassis loads through a service provider.

The short version: Chassis gives you the layout and components, `chassis.json` sets how they look, and your templates use the class names. You don't need Sass or a build step unless you want styles of your own.

## What you'll build

```
starter/
├── composer.json          gets techkodeninja/chassis
├── chassis.json           the design: colors, fonts, spacing, the menu
├── style.css              theme header (ClassicPress needs it)
├── functions.php          registers Chassis, the menu, and the sidebar
├── header.php             .app, .app-header, .app-nav
├── index.php              .app-content, the post loop
├── sidebar.php            .app-sidebar
└── footer.php             .app-footer
```

[Step 9](#9-optional-add-your-own-styles-with-sass) adds `package.json`, `vite.config.js`, and `resources/scss/` if you want your own Sass on top.

## 1. Create the theme folder and `style.css`

In `wp-content/themes/`, make a folder called `starter` and add `style.css`. ClassicPress reads the theme's name from this comment. Nothing else goes in this file.

```css
/*
 * Theme Name:  Starter
 * Description: Learning theme built on Chassis.
 * Version:     0.1.0
 * Author:      Your Name
 * Requires PHP: 7.4
 * License:     GNU General Public License v2 or later
 * Text Domain: starter
 */
```

## 2. Get the package with Composer

`composer.json`:

```json
{
	"name": "you/starter",
	"type": "wordpress-theme",
	"require": {
		"php": ">=7.4",
		"backdrop-dev/framework": "dev-develop",
		"techkodeninja/chassis": "^0.1"
	}
}
```

```bash
composer install
```

You now have `vendor/techkodeninja/chassis/` with `dist/`, `scss/`, `js/`, `schemas/`, and `src/` inside. `dist/` holds the CSS and JavaScript that Chassis loads. You don't touch those files.

## 3. Set the design: `chassis.json`

Next to `style.css`, add `chassis.json`. Chassis reads it and prints the values right after its own stylesheet, so they replace the defaults. List only what you change; everything else keeps its default.

```json
{
	"$schema": "https://raw.githubusercontent.com/techkodeninja/chassis/develop/schemas/chassis.schema.json",
	"color": {
		"global": {
			"accent": "#e63946",
			"link": "#1d3557",
			"link-hover": "#e63946"
		},
		"header": {
			"background": "#1d3557"
		},
		"content": {
			"heading": "#1d3557"
		},
		"footer": {
			"background": "#1d3557"
		}
	},
	"font": {
		"heading": "Georgia, serif"
	},
	"spacing": {
		"content-padding": "l",
		"sidebar-gap": "l"
	},
	"layout": {
		"wide": "70rem"
	},
	"menu": {
		"toggle": "bottom-left",
		"design": {
			"background": "#e63946",
			"padding": "s l"
		}
	}
}
```

What each part does:

| Part | Sets | Example |
|---|---|---|
| `color` | Colors, grouped by where they appear: `global`, `header`, `content`, `widget`, `footer` | `header.background` becomes `--color-header-background` |
| `font` | Font stacks for `body`, `heading`, `code`, and `meta` | `heading` becomes `--font-family-heading` |
| `spacing` | Padding and gaps. Size names `xs` 4px, `s` 8px, `m` 12px, `l` 18px, `l-plus` 24px, `xl` 40px, or plain CSS like `10px` | `"s l"` is 8px top and bottom, 18px left and right |
| `layout` | The page's maximum width (`wide`) and the sidebar's width (`sidebar`). `width` is the older name and still works | `wide` becomes `--width-wide` |
| `menu.toggle` | Where the Menu button sits on phones: `bottom-left` (the default), `bottom-right`, `top-right`, `top-left` | |
| `menu.design` | How the button looks: `background`, `text`, `border`, `padding` | `"border": "2px solid #e63946"` |
| `menu.hide-on-scroll` | `false` keeps the button on screen while scrolling | |
| `design` | Any other setting, such as `radius` or `shadow` | `"radius": "0.5rem"` |

There's also a shape with friendlier names, `settings` and `styles`, which groups settings by part of the page (`header`, `post`, `comments`…) and names colors once in a palette. Both work; see [`settings` and `styles`](../readme.md#settings-and-styles).

The `$schema` line gives your editor autocomplete and catches typos. If VS Code says the schema location is untrusted, use its Quick Fix to trust `raw.githubusercontent.com`. Every setting is listed in the [readme](../readme.md#set-the-design-with-chassisjson).

## 4. Turn on Chassis and register the menu: `functions.php`

```php
<?php
/**
 * Starter theme setup.
 */

require_once __DIR__ . '/vendor/autoload.php';

// This is what turns Chassis on. It loads the CSS, chassis.json, and the
// menu script.
$theme = new Backdrop\Core\Application();
$theme->provider( Techkodeninja\Chassis\Providers\Backdrop::class );
$theme->boot();

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );

	register_nav_menus( [
		'primary' => __( 'Primary', 'starter' ),
	] );
} );

add_action( 'widgets_init', function () {
	register_sidebar( [
		'id'            => 'primary',
		'name'          => __( 'Primary Sidebar', 'starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	] );
} );
```

Each widget gets the package's `widget` class, so the sidebar is styled with no extra CSS.

Most Backdrop themes register providers in `app/framework.php` instead of building their own `Application` — see the [readme](../readme.md#classicpress-backdrop). Starter builds its own here so the whole setup fits in one file.

## 5. Check that it loads

Open the site and view the page source. In the head you should see:

```html
<link rel='stylesheet' id='chassis-css' href='…/vendor/techkodeninja/chassis/dist/chassis.css' … />
<style id='chassis-inline-css'>:root{--color-accent:#e63946;--color-link:#1d3557;…}</style>
```

The `<style>` block is your `chassis.json`. If it's missing, check that the file is valid JSON. Chassis logs a warning for anything it skips.

## 6. The templates

### `header.php`: `.app`, `.app-header`, `.app-nav`

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'starter' ); ?></a>

<div class="app">

	<header class="app-header">
		<div class="app-header__inner container">

			<div class="app-header__branding">
				<a class="app-header__title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
				<span class="app-header__tagline"><?php bloginfo( 'description' ); ?></span>
			</div>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<nav class="app-nav" aria-label="<?php esc_attr_e( 'Primary', 'starter' ); ?>">
					<button class="app-nav__toggle" type="button" aria-expanded="false" aria-controls="app-nav-menu">
						<?php esc_html_e( 'Menu', 'starter' ); ?>
					</button>
					<?php wp_nav_menu( [
						'theme_location' => 'primary',
						'container'      => false,
						'menu_id'        => 'app-nav-menu',
						'menu_class'     => 'app-nav__menu',
						'depth'          => 3,
					] ); ?>
				</nav>
			<?php endif; ?>

		</div>
	</header>
```

`wp_nav_menu()` prints `<ul id="app-nav-menu" class="app-nav__menu">` with plain `<li>` and nested `<ul class="sub-menu">`. The package styles the menu by that structure, so nothing else is needed. The current page gets `.current-menu-item`, which the package underlines.

On phones, the **Menu** button floats at the bottom left and hides while you scroll down. Both come from `menu` in `chassis.json`, not from the markup.

### `index.php`: `.app-content`, cards in a grid

```php
<?php get_header(); ?>

	<div class="app-content app-content--sidebar-right container">

		<main id="main" class="app-main">

			<?php if ( have_posts() ) : ?>

				<div class="grid grid--2">
					<?php while ( have_posts() ) : the_post(); ?>

						<article id="post-<?php the_ID(); ?>" <?php post_class( 'card entry' ); ?>>
							<h2 class="entry__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="entry__metadata"><?php echo esc_html( get_the_date() ); ?></div>
							<?php the_excerpt(); ?>
							<a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'starter' ); ?></a>
						</article>

					<?php endwhile; ?>
				</div>

				<?php the_posts_pagination(); ?>

			<?php else : ?>
				<p><?php esc_html_e( 'Nothing here yet.', 'starter' ); ?></p>
			<?php endif; ?>

		</main>

		<?php get_sidebar(); ?>

	</div>

<?php get_footer(); ?>
```

Each class on an article comes from a different place:
- `card` comes from the package: the dotted box, with the button pinned to the bottom.
- `entry` gets the package's post box, the same box `.app-main > article` gets; the theme's own look for a post lives in [step 9](#9-optional-add-your-own-styles-with-sass).
- `grid grid--2` comes from the package: one column on phones, two from 768px.

### `sidebar.php`: `.app-sidebar`

```php
<?php if ( is_active_sidebar( 'primary' ) ) : ?>
	<aside class="app-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'starter' ); ?>">
		<?php dynamic_sidebar( 'primary' ); ?>
	</aside>
<?php endif; ?>
```

### `footer.php`: `.app-footer`

```php
	<footer class="app-footer">
		<div class="app-footer__inner container">
			<p class="app-footer__credit">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
			</p>
		</div>
	</footer>

</div><!-- .app -->

<?php wp_footer(); ?>
</body>
</html>
```

## 7. Activate and try it

1. Activate **Starter** in *Appearance → Themes*.
2. Create a menu in *Appearance → Menus*, add a few pages and a submenu, and assign it to **Primary**.
3. Add a couple of widgets to **Primary Sidebar**.

Then make the browser narrow:

| Width | What changes | Where it comes from |
|---|---|---|
| under 640px | Tagline hides | `06.layouts/02.header`, `up( small )` |
| under 768px | Menu collapses behind the **Menu** button; posts go to one column | `06.layouts/03.navigation` and `05.objects/03.grids`, `medium` |
| under 1024px | Sidebar moves below the posts | `06.layouts/04.content`, `up( large )` |

### The HTML it prints

View the page source and you'll see this, trimmed. Every class follows the same BEM pattern:

- **Block:** a standalone piece, like `app-header`, `app-nav`, `entry`, or `widget`.
- **Element:** a part of a block, written `block__element`, like `app-header__title` or `entry__title`.
- **Modifier:** a variation, written `block--modifier`, like `app-content--sidebar-right` or `button--primary`.

```html
<head>
	<link rel="stylesheet" id="chassis-css" href="…/chassis/dist/chassis.css">
	<style id="chassis-inline-css">:root{--color-accent:#e63946;--color-link:#1d3557;--color-header-background:#1d3557;…}</style>
</head>
<body class="home blog">
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>

<div class="app">

	<header class="app-header">
		<div class="app-header__inner container">
			<div class="app-header__branding">
				<a class="app-header__title" href="/">Starter</a>
				<span class="app-header__tagline">Learning theme built on Chassis</span>
			</div>
			<nav class="app-nav app-nav--ready" aria-label="Primary">
				<button class="app-nav__toggle" type="button" aria-expanded="false" aria-controls="app-nav-menu">Menu</button>
				<ul id="app-nav-menu" class="app-nav__menu">
					<li class="current-menu-item"><a href="/" aria-current="page">Home</a></li>
					<li><a href="/blog">Blog</a><button class="app-nav__submenu-toggle" aria-expanded="false" aria-haspopup="true" aria-controls="app-nav-submenu-1" aria-label="Submenu for Blog">▾</button>
						<ul id="app-nav-submenu-1" class="sub-menu"><li><a href="/blog/news">News</a></li></ul>
					</li>
				</ul>
			</nav>
		</div>
	</header>

	<div class="app-content app-content--sidebar-right container">
		<main id="main" class="app-main">
			<div class="grid grid--2">
				<article id="post-1" class="card entry post-1 post">
					<h2 class="entry__title"><a href="/hello-world">Hello world</a></h2>
					<div class="entry__metadata">November 6, 2026</div>
					<p>Welcome to your new site…</p>
					<a class="button" href="/hello-world">Read more</a>
				</article>
			</div>
		</main>

		<aside class="app-sidebar" aria-label="Sidebar">
			<section id="categories-2" class="widget widget_categories">
				<h2 class="widget-title">Categories</h2>
				<ul><li><a href="/category/news">News</a></li></ul>
			</section>
		</aside>
	</div>

	<footer class="app-footer">
		<div class="app-footer__inner container">
			<p class="app-footer__credit">&copy; 2026 Starter</p>
		</div>
	</footer>

</div>
<script src="…/chassis/dist/chassis.js"></script>
</body>
```

Some of these come from elsewhere:
- `app-nav--ready` and `app-nav__submenu-toggle` are added by `chassis.js`. While the menu is open the nav also gets `app-nav--open`, and it gets `app-nav--hidden` while the button is scrolled away.
- `widget_categories`, `current-menu-item`, `sub-menu`, and `post-1` come from ClassicPress. Chassis styles menus by their structure and `aria-current`, so it doesn't need them.
- `container`, `grid`, `card`, and `button` are Chassis objects and components, so they have no `app-` prefix. The `app-` blocks are the page skeleton.

A Novaris site prints the same skeleton. [Nocturne](https://github.com/novaris-dev/nocturne)'s header and nav, for example:

```html
<header id="masthead" class="app-header app-header--full">
	<div class="app-header__inner container">
		<div class="app-header__branding">
			<p class="app-header__title"><a href="https://nocturne.codestacks.cc" rel="home">Nocturne</a></p>
			<span class="app-header__tagline">A Novaris Theme</span>
		</div>
		<nav class="app-nav">
			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">Menu</button>
			<ul id="primary-menu" class="app-nav__menu">
				<li class="menu-items__item"><a href="/blog" class="menu-items__item-anchor">Blog</a></li>
				<li class="menu-items__item"><a href="/about" class="menu-items__item-anchor">About</a></li>
			</ul>
		</nav>
	</div>
</header>
```

Novaris prints `menu-toggle` instead of `app-nav__toggle`, and its own `menu-items__*` classes. Chassis styles both the same way.

## 8. Experiments

Each one is a one-line change. For `chassis.json`, save and refresh; there's nothing to build.

- **Recolor.** Change `header.background` in `chassis.json`. The header changes on the next refresh.
- **Move the Menu button.** Set `menu.toggle` to `top-right`. On phones the button moves into the header, and the menu opens below it.
- **Keep the button on screen.** Add `"hide-on-scroll": false` under `menu`.
- **Rounder corners.** Add `"design": { "radius": "0.5rem" }`. Cards, buttons, and badges all change.
- **Sidebar on the left.** In `index.php`, change `app-content--sidebar-right` to `app-content--sidebar-left`.
- **No sidebar.** Remove the `--sidebar-right` class and `get_sidebar()`.
- **Break it on purpose.** Set `menu.toggle` to `middle`. Chassis skips it, logs a warning that lists the real values, and the button stays at the default.

## 9. Optional: add your own styles with Sass

`chassis.json` covers the settings Chassis has. For anything else, like how a post title looks, add your own stylesheet. It loads after Chassis.

`package.json`:

```json
{
	"private": true,
	"type": "module",
	"scripts": {
		"build": "vite build",
		"watch": "vite build --watch"
	},
	"devDependencies": {
		"sass": "^1.95.0",
		"vite": "^7.2.7"
	}
}
```

`vite.config.js`:

```js
import { defineConfig } from 'vite';

export default defineConfig( {
	// We write into public/, so turn off Vite's own public folder.
	publicDir: false,

	css: {
		preprocessorOptions: {
			// Lets SCSS find 'techkodeninja/chassis/scss' inside vendor/.
			scss: { loadPaths: [ 'vendor' ] },
		},
	},

	build: {
		outDir: 'public',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				screen: 'resources/scss/screen.scss',
			},
			output: {
				// Fixed names (no hashes) keep the PHP simple.
				assetFileNames: 'css/[name][extname]',
			},
		},
	},
} );
```

```bash
npm install
```

`resources/scss/screen.scss` holds only your own styles. Your design stays in `chassis.json`.

```scss
////
/// Starter: the theme's own styles. The design is in chassis.json.
////

@forward '07.components';
```

Number your folders the same way the package does (`07.components` is the components layer). `resources/scss/07.components/_index.scss`:

```scss
@forward '01.entry';
```

`resources/scss/07.components/01.entry/_index.scss`:

```scss
////
/// Entry: one post in the loop. This is the theme's own look.
////

// Load only the tools: mixins and functions, no CSS output.
@use 'techkodeninja/chassis/scss/02.tools' as ui;

.entry {
	&__title {
		font-size: 1.5rem;

		a {
			text-decoration: none;
		}

		// Bigger titles from tablets up.
		@include ui.up( medium ) {
			font-size: 2rem;
		}
	}
}
```

The date under the title uses `.entry__metadata`, which the package already styles, so the theme's own styles only cover the title.

Notice the split: the **package** gives you `.card`, the grid, and the breakpoints. `chassis.json` gives them your colors. The **theme** decides what a post title looks like. The tools print no CSS of their own; `ui.color()` and `ui.space()` turn into `var( --… )`, so they pick up your `chassis.json` values.

Load the stylesheet in `functions.php`, after Chassis:

```php
add_action( 'wp_enqueue_scripts', function () {
	$css = 'public/css/screen.css';

	// filemtime() changes the version on every build, so browsers re-download.
	wp_enqueue_style( 'starter', get_theme_file_uri( $css ), [ 'chassis' ], filemtime( get_theme_file_path( $css ) ) );
} );
```

Then build:

```bash
npm run build
```

If the build says it can't find `techkodeninja/chassis/scss`, run `composer install` first. If it says `Unknown breakpoint` or `Unknown color`, there's a typo in a name, and the error lists the valid ones.

## 10. Optional: use the PHP helpers

```php
<?php $ui = Backdrop\App::resolve( 'chassis' ); ?>

<?= $ui->badge( 'Featured' ); ?>
<?= $ui->button( 'Read more', get_permalink(), [ 'primary' ] ); ?>
```

They print the same markup you'd write by hand (`<span class="badge">…`), escaped.

## What to ship

The ClassicPress site needs:

```
style.css  chassis.json  functions.php  header.php  index.php  sidebar.php  footer.php  vendor/
```

Add `public/` if you did step 9. `vendor/` has to stay, because that's where the provider and Chassis's CSS live. Leave out `node_modules/`, `resources/`, and the build files.

## The same package on a Novaris site

Only the PHP side differs:

- Register `Techkodeninja\Chassis\Providers\Novaris::class` in `config/app.php`.
- Print `styles()` in the head and `scripts()` at the end of the page. `styles()` includes your `chassis.json`.
- Put `chassis.json` in the theme's root, next to `theme.json`. A child theme's `chassis.json` overrides its parent's, setting by setting.
- Use the same class names in your views.

[Nocturne](https://github.com/novaris-dev/nocturne) is a working example: its whole design is one `chassis.json`, with no Sass.

## Without Novaris or Backdrop

Any PHP site can turn Chassis on with one line:

```php
$chassis = Techkodeninja\Chassis\Chassis::standalone( __DIR__ . '/public/chassis', '/chassis', __DIR__ . '/chassis.json' );
```

Then print `$chassis->styles()` and `$chassis->scripts()`, and use the same templates. A plain HTML page can load the files from a CDN instead. See [Without Novaris or Backdrop](../readme.md#without-novaris-or-backdrop) in the readme.
