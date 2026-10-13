# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Redesign of the website for **Párkinson Bahía de Cádiz** (Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz), a non-profit in San Fernando (Cádiz) whose main service is a day centre (Unidad de Estancia Diurna) for people with Parkinson's and their families across the province. The current live site (parkinsonbahiadecadiz.org) runs a generic free theme and is being replaced by **WordPress + Elementor with a custom theme**.

**Author:** Daniel Lucia — https://daniellucia.es. Use this for the theme/plugin headers (`Author`, `Author URI`) and any credit in the footer.

Full research (association data, current-site audit, proposed sitemap, open questions for the client): `docs/investigacion.md`. Read it before writing content, page structure or CPTs.

Current state: stock WordPress 7.1.3 install (default Twenty Twenty-* themes, Akismet, Hello Dolly). Elementor is not installed yet and no custom theme/plugin exists. Custom work belongs in `wp-content/themes/` or `wp-content/plugins/`; never edit WordPress core (`wp-admin/`, `wp-includes/`, root `wp-*.php`).

## Requirements that shape the code

**Language:** the site is Spanish-only. All front-end strings in Spanish (including "Saltar al contenido", pagination, form labels, comment/a11y text) and wrapped in i18n functions with the theme's text domain. Brand spelling: "Párkinson" with accent.

**Accessibility is the top priority.** Audience: elderly people with tremor, rigidity, low vision and cognitive decline. Target WCAG 2.2 AA (AAA-leaning typography):
- Base font 18–20 px, generous line-height, high contrast, visible focus, touch targets ≥ 44–48 px.
- No auto-playing carousels/sliders; no hover-only interactions; no time limits.
- Phone number always visible and clickable (`tel:`) in the mobile header.
- Forms: visible labels, a phone field, no cognitive captcha (use honeypot or Turnstile).
- Semantic HTML, meaningful `alt` on every image, no essential information inside images or PDFs only.
- Comments disabled on pages.

**Editability:** the client will maintain content through Elementor, so layouts should be editable there; structured data goes in CPTs rendered by the theme.

**Suggested CPTs:** Servicio/Terapia (one page per therapy, shared template), Recurso/Descarga (filterable downloads library), Miembro del equipo (team + board), Financiador/Colaborador (logo band — required by many grants: Next Generation EU, Junta de Andalucía, Diputación de Cádiz), Documento de transparencia.

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
- Widget settings are untrusted input: escape everything in `render()`, validate tag controls with `\Elementor\Utils::validate_html_tag()`, and output URL controls with `add_link_attributes()`.

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

## Development conventions (custom themes and plugins)

**Authorship:** every theme and plugin we create is authored by Daniel Lucia (https://daniellucia.es). Set `Author: Daniel Lucia` and `Author URI: https://daniellucia.es` in the theme `style.css` header and in each plugin's main file header, and use the same data in the `authors` field of `composer.json`.

**Best practices:** follow WordPress, PHP and Elementor best practices.
- WordPress: WordPress Coding Standards, hooks instead of core edits, escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), sanitize on input, nonces and capability checks, `$wpdb->prepare()`, assets via `wp_enqueue_*`, unique prefixes/namespaces, i18n with the text domain.
- PHP: modern PHP 8.4 (`declare(strict_types=1)`, typed properties, parameters and return types), small single-responsibility classes, no logic in template files beyond presentation.
- Elementor: extend through its official APIs (widgets extending `\Elementor\Widget_Base`, registered on `elementor/widgets/register`; controls, dynamic tags and custom categories through their own hooks), declare widget script/style dependencies with `get_script_depends()` / `get_style_depends()`, and check that Elementor is loaded before using it.

**Class autoloading:** use Composer with PSR-4 — no manual `require` chains for classes. Each theme/plugin has its own `composer.json` with an `autoload.psr-4` mapping (vendor namespace → `src/`), one class per file with the file name matching the class name, and the bootstrap file loads `vendor/autoload.php`. Run it through DDEV (`ddev composer install`, `ddev composer dump-autoload`) from the theme/plugin directory.

**CSS:** all CSS files use BEM naming (`.block`, `.block__element`, `.block--modifier`). Keep specificity flat — no IDs or tag-qualified selectors for styling, no deep nesting.

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
