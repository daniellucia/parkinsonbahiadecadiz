<?php
/**
 * Mapa del bloque de contacto común (gancho del tema «parkinson_bahia/contact_map»).
 *
 * Se muestra un panel con un botón: el mapa de OpenStreetMap solo se carga
 * cuando la persona lo pide, así la página no conecta con terceros ni pesa
 * más al cargar. Sin JavaScript, el botón abre el mapa en OpenStreetMap.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Frontend;

defined( 'ABSPATH' ) || exit;

final class ContactMap {

	/**
	 * Situación del centro: C/ Milongas s/n, San Fernando.
	 */
	private const LAT = 36.44795;
	private const LON = -6.21460;

	private const HANDLE = 'pbc-contact-map';

	public function register(): void {
		add_action( 'init', [ $this, 'register_script' ] );
		add_action( 'parkinson_bahia/contact_map', [ $this, 'render' ] );
	}

	public function register_script(): void {
		$file = 'assets/js/contact-map.js';

		wp_register_script(
			self::HANDLE,
			PBC_CORE_URL . $file,
			[],
			(string) filemtime( PBC_CORE_DIR . $file ),
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	public function render(): void {
		/**
		 * Coordenadas del mapa del bloque de contacto.
		 *
		 * @param array{0: float, 1: float} $coords Latitud y longitud.
		 */
		[ $lat, $lon ] = apply_filters( 'parkinson_bahia/contact_map_coords', [ self::LAT, self::LON ] );
		$lat           = (float) $lat;
		$lon           = (float) $lon;

		$embed = add_query_arg(
			[
				'bbox'   => implode( ',', [ $lon - 0.008, $lat - 0.004, $lon + 0.008, $lat + 0.004 ] ),
				'layer'  => 'mapnik',
				'marker' => $lat . ',' . $lon,
			],
			'https://www.openstreetmap.org/export/embed.html'
		);
		$page  = sprintf( 'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=17/%1$s/%2$s', $lat, $lon );

		wp_enqueue_script( self::HANDLE );
		?>
		<div class="pbc-contact__map pbc-contact__map--facade" data-pbc-map data-pbc-map-src="<?php echo esc_url( $embed ); ?>" data-pbc-map-title="<?php esc_attr_e( 'Mapa con la situación del centro', 'parkinson-bahia-core' ); ?>">
			<p class="pbc-contact__map-text"><?php esc_html_e( 'El mapa se carga desde OpenStreetMap cuando lo pidas.', 'parkinson-bahia-core' ); ?></p>
			<a class="pbc-button pbc-button--primary" href="<?php echo esc_url( $page ); ?>" data-pbc-map-button><?php esc_html_e( 'Ver el mapa', 'parkinson-bahia-core' ); ?></a>
		</div>
		<?php
	}
}
