<?php
/**
 * Aplica las redirecciones en la web pública.
 *
 * Orden: 1) regla exacta para la ruta (o para la ruta sin «/page/2», «/feed»…),
 * aunque la página exista en la web nueva; 2) si la página no existe, regla
 * «y todo lo que cuelga» más larga. Así una regla general como «/category/*»
 * nunca tapa una categoría nueva que sí existe.
 *
 * Si el ajuste «Preferir entradas con el mismo nombre» está activo y una regla
 * exacta apunta una ruta de un solo tramo («/mi-noticia»), pero ya hay una
 * entrada publicada con ese slug, se lleva a la entrada: así, al importar una
 * noticia antigua, su dirección vieja la encuentra sola.
 *
 * El destino nunca sale de la petición: es siempre el que se guardó en la tabla.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Redirector {

	/** Se ha respondido 410 en esta petición: no es un 404 que apuntar. */
	private bool $gone = false;

	public function __construct(
		private readonly Repository $rules,
		private readonly Settings $settings,
		private readonly NotFoundLog $not_found
	) {}

	public function register(): void {
		// Antes de redirect_canonical (10), que intenta adivinar adónde llevar un 404.
		add_action( 'template_redirect', [ $this, 'maybe_redirect' ], 1 );
		// Al final: solo llegan aquí los 404 que nada ha resuelto.
		add_action( 'template_redirect', [ $this, 'maybe_log' ], 9999 );
	}

	public function maybe_redirect(): void {
		if ( ! $this->is_public_get() ) {
			return;
		}

		$path = self::request_path();
		if ( '' === $path || '/' === $path ) {
			return;
		}

		$result = $this->resolve( $path, is_404() );
		if ( null === $result ) {
			return;
		}

		[ $rule, $url ] = $result;

		$this->rules->hit( $rule->id );

		if ( $rule->is_gone() ) {
			global $wp_query;
			$this->gone = true;
			$wp_query->set_404();
			status_header( 410 );
			nocache_headers();
			return;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( is_string( $host ) && ! Path::is_local( $url ) ) {
			add_filter( 'allowed_redirect_hosts', static fn ( array $hosts ): array => [ ...$hosts, $host ] );
		}

		if ( wp_safe_redirect( $url, $rule->code, 'Redirecciones' ) ) {
			exit;
		}
	}

	/**
	 * Regla que se aplicaría a una ruta y la dirección de destino.
	 *
	 * @param string $path      Ruta normalizada.
	 * @param bool   $not_found Si la ruta no existe en la web nueva (activa las reglas generales).
	 * @return array{0: Rule, 1: string}|null Regla y destino (vacío si es un 410).
	 */
	public function resolve( string $path, bool $not_found ): ?array {
		$rule = $this->rules->match_exact( Path::candidates( $path ) );

		if ( null === $rule && $not_found ) {
			$rule = $this->rules->match_prefix( $path );
		}

		if ( null === $rule ) {
			return null;
		}

		if ( $rule->is_gone() ) {
			return [ $rule, '' ];
		}

		$url = $this->same_name_post( $rule ) ?? Target::url( $rule );

		// Destino despublicado o borrado, o que vuelve a la misma ruta: no se hace nada.
		if ( null === $url || ( Path::is_local( $url ) && Path::normalize( $url ) === $path ) ) {
			return null;
		}

		return [ $rule, $url ];
	}

	private function same_name_post( Rule $rule ): ?string {
		if ( $rule->is_prefix() || ! Path::is_single_segment( $rule->source ) || ! $this->settings->prefer_posts() ) {
			return null;
		}

		$posts = get_posts(
			[
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'name'                   => Path::slug( $rule->source ),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false,
			]
		);

		return $posts ? ( get_permalink( $posts[0] ) ?: null ) : null;
	}

	public function maybe_log(): void {
		if ( ! $this->gone && is_404() && $this->is_public_get() && $this->settings->log_404() ) {
			$this->not_found->record( self::request_path() );
		}
	}

	private function is_public_get(): bool {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

		return in_array( $method, [ 'GET', 'HEAD' ], true )
			&& ! is_admin()
			&& ! wp_doing_ajax()
			&& ! wp_doing_cron()
			&& ! is_preview()
			&& ! is_customize_preview()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	public static function request_path(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Path::normalize() la limpia; solo se usa para buscar con marcadores.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		return Path::normalize( $uri );
	}
}
