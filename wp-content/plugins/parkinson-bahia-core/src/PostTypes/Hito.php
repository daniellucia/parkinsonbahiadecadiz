<?php
/**
 * Tipo de contenido «Hito»: un momento de la historia de la asociación.
 *
 * Título = titular del hito, extracto = explicación, campo «Año».
 * Se ordenan solos por año.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Hito extends PostType {

	public const POST_TYPE = 'pbc_hito';

	public const META_YEAR = '_pbc_year';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Qué pasó (por ejemplo: Abre el centro de día)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Hito', 'parkinson-bahia-core' ),
				__( 'Historia', 'parkinson-bahia-core' ),
				__( 'Añadir hito', 'parkinson-bahia-core' )
			),
			[ 'title', 'excerpt' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_YEAR => [
				'label'       => __( 'Año', 'parkinson-bahia-core' ),
				'type'        => 'number',
				'description' => __( 'Por ejemplo: 2003. Los hitos se ordenan por año.', 'parkinson-bahia-core' ),
			],
		];
	}
}
