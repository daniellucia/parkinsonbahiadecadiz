<?php
/**
 * Widget «Texto con foto»: una foto a un lado y un título con texto al otro.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class MediaText extends Widget {

	protected function slug(): string {
		return 'media-text';
	}

	public function get_title(): string {
		return esc_html__( 'Texto con foto', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-image-box';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->add_control(
			'text',
			[
				'label'       => esc_html__( 'Texto', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Deja una línea en blanco para separar párrafos.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
			]
		);
		$this->text_control( 'button_label', esc_html__( 'Texto del botón (opcional)', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button_link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->image_control( 'image', esc_html__( 'Foto (opcional)', 'parkinson-bahia-core' ) );
		$this->add_control(
			'image_side',
			[
				'label'   => esc_html__( 'Lado de la foto', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''      => esc_html__( 'Izquierda', 'parkinson-bahia-core' ),
					'right' => esc_html__( 'Derecha', 'parkinson-bahia-core' ),
				],
				'default' => '',
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
				],
				'default' => 'sand',
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$title_id   = $this->title_id();
		$paragraphs = array_filter( array_map( 'trim', preg_split( '/\R{2,}/', (string) $settings['text'] ) ?: [] ) );
		$link       = $this->link_value( $settings, 'button_link' );
		$image      = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'   => 'pbc-media-text__image',
				'sizes'   => '(min-width: 900px) 660px, 100vw',
				'loading' => 'lazy',
			]
		);
		$classes    = [ 'pbc-media-text' ];

		if ( 'sand' === ( $settings['variant'] ?? '' ) ) {
			$classes[] = 'pbc-media-text--sand';
		}
		if ( 'right' === ( $settings['image_side'] ?? '' ) ) {
			$classes[] = 'pbc-media-text--image-right';
		}
		if ( '' === $image ) {
			$classes[] = 'pbc-media-text--text-only';
		}
		if ( $link ) {
			$this->add_link_attributes( 'button', $link );
		}
		$this->add_render_attribute( 'button', 'class', 'pbc-button pbc-button--primary' );
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-media-text__inner">
				<?php if ( '' !== $image ) : ?>
					<figure class="pbc-media-text__figure">
						<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
					</figure>
				<?php endif; ?>
				<div class="pbc-media-text__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-media-text__title', $title_id ); ?>
					<?php foreach ( $paragraphs as $paragraph ) : ?>
						<?php $this->render_text( $paragraph, 'pbc-media-text__text' ); ?>
					<?php endforeach; ?>
					<?php if ( '' !== trim( (string) $settings['button_label'] ) && $link ) : ?>
						<a <?php $this->print_render_attribute_string( 'button' ); ?>><?php echo esc_html( $settings['button_label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
