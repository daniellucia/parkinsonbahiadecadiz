<?php
/**
 * Tipo de contenido «Financiador»: entidades que apoyan a la asociación.
 *
 * Título = nombre (también es el texto alternativo del logotipo), imagen
 * destacada = logotipo, campo «Página web», orden = campo «Orden». Se
 * muestran en la banda «Con el apoyo de» del pie de todas las páginas.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Financiador extends PostType {

	public const POST_TYPE = 'pbc_financiador';

	public const META_URL = '_pbc_url';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre de la entidad (por ejemplo: Diputación de Cádiz)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Financiador', 'parkinson-bahia-core' ),
				__( 'Financiadores', 'parkinson-bahia-core' ),
				__( 'Añadir financiador', 'parkinson-bahia-core' )
			),
			[ 'title', 'thumbnail', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_URL => [
				'label'       => __( 'Página web', 'parkinson-bahia-core' ),
				'type'        => 'url',
				'description' => __( 'Opcional. El logotipo se elige en «Imagen destacada»; sin logotipo se muestra el nombre.', 'parkinson-bahia-core' ),
			],
		];
	}
}
