---
name: parkinson-wp-elementor
description: Build and maintain the Párkinson Bahía de Cádiz website - WordPress custom theme and plugin, custom Elementor widgets (one per page section), CPTs, forms, accessible visual design and Elementor page editing through DDEV. Use for any work on the theme, the plugin, an Elementor widget or page, CSS/design, forms, payments, redirects or a security review of this project's code.
---

# Párkinson Bahía de Cádiz: WordPress + Elementor

One skill for the whole build. It replaces three generic skills (`wordpress-pro`, `wordpress-elementor`, `frontend-design`) and resolves their conflicts in favour of this project's rules. `CLAUDE.md` and `docs/investigacion.md` stay the source of truth for content, contact data and open questions; never invent unconfirmed facts, leave a clearly marked placeholder.

Priority when rules collide: **security > accessibility > performance > editability by the client > visual distinctiveness > developer convenience**.

## 1. How the site is built

The site is WordPress + Elementor, and **every section of every page is a custom Elementor widget** that the client can customise. The client composes and edits pages in Elementor; they never touch code and never need a stock widget for a section we designed.

- **Plugin** (`wp-content/plugins/`): everything that must survive a theme change: CPTs and taxonomies, the Elementor widgets, forms and their handlers, redirects, integrations.
- **Theme** (`wp-content/themes/`): presentation only: design tokens, base CSS, header/footer, templates for CPT singles and archives, skip link, a11y helpers.
- Do not depend on Elementor Pro (Theme Builder, Form widget, dynamic tags) unless the client confirms a licence. Forms come from our own widget.
- Header and footer are built the traditional WordPress way, never with Elementor: `header.php` / `footer.php` in the theme, menus via `register_nav_menus()` + `wp_nav_menu()`, logo via `custom-logo` theme support, client-editable footer content via widget areas or the Customizer. Custom Elementor widgets are for page-content sections only.
- Structured data lives in CPTs and widgets *query* it (services, team, funders, downloads, transparency documents). Free text and images that belong to one page live in the widget's controls.
- Both packages: author Daniel Lucia / https://daniellucia.es, `composer.json` with PSR-4 (`src/`, one class per file), `declare(strict_types=1)`, PHP 8.4 types, text domain on every string, strings in Spanish.

Suggested widget set, one per section type (adjust to the final sitemap): portada/hero, bloque de texto con imagen, rejilla de servicios, ficha de terapia, equipo, banda de financiadores, llamada a la acción (hazte socio / dona), datos de contacto con mapa, formulario (contacto, voluntariado, socio), biblioteca de descargas, últimas noticias, preguntas frecuentes, documentos de transparencia.

## 2. Writing an Elementor widget

```php
<?php
declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Widgets;

use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

final class Hero extends Widget_Base {

	public function get_name(): string { return 'pbc-hero'; }
	public function get_title(): string { return esc_html__( 'Portada', 'parkinson-bahia' ); }
	public function get_icon(): string { return 'eicon-banner'; }
	public function get_categories(): array { return [ 'parkinson-bahia' ]; }
	public function get_style_depends(): array { return [ 'pbc-hero' ]; }
	public function has_widget_inner_wrapper(): bool { return false; }

	// true for any widget whose HTML depends on a query, the visitor or the time.
	protected function is_dynamic_content(): bool { return false; }

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia' ) ] );
		$this->add_control( 'title', [
			'label'   => esc_html__( 'Título', 'parkinson-bahia' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );
		$this->add_control( 'title_tag', [
			'label'   => esc_html__( 'Nivel del título', 'parkinson-bahia' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3' ],
			'default' => 'h2',
		] );
		$this->add_control( 'button_link', [
			'label' => esc_html__( 'Enlace del botón', 'parkinson-bahia' ),
			'type'  => Controls_Manager::URL,
		] );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$tag      = Utils::validate_html_tag( $settings['title_tag'] );

		$this->add_render_attribute( 'root', 'class', 'pbc-hero' );
		if ( ! empty( $settings['button_link']['url'] ) ) {
			$this->add_link_attributes( 'button', $settings['button_link'] );
			$this->add_render_attribute( 'button', 'class', 'pbc-hero__button' );
		}
		?>
		<section <?php $this->print_render_attribute_string( 'root' ); ?>>
			<<?php echo esc_html( $tag ); ?> class="pbc-hero__title"><?php echo esc_html( $settings['title'] ); ?></<?php echo esc_html( $tag ); ?>>
		</section>
		<?php
	}
}
```

Rules:

- Register on `elementor/widgets/register` with `$widgets_manager->register( new Hero() )`; add our category on `elementor/elements/categories_registered`. Bail out with an admin notice if `did_action( 'elementor/loaded' )` is false. Register each widget's CSS/JS with `wp_register_*` and declare it in `get_style_depends()` / `get_script_depends()` so it only loads on pages that use the widget.
- Front-end JS goes in a handler extending `elementorModules.frontend.handlers.Base`, attached on `elementor/frontend/init`, so it also works inside the editor preview. No inline `<script>` in `render()`.
- One BEM block per widget, block name equal to the widget name (`.pbc-hero`, `.pbc-hero__title`, `.pbc-hero--inverted`). Style controls write CSS custom properties through `selectors` (`'{{WRAPPER}}' => '--pbc-hero-bg: {{VALUE}};'`) and the CSS file consumes them; do not generate deep Elementor selectors.
- Customisable does not mean unlimited. Offer the choices the client really needs (texts, image, link, heading level, variant, a palette of pre-approved colour pairs) and leave out the ones that break accessibility: no free font-size below the base, no arbitrary text/background colour pairs, no autoplay, no entrance animations, no carousel controls.
- Links are never a bare URL control: use the base class `link_controls()` / `link_value()` (select of published pages, with "Enlace personalizado" last, which reveals the URL field).
- Extend the plugin's abstract `Widget` base class and follow `CLAUDE.md` ("Plugin", "Data model: CPTs first"); update `README.md` when a widget or feature is added.
- Every image control needs an alt path: use `wp_get_attachment_image()` so the media library alt is used, plus a "decorativa" switch that outputs `alt=""`.
- Give every control a Spanish label and a sensible default so a freshly dropped widget already looks right. Use placeholder text that is obviously a placeholder for unconfirmed facts.
- Keep `content_template()` only when it stays in sync with `render()`; otherwise omit it and let Elementor render through PHP.

## 3. Security rules

Treat widget settings as untrusted input: any user who can edit a page with Elementor (down to Contributor if the role is allowed) controls them, and they are stored and replayed to every visitor.

**Output**
- Escape at the point of output, always: `esc_html`, `esc_attr`, `esc_url`, `esc_textarea`; rich text through `wp_kses_post()`.
- Tag names from a control go through `Utils::validate_html_tag()`. A raw `<{$settings['tag']}>` is the classic stored XSS of Elementor add-ons.
- URL controls go through `add_link_attributes()`; never concatenate `$settings['link']['url']` or its `custom_attributes` into markup.
- Icons through `\Elementor\Icons_Manager::render_icon()`. Do not enable SVG uploads without a sanitiser.
- In `content_template()` use `{{ }}` (escaped). `{{{ }}}` only for values already sanitised, and validate tags with `elementor.helpers.validateHTMLTag()`.
- Data for JS: `wp_add_inline_script` / `wp_localize_script`, or a `data-*` attribute with `esc_attr( wp_json_encode( $data ) )`. If JSON must go inside `<script>`, encode with `JSON_HEX_TAG | JSON_HEX_AMP`.
- No widget offers free HTML, JS, PHP or shortcode execution. No `eval`, `unserialize` of stored input, `extract`, or `include` of a path built from a setting.
- A `phpcs:ignore` on an escaping rule needs a comment proving why the value is safe. Cached HTML fragments must be built only from already-escaped output.

**Input, requests and data**
- Every state-changing request: nonce (`check_ajax_referer` / `wp_verify_nonce` on `sanitize_text_field( wp_unslash( ... ) )`) **and** `current_user_can()`. A nonce is CSRF protection, not authorisation. `save_post` handlers also check the nonce, autosave and `edit_post` for that ID.
- Public endpoints (`wp_ajax_nopriv_*`, REST) get no authorisation from a nonce: validate and whitelist every parameter, never accept raw `WP_Query` args from the client, force `post_status => 'publish'`, exclude password-protected posts, cap `posts_per_page`.
- `register_rest_route` always has a real `permission_callback`; `__return_true` only for deliberately public, read-only data, with `args` schemas (`sanitize_callback`, `validate_callback`).
- SQL only through `$wpdb->prepare()` or the `$wpdb->insert/update/delete` helpers; `esc_like()` for LIKE.
- `wp_unslash()` before sanitising any superglobal. Sanitise by type (`absint`, `sanitize_email`, `sanitize_key`, `esc_url_raw`, `sanitize_textarea_field`).
- Redirects: `wp_safe_redirect()` + `exit`. The 301 map for the old site is a fixed table (old path to new path), never a destination taken from the request.
- Downloads are media attachments linked by URL. Never write a "download.php?file=" style endpoint.
- Uploads from visitors are off by default. If one is ever needed: `wp_handle_upload` with an explicit `mimes` list, `wp_check_filetype_and_ext`, size limit, capability or strong anti-abuse, and store outside public listing.

**Forms, personal data and payments** (this is a health-related association; treat membership and contact data as sensitive under GDPR/LOPDGDD)
- Forms: visible labels, honeypot plus a time-trap or Turnstile (no cognitive captcha), nonce, server-side validation with clear Spanish errors, per-IP throttling that tolerates shared IPs (day centres and residences share one). Consent checkbox with link to the privacy policy, never pre-ticked.
- Mail: build headers from sanitised values only, no user-supplied newlines; `Reply-To` set with `sanitize_email()`.
- Collect the minimum. Do not put diagnosis or health details in a required field. Do not store IBANs in plain post meta or send them by email; SEPA mandates need a decision with the client on storage and retention (open question).
- Card data never touches this server: Stripe Checkout / Redsys hosted page only. Verify Stripe webhook signatures and the Redsys HMAC with `hash_equals()`. Mark a donation as paid only from the verified server-to-server notification, never from the return URL.
- Secrets (Stripe, Redsys, Turnstile, SMTP) are constants in `wp-config.php` or environment variables, never committed, never in a widget control, never printed to the front end.
- No database dumps, logs or exports under `wp-content/uploads/`. The stack is nginx, so `.htaccess` "deny from all" protects nothing. Backups are `ddev snapshot` / `ddev export-db` locally and the host's tool in production.

**Hardening that fits this stack**
- Do not ship a hand-written CSP with `'unsafe-inline' 'unsafe-eval'`; Elementor needs inline styles, so a CSP has to be designed and tested as its own task. Safe headers: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `frame-ancestors 'self'`, a minimal `Permissions-Policy`. `X-XSS-Protection` is obsolete, do not add it.
- Login throttling by `REMOTE_ADDR` locks out everyone behind a proxy or CDN; use a maintained plugin or the host's WAF instead of home-made code.
- No self-hosted plugin update channel: updates come from the repo/deploy, not from an unsigned JSON fetched over HTTP.
- Self-host fonts and avoid third-party embeds before consent (Google Fonts, Maps, YouTube load only after cookie consent or as a click-to-load facade).
- Third-party plugins: as few as possible, actively maintained, checked for known vulnerabilities before installing. Remove Hello Dolly and unused default themes before launch.

Before finishing any PHP change, run through: escaped output, validated tags and URLs, nonce + capability, prepared SQL, no secrets, Spanish i18n. Run `ddev composer exec phpcs` if WPCS is configured for the package.

## 4. Design

Accessibility for elderly people with tremor, rigidity, low vision and cognitive decline is the brief. Distinctiveness is welcome only inside it.

- Start from tokens in the theme (`:root` custom properties): colour pairs with measured contrast (4.5:1 minimum, 7:1 for body text where possible), a type scale with an 18-20 px base and line-height of 1.5 or more, spacing, radius, focus ring. Brand colours and fonts are unconfirmed: use named placeholder tokens and say so.
- One or two typefaces chosen for legibility at large sizes (open counters, clear l/I/1 and a/o distinction), self-hosted. Lines under about 75 characters. No all-caps labels, no text in images, no justified text, no light grey on white.
- Layout is calm and predictable: one main action per section, the same position for navigation and phone number on every page, targets of 48 px with space between them, nothing that needs hover, drag or precise pointing.
- Motion: none that starts by itself. Transitions only as feedback to an action and disabled under `prefers-reduced-motion`.
- Avoid the generic look (identical rounded cards with soft shadows, gradient washes, cream background with terracotta accent, eyebrow labels over every heading, numbered markers on content that is not a sequence). Make it specific to this place instead: real photos of the centre and its people, the Bahía de Cádiz, plain warm language.
- Spend the boldness in one place per page and keep the rest quiet.
- Copy in plain Spanish, usted or a consistent warm tú as the client prefers (ask), short sentences, active voice. Buttons say what happens ("Llamar al 956 59 19 28", "Hacerme socio"). Errors say what went wrong and how to fix it.
- Check the result in the browser at 320 px, at 200 % zoom and with keyboard only, and take a screenshot to review before calling a section done.

## 5. Editing Elementor content from the command line

Local only, through DDEV. Do not run these against production from here, and do not ask for, type or store WordPress passwords: use `ddev wp` or the user's own logged-in browser session.

```bash
ddev snapshot                                   # before any direct data change
ddev wp plugin status elementor
ddev wp post list --post_type=page --meta_key=_elementor_edit_mode --meta_value=builder --fields=ID,post_title,post_name,post_status
ddev wp post meta get <ID> _elementor_data      # inspect one page's JSON
ddev wp elementor flush-css                     # after any change to Elementor data
```

- Editor URL: `https://parkinsonbahiadecadiz.ddev.site/wp-admin/post.php?post=<ID>&action=elementor`.
- Structural or visual changes go through the editor (or through code, by changing the widget). Direct edits to `_elementor_data` are for text or URL fixes only.
- `wp search-replace` over `wp_postmeta` touches every page, template and global widget at once. Always run it with `--dry-run` first, check the count, keep it to `--include-columns=meta_value --precise`, and prefer changing a single post's meta when only one page should change.
- When copying `_elementor_data` between posts, go through PHP (`ddev wp eval` with `get_post_meta` and `update_post_meta( $id, '_elementor_data', wp_slash( $json ) )`), not through a shell variable: the shell round-trip strips the escaping and corrupts the JSON.
- If the `elementor` CLI command is missing, regenerate CSS from Elementor > Herramientas instead of deleting options by hand.

## 6. Definition of done for a section

1. A widget in the plugin, PSR-4 class, registered in our category, with Spanish labels and defaults.
2. BEM stylesheet loaded only with the widget, built on the theme tokens.
3. Security checklist of section 3 passed.
4. Performance rules of `CLAUDE.md` ("Performance and PageSpeed") followed and Lighthouse mobile checked: per-widget assets only, images through `wp_get_attachment_image()`, no layout shift, no new render-blocking resource.
5. Keyboard, zoom, contrast and screen-reader check passed; headings in order; meaningful alt text.
6. Editable in Elementor without breaking: empty fields render nothing instead of empty tags.
7. `CLAUDE.md` or `docs/` updated if a decision or an open question changed.
