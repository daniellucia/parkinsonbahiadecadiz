<?php
/**
 * Widget «Horario del día»: texto, foto y una lista de momentos con su hora.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Schedule extends Widget {

	protected function slug(): string {
		return 'schedule';
	}

	public function get_title(): string {
		return esc_html__( 'Horario del día', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-clock-o';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Así es un día en el centro', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->image_control( 'image', esc_html__( 'Foto (opcional)', 'parkinson-bahia-core' ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'time',
			[
				'label'       => esc_html__( 'Hora', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Por ejemplo: 9:00', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
			]
		);
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Qué se hace', 'parkinson-bahia-core' ),
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
				'label'       => esc_html__( 'Momentos del día (en orden)', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ time }} · {{ title }}',
				'default'     => [],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$items    = array_filter(
			is_array( $settings['items'] ?? null ) ? $settings['items'] : [],
			static fn ( $item ): bool => '' !== trim( (string) ( $item['title'] ?? '' ) )
		);
		$image    = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'   => 'pbc-schedule__image',
				'sizes'   => '(min-width: 900px) 560px, 100vw',
				'loading' => 'lazy',
			]
		);
		?>
		<section class="pbc-schedule" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-schedule__inner">
				<div class="pbc-schedule__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-schedule__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-schedule__text' ); ?>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
				</div>
				<?php if ( $items ) : ?>
					<ol class="pbc-schedule__list">
						<?php foreach ( $items as $item ) : ?>
							<li class="pbc-schedule__item">
								<span class="pbc-schedule__time"><?php echo esc_html( (string) ( $item['time'] ?? '' ) ); ?></span>
								<span class="pbc-schedule__detail">
									<span class="pbc-schedule__item-title"><?php echo esc_html( $item['title'] ); ?></span>
									<?php if ( '' !== trim( (string) ( $item['text'] ?? '' ) ) ) : ?>
										<span class="pbc-schedule__item-text"><?php echo esc_html( $item['text'] ); ?></span>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
