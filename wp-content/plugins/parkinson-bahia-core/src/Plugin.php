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
			new PostTypes\Espacio(),
			new PostTypes\Pregunta(),
			new PostTypes\Recurso(),
			new PostTypes\Video(),
			new PostTypes\Prensa(),
			new PostTypes\Financiador(),
		];
	}

	public function register(): void {
		foreach ( self::post_types() as $post_type ) {
			$post_type->register();
		}

		( new Integration() )->register();
		( new Forms\Submissions() )->register();
		( new Forms\Handler() )->register();
		( new Forms\Privacy() )->register();
		( new Frontend\ContactMap() )->register();
		( new Frontend\Press() )->register();
		( new Frontend\Funders() )->register();
		( new Frontend\CookieServices() )->register();

		if ( is_admin() ) {
			( new Forms\Settings() )->register();
			( new Forms\Crm() )->register();
		}

		if ( is_admin() ) {
			( new Menu() )->register();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$seed = new SeedCommand();
			\WP_CLI::add_command( 'pbc seed-home', [ $seed, 'home' ] );
			\WP_CLI::add_command( 'pbc seed-asociacion', [ $seed, 'asociacion' ] );
			\WP_CLI::add_command( 'pbc seed-centro', [ $seed, 'centro' ] );
			\WP_CLI::add_command( 'pbc seed-colabora', [ $seed, 'colabora' ] );
			\WP_CLI::add_command( 'pbc seed-privacidad', [ $seed, 'privacidad' ] );
			\WP_CLI::add_command( 'pbc seed-terapias', [ $seed, 'terapias' ] );
			\WP_CLI::add_command( 'pbc seed-familias', [ $seed, 'familias' ] );
			\WP_CLI::add_command( 'pbc seed-contacto', [ $seed, 'contacto' ] );
			\WP_CLI::add_command( 'pbc seed-actualidad', [ $seed, 'actualidad' ] );
			\WP_CLI::add_command( 'pbc seed-pie', [ $seed, 'pie' ] );
			\WP_CLI::add_command( 'pbc seed-cookies', [ $seed, 'cookies' ] );
			\WP_CLI::add_command( 'pbc seed-diagnostico', [ $seed, 'diagnostico' ] );
			\WP_CLI::add_command( 'pbc seed-mapa', [ $seed, 'mapa' ] );
			\WP_CLI::add_command( 'pbc seed-redirecciones', [ $seed, 'redirecciones' ] );
		}
	}
}
