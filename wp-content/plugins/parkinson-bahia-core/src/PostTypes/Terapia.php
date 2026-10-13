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

final class Terapia {

	public const POST_TYPE = 'pbc_terapia';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'        => [
					'name'               => __( 'Terapias', 'parkinson-bahia-core' ),
					'singular_name'      => __( 'Terapia', 'parkinson-bahia-core' ),
					'add_new'            => __( 'Añadir terapia', 'parkinson-bahia-core' ),
					'add_new_item'       => __( 'Añadir terapia', 'parkinson-bahia-core' ),
					'edit_item'          => __( 'Editar terapia', 'parkinson-bahia-core' ),
					'new_item'           => __( 'Nueva terapia', 'parkinson-bahia-core' ),
					'view_item'          => __( 'Ver terapia', 'parkinson-bahia-core' ),
					'search_items'       => __( 'Buscar terapias', 'parkinson-bahia-core' ),
					'not_found'          => __( 'No hay terapias.', 'parkinson-bahia-core' ),
					'not_found_in_trash' => __( 'No hay terapias en la papelera.', 'parkinson-bahia-core' ),
					'all_items'          => __( 'Todas las terapias', 'parkinson-bahia-core' ),
					'menu_name'          => __( 'Terapias', 'parkinson-bahia-core' ),
				],
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
			]
		);
	}
}
