<?php
/**
 * Punto de entrada: registra cada módulo. Aquí solo se enganchan acciones.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public function register(): void {
		$settings = new Settings();
		$services = new Services( $settings );

		( new Frontend( $settings, $services ) )->register();
		( new Shortcodes( $settings, $services ) )->register();

		if ( is_admin() ) {
			( new SettingsPage( $settings, $services ) )->register();
		}
	}
}
