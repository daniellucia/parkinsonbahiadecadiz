<?php
/**
 * Tipo de contenido «Terapia»: cada terapia o servicio tiene su propia página.
 *
 * Título = nombre, extracto = resumen corto para los listados, contenido = ficha,
 * imagen destacada = foto, orden = campo «Orden» (menu_order). Campos extra:
 * «Qué trabajamos» (un punto por línea) y «Quién la hace».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Terapia extends PostType {

	public const POST_TYPE = 'pbc_terapia';

	public const META_WORKS = '_pbc_works';

	public const META_WHO = '_pbc_who';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre de la terapia', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return [
			'labels'        => $this->labels(
				__( 'Terapia', 'parkinson-bahia-core' ),
				__( 'Terapias', 'parkinson-bahia-core' ),
				__( 'Añadir terapia', 'parkinson-bahia-core' )
			),
			'public'        => true,
			'has_archive'   => false,
			'show_in_rest'  => true,
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-heart',
			'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ],
			'rewrite'       => [
				'slug'       => 'terapias',
				'with_front' => false,
			],
		];
	}

	protected function fields(): array {
		return [
			self::META_WORKS => [
				'label'       => __( 'Qué trabajamos', 'parkinson-bahia-core' ),
				'type'        => 'textarea',
				'description' => __( 'Un punto por línea, por ejemplo: «Equilibrio y prevención de caídas».', 'parkinson-bahia-core' ),
			],
			self::META_WHO   => [
				'label'       => __( 'Quién la hace', 'parkinson-bahia-core' ),
				'type'        => 'text',
				'description' => __( 'Por ejemplo: «Fisioterapeuta, en sesiones individuales y en grupo».', 'parkinson-bahia-core' ),
			],
		];
	}

	/**
	 * Puntos de «Qué trabajamos» de una terapia.
	 *
	 * @return string[]
	 */
	public static function works( int $post_id ): array {
		$lines = preg_split( '/\R/', (string) get_post_meta( $post_id, self::META_WORKS, true ) );

		return array_values( array_filter( array_map( 'trim', is_array( $lines ) ? $lines : [] ) ) );
	}
}
