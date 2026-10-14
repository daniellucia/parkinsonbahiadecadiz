<?php
/**
 * Widget «Destacado con fotos»: texto, datos clave, botón y mosaico de tres fotos.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Feature extends Widget {

	protected function slug(): string {
		return 'feature';
	}

	public function get_title(): string {
		return esc_html__( 'Destacado con fotos', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-image-box';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'eyebrow', esc_html__( 'Antetítulo', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			[
				'label' => esc_html__( 'Dato', 'parkinson-bahia-core' ),
				'type'  => Controls_Manager::TEXT,
			]
		);
		$repeater->add_control(
			'value',
			[
				'label' => esc_html__( 'Valor', 'parkinson-bahia-core' ),
				'type'  => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'facts',
			[
				'label'       => esc_html__( 'Datos clave', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ label }}: {{ value }}',
				'default'     => [],
			]
		);

		$this->text_control( 'button_label', esc_html__( 'Texto del botón', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button_link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'media', [ 'label' => esc_html__( 'Fotos', 'parkinson-bahia-core' ) ] );
		foreach ( [ 1, 2, 3 ] as $number ) {
			/* translators: %d: número de la foto. */
			$this->image_control( 'image_' . $number, sprintf( esc_html__( 'Foto %d', 'parkinson-bahia-core' ), $number ) );
			/* translators: %d: número de la foto. */
			$this->text_control( 'caption_' . $number, sprintf( esc_html__( 'Pie de la foto %d', 'parkinson-bahia-core' ), $number ) );
		}
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$facts    = is_array( $settings['facts'] ?? null ) ? $settings['facts'] : [];
		$title_id = $this->title_id();
		$figures  = [];

		foreach ( [ 1, 2, 3 ] as $number ) {
			$html = $this->image_html(
				$settings[ 'image_' . $number ] ?? null,
				1 === $number ? 'pbc-feature' : 'pbc-square',
				[
					'class'   => 'pbc-feature__image',
					'sizes'   => 1 === $number ? '(min-width: 900px) 660px, 100vw' : '(min-width: 900px) 330px, 50vw',
					'loading' => 'lazy',
				]
			);
			if ( '' !== $html ) {
				$figures[ $number ] = [ $html, (string) ( $settings[ 'caption_' . $number ] ?? '' ) ];
			}
		}

		$button_link = $this->link_value( $settings, 'button_link' );
		if ( $button_link ) {
			$this->add_link_attributes( 'button', $button_link );
		}
		$this->add_render_attribute( 'button', 'class', 'pbc-button pbc-button--primary' );
		?>
		<section class="pbc-feature" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-feature__inner">
				<div class="pbc-feature__body">
					<?php $this->render_text( (string) $settings['eyebrow'], 'pbc-feature__eyebrow' ); ?>
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-feature__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-feature__text' ); ?>
					<?php if ( $facts ) : ?>
						<dl class="pbc-feature__facts">
							<?php foreach ( $facts as $fact ) : ?>
								<?php
								if ( '' === trim( (string) ( $fact['label'] ?? '' ) ) || '' === trim( (string) ( $fact['value'] ?? '' ) ) ) {
									continue;
								}
								?>
								<div class="pbc-feature__fact">
									<dt class="pbc-feature__fact-label"><?php echo esc_html( $fact['label'] ); ?></dt>
									<dd class="pbc-feature__fact-value"><?php echo esc_html( $fact['value'] ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $settings['button_label'] ) && $button_link ) : ?>
						<a <?php $this->print_render_attribute_string( 'button' ); ?>><?php echo esc_html( $settings['button_label'] ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $figures ) : ?>
					<div class="pbc-feature__gallery">
						<?php foreach ( $figures as $number => [ $html, $caption ] ) : ?>
							<figure class="pbc-feature__figure pbc-feature__figure--<?php echo esc_attr( (string) $number ); ?>">
								<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
								<?php if ( '' !== trim( $caption ) ) : ?>
									<figcaption class="pbc-feature__caption"><?php echo esc_html( $caption ); ?></figcaption>
								<?php endif; ?>
							</figure>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
