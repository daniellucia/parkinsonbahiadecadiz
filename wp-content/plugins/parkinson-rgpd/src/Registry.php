<?php
/**
 * Lo que la web hace con datos personales, declarado por cada plugin o tema.
 *
 * Tratamientos («parkinson_rgpd/treatments»): cada uno es un array con
 * id, name, purpose, basis, people, data, recipients, transfers, retention
 * y security (textos). Con ellos se arma el registro de actividades de
 * tratamiento (art. 30 RGPD).
 *
 * Plazos de conservación («parkinson_rgpd/retention»): cada uno es un array
 * con id, label, description, default (meses; 0 = no borrar solo) y purge,
 * una función que recibe la marca de tiempo límite y borra lo anterior a
 * ella, devolviendo cuántos elementos ha borrado.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Registry {

	public const TREATMENT_FIELDS = [ 'name', 'purpose', 'basis', 'people', 'data', 'recipients', 'transfers', 'retention', 'security' ];

	/**
	 * @return array<string, array<string, string>>
	 */
	public function treatments(): array {
		$clean = [];

		foreach ( (array) apply_filters( 'parkinson_rgpd/treatments', [] ) as $treatment ) {
			$id = sanitize_key( (string) ( $treatment['id'] ?? '' ) );
			if ( '' === $id ) {
				continue;
			}

			$item = [ 'id' => $id ];
			foreach ( self::TREATMENT_FIELDS as $field ) {
				$item[ $field ] = (string) ( $treatment[ $field ] ?? '' );
			}
			$clean[ $id ] = $item;
		}

		return $clean;
	}

	/**
	 * @return array<string, array{id: string, label: string, description: string, default: int, purge: callable}>
	 */
	public function retention(): array {
		$clean = [];

		foreach ( (array) apply_filters( 'parkinson_rgpd/retention', [] ) as $item ) {
			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );
			if ( '' === $id || ! is_callable( $item['purge'] ?? null ) ) {
				continue;
			}

			$clean[ $id ] = [
				'id'          => $id,
				'label'       => (string) ( $item['label'] ?? $id ),
				'description' => (string) ( $item['description'] ?? '' ),
				'default'     => absint( $item['default'] ?? 0 ),
				'purge'       => $item['purge'],
			];
		}

		return $clean;
	}
}
