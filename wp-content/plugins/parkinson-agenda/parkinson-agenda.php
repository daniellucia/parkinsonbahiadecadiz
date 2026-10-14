<?php
/**
 * Plugin Name:       Agenda
 * Plugin URI:        https://daniellucia.es
 * Description:       Agenda de actividades de la asociación: talleres, charlas, celebraciones y salidas. Cada evento tiene su ficha, y dos widgets de Elementor muestran los próximos eventos y la agenda completa.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Requires Plugins:  elementor, parkinson-bahia-core
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-agenda
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PAG_VERSION', '1.0.0' );
define( 'PAG_FILE', __FILE__ );
define( 'PAG_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAG_URL', plugin_dir_url( __FILE__ ) );

$pag_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $pag_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin «Agenda» necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-agenda' )
			);
		}
	);
	return;
}

require_once $pag_autoload;

register_activation_hook( __FILE__, [ DanielLucia\ParkinsonBahia\Agenda\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

( new DanielLucia\ParkinsonBahia\Agenda\Plugin() )->register();
