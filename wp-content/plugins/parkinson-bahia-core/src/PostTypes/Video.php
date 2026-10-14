<?php
/**
 * Tipo de contenido «Vídeo»: ejercicios para hacer en casa, publicados en YouTube.
 *
 * Título = nombre del vídeo, imagen destacada = miniatura (se sirve desde
 * esta web: YouTube solo se carga cuando la persona pulsa el vídeo),
 * campo «Dirección en YouTube», orden = campo «Orden» (menu_order).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Video extends PostType {

	public const POST_TYPE = 'pbc_video';

	public const META_URL = '_pbc_youtube';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function admin_menu(): string {
		return self::ADMIN_MENU_FAMILIES;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre del vídeo (por ejemplo: Ejercicios de equilibrio)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Vídeo', 'parkinson-bahia-core' ),
				__( 'Vídeos de ejercicios', 'parkinson-bahia-core' ),
				__( 'Añadir vídeo', 'parkinson-bahia-core' )
			),
			[ 'title', 'thumbnail', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_URL => [
				'label'       => __( 'Dirección en YouTube', 'parkinson-bahia-core' ),
				'type'        => 'url',
				'description' => __( 'Copia la dirección del vídeo, por ejemplo https://www.youtube.com/watch?v=… La miniatura se elige en «Imagen destacada».', 'parkinson-bahia-core' ),
			],
		];
	}

	/**
	 * Identificador de YouTube (11 caracteres) a partir de la dirección guardada.
	 */
	public static function youtube_id( int $post_id ): string {
		$url   = (string) get_post_meta( $post_id, self::META_URL, true );
		$parts = wp_parse_url( $url );
		$host  = strtolower( (string) ( $parts['host'] ?? '' ) );
		$path  = (string) ( $parts['path'] ?? '' );
		$id    = '';

		if ( 'youtu.be' === $host ) {
			$id = trim( $path, '/' );
		} elseif ( in_array( $host, [ 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com' ], true ) ) {
			if ( preg_match( '#^/(?:embed|shorts|live)/([^/]+)#', $path, $match ) ) {
				$id = $match[1];
			} else {
				parse_str( (string) ( $parts['query'] ?? '' ), $query );
				$id = is_string( $query['v'] ?? null ) ? $query['v'] : '';
			}
		}

		return preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? $id : '';
	}
}
