<?php
/**
 * Tipo de contenido «Entidad»: una federación o red de la que forma parte la asociación.
 *
 * Título = nombre completo, campos «Siglas» y «Página web», orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Entidad extends PostType {

	public const POST_TYPE = 'pbc_entidad';

	public const META_SHORT = '_pbc_short';
	public const META_URL   = '_pbc_url';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre completo de la entidad', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Entidad', 'parkinson-bahia-core' ),
				__( 'Redes y federaciones', 'parkinson-bahia-core' ),
				__( 'Añadir entidad', 'parkinson-bahia-core' )
			),
			[ 'title', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_SHORT => [
				'label' => __( 'Siglas', 'parkinson-bahia-core' ),
				'type'  => 'text',
			],
			self::META_URL   => [
				'label'       => __( 'Página web', 'parkinson-bahia-core' ),
				'type'        => 'url',
				'description' => __( 'Opcional. Si se rellena, la entidad enlaza a su web.', 'parkinson-bahia-core' ),
			],
		];
	}
}
