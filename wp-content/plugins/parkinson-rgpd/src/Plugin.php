<?php
/**
 * Punto de entrada: registra cada módulo. Aquí solo se enganchan acciones.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public function register(): void {
		$settings = new Settings();
		$registry = new Registry();

		( new Exposure() )->register();
		( new Retention( $settings, $registry ) )->register();
		( new Footer( $settings ) )->register();
		( new Shortcodes( $settings, $registry ) )->register();

		if ( is_admin() ) {
			( new AdminPage( $settings, $registry ) )->register();
		}
	}
}
