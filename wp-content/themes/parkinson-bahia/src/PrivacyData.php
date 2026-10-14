<?php
/**
 * Pasa al plugin de RGPD los datos del responsable del tratamiento que ya
 * están en el Personalizador (nombre legal y CIF, dirección, correo y
 * teléfono), para no escribirlos dos veces. En Ajustes → Protección de datos
 * se pueden cambiar.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class PrivacyData {

	public function register(): void {
		add_filter( 'parkinson_rgpd/controller', [ $this, 'controller' ] );
	}

	/**
	 * @param array<string, string> $defaults Datos por defecto.
	 * @return array<string, string>
	 */
	public function controller( array $defaults ): array {
		// «Asociación … Bahía de Cádiz. CIF G-11591302.» → nombre y CIF por separado.
		$legal = TemplateTags::mod( 'pbc_legal_name' );
		$nif   = preg_match( '/\b(?:CIF|NIF)[:\s]*([A-Z0-9-]+)/i', $legal, $match ) ? rtrim( $match[1], '.-' ) : '';
		$name  = trim( (string) preg_replace( '/\.?\s*(?:CIF|NIF)[:\s]*[A-Z0-9-]+\.?\s*$/i', '', $legal ) );

		return array_merge(
			$defaults,
			array_filter(
				[
					'name'    => $name,
					'nif'     => $nif,
					'address' => TemplateTags::mod( 'pbc_address' ),
					'email'   => sanitize_email( TemplateTags::mod( 'pbc_email' ) ),
					'phone'   => TemplateTags::mod( 'pbc_phone' ),
				]
			)
		);
	}
}
