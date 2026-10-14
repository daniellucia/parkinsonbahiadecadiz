<?php
/**
 * Widget «Cabecera de página»: migas de pan, título principal (h1), texto y foto.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class PageHeader extends Widget {

	protected function slug(): string {
		return 'page-header';
	}

	public function get_title(): string {
		return esc_html__( 'Cabecera de página', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-header';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'eyebrow', esc_html__( 'Antetítulo', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título de la página', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control( 'h1' );
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->image_control( 'image', esc_html__( 'Foto (opcional)', 'parkinson-bahia-core' ) );
		$this->add_control(
			'image_decorative',
			[
				'label'       => esc_html__( 'Foto decorativa', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Actívalo solo si la foto no aporta información.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SWITCHER,
			]
		);
		$this->add_control(
			'variant',
			[
				'label'   => esc_html__( 'Fondo', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => esc_html__( 'Claro', 'parkinson-bahia-core' ),
					'sand' => esc_html__( 'Arena', 'parkinson-bahia-core' ),
					'clay' => esc_html__( 'Naranja', 'parkinson-bahia-core' ),
					'dark' => esc_html__( 'Azul oscuro', 'parkinson-bahia-core' ),
				],
				'default' => '',
			]
		);
		$this->end_controls_section();

		$this->start_controls_section( 'facts_section', [ 'label' => esc_html__( 'Datos clave (opcional)', 'parkinson-bahia-core' ) ] );
		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			[
				'label'       => esc_html__( 'Dato', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'value',
			[
				'label'       => esc_html__( 'Valor', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$this->add_control(
			'facts',
			[
				'label'       => esc_html__( 'Datos', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Se muestran junto al título, en tarjetas.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ label }}: {{ value }}',
				'default'     => [],
			]
		);
		$this->end_controls_section();

		$this->start_controls_section( 'jumps_section', [ 'label' => esc_html__( 'Accesos (opcional)', 'parkinson-bahia-core' ) ] );
		$this->add_control(
			'jumps_source',
			[
				'label'       => esc_html__( 'Qué accesos mostrar', 'parkinson-bahia-core' ),
				'description' => esc_html__( '«Las terapias» enlaza con cada ficha del widget «Fichas de terapias» de esta misma página, en el orden del menú «Terapias».', 'parkinson-bahia-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					''          => esc_html__( 'Los que escriba aquí', 'parkinson-bahia-core' ),
					'therapies' => esc_html__( 'Las terapias', 'parkinson-bahia-core' ),
				],
				'default'     => '',
			]
		);
		$this->add_control(
			'chips_label',
			[
				'label'       => esc_html__( 'Nombre de la lista de accesos', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Lo oyen quienes usan lector de pantalla.', 'parkinson-bahia-core' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Ir a una terapia', 'parkinson-bahia-core' ),
				'label_block' => true,
				'condition'   => [ 'jumps_source' => 'therapies' ],
			]
		);
		$jumps = new Repeater();
		$jumps->add_control(
			'label',
			[
				'label'       => esc_html__( 'Texto', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$this->link_controls( $jumps, 'link', esc_html__( 'Enlace', 'parkinson-bahia-core' ) );
		$this->add_control(
			'jumps',
			[
				'label'       => esc_html__( 'Accesos', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Botones grandes bajo el título. Para saltar a una sección de esta página elige «Enlace personalizado» y escribe su ancla, por ejemplo #socio. El primero va destacado.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $jumps->get_controls(),
				'title_field' => '{{ label }}',
				'default'     => [],
				'condition'   => [ 'jumps_source' => '' ],
			]
		);
		$this->add_control(
			'jumps_style',
			[
				'label'       => esc_html__( 'Cómo se ven los accesos', 'parkinson-bahia-core' ),
				'description' => esc_html__( '«Índice»: el primero es un botón y el resto, enlaces sencillos bajo «En esta página». Va mejor cuando hay muchos accesos o textos largos.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					''      => esc_html__( 'Botones grandes', 'parkinson-bahia-core' ),
					'index' => esc_html__( 'Botón principal e índice', 'parkinson-bahia-core' ),
				],
				'default'     => '',
				'condition'   => [ 'jumps_source' => '' ],
			]
		);
		$this->add_control(
			'index_label',
			[
				'label'       => esc_html__( 'Título del índice', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'En esta página', 'parkinson-bahia-core' ),
				'label_block' => true,
				'condition'   => [
					'jumps_source' => '',
					'jumps_style'  => 'index',
				],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Solo consulta contenidos cuando los accesos salen de las terapias.
	 */
	protected function is_dynamic_content(): bool {
		return 'therapies' === $this->get_settings( 'jumps_source' );
	}

	/**
	 * Accesos a las fichas de las terapias: ancla => nombre.
	 *
	 * @return array<string, string>
	 */
	private function therapy_chips(): array {
		$chips = [];

		foreach ( $this->items( \DanielLucia\ParkinsonBahia\Core\PostTypes\Terapia::POST_TYPE, 30 ) as $item ) {
			$chips[ '#' . TherapyDetails::ANCHOR_PREFIX . $item->post_name ] = get_the_title( $item );
		}

		return $chips;
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$image    = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'         => 'pbc-page-hero__image',
				'sizes'         => '(min-width: 960px) 580px, 100vw',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			],
			'yes' === ( $settings['image_decorative'] ?? '' )
		);
		$facts    = array_filter(
			is_array( $settings['facts'] ?? null ) ? $settings['facts'] : [],
			static fn ( $fact ): bool => '' !== trim( (string) ( $fact['label'] ?? '' ) ) && '' !== trim( (string) ( $fact['value'] ?? '' ) )
		);
		$has_side = '' !== $image || $facts;
		$variant = (string) ( $settings['variant'] ?? '' );
		$jumps   = [];
		$chips   = 'therapies' === ( $settings['jumps_source'] ?? '' ) ? $this->therapy_chips() : [];
		$as_index = 'index' === ( $settings['jumps_style'] ?? '' );

		foreach ( $chips ? [] : ( is_array( $settings['jumps'] ?? null ) ? $settings['jumps'] : [] ) as $index => $jump ) {
			$link = $this->link_value( $jump, 'link' );
			if ( '' !== trim( (string) ( $jump['label'] ?? '' ) ) && $link ) {
				$key = 'jump-' . $index;
				$this->add_link_attributes( $key, $link );
				if ( $as_index ) {
					$this->add_render_attribute( $key, 'class', $jumps ? 'pbc-page-hero__index-link' : 'pbc-button pbc-button--primary pbc-page-hero__main' );
				} else {
					$this->add_render_attribute( $key, 'class', $jumps ? 'pbc-page-hero__jump' : 'pbc-page-hero__jump pbc-page-hero__jump--main' );
				}
				$jumps[ $key ] = (string) $jump['label'];
			}
		}
		?>
		<section class="pbc-page-hero<?php echo in_array( $variant, [ 'sand', 'clay', 'dark' ], true ) ? ' pbc-page-hero--' . esc_attr( $variant ) : ''; ?>">
			<div class="pbc-page-hero__inner">
				<?php $this->render_breadcrumb(); ?>
				<div class="pbc-page-hero__grid<?php echo $has_side ? '' : ' pbc-page-hero__grid--text'; ?>">
					<div class="pbc-page-hero__body">
						<?php $this->render_text( (string) $settings['eyebrow'], 'pbc-page-hero__eyebrow' ); ?>
						<?php $this->render_heading( $this->heading_tag( $settings, 'h1' ), (string) $settings['title'], 'pbc-page-hero__title' ); ?>
						<?php $this->render_text( (string) $settings['text'], 'pbc-page-hero__text' ); ?>
					</div>
					<?php if ( '' !== $image ) : ?>
						<figure class="pbc-page-hero__figure">
							<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
						</figure>
					<?php endif; ?>
					<?php if ( $facts ) : ?>
						<dl class="pbc-page-hero__facts">
							<?php foreach ( $facts as $fact ) : ?>
								<div class="pbc-page-hero__fact">
									<dt class="pbc-page-hero__fact-label"><?php echo esc_html( $fact['label'] ); ?></dt>
									<dd class="pbc-page-hero__fact-value"><?php echo esc_html( $fact['value'] ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</div>
				<?php if ( $chips ) : ?>
					<ul class="pbc-page-hero__chips"<?php echo '' !== trim( (string) ( $settings['chips_label'] ?? '' ) ) ? ' aria-label="' . esc_attr( $settings['chips_label'] ) . '"' : ''; ?>>
						<?php foreach ( $chips as $anchor => $label ) : ?>
							<li class="pbc-page-hero__chips-item"><a class="pbc-page-hero__chip" href="<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $jumps && $as_index ) : ?>
					<?php
					$main_key   = (string) array_key_first( $jumps );
					$links      = array_slice( $jumps, 1, null, true );
					$index_id   = $this->title_id() . '-indice';
					$index_name = trim( (string) ( $settings['index_label'] ?? '' ) );
					?>
					<div class="pbc-page-hero__index-block">
						<a <?php $this->print_render_attribute_string( $main_key ); ?>><?php echo esc_html( $jumps[ $main_key ] ); ?></a>
						<?php if ( $links ) : ?>
							<nav class="pbc-page-hero__index" aria-labelledby="<?php echo esc_attr( $index_id ); ?>">
								<p class="pbc-page-hero__index-title" id="<?php echo esc_attr( $index_id ); ?>"><?php echo esc_html( '' !== $index_name ? $index_name : __( 'En esta página', 'parkinson-bahia-core' ) ); ?></p>
								<ul class="pbc-page-hero__index-list">
									<?php foreach ( $links as $key => $label ) : ?>
										<li><a <?php $this->print_render_attribute_string( $key ); ?>><span class="pbc-page-hero__index-text"><?php echo esc_html( $label ); ?></span></a></li>
									<?php endforeach; ?>
								</ul>
							</nav>
						<?php endif; ?>
					</div>
				<?php elseif ( $jumps ) : ?>
					<ul class="pbc-page-hero__jumps">
						<?php foreach ( $jumps as $key => $label ) : ?>
							<li class="pbc-page-hero__jumps-item"><a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
