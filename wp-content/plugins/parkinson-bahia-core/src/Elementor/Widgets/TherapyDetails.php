<?php
/**
 * Widget «Fichas de terapias»: una sección por terapia con foto, resumen,
 * «Qué trabajamos» y «Quién la hace».
 *
 * Los datos salen del tipo de contenido Terapia. Cada ficha lleva el ancla
 * «terapia-<slug>», que usan los accesos de «Cabecera de página».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Terapia;

defined( 'ABSPATH' ) || exit;

final class TherapyDetails extends Widget {

	/**
	 * Prefijo del ancla de cada ficha.
	 */
	public const ANCHOR_PREFIX = 'terapia-';

	protected function slug(): string {
		return 'therapy-details';
	}

	public function get_title(): string {
		return esc_html__( 'Fichas de terapias', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-post-list';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las terapias se añaden y ordenan en el menú «Terapias» del escritorio: nombre, resumen (extracto), foto (imagen destacada), «Qué trabajamos» y «Quién la hace». Cada nombre es un título H2.', 'parkinson-bahia-core' ) );
		$this->text_control( 'works_label', esc_html__( 'Título de la lista', 'parkinson-bahia-core' ), esc_html__( 'Qué trabajamos', 'parkinson-bahia-core' ) );
		$this->text_control( 'who_label', esc_html__( 'Texto antes de «quién la hace»', 'parkinson-bahia-core' ), esc_html__( 'Quién la hace:', 'parkinson-bahia-core' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$items    = $this->items( Terapia::POST_TYPE, 30 );

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay terapias. Añádelas en el menú «Terapias» del escritorio.', 'parkinson-bahia-core' ) );
			return;
		}

		$this->prime_thumbnails( $items );
		?>
		<div class="pbc-therapy-details">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$title_id = 'pbc-therapy-' . $item->ID;
				$works    = Terapia::works( $item->ID );
				$who      = trim( (string) get_post_meta( $item->ID, Terapia::META_WHO, true ) );
				$lead     = trim( wp_strip_all_tags( (string) $item->post_excerpt ) );
				$image    = has_post_thumbnail( $item ) ? (string) get_the_post_thumbnail(
					$item,
					'pbc-feature',
					[
						'class'   => 'pbc-therapy-details__image',
						'sizes'   => '(min-width: 1000px) 560px, 100vw',
						'loading' => 'lazy',
					]
				) : '';
				?>
				<section class="pbc-therapy-details__item<?php echo '' === $image ? ' pbc-therapy-details__item--text' : ''; ?>" id="<?php echo esc_attr( self::ANCHOR_PREFIX . $item->post_name ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
					<?php if ( '' !== $image ) : ?>
						<figure class="pbc-therapy-details__figure">
							<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por get_the_post_thumbnail(). ?>
						</figure>
					<?php endif; ?>
					<div class="pbc-therapy-details__body">
						<h2 class="pbc-therapy-details__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></h2>
						<?php $this->render_text( $lead, 'pbc-therapy-details__lead' ); ?>
						<?php if ( $works ) : ?>
							<div class="pbc-therapy-details__works">
								<?php $this->render_heading( 'h3', (string) $settings['works_label'], 'pbc-therapy-details__works-title' ); ?>
								<ul class="pbc-therapy-details__list">
									<?php foreach ( $works as $work ) : ?>
										<li class="pbc-therapy-details__list-item"><?php echo esc_html( $work ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $who ) : ?>
							<p class="pbc-therapy-details__who">
								<?php if ( '' !== trim( (string) $settings['who_label'] ) ) : ?>
									<strong class="pbc-therapy-details__who-label"><?php echo esc_html( $settings['who_label'] ); ?></strong>
								<?php endif; ?>
								<?php echo esc_html( $who ); ?>
							</p>
						<?php endif; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
