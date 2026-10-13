<?php
/**
 * Tipo de contenido «Puesto»: un perfil del equipo profesional y cuántas personas lo ocupan.
 *
 * Título = puesto (por ejemplo «Fisioterapeuta»), campo «Número de personas»,
 * orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Puesto extends PostType {

	public const POST_TYPE = 'pbc_puesto';

	public const META_COUNT = '_pbc_count';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Puesto (por ejemplo: Fisioterapeuta)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Puesto del equipo', 'parkinson-bahia-core' ),
				__( 'Equipo profesional', 'parkinson-bahia-core' ),
				__( 'Añadir puesto', 'parkinson-bahia-core' )
			),
			[ 'title', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_COUNT => [
				'label'       => __( 'Número de personas', 'parkinson-bahia-core' ),
				'type'        => 'number',
				'description' => __( 'Cuántas personas del equipo tienen este puesto.', 'parkinson-bahia-core' ),
			],
		];
	}
}
