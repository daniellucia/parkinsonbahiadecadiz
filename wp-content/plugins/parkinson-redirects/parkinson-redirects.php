<?php
/**
 * Plugin Name:       Redirecciones
 * Plugin URI:        https://daniellucia.es
 * Description:       Lleva las direcciones de la web antigua a su sitio en la nueva (301), avisa de las páginas que no se encuentran (404) y deja comprobar cualquier dirección. No guarda datos de las personas que visitan la web.
 * Version:           1.0.1
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-redirects
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PRD_VERSION', '1.0.1' );
define( 'PRD_FILE', __FILE__ );
define( 'PRD_DIR', plugin_dir_path( __FILE__ ) );

$prd_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $prd_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin «Redirecciones» necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-redirects' )
			);
		}
	);
	return;
}

require_once $prd_autoload;

register_activation_hook( __FILE__, [ DanielLucia\Redirects\Schema::class, 'install' ] );

( new DanielLucia\Redirects\Plugin() )->register();
