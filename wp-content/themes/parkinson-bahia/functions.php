<?php
/**
 * Arranque del tema.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$parkinson_bahia_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $parkinson_bahia_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'El tema Párkinson Bahía de Cádiz necesita sus dependencias. Ejecuta «composer install» en la carpeta del tema.', 'parkinson-bahia' )
			);
		}
	);
	return;
}

require_once $parkinson_bahia_autoload;

( new DanielLucia\ParkinsonBahia\Theme\Theme() )->register();
