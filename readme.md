# Chassis

Shared structure and UI building blocks for themes. One package, used the same way by **ClassicPress themes** (built on [Backdrop](https://github.com/backdrop-dev/framework)) and **[Novaris](https://github.com/novaris-dev/framework) sites**:

- **SCSS:** settings, a reset, base element styles, the page skeleton (header, mobile menu, content with sidebar, footer), layout objects, components, and utilities. Your project's own build (Vite + Sass) compiles it.
- **JS:** the mobile menu toggle.
- **PHP:** small helpers that print component markup, with a service provider for each framework.

**New here?** [Build a theme from scratch](docs/build-a-theme.md) walks through a complete ClassicPress theme that uses every part of the package.

## How it's organized

The SCSS follows ITCSS: layers go from the most general to the most specific, and each layer can use everything above it.

The numbers are the load order, the same convention as [novaris-dev/novaris](https://github.com/novaris-dev/novaris/tree/develop/resources/scss). Each piece is a numbered folder with an `_index.scss` (`scss/01.settings/02.colors/_index.scss`).

| Layer | Folder | What's in it | Outputs CSS? |
|---|---|---|---|
| Settings | `scss/01.settings` | `01.breaks`, `02.colors`, `03.fonts`, `04.sizings`, `05.spacings`, `06.design` | No |
| Tools | `scss/02.tools` | `01.breakpoints`: `up()` / `down()` mixins; `02.functions`: `color()` / `space()` / `width()` | No |
| Generics | `scss/03.generics` | `01.tokens`: `:root` custom properties; `02.resets` | Yes |
| Elements | `scss/04.elements` | `01.defaults`, `02.headings`, `03.links`, `04.tables` | Yes |
| Objects | `scss/05.objects` | `01.containers`, `02.sections`, `03.grids` | Yes |
| Layouts | `scss/06.layouts` | `01.app`, `02.header`, `03.navigation`, `04.content`, `05.footer` | Yes |
| Components | `scss/07.components` | `01.cards`, `02.badges`, `03.buttons`, `04.section-headers` | Yes |
| Utilities | `scss/08.utilities` | `01.a11y`, `02.alignments`, `03.display`, `04.text` | Yes |

**What belongs in the package, and what stays in your theme:** the package handles structure (how the header lays out, when the menu collapses, how a grid drops to one column). Your theme handles the look (colors, fonts, the style of posts and comments, anything unique). Colors and spacing are settings, so each theme sets its own.

## Requirements

- PHP 7.4 or later and Composer
- Node with Vite and `sass` (or any Sass build) to compile the SCSS

## Install

```bash
composer require techkodeninja/chassis
```

Then [turn Chassis on](#turning-chassis-on) with its service provider.

## Styles

Chassis loads its own compiled CSS through the service provider, so a theme doesn't compile it. A theme's stylesheet loads after Chassis and changes it with custom properties:

```scss
// resources/scss/screen.scss
:root {
	--color-header-background: #1d3557;
	--color-accent: #e63946;
	--font-family-body: 'Merriweather', serif;
	--radius: 0.5rem;
}
```

You only list what you change, and everything else keeps its default. Breakpoints are fixed, because media queries can't read custom properties.

### Use the tools in your own partials

Sass can read the package from `vendor`. Add `loadPaths: [ 'vendor' ]` to your Sass options, run `composer install` before you build, and load just the tools. They output no CSS, so this is safe in any partial.

```scss
// resources/scss/07.components/01.entry/_index.scss
@use 'techkodeninja/chassis/scss/02.tools' as ui;

.entry {
	padding: ui.space( m );                 // var( --space-m )
	border-bottom: var( --border-rule );

	@include ui.up( medium ) {              // 768px and wider
		padding: ui.space( xl );
	}
}
```

`ui.color( name )`, `ui.space( name )`, and `ui.width( name )` return the matching `var( --… )`, and fail the build if the name doesn't exist.

## Settings reference

These are the package's Sass settings, used to build `dist/`. A theme sets the matching custom property instead.

| Setting | Defaults | Custom properties |
|---|---|---|
| `$breakpoints` | `small` 40rem (640px), `medium` 48rem (768px), `large` 64rem (1024px), `wide` 80rem (1280px) | none (media queries can't use custom properties) |
| `$colors` | See [Colors](#colors) | `--color-*` |
| `$spacing` | `xs` .25rem, `s` .5rem, `m` .75rem, `l` 1.125rem, `l-plus` 1.5rem, `xl` 2.5rem | `--space-*` |
| `$widths` | `wide` 80rem, `medium` 64rem, `narrow` 50rem, `sidebar` 18rem | `--width-*` |
| `$font-family-body`, `$font-family-heading`, `$font-family-code` | system fonts | `--font-family-*` |
| `$font-family-meta` | the body font | `--font-family-meta` (dates and small labels) |
| `$font-size-base`, `$line-height-base`, `$line-height-heading` | 1rem, 1.7, 1.25 | same names |
| `$border-width` | 0.063rem | `--border-width`, `--border-dotted`, `--border-solid`, `--border-rule` |

### Colors

Colors are grouped by where they appear. Copy this block as a starting point, and keep only the ones you change. Everything else keeps its default.

```scss
:root {
	// Global
	--color-page-background: #ffffff;  // behind everything
	--color-link:            #000000;  // links
	--color-link-hover:      #5c7079;  // links on hover
	--color-accent:          #000000;  // buttons and badges
	--color-accent-text:     #ffffff;  // text on buttons and badges

	// Header
	--color-header-background: #000000;  // the top bar
	--color-header-text:       #ffffff;  // site title, tagline, menu links

	// Content
	--color-content-background: #ffffff;  // post boxes and cards
	--color-content-border:     #000000;  // borders around those boxes
	--color-content-heading:    #000000;  // post titles, h1 to h6
	--color-content-text:       #5c7079;  // paragraphs
	--color-content-muted:      #555555;  // captions, quieter text
	--color-content-divider:    #cccccc;  // lines between things
	--color-content-alt:        #f5f5f5;  // muted boxes, code blocks

	// Widget: these follow the content colors unless you set them
	--color-widget-background: #ffffff;  // sidebar boxes
	--color-widget-border:     #000000;  // borders around them
	--color-widget-title:      #000000;  // "Categories", "Archives"
	--color-widget-text:       #5c7079;  // text inside them

	// Comments: transparent unless you set them, so there's no box
	--color-comments-background: transparent;  // behind the comments and the form
	--color-comments-border:     transparent;  // the border around them

	// Footer
	--color-footer-background: #000000;  // the bottom bar
	--color-footer-text:       #ffffff;  // footer text and links
}
```

`--color-black` and `--color-white` are available too.

### Design settings

These control the look of the shared pieces, so each theme can have its own design while the package stays the same. The defaults keep the package's standard look.

| Setting | Default | Custom property |
|---|---|---|
| `$radius` | `0` | `--radius` (cards, buttons, badges) |
| `$shadow` | `none` | `--shadow` (cards) |
| `$box-border-style`, `$box-border-bottom-style` | `dotted`, `solid` | `--border-box`, `--border-box-bottom` |
| `$font-weight-heading` | `700` | `--font-weight-heading` |
| `$heading-sizes` | `h1` 2.25rem, `h2` 1.75rem, `h3` 1.375rem, `h4` 1.125rem, `h5` 1rem, `h6` 0.875rem | `--font-size-h1` to `--font-size-h6` |
| `$header-padding` | `var( --space-m )` | `--header-padding` |
| `$header-title-size`, `$header-title-size-large` | 1.5rem, 1.75rem | `--header-title-size`, `--header-title-size-large` |
| `$header-tagline-size` | 0.9375rem | `--header-tagline-size` |
| `$nav-link-padding` | `var( --space-s ) var( --space-m )` | `--nav-link-padding` |
| `$button-border-width`, `$button-padding` | 0.125rem, `var( --space-s ) 1.25rem` | `--button-border-width`, `--button-padding` |
| `$badge-font-size`, `$badge-letter-spacing`, `$badge-padding`, `$badge-text-transform` | 0.75rem, 0.08em, 0.2rem 0.6rem, `uppercase` | `--badge-font-size`, `--badge-letter-spacing`, `--badge-padding`, `--badge-text-transform` |
| `$pagination-padding` | `var( --space-xs ) var( --space-m )` | `--pagination-padding` |
| `$content-padding` | `var( --space-xl )` | `--content-padding` (above and below the content) |
| `$sidebar-gap` | `var( --space-xl )` | `--sidebar-gap` (between the content and sidebar) |
| `$entry-padding` | `var( --space-l )` | `--entry-padding` (inside post boxes) |
| `$comments-padding`, `$comments-shadow` | `0`, `none` | `--comments-padding`, `--comments-shadow` (the comments box, ClassicPress) |
| `$comments-gap` | `var( --space-xl )` | `--comments-gap` (between the post and the comments, ClassicPress) |
| `$comments-meta-gap` | `var( --space-m )` | `--comments-meta-gap` (between a comment's author and date and its text, ClassicPress) |

```scss
:root {
	--color-page-background: #0f1117;
	--color-content-text: #d7dae0;
	--color-content-border: #2d3446;
	--radius: 0.5rem;
	--border-box: var( --border-width ) solid var( --color-content-border );
	--header-padding: var( --space-s );
}
```

Dark themes should set `--color-content-border`, because it defaults to black.

## Layouts: the page skeleton

```html
<body>
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>
<div class="app">

	<header class="app-header">
		<div class="app-header__inner container">
			<div class="app-header__branding">
				<a class="app-header__title" href="/">Site Title</a>
				<span class="app-header__tagline">Tagline</span>
			</div>
			<nav class="app-nav" aria-label="Primary">
				<button class="app-nav__toggle" type="button" aria-expanded="false" aria-controls="app-nav-menu">Menu</button>
				<ul class="app-nav__menu" id="app-nav-menu">
					<li><a href="/" aria-current="page">Home</a></li>
					<li><a href="/blog">Blog</a>
						<ul><li><a href="/blog/news">News</a></li></ul>
					</li>
				</ul>
			</nav>
		</div>
	</header>

	<div class="app-content app-content--sidebar-right container">
		<main id="main" class="app-main">…</main>
		<aside class="app-sidebar">…</aside>
	</div>

	<footer class="app-footer">
		<div class="app-footer__inner container">
			<p class="app-footer__credit">© 2026 Site Title</p>
		</div>
	</footer>

</div>
</body>
```

| Class | What it does |
|---|---|
| `.app` | Wraps the page; keeps the footer at the bottom on short pages |
| `.app-header` (`__inner`, `__branding`, `__title`, `__tagline`) · `--full` | Branding left, menu right. Tagline hides on phones. `--full` lets the row run the full width of the screen |
| `.app-nav` (`__toggle`, `__menu`) · `--bottom` | Phones: Menu button opens a panel below the header. `medium` and up: a row of links with dropdowns. `--bottom` moves the phone button to the bottom left, with the menu above it |
| `.app-content` · `--sidebar-right` · `--sidebar-left` | Content area; the sidebar sits beside the main column from `large` up and stacks below it before that |
| `.app-main`, `.app-sidebar` | The two columns |
| `.app-footer` (`__inner`, `__credit`) | Footer band |

The menu is styled by its structure (`li`, `a`, nested `ul`), so the markup from ClassicPress's `wp_nav_menu()` and Novaris's menu works once the container has `.app-nav` and the list has `.app-nav__menu`. In Novaris, pass those as `container_class` and `menu_class`. The button can be `.app-nav__toggle` or the `.menu-toggle` that Novaris prints. On phones the button floats at the bottom left of the screen, with a solid background, and the menu opens above it. Set `menu.toggle` in [`chassis.json`](#set-the-design-with-chassisjson) to move it to `bottom-right`, or into the header with `top-right` or `top-left`, where it's an outlined button and the menu opens below the header. Set how the button looks in `menu.design`. These win over the look that comes with each position:

```json
"menu": {
	"toggle": "bottom-left",
	"design": {
		"background": "#7c9cff",
		"text": "#0b0d12",
		"border": "0",
		"padding": "s l"
	}
}
```

While you scroll down on a phone, the button slides off the screen; it comes back when you scroll up, reach the top or bottom of the page, open the menu, or tab to it. `"hide-on-scroll": false` under `menu` keeps it on screen.

Themes without `chassis.json` can use the `.app-nav--top` or `.app-nav--bottom` class instead, and a class wins over `chassis.json`. The current page is underlined, marked by `aria-current="page"`, `.current-menu-item`, or `.menu-items__item--current`.

## Objects

| Class | What it does |
|---|---|
| `.container` · `--medium` · `--narrow` | Centered, max-width, side padding |
| `.section` · `--muted` | Full-width band with vertical padding; `--muted` is light gray |
| `.grid` · `--2` · `--3` · `--4` | One column on phones; 2 or 3 columns from `medium`; `--4` is 2 then 4 from `large`. Works on lists |
| `.gallery` · `--3` · `--4` | A grid of images that share the same shape. Two columns on phones |

## Components

| Class | What it does |
|---|---|
| `.card` · `--muted` | Dotted box; its last child (a link or button) sits at the bottom |
| `.badge` · `--large` | Small uppercase label in the `accent` color |
| `.button` · `--primary`, `.buttons` | Outlined / filled button; a wrapping row of buttons |
| `.section-header` (`__title`, `__description`) | Centered heading block |
| `.pagination` (`__items`, `__item`, `__anchor`) | Numbered page links. Novaris's default `pagination-items` classes work too |
| `.widget`, `.widget-title` | Sidebar boxes, as printed by Novaris and ClassicPress |
| `.code-copy` | The Copy button added to code blocks in post content |
| `.app-main > article`, `.entry__header`, `__metadata`, `__title`, `__title-anchor`, `__thumbnail`, `__content`, `__more-link`, `__terms` | Posts and pages, in a box that matches the widgets |
| `.social-menu` | A row of icon links |

```html
<section class="section section--muted">
	<div class="container">
		<header class="section-header">
			<h2 class="section-header__title">Latest Posts</h2>
		</header>
		<ul class="grid grid--3">
			<li class="card">
				<span class="badge">News</span>
				<h3>Title</h3>
				<p>Summary…</p>
				<a class="button" href="#">Read more</a>
			</li>
		</ul>
	</div>
</section>
```

## Platforms

The core works on Novaris and ClassicPress. ClassicPress prints some markup that themes can't rename, so that lives in an add-on, `dist/chassis-classicpress.css`. The Backdrop provider loads it for you.

It covers captions, sticky posts, threaded comments and the comment form (in a box when the theme sets one, see below), the search form, the calendar widget, `the_posts_pagination()`, `entry-*` classes, and the `[gallery]` shortcode. It uses the same settings as the core, so a theme's colors and shapes apply here too. Novaris sites don't need it.

## Utilities

| Class | What it does |
|---|---|
| `.screen-reader-text` | Hidden visually, still read by screen readers |
| `.skip-link` | With `.screen-reader-text`: appears on keyboard focus (any `.screen-reader-text` does) |
| `.alignleft`, `.alignright`, `.aligncenter`, `.alignwide`, `.alignfull` | The editor's alignment classes; `.alignwide` uses `--width-wide` |
| `.hide-mobile`, `.hide-desktop` | Hide below / from the `medium` breakpoint |
| `.text-left`, `.text-center`, `.text-right`, `.text-muted`, `.text-small` | Text helpers |

## JavaScript

`dist/chassis.js` runs the mobile menu and the Copy button on code blocks, and the service provider loads it. The menu toggles `.app-nav--open` and `aria-expanded`, closes on Escape (returning focus to the button), on a click outside, and when the screen widens past the mobile breakpoint. Links with a submenu get a button that opens it, which helps on touch screens.

## Turning Chassis on

Chassis doesn't load until a site registers its service provider. Nothing else turns it on. Other PHP sites use [`Chassis::standalone()`](#without-novaris-or-backdrop), and plain HTML pages can load it from a CDN.

**Novaris.** Register the provider in `config/app.php`:

```php
'providers' => [
	Techkodeninja\Chassis\Providers\Novaris::class,
],
```

Then print the files in the theme's views, with `styles()` before the theme's own stylesheet. Check for Chassis first, so the site still loads without it:

```php
<?= app( 'chassis' ) ? app( 'chassis' )->styles() : '' ?>   // in the head
<?= app( 'chassis' ) ? app( 'chassis' )->scripts() : '' ?>  // at the end of the page
```

The provider copies the built files into `public/assets/chassis`, because browsers can't reach `vendor`. If it can't write there, it logs a warning and prints nothing.

**ClassicPress.** Register `Providers\Backdrop` in the theme's `app/framework.php`. It loads the CSS, the ClassicPress add-on, the theme's `chassis.json`, and the JavaScript for you.

### Set the design with `chassis.json`

A theme sets its design in a `chassis.json` file in its root folder, next to `theme.json`. It needs no Sass and no build step: `styles()` turns it into custom properties and prints them right after the stylesheet. Keep only the settings you change; everything else keeps its default.

```json
{
	"$schema": "https://raw.githubusercontent.com/techkodeninja/chassis/develop/schemas/chassis.schema.json",
	"color": {
		"global":  { "page-background": "#0f1117", "link": "#9db4ff", "accent": "#7c9cff" },
		"header":  { "background": "#0b0d12", "text": "#ffffff" },
		"content": { "background": "#161a23", "border": "#2d3446", "text": "#d7dae0" },
		"widget":  { "background": "#161a23" },
		"footer":  { "background": "#0b0d12", "text": "#ffffff" }
	},
	"font": { "body": "'Merriweather', serif", "heading": "'Fira Sans', sans-serif" },
	"spacing": { "header-padding": "s", "nav-link-padding": "xs m", "content-padding": "l" },
	"layout":  { "wide": "75rem", "sidebar": "20rem" },
	"design": { "radius": "0.5rem" },
	"menu": { "toggle": "bottom-left" }
}
```

| In `chassis.json` | Becomes |
|---|---|
| `color.global.link` | `--color-link` |
| `color.header.text` (and `content`, `widget`, `footer`) | `--color-header-text` |
| `font.body` | `--font-family-body` |
| `spacing.content-padding` | `--content-padding`. The size names `xs`, `s`, `m`, `l`, `l-plus`, and `xl` become `var( --space-… )`, and `xs m` gives two values. Plain CSS such as `1rem` works too. |
| `layout.wide` | `--width-wide`. `width` is the older name for `layout` and still works. |
| `design.radius` | `--radius`, for any other setting in the [settings reference](#settings-reference) |
| `menu.toggle` | Where the Menu button sits on phones: `bottom-left` (the default), `bottom-right`, `top-right`, or `top-left` |
| `menu.design` | How the Menu button looks: `background`, `text`, `border`, and `padding` (size names work, e.g. `s l`) |
| `menu.hide-on-scroll` | `false` keeps the Menu button on screen while scrolling. It hides on the way down by default. |

**Comments in a box (ClassicPress).** The comments and the comment form sit on the page by default. To put them in a box like a post's, set its colors and padding:

```json
{
	"color":   { "comments": { "background": "#161a23", "border": "#2d3446" } },
	"spacing": { "comments-padding": "l" },
	"design":  { "comments-shadow": "none" }
}
```

The box uses the post boxes' border style and `design.radius`. `design.comments-shadow` is optional.

#### `settings` and `styles`

Instead of `color`, `spacing`, `layout`, `design` and `menu`, `chassis.json` can use two groups with friendlier names:

- **`settings`** defines what the design is made from: the `palette`, `font`s, the `spacing` scale, `layout` widths and `shape`s.
- **`styles`** applies it: the whole site first, then `elements` (links, headings, buttons) that appear anywhere, then each part of the page.

```json
{
	"$schema": "https://raw.githubusercontent.com/techkodeninja/chassis/develop/schemas/chassis.schema.json",
	"settings": {
		"palette": { "night": "#0b0d12", "ink": "#0f1117", "slate": "#161a23", "line": "#2d3446", "snow": "#ffffff", "mist": "#d7dae0", "blue": "#7c9cff", "sky": "#9db4ff", "ice": "#c7d4ff" },
		"font":    { "body": "'Merriweather', serif", "heading": "'Fira Sans', sans-serif" },
		"layout":  { "width": "75rem", "sidebar": "20rem" }
	},
	"styles": {
		"color": { "background": "@ink", "text": "@mist", "accent": "@blue", "accent-text": "@night" },
		"elements": {
			"link":    { "color": { "text": "@sky" }, ":hover": { "color": { "text": "@ice" } } },
			"heading": { "color": { "text": "@snow" } }
		},
		"header":       { "color": { "background": "@night", "text": "@snow" }, "spacing": { "padding": "s" } },
		"primary-menu": { "link": { "spacing": { "padding": "xs m" } } },
		"menu-button":  { "position": "bottom-left", "border": { "color": "@snow" } },
		"content":      { "spacing": { "padding": "l", "gap": "l" } },
		"post":         { "color": { "background": "@slate" }, "border": { "color": "@line" } },
		"comments":     { "color": { "background": "@slate" }, "border": { "color": "@line" }, "spacing": { "padding": "l", "margin": "l" } },
		"footer":       { "color": { "background": "@night", "text": "@snow" } }
	}
}
```

How it reads:

- **Palette colors** are written once and used as `@name`. `slate` becomes `--palette-slate`, and `@slate` becomes `var( --palette-slate )`, so a child theme can change one palette color and everything using it follows. An unknown `@name` is skipped with a warning.
- **Every part uses the same keys:** `color`, `border`, `spacing`, `typography`. A part's own pieces nest inside it, like `header.title` or `comments.meta`, and states like `:hover` nest the same way.
- **Each part has a friendly name**, and its schema description names the BEM block it styles:

| Part | BEM block | What it is |
|---|---|---|
| `header` | `.app-header` | The top bar; `title` and `tagline` inside it |
| `primary-menu` | `.app-nav` | The main menu in the header; `link` inside it |
| `menu-button` | `.app-nav__toggle` | The button that opens the primary menu on phones |
| `content` | `.app-content` | The content area: posts and sidebar |
| `post` | `.entry` | Each post's box |
| `widget` | `.widget` | Sidebar and footer widgets; `title` inside it. Follows `post` unless set |
| `comments` | `.comments-area` | The comments and the form (ClassicPress); `meta` inside it |
| `pagination` | `.pagination` | Page numbers |
| `badge` | `.badge` | Badges |
| `footer` | `.app-footer` | The bottom bar |

The schema lists every setting, with the custom property it sets, so an editor autocompletes them and flags typos. Chassis also skips an unknown setting with a warning. `settings.custom` sets any other custom property by its own name, like `design` in the older shape.

Both shapes work, in the same file or across a parent and child theme. Where both set the same thing, `settings` and `styles` win. They cover everything the older shape can set, and new settings will be added to them only.

The `$schema` line gives editors autocomplete and catches typos. A child theme's `chassis.json` overrides its parent's, setting by setting. Values that aren't plain CSS (with `{`, `}`, `;`, `<`, or `>`) are skipped with a warning.

A theme can still set custom properties in its own stylesheet instead, which loads after Chassis:

```scss
:root {
	--color-page-background: #0f1117;
	--color-accent: #7c9cff;
}
```

Colors, spacing, fonts, and widths all work this way. Breakpoints are fixed at the defaults, because media queries can't read custom properties. A theme can still use the Sass helpers, which print no CSS of their own:

```scss
@use 'techkodeninja/chassis/scss/02.tools' as ui;
```

## PHP

### Novaris

The helpers are for sites, not distributed themes. An exported theme doesn't include `vendor`, and a site's `providers` setting replaces the theme's, so a theme should write the class names directly. To use the helpers on a site, require the package in that site's `composer.json`, then register the provider in `config/app.php`:

```php
'providers' => [
	Techkodeninja\Chassis\Providers\Novaris::class,
],
```

```php
<?= app( 'chassis' )->button( 'Download', $url, [ 'primary' ] ); ?>
```

The provider also adds template tags, so Novaris views can use `$engine->button( 'Download', $url, [ 'primary' ] )` and `$engine->badge( 'Featured' )`.

### ClassicPress (Backdrop)

Register the provider in your theme's `app/framework.php`:

```php
$theme->provider( Techkodeninja\Chassis\Providers\Backdrop::class );
```

```php
<?= Backdrop\App::resolve( 'chassis' )->badge( 'Featured' ); ?>
```

Each project only loads its own provider, so a ClassicPress theme never needs Novaris and a Novaris site never needs Backdrop.

### Which platform is running

The provider a site registers decides the platform, so nothing has to guess. `platform()` returns `novaris`, `backdrop`, or `plain` when no provider is in use:

```php
if ( 'novaris' === app( 'chassis' )->platform() ) {
	// Novaris version
}
```

### Without Novaris or Backdrop

Chassis doesn't need either framework. Any PHP site can use it with one line, and a plain HTML page can load it from a CDN.

**Any PHP site.** Install it with `composer require techkodeninja/chassis`, then:

```php
require __DIR__ . '/vendor/autoload.php';

$chassis = Techkodeninja\Chassis\Chassis::standalone(
	__DIR__ . '/public/chassis',  // a folder the browser can reach
	'/chassis',                   // its URL
	__DIR__ . '/chassis.json'     // your design (optional)
);
```

```php
<?= $chassis->styles() ?>   // in the head: chassis.css, then your chassis.json
<?= $chassis->scripts() ?>  // at the end of the page
```

That's the same setup the providers do. Chassis copies its CSS and JavaScript into the folder the first time they're needed, with a hash in each name so browsers pick up new versions. It removes old copies, and logs a warning if the folder isn't writable. Pass several `chassis.json` files, parent first, and later ones override earlier ones. The helpers work too: `$chassis->button( 'Download', $url, [ 'primary' ] )`.

**Plain HTML, no PHP.** Load the files from jsDelivr. Use the version you want in the URL:

```html
<head>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/techkodeninja/chassis@0.1.8/dist/chassis.css">
	<style>
		:root {
			--color-accent: #e63946;
			--color-header-background: #1d3557;
		}
	</style>
</head>
<body>
	…
	<script src="https://cdn.jsdelivr.net/gh/techkodeninja/chassis@0.1.8/dist/chassis.js"></script>
</body>
```

To host the files yourself instead, download a release from [GitHub](https://github.com/techkodeninja/chassis/releases) and copy `dist/chassis.css` and `dist/chassis.js` into your site. Reading `chassis.json` needs PHP, so write the design as custom properties after the stylesheet. Every `chassis.json` setting has one (see [how they map](#set-the-design-with-chassisjson)). The menu script, hide on scroll, and the code Copy button all work.

**Either way**, the HTML uses the same class names as on Novaris and ClassicPress. The [build-a-theme guide](docs/build-a-theme.md#the-html-it-prints) shows a full page.

**What you don't get without a provider:** loading through `wp_enqueue_*` (ClassicPress), finding the theme's `chassis.json` for you, and the Novaris template tags (`$engine->button()`).

### Helpers

| Method | Returns |
|---|---|
| `badge( $text, $modifiers = [] )` | `<span class="badge …">` |
| `button( $text, $url, $modifiers = [] )` | `<a class="button …">` |
| `classes( $block, $modifiers = [] )` | `'button button--primary'` |
| `scssPath( $append = '' )`, `jsPath( $append = '' )` | Paths to the package's SCSS and JS |

Text and URLs are escaped for you.

## License

GPL-2.0-or-later. See [license.md](license.md).
