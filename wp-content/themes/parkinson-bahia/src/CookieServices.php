<?php
/**
 * Declara al plugin «Cookies accesibles» lo que guarda el tema en el
 * navegador: el tamaño del texto y el alto contraste (almacenamiento local,
 * solo si la persona usa esos botones). Son necesarias: las pide ella misma.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class CookieServices {

	public function register(): void {
		add_filter( 'parkinson_cookies/services', [ $this, 'services' ] );
	}

	/**
	 * @param array<int, array<string, string>> $services Servicios ya declarados.
	 * @return array<int, array<string, string>>
	 */
	public function services( array $services ): array {
		$services[] = [
			'id'       => 'opciones-lectura',
			'category' => 'necesarias',
			'name'     => __( 'Opciones de lectura', 'parkinson-bahia' ),
			'provider' => __( 'Esta web', 'parkinson-bahia' ),
			'purpose'  => __( 'Recuerda el tamaño del texto y el alto contraste que hayas elegido arriba de la página. Solo se guarda si usas esos botones.', 'parkinson-bahia' ),
			'cookies'  => __( 'pbc_fs y pbc_contrast (almacenamiento local, no son cookies)', 'parkinson-bahia' ),
			'duration' => __( 'Hasta que borres los datos del navegador', 'parkinson-bahia' ),
		];

		return $services;
	}
}
