<?php
/**
 * [pbc_rgpd_plazos]: lista de los plazos de conservación configurados, para
 * la política de privacidad. Así la política dice lo mismo que hace la web.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Shortcodes {

	public function __construct(
		private readonly Settings $settings,
		private readonly Registry $registry
	) {}

	public function register(): void {
		add_shortcode( 'pbc_rgpd_plazos', [ $this, 'retention' ] );
	}

	public function retention(): string {
		$items = '';

		foreach ( $this->registry->retention() as $id => $item ) {
			$months = $this->settings->months( $id, $item['default'] );
			$when   = $months > 0
				/* translators: %d: número de meses. */
				? sprintf( _n( 'se borran de forma automática al cumplir %d mes', 'se borran de forma automática al cumplir %d meses', $months, 'parkinson-rgpd' ), $months )
				: __( 'se borran a mano cuando ya no hacen falta', 'parkinson-rgpd' );

			$items .= sprintf(
				'<li><strong>%1$s:</strong> %2$s%3$s.</li>',
				esc_html( $item['label'] ),
				'' !== $item['description'] ? esc_html( $item['description'] ) . ' ' : '',
				esc_html( $when )
			);
		}

		return '' !== $items ? '<ul class="pbc-rgpd-plazos">' . $items . '</ul>' : '';
	}
}
