<?php
/**
 * Widget «Cita destacada»: la misión u otra frase importante, con un texto de apoyo.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

final class Mission extends Widget {

	protected function slug(): string {
		return 'mission';
	}

	public function get_title(): string {
		return esc_html__( 'Cita destacada', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-blockquote';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Nuestra misión', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'quote', esc_html__( 'Frase destacada', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		?>
		<section class="pbc-mission" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-mission__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-mission__title', $title_id ); ?>
				<?php if ( '' !== trim( (string) $settings['quote'] ) ) : ?>
					<blockquote class="pbc-mission__quote"><p class="pbc-mission__quote-text"><?php echo esc_html( $settings['quote'] ); ?></p></blockquote>
				<?php endif; ?>
				<?php $this->render_text( (string) $settings['text'], 'pbc-mission__text' ); ?>
			</div>
		</section>
		<?php
	}
}
