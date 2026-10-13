<?php
/**
 * Widget «Llamada con botón»: un título, un texto y un botón en una franja.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

final class Cta extends Widget {

	protected function slug(): string {
		return 'cta';
	}

	public function get_title(): string {
		return esc_html__( 'Llamada con botón', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'button_label', esc_html__( 'Texto del botón', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button_link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$link     = $this->link_value( $settings, 'button_link' );

		if ( $link ) {
			$this->add_link_attributes( 'button', $link );
		}
		$this->add_render_attribute( 'button', 'class', 'pbc-button pbc-button--primary pbc-cta__button' );
		?>
		<section class="pbc-cta"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-cta__inner">
				<div class="pbc-cta__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-cta__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-cta__text' ); ?>
				</div>
				<?php if ( '' !== trim( (string) $settings['button_label'] ) && $link ) : ?>
					<a <?php $this->print_render_attribute_string( 'button' ); ?>><?php echo esc_html( $settings['button_label'] ); ?></a>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
