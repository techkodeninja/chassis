# Changelog

All notable changes to Chassis will be documented in this file.

## [Unreleased]

### Fixed

- PHP 7.4 again: `Tags\Badge` and `Tags\Button` used constructor property promotion (PHP 8.0+), which white-screened any view calling `$engine->badge()` on PHP 7.4. The properties are now declared explicitly, and the rest of `src/` was audited for other PHP 8.0+ syntax.
- Without JavaScript, the phone menu was hidden with no way to open it. The menu is now only hidden once `chassis.js` adds `app-nav--ready`, so no-JS visitors still get the links.
- A long phone menu could overflow the screen with its top items unreachable. The open panel now scrolls (`max-height` + `overflow-y: auto`).
- The build now parses `dist/chassis.js` (`node --check`), so a broken concatenation fails the build instead of shipping.
- `assetUrl()` and `Publisher` now refuse paths that escape `dist/`, so a `../` in a file name can't copy `composer.json` or `wp-config.php` somewhere the browser can reach.
- The older `chassis.json` shape now warns on unknown keys, like the newer shape. Unknown `@name`s in composed borders warn and fall back instead of being dropped silently, and array values warn instead of becoming "Array to string conversion".
- `button()` only allows safe URL schemes (`#…`, `/…`, relative, `http`, `https`, `mailto`, `tel`); anything else becomes `#` with a warning.
- `<figure>` margins are reset, so captioned images and gallery items line up with the text.
- Floated images no longer escape post boxes: `.entry__content` and ClassicPress's `.entry-content` use `display: flow-root`, and `.entry-footer` clears them.
- The `:focus-visible` outline is visible on dark surfaces: the header and footer use their own text color, and focused pagination anchors use the accent text color.
- `.alignfull` no longer makes a horizontal scrollbar appear on classic scrollbars (`overflow-x: clip` on `body`).
- `.comment-moderation` is now `.comment-awaiting-moderation`, the class ClassicPress actually prints.
- The Copy button works on plain `http://` pages (a temporary-textarea fallback when the clipboard API isn't available), stays at the top of scrolling code blocks, and no longer resets its label early on fast double-clicks.
- Submenu toggles now have `aria-haspopup` and `aria-controls`; Escape works with focus outside the nav; tabbing out of the menu closes it.
- Sticky posts get the content background as well as the left accent, so the treatment is visible.
- Inline SVGs (icons in buttons and running text) stay inline; the social menu keeps its own block display.
- `.gallery-columns-1` exists; `.gallery-columns-2` is gone (the gallery already defaults to 2 columns).
- The comment form no longer duplicates the elements layer's input styles.
- `.entry` is now a real hook: the post box styles apply to `.entry` as well as `.app-main > article`.
- The "Read more" link doesn't wrap mid-phrase.
- `--menu-hide-on-scroll` is now defined in the tokens (`1` by default), instead of only existing when PHP emits it.
- The built files carry a version banner (`/*! Chassis 0.1.8 | … */`).
- `package-lock.json` is committed, so installs are reproducible; CI uses `npm ci`.

### Changed

- The older `chassis.json` shape still accepts extra keys (a theme can set its own custom properties), but warns about them; only `menu.design` (closed by the schema) skips unknown keys.
- `Backdrop::url()` warns when a path can't be mapped to a theme URL instead of returning an empty string, and the add-on stylesheet only loads when the core CSS did.
- `classes()` sanitizes the block name like it already did the modifiers.
- `design()` is cached per `chassis.json` set, and handles unreadable files, top-level scalars, and UTF-8 BOMs with a clear message.
- `Publisher` handles files without an extension.
- Skipped settings emit a PHP warning by design (see the readme); `trigger_error()` stays the diagnostic mechanism.
- The readme's ITCSS layer table and the `scss/_index.scss` comment list every layer (forms, galleries, all nine component partials); the helpers table lists every public method; the theme guide uses `.entry__metadata`, the class the package actually styles.

### Removed

- `--border-dotted` and `--border-solid` are gone — nothing used them. **Breaking** for themes that set them; use `--border-rule` or a full border value instead.

### Added

- `npm run check` runs `check:version` and `check:schema`: `scripts/check-version.mjs` verifies `Chassis::VERSION`, `package.json`, the readme's jsDelivr links, and the changelog's newest entry all agree; `scripts/check-schema.mjs` checks the schema is well-formed, that every `settings`/`styles` leaf is in `STYLES_MAP` or handled, and that the `chassis.json` examples in the docs only use settings the schema knows.
- The workflow changes for these checks and for the dist build's concurrency control are in the pull request body, for a direct push — the GitHub App can't modify workflow files.

## [0.1.8] - 2026-11-08

### Fixed

- `Chassis::VERSION`, `package.json` and the jsDelivr links in the readme now give the current version. The 0.1.7 tag still said 0.1.6. The only code that reads `Chassis::VERSION` is the stylesheet version used when Chassis can't read a file's hash, so no site behaved differently.

## [0.1.7] - 2026-11-06

### Breaking

- The `settings` and `styles` shape no longer has a version number: the schema drops `version`, and the docs call it by its groups instead of "version 2". Chassis never needed it, since it reads `settings` and `styles` wherever they are. Remove `"version": 2` from a `chassis.json` that has it, or the editor flags it.

## [0.1.6] - 2026-11-06

### Added

- A second `chassis.json` shape with friendlier names, `settings` and `styles`. `settings` defines the `palette`, `font`s, `spacing` scale, `layout` widths and `shape`s; `styles` applies them to the whole site, to `elements` (links, headings, buttons), and to each part of the page by a friendly name (`header`, `primary-menu`, `menu-button`, `content`, `post`, `widget`, `comments`, `pagination`, `badge`, `footer`). Every part uses the same keys (`color`, `border`, `spacing`, `typography`), with its pieces and `:hover` states nested inside. Palette colors are used as `@name` and become `--palette-{name}`. It covers everything the first shape can set and produces the same custom properties, so a theme looks the same after switching. The first shape still works; where both set the same thing, `settings` and `styles` win. Unknown settings and palette names are skipped with a warning, and the schema flags them in editors.

## [0.1.5] - 2026-11-06

### Added

- On ClassicPress, the space between a post and its comments is a setting, `--comments-gap` (`var( --space-xl )` by default), which `chassis.json` sets with `spacing.comments-gap`. A theme that boxes its comments can set it to `l` to match the space between posts.

## [0.1.4] - 2026-11-06

### Changed

- In `chassis.json`, the `width` group is now `layout`: `"layout": { "wide": "75rem", "sidebar": "20rem" }`. `width` still works, so existing themes don't change; when both set the same width, `layout` wins. The schema marks `width` as deprecated.

## [0.1.3] - 2026-11-06

### Fixed

- On ClassicPress, a comment's author and date sat right on top of its text. `.comment-meta` now has space below it, `--comments-meta-gap` (`var( --space-m )` by default), which `chassis.json` sets with `spacing.comments-meta-gap`.

## [0.1.2] - 2026-11-06

### Changed

- The comments box from 0.1.1 is now a setting, off by default, so comments sit on the page again unless a theme asks for the box. Turn it on in `chassis.json` with `color.comments.background`, `color.comments.border` and `spacing.comments-padding` (and `design.comments-shadow` for a shadow). These become `--color-comments-background`, `--color-comments-border`, `--comments-padding` and `--comments-shadow`.

## [0.1.1] - 2026-11-06

### Changed

- On ClassicPress, the comments and the comment form (`.comments-area`) sit in a box like a post's, built from the same settings: `--color-content-background`, `--border-box`, `--radius`, `--shadow` and `--entry-padding`. A theme's `chassis.json` (`color.content`, `design`) styles both. Before, the comments sat on the page background.
- Comments printed by a custom callback (`.comments` items) get the same divider as the default markup, under `.comment-content`.

## [0.1.0] - 2026-11-06

Moving to 0.1 marks the end of the 0.0.x series. Themes that require `~0.0.x` need `^0.1` to get this and later updates:

```json
"techkodeninja/chassis": "^0.1"
```

### Fixed

- On ClassicPress, each file's `?ver=` is now a short hash of the file instead of the Chassis version, so browsers load the new CSS and JavaScript after every update. Before, a release that didn't change the version number (like 0.0.9) kept the old `?ver=` and browsers could keep the old files.

## [0.0.9] - 2026-11-06

### Fixed

- The submenu toggle (▾) was about 21px wide, too small to tap reliably. It's now at least 24px everywhere, and a 44px square on phones.
- The submenu arrow showed as `â–¾` on pages that aren't served as UTF-8. `chassis.js` now writes it as an escape, so the file is plain ASCII.
- On ClassicPress, pages scrolled by 32px (46px on phones) when the admin bar was showing, because `.app` was a full screen tall on top of the space the bar takes. `chassis-classicpress.css` now takes the bar off.

## [0.0.8] - 2026-11-06

### Added

- `Chassis::standalone( $folder, $url, $chassisJson )` turns Chassis on in any PHP site without Novaris or Backdrop. It copies the built files to a public folder and reads `chassis.json`, the same as the providers.
- `Techkodeninja\Chassis\Publisher` copies the built files with hashed names. The Novaris provider now uses it too, and works the same as before.

### Docs

- The readme explains how to use Chassis without Novaris or Backdrop: with `standalone()` in PHP, or from the jsDelivr CDN in plain HTML.

## [0.0.7] - 2026-11-06

### Docs

- [Build a theme from scratch](docs/build-a-theme.md) now sets the design in `chassis.json`, with no Sass needed. It adds a step that checks it loads, a sample of the HTML a theme prints with the BEM naming explained, and experiments for the Menu button. Sass is an optional step for a theme's own styles.

## [0.0.6] - 2026-11-06

### Added

- The Menu button hides on phones while you scroll down, and comes back when you scroll up, reach the top or bottom of the page, open the menu, or tab to it. This works in every `menu.toggle` position. `"hide-on-scroll": false` under `menu` in `chassis.json` turns it off.

## [0.0.5] - 2026-11-06

### Changed

- The Menu button now sits at the bottom left on phones by default, when `chassis.json` has no `menu.toggle`. Set `"menu": { "toggle": "top-right" }` for the old place, or use the new `.app-nav--top` class.
- Settings in `design` now win over the `menu.toggle` presets.

### Added

- `menu.design` in `chassis.json` sets how the Menu button looks: `background`, `text`, `border`, and `padding`. These become `--menu-toggle-background`, `--menu-toggle-color`, `--menu-toggle-border`, and `--menu-toggle-padding`, and win over the look that comes with `menu.toggle`.
- `.app-nav--top` for themes without `chassis.json`.

## [0.0.4] - 2026-11-06

### Added

- `menu.toggle` in `chassis.json` sets where the Menu button sits on phones: `top-right` (the default), `top-left`, `bottom-left`, or `bottom-right`.
- The build keeps `dist/` from each pull request as a download for 7 days.

### Changed

- The phone menu's position comes from custom properties (`--menu-toggle-*`, `--menu-panel-*`). `.app-nav--bottom` works as before.

## [0.0.3] - 2026-11-06

### Added

- `chassis.json`: a theme sets its colors, fonts, spacing, and widths in one file, with no Sass. `styles()` prints them after the stylesheet. A child theme's file overrides its parent's. See [Set the design with `chassis.json`](readme.md#set-the-design-with-chassisjson).
- `schemas/chassis.schema.json`, for autocomplete and typo checks in editors.
- `designFiles()`, `design()`, and `designCss()` on `Chassis`.

### Fixed

- `Providers\Backdrop` now loads the CSS, the ClassicPress add-on, and the JavaScript, as the docs said it did.

## [0.0.2] - 2026-11-05

### Fixed

- `.social-menu` now works with text links as well as icons, for Novaris's `$engine->social()`.

## [0.0.1] - 2026-11-05

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
- `Providers\Backdrop` and the ClassicPress add-on were tested on a live ClassicPress site on 2026-11-19 (Beyond Expectations on a test install): the stylesheets load, `chassis.json` prints its custom properties, and the JavaScript loads. The guide still hasn't been followed from start to finish.
- `docs/build-a-theme.md` hasn't been followed from start to finish yet.
