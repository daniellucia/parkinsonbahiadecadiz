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

defined( 'ABSPATH' ) || exit;

final class Menu {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function add_menu(): void {
		// Sin pantalla propia: WordPress enlaza el menú con su primer submenú.
		add_menu_page(
			__( 'La asociación', 'parkinson-bahia-core' ),
			__( 'La asociación', 'parkinson-bahia-core' ),
			'edit_posts',
			PostType::ADMIN_MENU,
			'',
			'dashicons-groups',
			22
		);
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
