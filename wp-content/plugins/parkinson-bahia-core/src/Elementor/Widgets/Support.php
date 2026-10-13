<?php
/**
 * Widget «Llamada a colaborar»: mensaje y enlaces grandes (socio, donativo, voluntariado).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Support extends Widget {

	protected function slug(): string {
		return 'support';
	}

	public function get_title(): string {
		return esc_html__( 'Llamada a colaborar', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
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
				'label'       => esc_html__( 'Enlaces (el primero va destacado)', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ label }}',
				'default'     => [],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$items    = is_array( $settings['items'] ?? null ) ? $settings['items'] : [];
		$title_id = $this->title_id();
		$first    = true;
		?>
		<section class="pbc-support" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-support__inner">
				<div class="pbc-support__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-support__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-support__text' ); ?>
				</div>
				<?php if ( $items ) : ?>
					<ul class="pbc-support__list">
						<?php foreach ( $items as $index => $item ) : ?>
							<?php
							$link = $this->link_value( $item, 'link' );
							if ( '' === trim( (string) ( $item['label'] ?? '' ) ) || ! $link ) {
								continue;
							}
							$key = 'support-' . $index;
							$this->add_link_attributes( $key, $link );
							$this->add_render_attribute( $key, 'class', $first ? 'pbc-support__link pbc-support__link--main' : 'pbc-support__link' );
							$first = false;
							?>
							<li class="pbc-support__item">
								<a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $item['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
