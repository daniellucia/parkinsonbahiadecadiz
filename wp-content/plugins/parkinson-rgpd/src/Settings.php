<?php
/**
 * Ajustes del plugin (opción «prgpd_settings»): datos del responsable del
 * tratamiento y meses de conservación de cada tipo de dato.
 *
 * Los datos del responsable que se dejen vacíos se toman del filtro
 * «parkinson_rgpd/controller», que puede rellenar el tema o otro plugin.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION = 'prgpd_settings';

	/** Campos del responsable del tratamiento. */
	public const CONTROLLER_FIELDS = [ 'name', 'nif', 'address', 'email', 'phone', 'dpo' ];

	/**
	 * @return array{controller: array<string, string>, retention: array<string, int>}
	 */
	private function saved(): array {
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		$controller = [];
		foreach ( self::CONTROLLER_FIELDS as $field ) {
			$controller[ $field ] = (string) ( $saved['controller'][ $field ] ?? '' );
		}

		$retention = [];
		foreach ( (array) ( $saved['retention'] ?? [] ) as $id => $months ) {
			$retention[ sanitize_key( (string) $id ) ] = absint( $months );
		}

		return [
			'controller' => $controller,
			'retention'  => $retention,
		];
	}

	/**
	 * Datos guardados a mano, sin completar (para el formulario de ajustes).
	 *
	 * @return array<string, string>
	 */
	public function controller_saved(): array {
		return $this->saved()['controller'];
	}

	/**
	 * Datos del responsable: lo guardado y, si falta algo, lo que da el filtro.
	 *
	 * @return array<string, string>
	 */
	public function controller(): array {
		/**
		 * Datos por defecto del responsable del tratamiento.
		 *
		 * @param array<string, string> $defaults name, nif, address, email, phone y dpo.
		 */
		$defaults = (array) apply_filters(
			'parkinson_rgpd/controller',
			array_fill_keys( self::CONTROLLER_FIELDS, '' ) + [ 'name' => (string) get_bloginfo( 'name' ) ]
		);

		$controller = [];
		foreach ( $this->saved()['controller'] as $field => $value ) {
			$controller[ $field ] = '' !== trim( $value ) ? $value : (string) ( $defaults[ $field ] ?? '' );
		}

		return $controller;
	}

	/**
	 * Meses de conservación de un tipo de dato; si no se ha guardado, el valor por defecto.
	 */
	public function months( string $id, int $default ): int {
		$retention = $this->saved()['retention'];

		return array_key_exists( $id, $retention ) ? $retention[ $id ] : $default;
	}

	/**
	 * @param array{controller?: array<string, string>, retention?: array<string, int>} $values Valores nuevos.
	 */
	public function update( array $values ): void {
		update_option( self::OPTION, array_merge( $this->saved(), $values ), true );
	}
}
