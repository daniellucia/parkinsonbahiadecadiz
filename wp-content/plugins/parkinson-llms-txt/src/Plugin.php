<?php
/**
 * Punto de entrada: registra cada módulo. Aquí solo se enganchan acciones.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public function register(): void {
		$settings  = new Settings();
		$cache     = new Cache();
		$generator = new Generator( $settings, $cache, new Markdown() );

		$cache->register();
		( new Router( $settings, $generator ) )->register();

		if ( is_admin() ) {
			( new SettingsPage( $settings, $generator ) )->register();
			( new PostFields( $settings ) )->register();
		}
	}
}
