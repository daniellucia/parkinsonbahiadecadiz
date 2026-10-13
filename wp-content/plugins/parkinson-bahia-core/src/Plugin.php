<?php
/**
 * Punto de entrada del plugin: registra cada módulo.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core;

use DanielLucia\ParkinsonBahia\Core\Cli\SeedCommand;
use DanielLucia\ParkinsonBahia\Core\Elementor\Integration;
use DanielLucia\ParkinsonBahia\Core\PostTypes\Terapia;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public function register(): void {
		( new Terapia() )->register();
		( new Integration() )->register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pbc seed-home', [ new SeedCommand(), 'home' ] );
		}
	}
}
