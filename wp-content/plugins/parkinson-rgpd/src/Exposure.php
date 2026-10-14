<?php
/**
 * Privacidad por defecto (art. 25 RGPD): la web no recoge ni enseña datos
 * personales que no necesita.
 *
 * - Comentarios y pingbacks cerrados en todo el sitio: guardarían nombre,
 *   correo e IP sin el aviso legal de los formularios.
 * - La API REST no lista los usuarios a quien no ha iniciado sesión.
 * - No hay páginas de autor ni autores en el mapa del sitio, en oEmbed ni en
 *   los datos estructurados de The SEO Framework (publicaban el nombre de
 *   usuario de quien administra la web).
 * - Sin Gravatar: no se envía a Automattic el resumen del correo de nadie.
 *
 * Cada medida se puede apagar con el filtro «parkinson_rgpd/exposure»
 * (claves: comments, users, authors, gravatar).
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Exposure {

	public function register(): void {
		add_action( 'init', [ $this, 'setup' ], 20 );
	}

	/**
	 * Medidas activas.
	 *
	 * @return array<string, bool>
	 */
	public static function measures(): array {
		$defaults = [
			'comments' => true,
			'users'    => true,
			'authors'  => true,
			'gravatar' => true,
		];

		return array_map( 'boolval', array_merge( $defaults, (array) apply_filters( 'parkinson_rgpd/exposure', $defaults ) ) );
	}

	public function setup(): void {
		$measures = self::measures();

		if ( $measures['comments'] ) {
			add_filter( 'comments_open', '__return_false', 20 );
			add_filter( 'pings_open', '__return_false', 20 );
			add_filter( 'comments_array', '__return_empty_array', 20 );
			add_filter( 'feed_links_show_comments_feed', '__return_false' );
			add_action( 'pre_comment_on_post', [ $this, 'refuse_comment' ] );
			add_filter( 'xmlrpc_methods', [ $this, 'remove_pingbacks' ] );
			add_filter( 'wp_headers', [ $this, 'remove_pingback_header' ] );
			add_action( 'admin_menu', [ $this, 'remove_comments_menu' ] );
			add_action( 'admin_bar_menu', [ $this, 'remove_comments_bar' ], 999 );
		}

		if ( $measures['users'] ) {
			add_filter( 'rest_endpoints', [ $this, 'hide_users_endpoint' ] );
		}

		if ( $measures['authors'] ) {
			add_action( 'template_redirect', [ $this, 'no_author_archives' ], 1 );
			add_filter( 'wp_sitemaps_add_provider', [ $this, 'no_users_sitemap' ], 10, 2 );
			add_filter( 'oembed_response_data', [ $this, 'no_oembed_author' ] );
			add_filter( 'the_seo_framework_schema_graph_data', [ $this, 'no_schema_person' ] );
		}

		if ( $measures['gravatar'] ) {
			add_filter( 'pre_option_show_avatars', static fn (): string => '0' );
		}
	}

	public function refuse_comment(): void {
		wp_die(
			esc_html__( 'Esta web no admite comentarios. Si quieres decirnos algo, llámanos o usa el formulario de contacto.', 'parkinson-rgpd' ),
			esc_html__( 'Comentarios cerrados', 'parkinson-rgpd' ),
			[ 'response' => 403 ]
		);
	}

	/**
	 * @param array<string, mixed> $methods Métodos XML-RPC.
	 * @return array<string, mixed>
	 */
	public function remove_pingbacks( array $methods ): array {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	/**
	 * @param array<string, string> $headers Cabeceras HTTP.
	 * @return array<string, string>
	 */
	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	public function remove_comments_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	public function remove_comments_bar( \WP_Admin_Bar $bar ): void {
		$bar->remove_node( 'comments' );
	}

	/**
	 * Quien no puede ver la lista de usuarios en el escritorio tampoco la ve por la API.
	 *
	 * @param array<string, mixed> $endpoints Rutas de la API.
	 * @return array<string, mixed>
	 */
	public function hide_users_endpoint( array $endpoints ): array {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}

		foreach ( array_keys( $endpoints ) as $route ) {
			if ( str_starts_with( (string) $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Las páginas de autor (y «?author=1») muestran el nombre de usuario: no existen.
	 */
	public function no_author_archives(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo se comprueba si existe el parámetro.
		if ( ! is_author() && ! isset( $_GET['author'] ) ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * @param \WP_Sitemaps_Provider|false $provider Proveedor.
	 * @param string                      $name     Nombre del proveedor.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public function no_users_sitemap( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * @param array<string, mixed> $data Datos de oEmbed.
	 * @return array<string, mixed>
	 */
	public function no_oembed_author( array $data ): array {
		unset( $data['author_name'], $data['author_url'] );

		return $data;
	}

	/**
	 * Quita las personas (autores) del grafo de Schema.org y sus referencias.
	 *
	 * @param array<int, mixed> $graph Entidades.
	 * @return array<int, mixed>
	 */
	public function no_schema_person( array $graph ): array {
		$graph = array_values(
			array_filter(
				$graph,
				static fn ( $entity ): bool => ! is_array( $entity ) || 'Person' !== ( $entity['@type'] ?? '' )
			)
		);

		return array_map( [ $this, 'strip_author' ], $graph );
	}

	/**
	 * @param mixed $value Entidad o valor.
	 * @return mixed
	 */
	private function strip_author( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		unset( $value['author'] );

		return array_map( [ $this, 'strip_author' ], $value );
	}
}
