<?php
/**
 * Tipo de contenido «Aparición en prensa»: noticias de otros medios sobre la asociación.
 *
 * Título = titular, campos: medio, fecha de publicación y enlace. Se muestran
 * en Actualidad («En los medios»), de la más reciente a la más antigua.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Prensa extends PostType {

	public const POST_TYPE = 'pbc_prensa';

	public const META_MEDIA = '_pbc_media';
	public const META_DATE  = '_pbc_date';
	public const META_URL   = '_pbc_url';

	public function name(): string {
		return self::POST_TYPE;
	}

	/**
	 * Junto a las noticias, en el menú «Entradas».
	 */
	protected function admin_menu(): string {
		return 'edit.php';
	}

	protected function title_placeholder(): string {
		return __( 'Titular tal como lo publicó el medio', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Aparición en prensa', 'parkinson-bahia-core' ),
				__( 'En los medios', 'parkinson-bahia-core' ),
				__( 'Añadir aparición en prensa', 'parkinson-bahia-core' )
			),
			[ 'title' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_MEDIA => [
				'label'       => __( 'Medio', 'parkinson-bahia-core' ),
				'type'        => 'text',
				'description' => __( 'Por ejemplo: Diario de Cádiz.', 'parkinson-bahia-core' ),
			],
			self::META_DATE  => [
				'label'       => __( 'Fecha de publicación', 'parkinson-bahia-core' ),
				'type'        => 'date',
				'description' => __( 'Ordena la lista: las más recientes van primero.', 'parkinson-bahia-core' ),
			],
			self::META_URL   => [
				'label'       => __( 'Enlace a la noticia', 'parkinson-bahia-core' ),
				'type'        => 'url',
				'description' => __( 'Sin enlace, la aparición no se muestra.', 'parkinson-bahia-core' ),
			],
		];
	}
}
