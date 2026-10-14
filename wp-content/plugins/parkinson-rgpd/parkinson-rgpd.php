<?php
/**
 * Plugin Name:       Protección de datos (RGPD)
 * Plugin URI:        https://daniellucia.es
 * Description:       Ayuda a cumplir el RGPD y la LOPDGDD: registro de actividades de tratamiento, plazos de conservación con borrado automático, menos datos personales expuestos (comentarios, autores, API) y guía para atender derechos y brechas de seguridad.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-rgpd
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PRGPD_VERSION', '1.0.0' );
define( 'PRGPD_FILE', __FILE__ );

$prgpd_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $prgpd_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin «Protección de datos (RGPD)» necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-rgpd' )
			);
		}
	);
	return;
}

require_once $prgpd_autoload;

register_activation_hook( __FILE__, [ DanielLucia\Rgpd\Retention::class, 'schedule' ] );
register_deactivation_hook( __FILE__, [ DanielLucia\Rgpd\Retention::class, 'unschedule' ] );

( new DanielLucia\Rgpd\Plugin() )->register();
