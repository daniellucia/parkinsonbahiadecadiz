<?php
/**
 * Declara al plugin «Cookies accesibles» los servicios de terceros que usa
 * esta web, para que salgan en el aviso y en la política de cookies:
 * YouTube (vídeos de ejercicios), OpenStreetMap (mapa del bloque de contacto)
 * y, si está activado, Cloudflare Turnstile (formularios).
 *
 * El vídeo y el mapa solo se cargan con permiso: sus scripts preguntan con
 * pbcCookies.require( 'externos', … ) cuando el plugin está activo.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Frontend;

use DanielLucia\ParkinsonBahia\Core\Forms\Settings;

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
			'id'       => 'youtube',
			'category' => 'externos',
			'name'     => 'YouTube',
			'provider' => 'Google Ireland Limited',
			'purpose'  => __( 'Muestra los vídeos de ejercicios dentro de esta web. Usamos su modo de privacidad mejorada: no guarda cookies hasta que se reproduce un vídeo.', 'parkinson-bahia-core' ),
			'cookies'  => 'VISITOR_INFO1_LIVE, VISITOR_PRIVACY_METADATA, YSC, yt-* (almacenamiento local)',
			'duration' => __( 'De la sesión hasta 6 meses, según YouTube', 'parkinson-bahia-core' ),
			'policy'   => 'https://policies.google.com/privacy?hl=es',
		];

		$services[] = [
			'id'       => 'openstreetmap',
			'category' => 'externos',
			'name'     => 'OpenStreetMap',
			'provider' => 'OpenStreetMap Foundation',
			'purpose'  => __( 'Muestra el mapa con la situación del centro en el bloque «Ven a conocernos».', 'parkinson-bahia-core' ),
			'cookies'  => __( 'Las cookies técnicas que use openstreetmap.org para servir el mapa', 'parkinson-bahia-core' ),
			'duration' => __( 'Según OpenStreetMap', 'parkinson-bahia-core' ),
			'policy'   => 'https://osmfoundation.org/wiki/Privacy_Policy',
		];

		if ( Settings::turnstile_active() ) {
			$services[] = [
				'id'       => 'turnstile',
				'category' => 'necesarias',
				'name'     => 'Cloudflare Turnstile',
				'provider' => 'Cloudflare, Inc.',
				'purpose'  => __( 'Comprueba que quien envía un formulario es una persona y no un programa automático. Solo se carga cuando empiezas a rellenar un formulario.', 'parkinson-bahia-core' ),
				'cookies'  => __( 'Datos técnicos de seguridad de challenges.cloudflare.com', 'parkinson-bahia-core' ),
				'duration' => __( 'Según Cloudflare', 'parkinson-bahia-core' ),
				'policy'   => 'https://www.cloudflare.com/es-es/privacypolicy/',
			];
		}

		return $services;
	}
}
