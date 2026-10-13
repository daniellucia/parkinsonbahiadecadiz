# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Redesign of the website for **Párkinson Bahía de Cádiz** (Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz), a non-profit in San Fernando (Cádiz) whose main service is a day centre (Unidad de Estancia Diurna) for people with Parkinson's and their families across the province. The current live site (parkinsonbahiadecadiz.org) runs a generic free theme and is being replaced by **WordPress + Elementor with a custom theme**.

**Author:** Daniel Lucia — https://daniellucia.es. Use this for the theme/plugin headers (`Author`, `Author URI`) and any credit in the footer.

Full research (association data, current-site audit, proposed sitemap, open questions for the client): `docs/investigacion.md`. Read it before writing content, page structure or CPTs.

Current state: WordPress 7.1.3 with Elementor (free) and both custom packages active: the theme `wp-content/themes/parkinson-bahia/` and the plugin `wp-content/plugins/parkinson-bahia-core/`. The home page is built and set as the front page; the other pages exist as stubs. `README.md` lists what is done and what is pending. Custom work belongs in `wp-content/themes/` or `wp-content/plugins/` (folder names must start with `parkinson-` to be tracked by git); never edit WordPress core (`wp-admin/`, `wp-includes/`, root `wp-*.php`).

**Design source:** the claude.ai/design project "Rediseño Asociación Párkinson Cádiz", file `Parkinson Bahia de Cadiz.dc.html` (project id `9e4553ba-ae3a-407a-9f35-cda41cdbc775`, readable with the DesignSync tool after `/design-login`). It is the visual reference for the theme and for every widget. Where the design's sample copy contradicts confirmed facts (e.g. it says "abierto todo el año, también en agosto"), the canonical data in this file wins until the client confirms otherwise.

## Requirements that shape the code

**Language:** the site is Spanish-only. All front-end strings in Spanish (including "Saltar al contenido", pagination, form labels, comment/a11y text) and wrapped in i18n functions with the theme's text domain. Brand spelling: "Párkinson" with accent.

**Accessibility is the top priority.** Audience: elderly people with tremor, rigidity, low vision and cognitive decline. Conformance with WCAG 2.2 level AA is mandatory (see "Accessibility: WCAG 2.2 level AA is mandatory"), with AAA-leaning typography:
- Base font 18–20 px, generous line-height, high contrast, visible focus, touch targets ≥ 44–48 px.
- No auto-playing carousels/sliders; no hover-only interactions; no time limits.
- Phone number always visible and clickable (`tel:`) in the mobile header.
- Forms: visible labels, a phone field, no cognitive captcha (use honeypot or Turnstile).
- Semantic HTML, meaningful `alt` on every image, no essential information inside images or PDFs only.
- Comments disabled on pages.

**Editability:** the client will maintain content through Elementor, so layouts should be editable there; structured data goes in CPTs rendered by the theme.

**CPTs:** see "Data model: CPTs first" below.

**Proposed top-level IA:** Inicio · La asociación · Centro de día · Servicios y terapias · Familias y cuidadores · Actualidad · Colabora (socio, donación, voluntariado, empresas) · Contacto.

**Planned features:** online membership (form + SEPA direct debit), online donations (Stripe/Redsys, Bizum), volunteer form, map of the centre, properly configured cookie consent, accessibility statement.

**Migration/SEO:** Google is the main traffic source. Every old URL needs a 301 (`/terapias/…`, `/category/…` incl. slugs like `c54-talleres`, `/archivos/*.pdf`). Canonical domain: `https://parkinsonbahiadecadiz.org` (no www).

## Build approach: one custom Elementor widget per section

The site is built on WordPress with Elementor, and **every section of every page is a custom Elementor widget** that the client can customise (texts, images, links, heading level, variants). The client composes and edits pages in Elementor without touching code.

- **Plugin** (`wp-content/plugins/`): CPTs and taxonomies, the Elementor widgets, forms and handlers, redirects, integrations — everything that must survive a theme change.
- **Theme** (`wp-content/themes/`): presentation only — design tokens, base CSS, header/footer, CPT single/archive templates, a11y helpers.
- **Header and footer are built the traditional WordPress way, not with Elementor:** `header.php` and `footer.php` in the theme (loaded with `get_header()` / `get_footer()`), menus registered with `register_nav_menus()` and printed with `wp_nav_menu()`, logo through `add_theme_support( 'custom-logo' )`, and any client-editable footer content through widget areas (`register_sidebar()`) or the Customizer. No Elementor header/footer templates and no Theme Builder; custom Elementor widgets are for page-content sections only.
- Widgets expose only the options the client needs and none that can break accessibility (no free font sizes below the base, no arbitrary colour pairs, no autoplay, entrance animations or carousels).
- Structured data stays in CPTs and widgets query it; page-specific text and images live in widget controls.
- Do not depend on Elementor Pro (Theme Builder, Form widget, dynamic tags) unless the client confirms a licence.
- **Links in widgets:** every widget link is a select listing the published WordPress pages, with "Enlace personalizado" as the last option; choosing it reveals a URL field. Never add a bare URL control. Use the base class helpers `link_controls( $target, $id, $label )` (works for the widget and for repeaters) and `link_value( $settings, $id )`, then output with `add_link_attributes()`. The page choice is stored as `page-<ID>`, so links survive slug changes; an unpublished or deleted page renders no link.
- Widget settings are untrusted input: escape everything in `render()`, validate tag controls with `\Elementor\Utils::validate_html_tag()`, and output URL controls with `add_link_attributes()`.

## Accessibility: WCAG 2.2 level AA is mandatory

Everything we build — theme templates, widgets, forms, content seeded by us, and any third-party plugin output we choose to ship — **must conform to WCAG 2.2 level AA**. It is an acceptance criterion of every task, not a later audit: work that fails an A or AA success criterion is not finished. Where this project asks for more than AA (18–20 px base text, 44–48 px targets, no autoplay), the stricter project rule applies.

What that means in practice:

- **Perceivable:** text contrast ≥ 4.5:1 (≥ 3:1 only for text ≥ 24 px, or ≥ 18.66 px bold), UI component and focus-indicator contrast ≥ 3:1, including hover/pressed states and the high-contrast mode; use only the token pairs already checked (table below) or measure new ones. Meaningful `alt` on images (empty `alt` only when decorative); no information by colour alone; content reflows at 320 CSS px and at 200–400 % zoom without horizontal scrolling or clipped text (no fixed heights, no `white-space: nowrap` on text that can grow); text spacing can be increased without loss.
- **Operable:** everything works with the keyboard in a logical order, with a visible focus ring that is never hidden behind sticky elements; skip link first; targets ≥ 24 × 24 px at the very least (project rule 44–48 px); no keyboard traps, no time limits, no motion that starts by itself, nothing that needs dragging or hover; descriptive page titles and link texts; at least two ways to find a page (menu and the search form in the footer).
- **Understandable:** `lang` set, consistent navigation and naming across pages, phone/help in the same place everywhere; forms with visible labels, programmatic names, clear Spanish error messages next to the field, error prevention on anything that sends money or personal data, no cognitive captcha and no re-typing of data already given.
- **Robust:** semantic HTML first (landmarks with a label when repeated, one `h1` per page and no skipped heading levels, lists, `button` vs `a`), ARIA only where HTML has no equivalent, state exposed (`aria-expanded`, `aria-pressed`, `aria-current`), status messages announced (`role="status"`), and the accessible name of a control must contain its visible text.
- Widgets must make it hard for the editor to break conformance: heading level is a choice between valid levels, colours come from checked pairs, images take their `alt` from the media library.

**How to check before calling something done:** run axe-core (tags `wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa`) on every affected template in normal and high-contrast mode and fix every violation; then check by hand what tools cannot: keyboard pass, 320 px and 200 % zoom, headings/landmarks outline, focus order, meaningful alt texts and link texts. Record in the task summary what was and was not checked.

Checked colour pairs (ratio): ink on paper 14.7 · ink-2 on paper 7.5 / sand 7.0 / card 7.9 / mist 6.7 · sea on paper 6.6 / sand 6.1 / card 7.0 / mist 5.9 · clay-text on paper 5.7 / sand 5.3 / card 6.1 · white on sea 7.0 / clay 4.7 / sea-dark 12.6 / ink 15.6 · on-dark-muted on sea-dark 9.5 · footer-muted on ink 10.2 · focus ring `#C96F00` on paper 3.4 / sand 3.2 / mist 3.1 / sea-dark 3.5 / ink 4.3 (on the clay background the ring is white). Do **not** use `--pbc-clay` for text on light backgrounds (4.1–4.5) — use `--pbc-clay-text`.

## One `h1` per page

Every page has **exactly one `h1`**: the page's main title. Never zero, never two.

- Theme templates (`index.php`, `single-post.php`, `single.php`, `page.php` for non-Elementor pages, `404.php`) print the `h1` themselves. The site name/logo in the header is never a heading.
- Pages built with Elementor get no `h1` from the theme: the first widget of the page (the hero or page-header widget) provides it, with its "Nivel del título" set to H1. Every other widget on the page uses `h2` (sections) or `h3` (items inside a section), without skipping levels.
- New widgets default to `h2`; only hero/page-header widgets default to `h1`. The base class enforces the rule at render time (`Widget::heading_tag()` downgrades any second `h1` in the same request to `h2`), but pages must still be authored correctly.
- Titles of cards, list items and repeated blocks are `h2`/`h3` or plain text, never `h1`. Do not pick a heading level for its size — size comes from CSS.
- When creating or changing a page or template, count the `h1` elements in the rendered HTML (must be 1) and check the heading outline.

## Micro-interactions

Every interactive element (links, buttons, cards that are links, chips, menu items, form fields, accordions) gets a small, pleasant micro-interaction on hover, focus and click. They must feel kind and calm, never showy — the audience is elderly people with tremor and low vision.

- **Only as feedback to the person's action.** Nothing animates on its own: no entrance or scroll animations, no loops, no parallax, no autoplay (this does not change the "no auto-playing" accessibility rule).
- **Simple and short:** colour, border, shadow or underline changes; a lift of 1–3 px, an arrow that moves about 5 px, a press that scales to 0.97–0.99. Use the theme tokens `--pbc-speed` (180 ms) and `--pbc-ease`; nothing longer than about 250 ms, no bounce or elastic easing.
- **Never the only signal and never an obstacle:** the hover state must also be visible without motion (colour/border), focus keeps the visible ring, targets must not move away from the pointer or change the layout (use `transform`, not margins or sizes, so there is no layout shift), and the same feedback applies on `:focus-visible` where it makes sense.
- **Respect `prefers-reduced-motion`:** put every `transform` inside `@media (prefers-reduced-motion: no-preference)`; colour changes may stay. The theme also has a global reduced-motion rule as a safety net.
- **CSS only**, animating `transform`, `opacity`, colours and shadows. No animation libraries and no JavaScript for hover effects.
- **Form submit buttons show a loading spinner inside the button itself** as soon as the form is submitted, so the person knows something is happening and does not press again. This is already built into the theme for every `<form>` whose submit control is a `.pbc-button` (`main.js` + `.pbc-button--loading` in `main.css`): the button keeps its width, shows the spinner next to the text, gets `aria-busy="true"`, optionally swaps its text for `data-pbc-loading-text` (e.g. "Enviando…"), and ignores further presses. Do not use the `disabled` attribute for this (it drops focus and the value from the request). Forms sent with `fetch` must remove the state themselves when the response arrives (`window.pbcButtonLoading( button, false )`) and announce the result in a `role="status"` / `role="alert"` region next to the form. With `prefers-reduced-motion` the spinner does not rotate; the text change carries the message. Never leave a button spinning after an error.
- Each widget's stylesheet ends with a "Microinteracciones" block; reuse the patterns already in `main.css` (`.pbc-button`) and the home widgets so the whole site responds the same way.

## Data model: CPTs first

**Anything that can be a CPT must be a CPT**, so the client manages data in one obvious place in wp-admin instead of inside page layouts. Before building any section or widget, review the design and ask: is this a list of similar items, will it grow or change, or is it shown in more than one place? If yes to any, it is a CPT (registered in the plugin, never in the theme) and the widget only *queries and displays* it. Widget controls and repeaters are only for text that belongs to a single section of a single page.

- Register CPTs in the plugin with Spanish labels, a clear menu icon, `show_in_rest`, only the `supports` each one needs, and `public`/`has_archive` only when the item really has its own URL (otherwise `publicly_queryable => false` so no thin pages get indexed).
- Extra fields are registered post meta with a native meta box (sanitised on save, nonce + capability). No ACF or other field plugin unless agreed.
- Grouping and filtering use taxonomies, not free-text fields. Manual ordering uses `menu_order` (`page-attributes`).
- Each list widget lets the editor choose taxonomy term, number of items and order — never retype the items.

CPT inventory from the design review (`Parkinson Bahia de Cadiz.dc.html`; slugs to be fixed when the plugin is created):

| Data in the design | Where it appears | Model |
|---|---|---|
| Noticias | Inicio, Actualidad, detalle | Native posts + categories (Institucional, Proyectos, Consejos, Día Mundial) |
| Terapias (nombre, entradilla, "qué trabajamos", quién la imparte, foto) | Inicio, Terapias | Done: CPT Terapia, with its own page |
| Preguntas frecuentes | Centro de día | CPT Pregunta + taxonomy Tema (pending) |
| Instalaciones (foto, nombre, alt) | Centro de día, Inicio | CPT Espacio |
| Recursos descargables (guías y fichas) | Familias | CPT Recurso + taxonomy Tema (Alimentación, Voz y habla, Memoria, Ejercicio, Vida diaria); file as attachment |
| Vídeos de ejercicios | Familias | CPT Vídeo (or Recurso of type vídeo — decide with the plugin) |
| En los medios (medio, fecha, titular, enlace) | Actualidad | CPT Aparición en prensa |
| Equipo y junta directiva | La asociación | Done: CPT Puesto (role + number of people) and CPT Directivo (name + cargo) |
| Historia (año, título, texto) | La asociación | Done: CPT Hito |
| Documentos de transparencia | La asociación | Done: CPT Documento (file as attachment); taxonomy Tipo / field Año pending if needed |
| Redes y federaciones | La asociación, pie | Done: CPT Entidad |
| Financiadores | Pie de todas las páginas | CPT Financiador (logo, enlace) — rendered through the theme hook `parkinson_bahia/footer_funders` |
| Mensajes de formularios (contacto, socio, voluntariado, donativo) | Colabora, Contacto | Private CPT or custom table — decide with the forms work (personal data, see the skill's security rules) |

Stays in widget controls/repeaters (page-specific copy): hero texts, "¿En qué podemos ayudarte?" paths, "Así es un día en el centro" timetable, "Cómo solicitar plaza" steps, first steps after diagnosis, volunteering areas. Stays in plugin settings: membership fee and donation amounts, payment methods. Stays in the Customizer: contact data, header button, footer texts.

## Performance and PageSpeed

Every Elementor widget, template, theme file, plugin feature or third-party addition is built with page speed in mind; performance is a requirement of the task, not a later optimisation pass. Many visitors are elderly people on old phones and slow connections, and Google (the main traffic source) ranks on Core Web Vitals.

**Targets (mobile, measured with PageSpeed Insights / Lighthouse):** Performance score ≥ 90 on the home page and on every template type, LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1. If a change cannot meet them, say so and explain the trade-off instead of shipping it silently.

**Assets**
- Each widget registers its own small CSS/JS and declares it in `get_style_depends()` / `get_script_depends()`, so it loads only on pages that use it. No global "all widgets" bundle.
- Prefer CSS over JS and vanilla JS over libraries. No jQuery dependency in our code, no slider/animation/icon libraries, no icon fonts (inline SVG instead).
- Scripts load in the footer with `defer` (`wp_register_script( ..., [ 'strategy' => 'defer', 'in_footer' => true ] )`). Nothing render-blocking in `<head>` beyond the critical stylesheet.
- Version assets with `filemtime()` or the package version for long-lived caching; ship minified builds.

**Images and media**
- Always output images with `wp_get_attachment_image()` (never a raw URL in an `<img>` or a CSS background for content images) so `srcset`, `sizes`, `width`/`height` and lazy loading are generated. Register image sizes that match the real layout slots and pass an accurate `sizes` attribute.
- The LCP image (normally the hero) gets `fetchpriority="high"` and `loading="eager"`; everything below the fold is lazy. Never lazy-load the LCP image.
- Serve WebP/AVIF; no uploads straight from a camera at full resolution.
- Maps, YouTube and social embeds load as a click-to-load facade (static image + button), which also satisfies cookie consent.

**Fonts**
- Self-hosted WOFF2, at most two families and only the weights used, `font-display: swap`, preload only the one or two files needed above the fold, and a metric-matched fallback (`size-adjust`) to avoid layout shift.

**Layout stability**
- Reserve space for everything that loads late (images, embeds, cookie banner, funders' logos) with `width`/`height` or `aspect-ratio`. No content injected above existing content after load.

**Markup and Elementor**
- Keep the DOM shallow: widgets return `has_widget_inner_wrapper(): false`, render their own semantic markup, and are placed in flex/grid containers — never nested sections/columns for spacing. One widget per section instead of many stock widgets.
- Keep Elementor's performance features on (optimised markup, element caching, lazy-loaded backgrounds, inline font icons as SVG, improved asset/CSS loading) and Google Fonts loading from Elementor off.
- Set `is_dynamic_content()` to `false` whenever a widget's output is static so Elementor can cache it; `true` only when it depends on a query, the visitor or time.

**Server side**
- No query inside a loop. Widgets that list CPT items use one `WP_Query` with `no_found_rows => true` when there is no pagination, a capped `posts_per_page`, `update_post_meta_cache`/`update_post_term_cache` disabled when unused, and prime thumbnails once.
- Cache expensive or remote results in transients/object cache and invalidate on `save_post`. No remote HTTP request during a front-end page render.
- Autoloaded options stay small; do not store large data with `autoload` on.

**Third parties and plugins**
- Every extra plugin and external script has a cost: justify it, and prefer a few lines in our own plugin to a heavy add-on. Analytics, if any, must be lightweight and consent-gated.
- Page caching, compression and HTTP caching headers are configured at hosting level (to be defined with the client's hosting); the code must be cache-friendly — no per-visitor output on public pages, no nonces baked into cached HTML for anonymous forms without a refresh strategy.

**Verify before calling it done:** run Lighthouse (mobile preset) on the affected page, and check there is no new render-blocking resource, no layout shift and no unused asset loading on pages that do not use the widget. Local DDEV scores are only indicative; final numbers come from PageSpeed Insights on staging/production.

## Project skill

`.claude/skills/parkinson-wp-elementor/SKILL.md` is the single project skill for this build. It covers the architecture above, a widget skeleton and rules, the security checklist (output escaping, requests, forms/personal data/payments, hardening for the nginx stack), the accessible design process, safe Elementor editing through `ddev wp`, and a definition of done per section. Use it for any theme, plugin, widget, design, form or security-review work.

It replaces three generic third-party skills that were installed in `.agents/skills/` (`wordpress-pro`, `wordpress-elementor`, `frontend-design`) and have been removed together with `skills-lock.json`. Do not reinstall them: several of their examples are unsafe or wrong for this project (public REST `permission_callback => '__return_true'`, database backups written under `uploads/` protected only by `.htaccess` on an nginx stack, a CSP with `'unsafe-inline' 'unsafe-eval'`, an unsigned self-hosted plugin updater, site-wide `search-replace` on `wp_postmeta`, typing WordPress passwords into browser automation, and design advice that favours visual risk over accessibility).

## Theme: `wp-content/themes/parkinson-bahia/`

Namespace `DanielLucia\ParkinsonBahia\Theme` (PSR-4 → `src/`), text domain `parkinson-bahia`, CSS/JS prefix `pbc-`. After cloning run `ddev exec -d /var/www/html/wp-content/themes/parkinson-bahia composer install` (`vendor/` is not tracked).

- `src/`: `Theme` (bootstrap), `Setup` (supports, menus, image sizes `pbc-card` / `pbc-feature` / `pbc-wide`), `Menus` (BEM classes for `wp_nav_menu`), `Assets`, `Customizer`, `TemplateTags` (static helpers).
- Templates: `header.php`, `footer.php`, `index.php` (Actualidad, archives, search), `single-post.php` (news), `single.php` (CPT singles such as a therapy), `page.php` (full width when built with Elementor, prose layout otherwise), `404.php`, `searchform.php`, `template-parts/` (`breadcrumb`, `card`, `contact-band`).
- `assets/css/main.css`: design tokens (`--pbc-*` on `:root`, high-contrast overrides on `html[data-contrast="high"]`), base, `.pbc-button`, `.pbc-container`, header, footer. `assets/css/blog.css` loads only on news views. Widgets must reuse these tokens and blocks instead of redefining colours or buttons.
- Design tokens come from the design: paper `#FBF8F3`, ink `#1C252B`, sea `#1D5A9E`, sea-dark `#0F3360`, clay `#C2500F`, sand `#F7EFE7`, mist `#E4EDF7`; fonts Atkinson Hyperlegible Next (body) and Young Serif (headings), self-hosted in `assets/fonts/`. Two deliberate deviations for contrast: small accent text uses `--pbc-clay-text` (`#A8430A`) and the focus ring is `#C96F00`.
- `assets/js/main.js` (deferred, no dependencies): mobile menu, text size (3 steps) and high contrast saved in `localStorage` (`pbc_fs`, `pbc_contrast`), copy link. Everything works without JS except those toggles.
- Menu locations: `primary`, `footer-asociacion`, `footer-legal`. Contact data, header button, social links and footer texts are edited in Apariencia → Personalizar → "Párkinson Bahía de Cádiz" (defaults = canonical contact data).
- Hooks for the plugin: action `parkinson_bahia/footer_funders` (funders' logo band in the footer, hidden until something hooks in), action `parkinson_bahia/contact_map` (replaces the decorative map panel), filter `parkinson_bahia/show_contact_band` (hide the common contact block on a given page).

## Plugin: `wp-content/plugins/parkinson-bahia-core/`

Namespace `DanielLucia\ParkinsonBahia\Core` (PSR-4 → `src/`), text domain `parkinson-bahia-core`. After cloning run `ddev exec -d /var/www/html/wp-content/plugins/parkinson-bahia-core composer install`.

- `src/PostTypes/`: one class per CPT, all extending the abstract `PostType` (registration, extra fields as registered post meta, native meta box, save with nonce + capability + sanitising; field types `text`, `number`, `url`, `file`). `Plugin::post_types()` is the list. **Never call translation functions from `register()` or a constructor** — WordPress 6.7+ raises `_load_textdomain_just_in_time` if a text domain is used before `init`; `register()` only adds hooks, and labels/fields are built inside the callbacks.
  - `Terapia` (`pbc_terapia`): public, URLs under `/terapias/`; title = name, excerpt = short summary for lists, content = full description, `menu_order` = order.
  - List-only CPTs (no public URL, `list_only_args()`), grouped in the admin menu "La asociación" (`src/Admin/Menu.php`): `Hito` (`pbc_hito`, meta `_pbc_year`, excerpt = text), `Puesto` (`pbc_puesto`, meta `_pbc_count`), `Directivo` (`pbc_directivo`, meta `_pbc_role`), `Documento` (`pbc_documento`, meta `_pbc_file` = attachment ID chosen with the media picker), `Entidad` (`pbc_entidad`, meta `_pbc_short`, `_pbc_url`).
- `src/Elementor/Integration.php`: widget category `parkinson-bahia`, the `WIDGETS` map (slug => class) and one registered stylesheet per widget (`pbc-widget-<slug>` → `assets/css/widgets/<slug>.css`). It also stops Elementor from printing Google Fonts.
- `src/Elementor/Widgets/Widget.php`: abstract base every widget extends. A new widget implements `slug()`, `get_title()`, `get_icon()`, `register_controls()` and `render()`, is added to `WIDGETS`, and gets its CSS file. Helpers: `text_control`, `image_control`, `heading_tag_control` / `heading_tag`, `link_controls` / `link_value`, `render_heading`, `render_text`, `image_html`, `title_id`. Widgets that query content override `is_dynamic_content()` to return `true`.
- Widgets so far — home: `Hero`, `Paths`, `Feature`, `Therapies` (CPT), `Steps`, `LatestNews` (posts), `Support`; La asociación: `PageHeader` (breadcrumb + `h1`, reusable as the first widget of every inner page), `Mission`, `Timeline` (Hito), `Team` (Puesto), `Board` (Directivo), `Documents` (Documento), `Networks` (Entidad). List widgets use the base helpers `items()`, `source_note()` and `render_empty_notice()`.
- `src/Cli/SeedCommand.php`: `ddev wp pbc seed-asociacion [--force]` builds the La asociación page and its CPT items; `ddev wp pbc seed-home [--force] [--skip-images]` creates the base pages, the therapies, three news posts, the main menu and the home page (Elementor data saved through the document API), sets the front page and the posts page. Idempotent; take a `ddev snapshot` before using `--force`.
- Elementor's stylesheet has `.elementor img { border-radius: 0 }`; image rules in widget CSS need two classes (`.pbc-hero .pbc-hero__image`) to win.
- Local quirk: WordPress generates `http://` URLs here, so over `https://` the browser blocks images that have `srcset` (mixed content). Check pages over `http://parkinsonbahiadecadiz.ddev.site`.

## Development conventions (custom themes and plugins)

**Authorship:** every theme and plugin we create is authored by Daniel Lucia (https://daniellucia.es). Set `Author: Daniel Lucia` and `Author URI: https://daniellucia.es` in the theme `style.css` header and in each plugin's main file header, and use the same data in the `authors` field of `composer.json`.

**Best practices:** follow WordPress, PHP and Elementor best practices.
- WordPress: WordPress Coding Standards, hooks instead of core edits, escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), sanitize on input, nonces and capability checks, `$wpdb->prepare()`, assets via `wp_enqueue_*`, unique prefixes/namespaces, i18n with the text domain.
- PHP: modern PHP 8.4 (`declare(strict_types=1)`, typed properties, parameters and return types), small single-responsibility classes, no logic in template files beyond presentation.
- Elementor: extend through its official APIs (widgets extending `\Elementor\Widget_Base`, registered on `elementor/widgets/register`; controls, dynamic tags and custom categories through their own hooks), declare widget script/style dependencies with `get_script_depends()` / `get_style_depends()`, and check that Elementor is loaded before using it.

**Class autoloading:** use Composer with PSR-4 — no manual `require` chains for classes. Each theme/plugin has its own `composer.json` with an `autoload.psr-4` mapping (vendor namespace → `src/`), one class per file with the file name matching the class name, and the bootstrap file loads `vendor/autoload.php`. Run it through DDEV (`ddev composer install`, `ddev composer dump-autoload`) from the theme/plugin directory.

**CSS:** all CSS files use BEM naming (`.block`, `.block__element`, `.block--modifier`). Keep specificity flat — no IDs or tag-qualified selectors for styling, no deep nesting.

## Keeping README.md up to date

`README.md` (Spanish, for people) is the project's running record. Every time a feature is added, changed or removed, or something relevant about the project changes (a new CPT, widget, page, template, command, integration, setup step, decision or pending item), update `README.md` in the same piece of work and the same commit: its "Funcionalidades", "Páginas" and "Pendiente" sections and the setup steps if they changed. Rules and conventions for working on the code go in this file (`CLAUDE.md`) instead.

## Versioning of our theme and plugins

Our theme and plugin(s) carry their own version number and we raise it as work is done, following semantic versioning (`MAJOR.MINOR.PATCH`):

- **PATCH** (1.2.0 → 1.2.1): bug fixes, small style or copy corrections, accessibility or performance fixes with no new functionality.
- **MINOR** (1.2.1 → 1.3.0): new functionality that keeps what exists working — a new widget, CPT, template, control, page or option.
- **MAJOR** (1.3.0 → 2.0.0): changes that break what exists — renamed or removed widgets, controls, CPT or meta keys, hooks, CSS tokens/classes others rely on, or anything that needs a data migration.

Rules: bump only the package that changed, once per finished task (not per file), in the same commit as the work. Keep every place in sync — theme: `Version:` in `style.css` and `Theme::VERSION`; plugin: `Version:` in the main file header and `PBC_CORE_VERSION`. Asset cache-busting uses file modification times, so it does not depend on the version. Mention the new version in the commit message, and note relevant changes in `README.md`.

## Git workflow

Commit automatically, without asking, every time a task is finished or a significant, self-contained piece of work is done (a widget, a CPT, a template, a form, a fix, a documentation or config change). This is a standing instruction.

- One logical change per commit; do not mix unrelated work. Commit only when the result is in a working state (no syntax errors, site still loads).
- Before committing, review `git status` / `git diff` and stage only the intended files. `.gitignore` is a whitelist that tracks only our own work; never force-add ignored files, and never commit secrets, database dumps or `wp-config.php`.
- Short imperative subject line saying what changed, plus a body when the reason is not obvious.
- Do not push, rewrite history or create branches/tags unless asked.

## Canonical contact data

Use these; the old address (C/ Concha Pérez Baturone 9) is obsolete and must not appear.
- C/ Milongas s/n, 11100 San Fernando (Cádiz) — 36.44795, -6.21460
- Tel. 956 59 19 28 · unidad@parkinsonbahiadecadiz.org
- Lunes a viernes 9:00–17:00, preferentemente con cita previa. Cerrado en agosto.
- Social: facebook.com/parkinsonbahiadecadiz · instagram.com/parkinsonbahiadecadiz · x.com/parkinsonbahia · YouTube channel UC7-c1fm0in6KnCjeZzd5Mlg

Several facts are still unconfirmed (utilidad pública status, number of places and fees, membership fees/IBAN, mission/vision text, brand colours/fonts) — see "Preguntas pendientes" in `docs/investigacion.md`. Don't invent them; leave clear placeholders.

## Local environment (DDEV)

Stack (`.ddev/config.yaml`): PHP 8.4, nginx-fpm, MariaDB 11.8, Node 24, Composer 2. Docroot is the repo root. Project URL: `https://parkinsonbahiadecadiz.ddev.site`.

```bash
ddev start                 # start containers
ddev launch                # open site in browser (ddev launch wp-admin/ for admin)
ddev wp <command>          # WP-CLI inside the web container, e.g. ddev wp plugin list
ddev ssh                   # shell in the web container
ddev mysql                 # DB client (db/db/db)
ddev export-db / ddev import-db --file=dump.sql.gz
ddev snapshot              # quick DB snapshot (stored in .ddev/db_snapshots)
ddev xdebug on|off
ddev logs                  # web container logs
```

Run `npm`/`composer` through `ddev npm ...` / `ddev composer ...` so they use the container's versions.

## Configuration

- `wp-config.php` and `wp-config-ddev.php` are DDEV-generated (`#ddev-generated` marker) and may be overwritten. DB credentials, `WP_HOME`/`WP_SITEURL` and `WP_DEBUG` (on by default) come from DDEV env vars in `wp-config-ddev.php`.
- To add custom constants, put them between the "Add any custom values" and "stop editing" markers in `wp-config.php` — and remove the `#ddev-generated` line first, or DDEV may regenerate the file and drop them.
- Toggle debug with `ddev config --web-environment-add=WP_DEBUG=false` then `ddev restart`.
