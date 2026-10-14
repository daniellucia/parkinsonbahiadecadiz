<?php
/**
 * Convierte el destino guardado en una dirección. Si el destino es una página
 * o un archivo de la web se guarda su ID, así que cambiar su slug no rompe la
 * redirección; si se despublica o se borra, la redirección deja de actuar.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Target {

	public static function url( Rule $rule ): ?string {
		$url = null;

		if ( $rule->target_post > 0 ) {
			$post = get_post( $rule->target_post );

			if ( $post instanceof \WP_Post ) {
				if ( 'attachment' === $post->post_type ) {
					$url = wp_get_attachment_url( $post->ID ) ?: null;
				} elseif ( 'publish' === $post->post_status ) {
					$url = get_permalink( $post ) ?: null;
				}
			}
		} elseif ( '' !== $rule->target_url ) {
			$url = str_starts_with( $rule->target_url, '/' ) && ! str_starts_with( $rule->target_url, '//' )
				? home_url( $rule->target_url )
				: $rule->target_url;
		}

		if ( null === $url ) {
			return null;
		}

		if ( '' !== $rule->anchor ) {
			$url = strtok( $url, '#' ) . '#' . $rule->anchor;
		}

		return $url;
	}

	/**
	 * Texto corto para la lista del administrador.
	 */
	public static function label( Rule $rule ): string {
		if ( $rule->is_gone() ) {
			return __( 'Ya no existe (410)', 'parkinson-redirects' );
		}

		$url = self::url( $rule );
		if ( null === $url ) {
			return '';
		}

		return Path::is_local( $url ) ? (string) preg_replace( '#^https?://[^/]+#i', '', $url ) : $url;
	}

	public static function sanitize_anchor( string $anchor ): string {
		return substr( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', ltrim( trim( $anchor ), '#' ) ), 0, 100 );
	}

	/**
	 * Enlace personalizado: ruta de la web («/contacto/») o dirección http(s).
	 */
	public static function sanitize_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		if ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) {
			$full = esc_url_raw( home_url( $url ) );
			$home = untrailingslashit( home_url() );

			return '' !== $full && str_starts_with( $full, $home ) ? substr( '/' . ltrim( substr( $full, strlen( $home ) ), '/' ), 0, 2000 ) : '';
		}

		return substr( esc_url_raw( $url, [ 'http', 'https' ] ), 0, 2000 );
	}
}
