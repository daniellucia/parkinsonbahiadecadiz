<?php
/**
 * Integración con Elementor: categoría propia, widgets y sus hojas de estilo.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor;

defined( 'ABSPATH' ) || exit;

final class Integration {

	public const CATEGORY = 'parkinson-bahia';

	/**
	 * Identificador corto de cada widget => clase.
	 *
	 * @var array<string, class-string<Widgets\Widget>>
	 */
	private const WIDGETS = [
		'hero'        => Widgets\Hero::class,
		'paths'       => Widgets\Paths::class,
		'feature'     => Widgets\Feature::class,
		'therapies'   => Widgets\Therapies::class,
		'steps'       => Widgets\Steps::class,
		'latest-news' => Widgets\LatestNews::class,
		'support'     => Widgets\Support::class,
		'page-header' => Widgets\PageHeader::class,
		'mission'     => Widgets\Mission::class,
		'timeline'    => Widgets\Timeline::class,
		'team'        => Widgets\Team::class,
		'board'       => Widgets\Board::class,
		'documents'   => Widgets\Documents::class,
		'networks'    => Widgets\Networks::class,
		'notice'      => Widgets\Notice::class,
		'schedule'    => Widgets\Schedule::class,
		'media-text'  => Widgets\MediaText::class,
		'spaces'      => Widgets\Spaces::class,
		'faq'         => Widgets\Faq::class,
		'form'        => Widgets\Form::class,
		'donation'    => Widgets\Donation::class,
		'cta'         => Widgets\Cta::class,
		'therapy-details' => Widgets\TherapyDetails::class,
		'resources'   => Widgets\Resources::class,
		'videos'      => Widgets\Videos::class,
	];

	/**
	 * Widgets que tienen JavaScript propio (assets/js/widgets/<slug>.js).
	 */
	public const SCRIPTS = [ 'form', 'resources', 'videos' ];

	public function register(): void {
		add_action( 'init', [ $this, 'register_styles' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'admin_notices', [ $this, 'missing_elementor_notice' ] );

		// Las fuentes están alojadas en el tema: Elementor no debe pedir nada a Google Fonts.
		add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
	}

	/**
	 * Cada widget tiene su hoja de estilo; Elementor solo la carga donde se usa.
	 */
	public function register_styles(): void {
		foreach ( array_keys( self::WIDGETS ) as $slug ) {
			$file = 'assets/css/widgets/' . $slug . '.css';
			$path = PBC_CORE_DIR . $file;

			wp_register_style(
				'pbc-widget-' . $slug,
				PBC_CORE_URL . $file,
				[],
				is_readable( $path ) ? (string) filemtime( $path ) : PBC_CORE_VERSION
			);
		}

		foreach ( self::SCRIPTS as $slug ) {
			$file = 'assets/js/widgets/' . $slug . '.js';
			$path = PBC_CORE_DIR . $file;

			wp_register_script(
				'pbc-widget-' . $slug,
				PBC_CORE_URL . $file,
				[],
				is_readable( $path ) ? (string) filemtime( $path ) : PBC_CORE_VERSION,
				[
					'strategy'  => 'defer',
					'in_footer' => true,
				]
			);
		}
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Gestor de categorías.
	 */
	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY,
			[
				'title' => esc_html__( 'Párkinson Bahía de Cádiz', 'parkinson-bahia-core' ),
				'icon'  => 'eicon-heart',
			]
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Gestor de widgets.
	 */
	public function register_widgets( $widgets_manager ): void {
		foreach ( self::WIDGETS as $class ) {
			$widgets_manager->register( new $class() );
		}
	}

	public function missing_elementor_notice(): void {
		if ( did_action( 'elementor/loaded' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Los widgets de Párkinson Bahía de Cádiz necesitan el plugin Elementor activo.', 'parkinson-bahia-core' )
		);
	}
}
