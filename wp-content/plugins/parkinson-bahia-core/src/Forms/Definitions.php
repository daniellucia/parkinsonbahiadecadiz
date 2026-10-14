<?php
/**
 * Formularios del sitio y sus campos.
 *
 * Tipos de campo: text, tel, email, dni, iban, amount (cantidad con «otra»),
 * choices (casillas), select (desplegable con las opciones de «options»,
 * que el servidor comprueba) y textarea (texto largo). «sensitive» = se guarda cifrado y no sale en el correo;
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
	public const CONTACT   = 'contacto';

	/**
	 * Longitud máxima de un campo de texto largo.
	 */
	public const TEXTAREA_MAX = 2000;

	/**
	 * @return array<string, string> Tipo => nombre.
	 */
	public static function types(): array {
		return [
			self::MEMBER    => __( 'Hacerse socio', 'parkinson-bahia-core' ),
			self::VOLUNTEER => __( 'Voluntariado', 'parkinson-bahia-core' ),
			self::CONTACT   => __( 'Contacto', 'parkinson-bahia-core' ),
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
			self::CONTACT   => [
				'nombre'  => [
					'label'        => __( 'Nombre', 'parkinson-bahia-core' ),
					'type'         => 'text',
					'required'     => true,
					'autocomplete' => 'name',
					'wide'         => true,
				],
				'tel'     => [
					'label'        => __( 'Teléfono', 'parkinson-bahia-core' ),
					'type'         => 'tel',
					'required'     => true,
					'autocomplete' => 'tel',
				],
				'email'   => [
					'label'        => __( 'Correo electrónico', 'parkinson-bahia-core' ),
					'type'         => 'email',
					'required'     => false,
					'autocomplete' => 'email',
				],
				'motivo'  => [
					'label'    => __( '¿Sobre qué quieres hablar?', 'parkinson-bahia-core' ),
					'type'     => 'select',
					'required' => true,
					'wide'     => true,
					'options'  => [
						__( 'Plaza en el centro de día', 'parkinson-bahia-core' ),
						__( 'Terapias', 'parkinson-bahia-core' ),
						__( 'Apoyo para familias', 'parkinson-bahia-core' ),
						__( 'Hacerme socio o donar', 'parkinson-bahia-core' ),
						__( 'Voluntariado', 'parkinson-bahia-core' ),
						__( 'Otra consulta', 'parkinson-bahia-core' ),
					],
				],
				'mensaje' => [
					'label'    => __( 'Tu mensaje', 'parkinson-bahia-core' ),
					'type'     => 'textarea',
					'required' => false,
					'wide'     => true,
					'help'     => __( 'No hace falta que nos cuentes datos de salud: te llamamos y hablamos con calma.', 'parkinson-bahia-core' ),
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
	 * Información básica de protección de datos (primera capa, art. 13 RGPD)
	 * que acompaña a la casilla de aceptación de cada formulario: quién trata
	 * los datos, para qué, con qué base, a quién se comunican y qué derechos
	 * hay. El detalle está en la política de privacidad, enlazada en la casilla.
	 */
	public static function legal_notice( string $type ): string {
		$controller = self::controller();

		[ $purpose, $basis, $recipients ] = match ( $type ) {
			self::MEMBER    => [
				__( 'darte de alta como socio y cobrar tu cuota', 'parkinson-bahia-core' ),
				__( 'porque nos pides hacerte socio', 'parkinson-bahia-core' ),
				__( 'Solo los comunicamos a nuestro banco, para cobrar la cuota, y cuando la ley nos obliga.', 'parkinson-bahia-core' ),
			],
			self::VOLUNTEER => [
				__( 'ponernos en contacto contigo y organizar tu voluntariado', 'parkinson-bahia-core' ),
				__( 'con tu consentimiento', 'parkinson-bahia-core' ),
				__( 'No los cedemos a nadie salvo obligación legal.', 'parkinson-bahia-core' ),
			],
			self::CONTACT   => [
				__( 'llamarte y responder a tu consulta', 'parkinson-bahia-core' ),
				__( 'con tu consentimiento', 'parkinson-bahia-core' ),
				__( 'No los cedemos a nadie salvo obligación legal.', 'parkinson-bahia-core' ),
			],
			default         => [
				__( 'atender tu solicitud', 'parkinson-bahia-core' ),
				__( 'con tu consentimiento', 'parkinson-bahia-core' ),
				__( 'No los cedemos a nadie salvo obligación legal.', 'parkinson-bahia-core' ),
			],
		};

		return sprintf(
			/* translators: 1: responsable, 2: finalidad, 3: base jurídica, 4: destinatarios, 5: correo, 6: teléfono. */
			__( '%1$s usará tus datos solo para %2$s, %3$s. %4$s Puedes consultarlos, corregirlos, pedir que los borremos y ejercer el resto de tus derechos escribiendo a %5$s o llamando al %6$s. Si crees que no los tratamos bien, puedes reclamar ante la Agencia Española de Protección de Datos.', 'parkinson-bahia-core' ),
			$controller['name'],
			$purpose,
			$basis,
			$recipients,
			$controller['email'],
			$controller['phone']
		);
	}

	/**
	 * Responsable del tratamiento: el del plugin de RGPD si está activo; si no,
	 * los datos de contacto confirmados.
	 *
	 * @return array{name: string, email: string, phone: string}
	 */
	public static function controller(): array {
		$controller = class_exists( \DanielLucia\Rgpd\Settings::class ) ? ( new \DanielLucia\Rgpd\Settings() )->controller() : [];

		return [
			'name'  => (string) ( $controller['name'] ?? '' ) ?: wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'email' => (string) ( $controller['email'] ?? '' ) ?: 'unidad@parkinsonbahiadecadiz.org',
			'phone' => (string) ( $controller['phone'] ?? '' ) ?: '956 59 19 28',
		];
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
