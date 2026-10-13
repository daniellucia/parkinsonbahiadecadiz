<?php
/**
 * Tipo de contenido «Pregunta»: una pregunta frecuente con su respuesta.
 *
 * Título = pregunta, contenido = respuesta, tema = taxonomía para agruparlas
 * por página, orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Pregunta extends PostType {

	public const POST_TYPE = 'pbc_pregunta';

	public const TAXONOMY = 'pbc_tema_pregunta';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function admin_menu(): string {
		return self::ADMIN_MENU_CENTRE;
	}

	protected function title_placeholder(): string {
		return __( 'Escribe la pregunta', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Pregunta', 'parkinson-bahia-core' ),
				__( 'Preguntas frecuentes', 'parkinson-bahia-core' ),
				__( 'Añadir pregunta', 'parkinson-bahia-core' )
			),
			[ 'title', 'editor', 'page-attributes' ]
		);
	}

	public function register_post_type(): void {
		parent::register_post_type();

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'labels'             => [
					'name'          => __( 'Temas de las preguntas', 'parkinson-bahia-core' ),
					'singular_name' => __( 'Tema', 'parkinson-bahia-core' ),
					'menu_name'     => __( 'Temas de las preguntas', 'parkinson-bahia-core' ),
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
