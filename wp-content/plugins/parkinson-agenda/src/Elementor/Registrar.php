<?php
/**
 * Hojas de estilo de la agenda y sus widgets de Elementor (en la categoría
 * «Párkinson Bahía de Cádiz» del plugin principal, cuya clase base usan).
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda\Elementor;

defined( 'ABSPATH' ) || exit;

final class Registrar {

	/** Tarjeta de evento compartida por los dos widgets. */
	public const CARD_STYLE = 'pag-event-card';

	public function register(): void {
		add_action( 'init', [ $this, 'register_styles' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}

	public function register_styles(): void {
		$styles = [
			self::CARD_STYLE                => [ 'assets/css/event-card.css', [] ],
			'pbc-widget-upcoming-events'    => [ 'assets/css/widgets/upcoming-events.css', [ self::CARD_STYLE ] ],
			'pbc-widget-agenda'             => [ 'assets/css/widgets/agenda.css', [ self::CARD_STYLE ] ],
			'pag-event-page'                => [ 'assets/css/event-page.css', [ 'pbc-main', self::CARD_STYLE ] ],
		];

		foreach ( $styles as $handle => [ $file, $deps ] ) {
			$path = PAG_DIR . $file;
			wp_register_style( $handle, PAG_URL . $file, $deps, is_readable( $path ) ? (string) filemtime( $path ) : PAG_VERSION );
		}
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Gestor de widgets.
	 */
	public function register_widgets( $widgets_manager ): void {
		if ( ! class_exists( \DanielLucia\ParkinsonBahia\Core\Elementor\Widgets\Widget::class ) ) {
			return;
		}

		$widgets_manager->register( new UpcomingEvents() );
		$widgets_manager->register( new Agenda() );
	}
}
