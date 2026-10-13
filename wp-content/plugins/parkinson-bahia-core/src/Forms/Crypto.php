<?php
/**
 * Cifrado de los datos sensibles de los formularios (DNI, IBAN).
 *
 * La clave no se guarda en la base de datos ni en el repositorio: se lee de la
 * constante o variable de entorno PBC_FORMS_KEY (32 bytes en base64). Sin
 * clave no se cifra nada y los formularios no piden esos datos.
 *
 * Generar una clave: php -r "echo base64_encode(random_bytes(32));"
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

defined( 'ABSPATH' ) || exit;

final class Crypto {

	private const PREFIX = 'pbc1:';

	public static function is_available(): bool {
		return null !== self::key();
	}

	public static function encrypt( string $plain ): ?string {
		$key = self::key();

		if ( null === $key || '' === $plain ) {
			return null;
		}

		$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = sodium_crypto_secretbox( $plain, $nonce, $key );

		return self::PREFIX . base64_encode( $nonce . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Codificación del texto cifrado, no ofuscación.
	}

	public static function decrypt( string $stored ): ?string {
		$key = self::key();

		if ( null === $key || ! str_starts_with( $stored, self::PREFIX ) ) {
			return null;
		}

		$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decodificación del texto cifrado.

		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}

		$plain = sodium_crypto_secretbox_open(
			substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
			substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
			$key
		);

		return false === $plain ? null : $plain;
	}

	private static function key(): ?string {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return null;
		}

		$encoded = defined( 'PBC_FORMS_KEY' ) ? (string) constant( 'PBC_FORMS_KEY' ) : (string) getenv( 'PBC_FORMS_KEY' );
		$key     = '' !== $encoded ? base64_decode( $encoded, true ) : false; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- La clave se guarda en base64.

		return is_string( $key ) && SODIUM_CRYPTO_SECRETBOX_KEYBYTES === strlen( $key ) ? $key : null;
	}
}
