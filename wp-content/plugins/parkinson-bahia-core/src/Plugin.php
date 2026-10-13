<?php
/**
 * Punto de entrada del plugin: registra cada módulo.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core;

use DanielLucia\ParkinsonBahia\Core\Admin\Menu;
use DanielLucia\ParkinsonBahia\Core\Cli\SeedCommand;
use DanielLucia\ParkinsonBahia\Core\Elementor\Integration;
use DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Tipos de contenido del sitio.
	 *
	 * @return PostTypes\PostType[]
	 */
	public static function post_types(): array {
		return [
			new PostTypes\Terapia(),
			new PostTypes\Hito(),
			new PostTypes\Puesto(),
			new PostTypes\Directivo(),
			new PostTypes\Documento(),
			new PostTypes\Entidad(),
		];
	}

	public function register(): void {
		foreach ( self::post_types() as $post_type ) {
			$post_type->register();
		}

		( new Integration() )->register();

		if ( is_admin() ) {
			( new Menu() )->register();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$seed = new SeedCommand();
			\WP_CLI::add_command( 'pbc seed-home', [ $seed, 'home' ] );
			\WP_CLI::add_command( 'pbc seed-asociacion', [ $seed, 'asociacion' ] );
		}
	}
}
