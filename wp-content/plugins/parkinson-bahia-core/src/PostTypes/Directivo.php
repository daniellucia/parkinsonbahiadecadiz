<?php
/**
 * Tipo de contenido «Directivo»: una persona de la junta directiva.
 *
 * Título = nombre y apellidos, campo «Cargo», orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Directivo extends PostType {

	public const POST_TYPE = 'pbc_directivo';

	public const META_ROLE = '_pbc_role';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre y apellidos', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Miembro de la junta', 'parkinson-bahia-core' ),
				__( 'Junta directiva', 'parkinson-bahia-core' ),
				__( 'Añadir miembro', 'parkinson-bahia-core' )
			),
			[ 'title', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_ROLE => [
				'label'       => __( 'Cargo', 'parkinson-bahia-core' ),
				'type'        => 'text',
				'description' => __( 'Por ejemplo: Presidente, Tesorera, Vocal.', 'parkinson-bahia-core' ),
			],
		];
	}
}
