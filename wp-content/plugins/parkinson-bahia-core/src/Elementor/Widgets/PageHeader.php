<?php
/**
 * Widget «Cabecera de página»: migas de pan, título principal (h1), texto y foto.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class PageHeader extends Widget {

	protected function slug(): string {
		return 'page-header';
	}

	public function get_title(): string {
		return esc_html__( 'Cabecera de página', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-header';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'eyebrow', esc_html__( 'Antetítulo', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título de la página', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control( 'h1' );
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->image_control( 'image', esc_html__( 'Foto (opcional)', 'parkinson-bahia-core' ) );
		$this->add_control(
			'image_decorative',
			[
				'label'       => esc_html__( 'Foto decorativa', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Actívalo solo si la foto no aporta información.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SWITCHER,
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$image    = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'         => 'pbc-page-hero__image',
				'sizes'         => '(min-width: 960px) 580px, 100vw',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			],
			'yes' === ( $settings['image_decorative'] ?? '' )
		);
		$page_id  = (int) get_queried_object_id();
		?>
		<section class="pbc-page-hero">
			<div class="pbc-page-hero__inner">
				<?php if ( $page_id > 0 && ! is_front_page() ) : ?>
					<nav class="pbc-breadcrumb" aria-label="<?php esc_attr_e( 'Estás en', 'parkinson-bahia-core' ); ?>">
						<ol class="pbc-breadcrumb__list">
							<li class="pbc-breadcrumb__item"><a class="pbc-breadcrumb__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'parkinson-bahia-core' ); ?></a></li>
							<?php foreach ( array_reverse( get_post_ancestors( $page_id ) ) as $ancestor ) : ?>
								<li class="pbc-breadcrumb__item"><a class="pbc-breadcrumb__link" href="<?php echo esc_url( get_permalink( $ancestor ) ); ?>"><?php echo esc_html( get_the_title( $ancestor ) ); ?></a></li>
							<?php endforeach; ?>
							<li class="pbc-breadcrumb__item" aria-current="page"><?php echo esc_html( get_the_title( $page_id ) ); ?></li>
						</ol>
					</nav>
				<?php endif; ?>
				<div class="pbc-page-hero__grid<?php echo '' === $image ? ' pbc-page-hero__grid--text' : ''; ?>">
					<div class="pbc-page-hero__body">
						<?php $this->render_text( (string) $settings['eyebrow'], 'pbc-page-hero__eyebrow' ); ?>
						<?php $this->render_heading( $this->heading_tag( $settings, 'h1' ), (string) $settings['title'], 'pbc-page-hero__title' ); ?>
						<?php $this->render_text( (string) $settings['text'], 'pbc-page-hero__text' ); ?>
					</div>
					<?php if ( '' !== $image ) : ?>
						<figure class="pbc-page-hero__figure">
							<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
						</figure>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
