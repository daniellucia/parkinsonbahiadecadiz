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
 * Nombres y duraciones medidos el 14-10-2026 con Chrome 151, en una página de
 * prueba y en la web (cookies de todos los dominios, localStorage,
 * sessionStorage e IndexedDB de cada marco): YouTube con el vídeo
 * reproduciéndose, el mapa de OpenStreetMap y Turnstile con la clave de
 * pruebas de Cloudflare. Hay que repetirlo si cambia un servicio.
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
			'purpose'  => __( 'Muestra los vídeos de ejercicios dentro de esta web. Usamos su modo de privacidad mejorada (youtube-nocookie.com): en nuestras pruebas no guardó cookies, ni siquiera al reproducir, pero el reproductor guarda datos técnicos en tu navegador y se conecta con servidores de Google, que reciben tu dirección IP.', 'parkinson-bahia-core' ),
			'cookies'  => __( 'Ninguna cookie. Almacenamiento local de youtube-nocookie.com: ytidb::LAST_RESULT_ENTRY_KEY, yt-player-caption-persistence, yt-icons-last-purged y la base de datos YtIdbMeta', 'parkinson-bahia-core' ),
			'duration' => __( 'Hasta que borres los datos del navegador', 'parkinson-bahia-core' ),
			'policy'   => 'https://policies.google.com/privacy?hl=es',
		];

		$services[] = [
			'id'       => 'openstreetmap',
			'category' => 'externos',
			'name'     => 'OpenStreetMap',
			'provider' => 'OpenStreetMap Foundation',
			'purpose'  => __( 'Muestra el mapa interactivo con la situación del centro en el bloque «Ven a conocernos». No guarda nada en tu navegador; solo se conecta con openstreetmap.org para descargar el mapa, que recibe tu dirección IP.', 'parkinson-bahia-core' ),
			'cookies'  => __( 'Ninguna (ni cookies ni almacenamiento local)', 'parkinson-bahia-core' ),
			'duration' => __( 'No guarda nada', 'parkinson-bahia-core' ),
			'policy'   => 'https://osmfoundation.org/wiki/Privacy_Policy',
		];

		if ( Settings::turnstile_active() ) {
			$services[] = [
				'id'       => 'turnstile',
				'category' => 'necesarias',
				'name'     => 'Cloudflare Turnstile',
				'provider' => 'Cloudflare, Inc.',
				'purpose'  => __( 'Comprueba que quien envía un formulario es una persona y no un programa automático. Solo se carga cuando empiezas a rellenar un formulario.', 'parkinson-bahia-core' ),
				'cookies'  => __( 'Ninguna cookie. Almacenamiento local de challenges.cloudflare.com: cf.turnstile.u', 'parkinson-bahia-core' ),
				'duration' => __( 'Hasta que borres los datos del navegador', 'parkinson-bahia-core' ),
				'policy'   => 'https://www.cloudflare.com/es-es/privacypolicy/',
			];
		}

		return $services;
	}
}
