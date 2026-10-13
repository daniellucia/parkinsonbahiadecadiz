<?php
/**
 * Tipo de contenido «Terapia»: cada terapia o servicio tiene su propia página.
 *
 * Título = nombre, extracto = resumen corto para los listados, contenido = ficha,
 * imagen destacada = foto, orden = campo «Orden» (menu_order).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Terapia extends PostType {

	public const POST_TYPE = 'pbc_terapia';

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
}
