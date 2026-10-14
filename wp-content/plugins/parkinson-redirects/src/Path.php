<?php
/**
 * Normaliza rutas para compararlas: sin dominio, sin parámetros, decodificada,
 * en minúsculas y sin barra final. «/Hazte-Socio/?x=1» y
 * «https://www.ejemplo.org/hazte-socio» son la misma ruta: «/hazte-socio».
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Path {

	public const MAX = 255;

	/**
	 * Sufijos que WordPress añade a una dirección (paginación, feed…). Una regla
	 * para «/category/blog» vale también para «/category/blog/page/3».
	 */
	private const SUFFIXES = '#/(?:page/\d+|feed(?:/(?:rss2?|atom|rdf))?|amp|embed|trackback|comment-page-\d+)$#';

	public static function normalize( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		// «ejemplo.org/ruta» sin protocolo: se trata como dirección completa.
		if ( ! str_starts_with( $raw, '/' ) && ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $raw ) && preg_match( '#^[^/]+\.[a-z]{2,}(/|$)#i', $raw ) ) {
			$raw = '//' . $raw;
		}

		$path = wp_parse_url( $raw, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return '';
		}

		$path = wp_check_invalid_utf8( rawurldecode( $path ), true );
		$path = (string) preg_replace( '/[\x00-\x1F\x7F]/', '', $path );
		$path = (string) preg_replace( '#/+#', '/', '/' . ltrim( $path, '/' ) );

		$home = self::home_path();
		if ( '' !== $home && ( $path === $home || str_starts_with( $path, $home . '/' ) ) ) {
			$path = '/' . ltrim( substr( $path, strlen( $home ) ), '/' );
		}

		$path = '/' === $path ? '/' : rtrim( $path, '/' );

		return mb_substr( mb_strtolower( $path, 'UTF-8' ), 0, self::MAX );
	}

	/**
	 * La ruta y sus versiones sin sufijos, de la más concreta a la más general.
	 *
	 * @return string[]
	 */
	public static function candidates( string $path ): array {
		$list = [ $path ];

		while ( preg_match( self::SUFFIXES, $path ) ) {
			$path = (string) preg_replace( self::SUFFIXES, '', $path );
			if ( '' === $path ) {
				break;
			}
			$list[] = $path;
		}

		return array_values( array_unique( array_filter( $list, static fn ( string $p ): bool => '/' !== $p ) ) );
	}

	public static function is_single_segment( string $path ): bool {
		return 1 === preg_match( '#^/[^/]+$#', $path );
	}

	/**
	 * Último tramo de la ruta, tal como lo guarda WordPress en «post_name».
	 */
	public static function slug( string $path ): string {
		return sanitize_title( basename( $path ) );
	}

	/**
	 * ¿La dirección es de esta web? Se ignora «www.».
	 */
	public static function is_local( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' );
		}

		return self::bare_host( $host ) === self::bare_host( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	private static function bare_host( string $host ): string {
		return (string) preg_replace( '/^www\./', '', strtolower( $host ) );
	}

	private static function home_path(): string {
		$path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );

		return is_string( $path ) ? rtrim( mb_strtolower( $path, 'UTF-8' ), '/' ) : '';
	}
}
