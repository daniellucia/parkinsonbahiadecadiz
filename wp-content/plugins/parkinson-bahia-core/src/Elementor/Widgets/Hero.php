<?php
/**
 * Widget «Portada»: titular, texto, dos botones y foto principal.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Hero extends Widget {

	protected function slug(): string {
		return 'hero';
	}

	public function get_title(): string {
		return esc_html__( 'Portada', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-banner';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'eyebrow', esc_html__( 'Antetítulo', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el titular', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control( 'h1' );
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->end_controls_section();

		$this->start_controls_section( 'buttons', [ 'label' => esc_html__( 'Botones', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'primary_label', esc_html__( 'Botón principal', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'primary_link', esc_html__( 'Enlace del botón principal', 'parkinson-bahia-core' ) );
		$this->text_control( 'secondary_label', esc_html__( 'Botón secundario', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'secondary_link', esc_html__( 'Enlace del botón secundario', 'parkinson-bahia-core' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'media', [ 'label' => esc_html__( 'Foto', 'parkinson-bahia-core' ) ] );
		$this->image_control( 'image', esc_html__( 'Foto', 'parkinson-bahia-core' ) );
		$this->add_control(
			'image_decorative',
			[
				'label'       => esc_html__( 'Foto decorativa', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Actívalo solo si la foto no aporta información.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SWITCHER,
			]
		);
		$this->text_control( 'note_title', esc_html__( 'Nota sobre la foto: título', 'parkinson-bahia-core' ) );
		$this->text_control( 'note_text', esc_html__( 'Nota sobre la foto: texto', 'parkinson-bahia-core' ), '', true );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$image    = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'         => 'pbc-hero__image',
				'sizes'         => '(min-width: 960px) 670px, 100vw',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			],
			'yes' === ( $settings['image_decorative'] ?? '' )
		);

		$links = [];
		foreach ( [ 'primary', 'secondary' ] as $button ) {
			$links[ $button ] = $this->link_value( $settings, $button . '_link' );
			if ( $links[ $button ] ) {
				$this->add_link_attributes( $button, $links[ $button ] );
			}
			$this->add_render_attribute( $button, 'class', 'pbc-button pbc-hero__button pbc-button--' . ( 'primary' === $button ? 'primary' : 'outline' ) );
		}
		?>
		<section class="pbc-hero">
			<div class="pbc-hero__inner">
				<div class="pbc-hero__body">
					<?php $this->render_text( (string) $settings['eyebrow'], 'pbc-hero__eyebrow' ); ?>
					<?php $this->render_heading( $this->heading_tag( $settings, 'h1' ), (string) $settings['title'], 'pbc-hero__title' ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-hero__text' ); ?>
					<div class="pbc-hero__actions">
						<?php foreach ( [ 'primary', 'secondary' ] as $button ) : ?>
							<?php if ( '' !== trim( (string) $settings[ $button . '_label' ] ) && $links[ $button ] ) : ?>
								<a <?php $this->print_render_attribute_string( $button ); ?>><?php echo esc_html( $settings[ $button . '_label' ] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<?php if ( '' !== $image ) : ?>
					<figure class="pbc-hero__figure">
						<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
						<?php if ( '' !== trim( (string) $settings['note_title'] . (string) $settings['note_text'] ) ) : ?>
							<figcaption class="pbc-hero__note">
								<?php if ( '' !== trim( (string) $settings['note_title'] ) ) : ?>
									<strong class="pbc-hero__note-title"><?php echo esc_html( $settings['note_title'] ); ?></strong>
								<?php endif; ?>
								<?php if ( '' !== trim( (string) $settings['note_text'] ) ) : ?>
									<span class="pbc-hero__note-text"><?php echo esc_html( $settings['note_text'] ); ?></span>
								<?php endif; ?>
							</figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
