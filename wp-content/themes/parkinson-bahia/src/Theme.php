<?php
/**
 * Punto de entrada del tema: registra cada módulo.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class Theme {

	public const VERSION = '1.0.0';

	public function register(): void {
		( new Setup() )->register();
		( new Menus() )->register();
		( new Assets() )->register();
		( new Customizer() )->register();
	}
}
