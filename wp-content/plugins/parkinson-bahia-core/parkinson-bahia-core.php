<?php
/**
 * Plugin Name:       Párkinson Bahía de Cádiz · Núcleo
 * Plugin URI:        https://parkinsonbahiadecadiz.org
 * Description:       Contenidos (tipos de contenido propios) y widgets de Elementor de la web de Párkinson Bahía de Cádiz.
 * Version:           1.12.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-bahia-core
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PBC_CORE_VERSION', '1.12.0' );
define( 'PBC_CORE_FILE', __FILE__ );
define( 'PBC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PBC_CORE_URL', plugin_dir_url( __FILE__ ) );

$pbc_core_autoload = PBC_CORE_DIR . 'vendor/autoload.php';

if ( ! is_readable( $pbc_core_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin Párkinson Bahía de Cádiz necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-bahia-core' )
			);
		}
	);
	return;
}

require_once $pbc_core_autoload;

( new DanielLucia\ParkinsonBahia\Core\Plugin() )->register();

register_activation_hook(
	__FILE__,
	static function (): void {
		foreach ( DanielLucia\ParkinsonBahia\Core\Plugin::post_types() as $pbc_post_type ) {
			$pbc_post_type->register_post_type();
		}
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
