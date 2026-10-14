<?php
/**
 * Tipo de contenido «Recurso»: guías y fichas para descargar (Familias y cuidadores).
 *
 * Título = nombre de la guía, archivo = adjunto de la biblioteca, tema =
 * taxonomía «Temas de los recursos», orden = campo «Orden» (menu_order).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Recurso extends PostType {

	public const POST_TYPE = 'pbc_recurso';

	public const TAXONOMY = 'pbc_tema_recurso';

	public const META_FILE = '_pbc_file';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function admin_menu(): string {
		return self::ADMIN_MENU_FAMILIES;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre de la guía o ficha (por ejemplo: Ejercicios de voz)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Recurso', 'parkinson-bahia-core' ),
				__( 'Guías y fichas', 'parkinson-bahia-core' ),
				__( 'Añadir guía o ficha', 'parkinson-bahia-core' )
			),
			[ 'title', 'page-attributes' ]
		);
	}

	protected function fields(): array {
		return [
			self::META_FILE => [
				'label'       => __( 'Archivo', 'parkinson-bahia-core' ),
				'type'        => 'file',
				'description' => __( 'Normalmente un PDF. Sin archivo, la guía aparece en la lista como «Próximamente».', 'parkinson-bahia-core' ),
			],
		];
	}

	public function register_post_type(): void {
		parent::register_post_type();

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'labels'             => [
					'name'          => __( 'Temas de las guías', 'parkinson-bahia-core' ),
					'singular_name' => __( 'Tema', 'parkinson-bahia-core' ),
					'menu_name'     => __( 'Temas de las guías', 'parkinson-bahia-core' ),
					'add_new_item'  => __( 'Añadir tema', 'parkinson-bahia-core' ),
					'edit_item'     => __( 'Editar tema', 'parkinson-bahia-core' ),
					'search_items'  => __( 'Buscar temas', 'parkinson-bahia-core' ),
					'all_items'     => __( 'Todos los temas', 'parkinson-bahia-core' ),
				],
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'hierarchical'       => true,
				'publicly_queryable' => false,
				'rewrite'            => false,
			]
		);
	}
}
