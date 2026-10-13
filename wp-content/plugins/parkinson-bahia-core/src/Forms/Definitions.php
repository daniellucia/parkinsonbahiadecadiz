<?php
/**
 * Formularios del sitio y sus campos.
 *
 * Tipos de campo: text, tel, email, dni, iban, amount (cantidad con «otra»),
 * choices (casillas). «sensitive» = se guarda cifrado y no sale en el correo;
 * esos campos solo se piden si hay clave de cifrado (ver Crypto).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

defined( 'ABSPATH' ) || exit;

final class Definitions {

	public const MEMBER    = 'socio';
	public const VOLUNTEER = 'voluntariado';

	/**
	 * @return array<string, string> Tipo => nombre.
	 */
	public static function types(): array {
		return [
			self::MEMBER    => __( 'Hacerse socio', 'parkinson-bahia-core' ),
			self::VOLUNTEER => __( 'Voluntariado', 'parkinson-bahia-core' ),
		];
	}

	/**
	 * Campos de un formulario, sin los sensibles si no se pueden cifrar.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields( string $type ): array {
		$fields = match ( $type ) {
			self::MEMBER    => [
				'cuota'  => [
					'label'    => __( 'Tu aportación', 'parkinson-bahia-core' ),
					'type'     => 'amount',
					'required' => true,
				],
				'nombre' => [
					'label'        => __( 'Nombre y apellidos', 'parkinson-bahia-core' ),
					'type'         => 'text',
					'required'     => true,
					'autocomplete' => 'name',
				],
				'dni'    => [
					'label'     => __( 'DNI o NIE', 'parkinson-bahia-core' ),
					'type'      => 'dni',
					'required'  => true,
					'sensitive' => true,
				],
				'tel'    => [
					'label'        => __( 'Teléfono', 'parkinson-bahia-core' ),
					'type'         => 'tel',
					'required'     => true,
					'autocomplete' => 'tel',
				],
				'email'  => [
					'label'        => __( 'Correo electrónico', 'parkinson-bahia-core' ),
					'type'         => 'email',
					'required'     => false,
					'autocomplete' => 'email',
				],
				'iban'   => [
					'label'       => __( 'Número de cuenta (IBAN)', 'parkinson-bahia-core' ),
					'type'        => 'iban',
					'required'    => true,
					'sensitive'   => true,
					'wide'        => true,
					'placeholder' => 'ES00 0000 0000 0000 0000 0000',
					'help'        => __( 'Lo usaremos solo para cobrar la cuota. Puedes darte de baja cuando quieras.', 'parkinson-bahia-core' ),
				],
			],
			self::VOLUNTEER => [
				'nombre' => [
					'label'        => __( 'Nombre', 'parkinson-bahia-core' ),
					'type'         => 'text',
					'required'     => true,
					'autocomplete' => 'name',
				],
				'tel'    => [
					'label'        => __( 'Teléfono', 'parkinson-bahia-core' ),
					'type'         => 'tel',
					'required'     => true,
					'autocomplete' => 'tel',
				],
				'email'  => [
					'label'        => __( 'Correo electrónico', 'parkinson-bahia-core' ),
					'type'         => 'email',
					'required'     => false,
					'autocomplete' => 'email',
					'wide'         => true,
				],
				'areas'  => [
					'label'    => __( '¿En qué te gustaría ayudar?', 'parkinson-bahia-core' ),
					'hint'     => __( '(puedes marcar varias)', 'parkinson-bahia-core' ),
					'type'     => 'choices',
					'required' => false,
				],
			],
			default         => [],
		};

		if ( ! Crypto::is_available() ) {
			$fields = array_filter( $fields, static fn ( array $field ): bool => empty( $field['sensitive'] ) );
		}

		return $fields;
	}

	/**
	 * Letra de control de un DNI o NIE.
	 */
	public static function is_valid_dni( string $value ): bool {
		$value = strtoupper( (string) preg_replace( '/[\s.\-]/', '', $value ) );

		if ( ! preg_match( '/^([XYZ]?)(\d{7,8})([A-Z])$/', $value, $match ) ) {
			return false;
		}

		$number = (int) ( strtr( $match[1], 'XYZ', '012' ) . $match[2] );

		return 'TRWAGMYFPDXBNJZSQVHLCKE'[ $number % 23 ] === $match[3];
	}

	/**
	 * Dígitos de control de un IBAN (módulo 97).
	 */
	public static function is_valid_iban( string $value ): bool {
		$iban = strtoupper( (string) preg_replace( '/\s+/', '', $value ) );

		if ( ! preg_match( '/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban ) || ( str_starts_with( $iban, 'ES' ) && 24 !== strlen( $iban ) ) ) {
			return false;
		}

		$rearranged = substr( $iban, 4 ) . substr( $iban, 0, 4 );
		$remainder  = 0;

		foreach ( str_split( $rearranged ) as $char ) {
			$digits = ctype_alpha( $char ) ? (string) ( ord( $char ) - 55 ) : $char;
			foreach ( str_split( $digits ) as $digit ) {
				$remainder = ( $remainder * 10 + (int) $digit ) % 97;
			}
		}

		return 1 === $remainder;
	}
}
