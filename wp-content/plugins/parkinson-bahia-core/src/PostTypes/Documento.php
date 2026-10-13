<?php
/**
 * Tipo de contenido «Documento»: un documento de transparencia descargable.
 *
 * Título = nombre del documento, campo «Archivo» (de la biblioteca de medios),
 * orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Documento extends PostType {

	public const POST_TYPE = 'pbc_documento';

	public const META_FILE = '_pbc_file';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre del documento (por ejemplo: Memoria de actividades 2025)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Documento', 'parkinson-bahia-core' ),
				__( 'Documentos de transparencia', 'parkinson-bahia-core' ),
				__( 'Añadir documento', 'parkinson-bahia-core' )
			),
			[ 'title', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_FILE => [
				'label'       => __( 'Archivo', 'parkinson-bahia-core' ),
				'type'        => 'file',
				'description' => __( 'Normalmente un PDF. Sin archivo, el documento aparece en la lista como «Próximamente».', 'parkinson-bahia-core' ),
			],
		];
	}
}
