<?php
/**
 * Widget «Pasos»: una secuencia numerada (por ejemplo, cómo pedir plaza).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Steps extends Widget {

	protected function slug(): string {
		return 'steps';
	}

	public function get_title(): string {
		return esc_html__( 'Pasos', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'intro', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Título del paso', 'parkinson-bahia-core' ),
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
		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Pasos (en orden)', 'parkinson-bahia-core' ),
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
		<section class="pbc-steps" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-steps__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-steps__title', $title_id ); ?>
				<?php $this->render_text( (string) $settings['intro'], 'pbc-steps__intro' ); ?>
				<?php if ( $items ) : ?>
					<ol class="pbc-steps__list">
						<?php foreach ( $items as $item ) : ?>
							<?php
							if ( '' === trim( (string) ( $item['title'] ?? '' ) ) ) {
								continue;
							}
							?>
							<li class="pbc-steps__item">
								<span class="pbc-steps__item-title"><?php echo esc_html( $item['title'] ); ?></span>
								<?php if ( '' !== trim( (string) ( $item['text'] ?? '' ) ) ) : ?>
									<span class="pbc-steps__item-text"><?php echo esc_html( $item['text'] ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
