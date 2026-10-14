<?php
/**
 * Mapa del bloque de contacto (gancho del tema «parkinson_bahia/contact_map»).
 *
 * Se ve siempre, sin pulsar nada: un mapa estático servido por esta web
 * (teselas de OpenStreetMap unidas por «wp pbc seed-mapa», con la situación
 * del centro marcada). No conecta con terceros ni guarda cookies.
 *
 * El mapa interactivo de OpenStreetMap sustituye a la imagen él solo si la
 * persona ya ha permitido el contenido de otras webs (plugin de cookies), o
 * cuando pulsa «Mover y ampliar el mapa» (y entonces se le pide permiso).
 * Sin JavaScript, ese botón abre el mapa en OpenStreetMap.
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

	/** Opción con el ID de la imagen del mapa estático. */
	public const IMAGE_OPTION = 'pbc_contact_map_image';

	private const HANDLE = 'pbc-contact-map';

	public function register(): void {
		add_action( 'init', [ $this, 'register_script' ] );
		add_action( 'parkinson_bahia/contact_map', [ $this, 'render' ] );
	}

	/**
	 * Latitud y longitud del centro.
	 *
	 * @return array{0: float, 1: float}
	 */
	public static function coords(): array {
		/**
		 * Coordenadas del mapa del bloque de contacto.
		 *
		 * @param array{0: float, 1: float} $coords Latitud y longitud.
		 */
		[ $lat, $lon ] = apply_filters( 'parkinson_bahia/contact_map_coords', [ self::LAT, self::LON ] );

		return [ (float) $lat, (float) $lon ];
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
		[ $lat, $lon ] = self::coords();

		$embed = add_query_arg(
			[
				'bbox'   => implode( ',', [ $lon - 0.008, $lat - 0.004, $lon + 0.008, $lat + 0.004 ] ),
				'layer'  => 'mapnik',
				'marker' => $lat . ',' . $lon,
			],
			'https://www.openstreetmap.org/export/embed.html'
		);
		$page  = sprintf( 'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=17/%1$s/%2$s', $lat, $lon );

		$image_id = absint( get_option( self::IMAGE_OPTION ) );
		$image    = $image_id > 0 ? wp_get_attachment_image(
			$image_id,
			'full',
			false,
			[
				'class'    => 'pbc-contact__map-image',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => '(min-width: 900px) 660px, 100vw',
			]
		) : '';

		wp_enqueue_script( self::HANDLE );
		?>
		<div class="pbc-contact__map <?php echo '' !== $image ? 'pbc-contact__map--static' : 'pbc-contact__map--facade'; ?>" data-pbc-map data-pbc-map-src="<?php echo esc_url( $embed ); ?>" data-pbc-map-title="<?php esc_attr_e( 'Mapa interactivo con la situación del centro', 'parkinson-bahia-core' ); ?>">
			<?php if ( '' !== $image ) : ?>
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
				<div class="pbc-contact__map-bar">
					<a class="pbc-button pbc-button--primary pbc-button--small" href="<?php echo esc_url( $page ); ?>" data-pbc-map-button><?php esc_html_e( 'Mover y ampliar el mapa', 'parkinson-bahia-core' ); ?></a>
					<p class="pbc-contact__map-credit">© <a href="https://www.openstreetmap.org/copyright"><?php esc_html_e( 'colaboradores de OpenStreetMap', 'parkinson-bahia-core' ); ?></a></p>
				</div>
			<?php else : ?>
				<p class="pbc-contact__map-text"><?php esc_html_e( 'El mapa se carga desde OpenStreetMap cuando lo pidas.', 'parkinson-bahia-core' ); ?></p>
				<a class="pbc-button pbc-button--primary" href="<?php echo esc_url( $page ); ?>" data-pbc-map-button><?php esc_html_e( 'Ver el mapa', 'parkinson-bahia-core' ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}
}
