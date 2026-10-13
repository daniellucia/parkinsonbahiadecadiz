<?php
/**
 * Menú «La asociación» del escritorio: agrupa los tipos de contenido que solo
 * alimentan listados, y carga el selector de archivos donde hace falta.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Admin;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Documento;
use DanielLucia\ParkinsonBahia\Core\PostTypes\PostType;
use DanielLucia\ParkinsonBahia\Core\PostTypes\Pregunta;

defined( 'ABSPATH' ) || exit;

final class Menu {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'parent_file', [ $this, 'highlight_taxonomy_menu' ] );
	}

	public function add_menu(): void {
		// El identificador es la URL de un listado real (ver PostType::ADMIN_MENU).
		add_menu_page(
			__( 'La asociación', 'parkinson-bahia-core' ),
			__( 'La asociación', 'parkinson-bahia-core' ),
			'edit_posts',
			PostType::ADMIN_MENU,
			'',
			'dashicons-groups',
			22
		);

		add_menu_page(
			__( 'Centro de día', 'parkinson-bahia-core' ),
			__( 'Centro de día', 'parkinson-bahia-core' ),
			'edit_posts',
			PostType::ADMIN_MENU_CENTRE,
			'',
			'dashicons-building',
			23
		);

		// Las taxonomías no se cuelgan solas de un menú propio. Prioridad 11 en otro gancho
		// para que quede después de los tipos de contenido.
		add_action(
			'admin_menu',
			static function (): void {
				add_submenu_page(
					PostType::ADMIN_MENU_CENTRE,
					__( 'Temas de las preguntas', 'parkinson-bahia-core' ),
					__( 'Temas de las preguntas', 'parkinson-bahia-core' ),
					'manage_categories',
					'edit-tags.php?taxonomy=' . Pregunta::TAXONOMY . '&post_type=' . Pregunta::POST_TYPE
				);
			},
			11
		);
	}

	/**
	 * Mantiene abierto el menú «Centro de día» al editar los temas de las preguntas.
	 */
	public function highlight_taxonomy_menu( string $parent_file ): string {
		$screen = get_current_screen();

		return $screen && Pregunta::TAXONOMY === $screen->taxonomy ? PostType::ADMIN_MENU_CENTRE : $parent_file;
	}

	public function enqueue(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base || Documento::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'pbc-admin-file-field',
			PBC_CORE_URL . 'assets/js/admin-file-field.js',
			[],
			(string) filemtime( PBC_CORE_DIR . 'assets/js/admin-file-field.js' ),
			true
		);
		wp_localize_script(
			'pbc-admin-file-field',
			'pbcFileField',
			[
				'title'  => __( 'Elegir archivo', 'parkinson-bahia-core' ),
				'button' => __( 'Usar este archivo', 'parkinson-bahia-core' ),
				'none'   => __( 'Ningún archivo elegido', 'parkinson-bahia-core' ),
			]
		);
	}
}
