# Changelog

All notable changes to Chassis will be documented in this file.

## [0.1.8] - 11.08.2026

### Fixed

- `Chassis::VERSION`, `package.json` and the jsDelivr links in the readme now give the current version. The 0.1.7 tag still said 0.1.6. The only code that reads `Chassis::VERSION` is the stylesheet version used when Chassis can't read a file's hash, so no site behaved differently.

## [0.1.7] - 11.06.2026

### Changed

- The `settings` and `styles` shape no longer has a version number: the schema drops `version`, and the docs call it by its groups instead of "version 2". Chassis never needed it, since it reads `settings` and `styles` wherever they are. Remove `"version": 2` from a `chassis.json` that has it, or the editor flags it.

## [0.1.6] - 11.06.2026

### Added

- A second `chassis.json` shape with friendlier names, `settings` and `styles`. `settings` defines the `palette`, `font`s, `spacing` scale, `layout` widths and `shape`s; `styles` applies them to the whole site, to `elements` (links, headings, buttons), and to each part of the page by a friendly name (`header`, `primary-menu`, `menu-button`, `content`, `post`, `widget`, `comments`, `pagination`, `badge`, `footer`). Every part uses the same keys (`color`, `border`, `spacing`, `typography`), with its pieces and `:hover` states nested inside. Palette colors are used as `@name` and become `--palette-{name}`. It covers everything the first shape can set and produces the same custom properties, so a theme looks the same after switching. The first shape still works; where both set the same thing, `settings` and `styles` win. Unknown settings and palette names are skipped with a warning, and the schema flags them in editors.

## [0.1.5] - 11.06.2026

### Added

- On ClassicPress, the space between a post and its comments is a setting, `--comments-gap` (`var( --space-xl )` by default), which `chassis.json` sets with `spacing.comments-gap`. A theme that boxes its comments can set it to `l` to match the space between posts.

## [0.1.4] - 11.06.2026

### Changed

- In `chassis.json`, the `width` group is now `layout`: `"layout": { "wide": "75rem", "sidebar": "20rem" }`. `width` still works, so existing themes don't change; when both set the same width, `layout` wins. The schema marks `width` as deprecated.

## [0.1.3] - 11.06.2026

### Fixed

- On ClassicPress, a comment's author and date sat right on top of its text. `.comment-meta` now has space below it, `--comments-meta-gap` (`var( --space-m )` by default), which `chassis.json` sets with `spacing.comments-meta-gap`.

## [0.1.2] - 11.06.2026

### Changed

- The comments box from 0.1.1 is now a setting, off by default, so comments sit on the page again unless a theme asks for the box. Turn it on in `chassis.json` with `color.comments.background`, `color.comments.border` and `spacing.comments-padding` (and `design.comments-shadow` for a shadow). These become `--color-comments-background`, `--color-comments-border`, `--comments-padding` and `--comments-shadow`.

## [0.1.1] - 11.06.2026

### Changed

- On ClassicPress, the comments and the comment form (`.comments-area`) sit in a box like a post's, built from the same settings: `--color-content-background`, `--border-box`, `--radius`, `--shadow` and `--entry-padding`. A theme's `chassis.json` (`color.content`, `design`) styles both. Before, the comments sat on the page background.
- Comments printed by a custom callback (`.comments` items) get the same divider as the default markup, under `.comment-content`.

## [0.1.0] - 11.06.2026

Moving to 0.1 marks the end of the 0.0.x series. Themes that require `~0.0.x` need `^0.1` to get this and later updates:

```json
"techkodeninja/chassis": "^0.1"
```

### Fixed

- On ClassicPress, each file's `?ver=` is now a short hash of the file instead of the Chassis version, so browsers load the new CSS and JavaScript after every update. Before, a release that didn't change the version number (like 0.0.9) kept the old `?ver=` and browsers could keep the old files.

## [0.0.9] - 11.06.2026

### Fixed

- The submenu toggle (▾) was about 21px wide, too small to tap reliably. It's now at least 24px everywhere, and a 44px square on phones.
- The submenu arrow showed as `â–¾` on pages that aren't served as UTF-8. `chassis.js` now writes it as an escape, so the file is plain ASCII.
- On ClassicPress, pages scrolled by 32px (46px on phones) when the admin bar was showing, because `.app` was a full screen tall on top of the space the bar takes. `chassis-classicpress.css` now takes the bar off.

## [0.0.8] - 11.06.2026

### Added

- `Chassis::standalone( $folder, $url, $chassisJson )` turns Chassis on in any PHP site without Novaris or Backdrop. It copies the built files to a public folder and reads `chassis.json`, the same as the providers.
- `Techkodeninja\Chassis\Publisher` copies the built files with hashed names. The Novaris provider now uses it too, and works the same as before.

### Docs

- The readme explains how to use Chassis without Novaris or Backdrop: with `standalone()` in PHP, or from the jsDelivr CDN in plain HTML.

## [0.0.7] - 11.06.2026

### Docs

- [Build a theme from scratch](docs/build-a-theme.md) now sets the design in `chassis.json`, with no Sass needed. It adds a step that checks it loads, a sample of the HTML a theme prints with the BEM naming explained, and experiments for the Menu button. Sass is an optional step for a theme's own styles.

## [0.0.6] - 11.06.2026

### Added

- The Menu button hides on phones while you scroll down, and comes back when you scroll up, reach the top or bottom of the page, open the menu, or tab to it. This works in every `menu.toggle` position. `"hide-on-scroll": false` under `menu` in `chassis.json` turns it off.

## [0.0.5] - 11.06.2026

### Changed

- The Menu button now sits at the bottom left on phones by default, when `chassis.json` has no `menu.toggle`. Set `"menu": { "toggle": "top-right" }` for the old place, or use the new `.app-nav--top` class.
- Settings in `design` now win over the `menu.toggle` presets.

### Added

- `menu.design` in `chassis.json` sets how the Menu button looks: `background`, `text`, `border`, and `padding`. These become `--menu-toggle-background`, `--menu-toggle-color`, `--menu-toggle-border`, and `--menu-toggle-padding`, and win over the look that comes with `menu.toggle`.
- `.app-nav--top` for themes without `chassis.json`.

## [0.0.4] - 11.06.2026

### Added

- `menu.toggle` in `chassis.json` sets where the Menu button sits on phones: `top-right` (the default), `top-left`, `bottom-left`, or `bottom-right`.
- The build keeps `dist/` from each pull request as a download for 7 days.

### Changed

- The phone menu's position comes from custom properties (`--menu-toggle-*`, `--menu-panel-*`). `.app-nav--bottom` works as before.

## [0.0.3] - 11.06.2026

### Added

- `chassis.json`: a theme sets its colors, fonts, spacing, and widths in one file, with no Sass. `styles()` prints them after the stylesheet. A child theme's file overrides its parent's. See [Set the design with `chassis.json`](readme.md#set-the-design-with-chassisjson).
- `schemas/chassis.schema.json`, for autocomplete and typo checks in editors.
- `designFiles()`, `design()`, and `designCss()` on `Chassis`.

### Fixed

- `Providers\Backdrop` now loads the CSS, the ClassicPress add-on, and the JavaScript, as the docs said it did.

## [0.0.2] - 11.05.2026

### Fixed

- `.social-menu` now works with text links as well as icons, for Novaris's `$engine->social()`.

## [0.0.1] - 11.05.2026

The first release. Chassis is the shared structure for Novaris and ClassicPress themes: it owns how things are built, and each theme decides how they look.

### Turning Chassis on

- The service provider is what turns Chassis on. Without it, the CSS and JavaScript don't load.
- `Providers\Novaris` copies the built files to `public/assets/chassis`, and themes print them with `styles()` and `scripts()`. It logs a warning if it can't copy them, and removes old copies.
- `Providers\Backdrop` loads the CSS, the ClassicPress add-on, and the JavaScript itself.
- `platform()` returns `novaris`, `backdrop`, or `plain`, so the registered provider decides the platform.
- `button` and `badge` template tags on Novaris.
- `Techkodeninja\Chassis\Chassis` with `badge()`, `button()`, `classes()`, `path()`, `scssPath()`, `jsPath()`, and `distPath()` helpers.

### Design

Themes change Chassis with CSS custom properties, which load after it.

- Colors grouped by where they appear: Global, Header, Content, Widget, and Footer. Widget colors follow the content colors unless a theme sets them.
- Settings for spacing, widths, fonts, heading sizes, corners, shadows, box borders, header and menu padding, content padding, the sidebar gap, buttons, badges, pagination, and post boxes.

### Styles

SCSS in numbered ITCSS layers, with BEM class names.

- **Settings and tools:** breakpoints, colors, fonts, sizes, and spacing, plus `up()`, `down()`, `color()`, `space()`, and `width()`, with build errors for unknown names.
- **Elements:** a reset, body text, headings, links, tables, forms, inline code, code blocks, and quotes.
- **Objects:** `.container`, `.section`, `.grid`, and `.gallery`.
- **Layouts:** `.app`, `.app-header` with `--full`, `.app-nav` with `--bottom`, `.app-content` with `--sidebar-left` and `--sidebar-right`, `.app-main`, `.app-sidebar`, and `.app-footer`.
- **Components:** `.card`, `.badge`, `.button`, `.section-header`, `.pagination`, `.widget`, `.social-menu`, `.entry__*`, and `.code-copy`.
- **Utilities:** `.screen-reader-text`, `.skip-link`, editor alignments, and text and visibility helpers.
- **ClassicPress add-on:** captions, sticky posts, threaded comments, the comment form, the search form, the calendar widget, `the_posts_pagination()`, `entry-*` classes, and the `[gallery]` shortcode.

### JavaScript

- `js/app-nav.js`: the mobile menu, with `aria-expanded`, Escape, click-outside, and resize handling, plus submenu buttons for touch screens.
- `js/code-copy.js`: a Copy button for code blocks in post content.

### Build and docs

- `dist/` holds the compiled files. A GitHub Action compiles every pull request and commits `dist/` on `develop`.
- `docs/build-a-theme.md`: a complete ClassicPress theme, step by step.

### Known limits

- Breakpoints are fixed, because media queries can't read custom properties.
- `Providers\Backdrop` and the ClassicPress add-on haven't been tested on a live ClassicPress site yet.
- `docs/build-a-theme.md` hasn't been followed from start to finish yet.
