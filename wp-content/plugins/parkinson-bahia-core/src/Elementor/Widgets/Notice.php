<?php
/**
 * Widget «Aviso»: un mensaje importante con un botón (por ejemplo, la lista de espera).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

final class Notice extends Widget {

	protected function slug(): string {
		return 'notice';
	}

	public function get_title(): string {
		return esc_html__( 'Aviso', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-alert';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'lead', esc_html__( 'Frase destacada', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el aviso.', 'parkinson-bahia-core' ) );
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'button_label', esc_html__( 'Texto del botón', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button_link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$lead     = trim( (string) $settings['lead'] );
		$text     = trim( (string) $settings['text'] );
		$link     = $this->link_value( $settings, 'button_link' );

		if ( '' === $lead && '' === $text ) {
			return;
		}

		if ( $link ) {
			$this->add_link_attributes( 'button', $link );
		}
		$this->add_render_attribute( 'button', 'class', 'pbc-button pbc-button--primary pbc-notice__button' );
		?>
		<div class="pbc-notice">
			<div class="pbc-notice__box" role="note">
				<p class="pbc-notice__text">
					<?php if ( '' !== $lead ) : ?>
						<strong class="pbc-notice__lead"><?php echo esc_html( $lead ); ?></strong>
					<?php endif; ?>
					<?php echo esc_html( $text ); ?>
				</p>
				<?php if ( '' !== trim( (string) $settings['button_label'] ) && $link ) : ?>
					<a <?php $this->print_render_attribute_string( 'button' ); ?>><?php echo esc_html( $settings['button_label'] ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
