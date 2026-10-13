<?php
/**
 * Sirve /llms.txt, /llms-full.txt y las versiones «.md» de cada contenido.
 *
 * No escribe ficheros en disco ni añade reglas de reescritura: responde
 * directamente a esas direcciones, así no hay que guardar enlaces permanentes
 * ni dar permisos de escritura.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

defined( 'ABSPATH' ) || exit;

final class Router {

	public function __construct(
		private readonly Settings $settings,
		private readonly Generator $generator
	) {}

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_serve' ], 0 );
		add_action( 'wp_head', [ $this, 'alternate_link' ] );
		add_filter( 'redirect_canonical', [ $this, 'keep_file_urls' ], 10, 2 );
	}

	/**
	 * WordPress no debe añadir una barra final a estas direcciones.
	 *
	 * @param string|false $redirect_url Destino propuesto.
	 * @return string|false
	 */
	public function keep_file_urls( $redirect_url, string $requested_url ) {
		$path = (string) wp_parse_url( $requested_url, PHP_URL_PATH );

		return preg_match( '/(llms(-full)?\.txt|\.md)$/', $path ) ? false : $redirect_url;
	}

	public function maybe_serve(): void {
		$settings = $this->settings->all();

		if ( ! $settings['enabled'] ) {
			return;
		}

		$path = $this->request_path();

		if ( '/llms.txt' === $path ) {
			$this->send( $this->generator->llms_txt() );
		}

		if ( '/llms-full.txt' === $path && $settings['full'] ) {
			$this->send( $this->generator->llms_full() );
		}

		if ( $settings['markdown'] && str_ends_with( $path, '.md' ) ) {
			$post_id = $this->post_id_from_markdown_path( $path );
			$content = $post_id > 0 ? $this->generator->post_markdown( $post_id ) : null;

			if ( null !== $content ) {
				$this->send( $content );
			}
		}
	}

	/**
	 * Anuncia en cada página su versión en Markdown.
	 */
	public function alternate_link(): void {
		$settings = $this->settings->all();

		if ( ! $settings['enabled'] || ! $settings['markdown'] || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if ( $post instanceof \WP_Post && $this->generator->is_listable( $post ) ) {
			printf( '<link rel="alternate" type="text/markdown" href="%s">' . "\n", esc_url( $this->generator->markdown_url( $post ) ) );
		}
	}

	/**
	 * Ruta pedida, relativa a la dirección del sitio y sin parámetros.
	 */
	private function request_path(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

		if ( '' !== $home && str_starts_with( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}

		return '/' . ltrim( rawurldecode( $path ), '/' );
	}

	private function post_id_from_markdown_path( string $path ): int {
		// «/pagina/index.html.md» y «/pagina.md» apuntan a «/pagina/».
		$clean = (string) preg_replace( '#(/index\.html\.md|/index\.md|\.md)$#', '', $path );

		if ( '' === $clean || '/' === $clean ) {
			return (int) get_option( 'page_on_front' );
		}

		return url_to_postid( home_url( user_trailingslashit( $clean ) ) );
	}

	/**
	 * Envía texto Markdown y termina la petición.
	 */
	private function send( string $content ): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=utf-8' );
		// Son copias del contenido para asistentes: no deben competir con las páginas en los buscadores.
		header( 'X-Robots-Tag: noindex, follow' );
		header( 'X-Content-Type-Options: nosniff' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Texto plano servido como text/markdown, no HTML.
		exit;
	}
}
