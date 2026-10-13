<?php
/**
 * Widget «Accesos por situación»: tarjetas enlazadas («¿En qué podemos ayudarte?»).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Paths extends Widget {

	protected function slug(): string {
		return 'paths';
	}

	public function get_title(): string {
		return esc_html__( 'Accesos por situación', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-info-box';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( '¿En qué podemos ayudarte?', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'intro', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Título', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'text',
			[
				'label' => esc_html__( 'Texto', 'parkinson-bahia-core' ),
				'type'  => Controls_Manager::TEXTAREA,
			]
		);
		$repeater->add_control(
			'cta',
			[
				'label'       => esc_html__( 'Texto del enlace', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$this->link_controls( $repeater, 'link', esc_html__( 'Enlace', 'parkinson-bahia-core' ) );

		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Tarjetas', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ title }}',
				'default'     => [],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$items    = is_array( $settings['items'] ?? null ) ? $settings['items'] : [];
		$title_id = $this->title_id();
		?>
		<section class="pbc-paths" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-paths__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-paths__title', $title_id ); ?>
				<?php $this->render_text( (string) $settings['intro'], 'pbc-paths__intro' ); ?>
				<?php if ( $items ) : ?>
					<ul class="pbc-paths__list">
						<?php foreach ( $items as $index => $item ) : ?>
							<?php
							$link = $this->link_value( $item, 'link' );
							if ( '' === trim( (string) ( $item['title'] ?? '' ) ) || ! $link ) {
								continue;
							}
							$key = 'path-' . $index;
							$this->add_link_attributes( $key, $link );
							$this->add_render_attribute( $key, 'class', 'pbc-paths__card' );
							?>
							<li class="pbc-paths__item">
								<a <?php $this->print_render_attribute_string( $key ); ?>>
									<span class="pbc-paths__card-title"><?php echo esc_html( $item['title'] ); ?></span>
									<?php if ( '' !== trim( (string) ( $item['text'] ?? '' ) ) ) : ?>
										<span class="pbc-paths__card-text"><?php echo esc_html( $item['text'] ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== trim( (string) ( $item['cta'] ?? '' ) ) ) : ?>
										<span class="pbc-paths__card-cta"><?php echo esc_html( $item['cta'] ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
