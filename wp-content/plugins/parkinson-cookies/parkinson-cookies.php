<?php
/**
 * Plugin Name:       Cookies accesibles
 * Plugin URI:        https://daniellucia.es
 * Description:       Aviso de cookies sencillo y accesible: aceptar, rechazar o elegir con el mismo esfuerzo, configuración siempre a mano y política de cookies generada a partir de los servicios que declara la web. Nada opcional se carga sin permiso.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-cookies
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PCK_VERSION', '1.0.0' );
define( 'PCK_FILE', __FILE__ );
define( 'PCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCK_URL', plugin_dir_url( __FILE__ ) );

$pck_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $pck_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin «Cookies accesibles» necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-cookies' )
			);
		}
	);
	return;
}

require_once $pck_autoload;

( new DanielLucia\Cookies\Plugin() )->register();
