<?php
/**
 * Plugin Name:       llms.txt automático
 * Plugin URI:        https://daniellucia.es
 * Description:       Genera y sirve el fichero /llms.txt (https://llmstxt.org) a partir del contenido del sitio, con versión en Markdown de cada página y página de ajustes.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Daniel Lucia
 * Author URI:        https://daniellucia.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parkinson-llms-txt
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'PLLMS_VERSION', '1.0.0' );
define( 'PLLMS_FILE', __FILE__ );

$pllms_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $pllms_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El plugin «llms.txt automático» necesita sus dependencias. Ejecuta «composer install» en la carpeta del plugin.', 'parkinson-llms-txt' )
			);
		}
	);
	return;
}

require_once $pllms_autoload;

( new DanielLucia\LlmsTxt\Plugin() )->register();
