<?php
/**
 * Widget «Vídeos de ejercicios»: vídeos de YouTube que se cargan al pulsarlos.
 *
 * Los datos salen del tipo de contenido Vídeo. Hasta que la persona pulsa,
 * solo se ve la miniatura, servida desde esta web: no se conecta con YouTube
 * ni se instalan sus cookies. Sin JavaScript, el enlace abre el vídeo en YouTube.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Video;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Videos extends Widget {

	private const MAX_ITEMS = 12;

	protected function slug(): string {
		return 'videos';
	}

	public function get_title(): string {
		return esc_html__( 'Vídeos de ejercicios', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-youtube';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Los vídeos se añaden en «Familias → Vídeos de ejercicios»: nombre, dirección de YouTube y miniatura (imagen destacada).', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Ejercicios para hacer en casa', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->add_control(
			'count',
			[
				'label'   => esc_html__( 'Cuántos mostrar', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => self::MAX_ITEMS,
				'default' => 3,
			]
		);
		$this->text_control( 'channel_label', esc_html__( 'Texto del enlace al canal', 'parkinson-bahia-core' ), esc_html__( 'Ver todos en YouTube', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'channel_link', esc_html__( 'Enlace al canal', 'parkinson-bahia-core' ) );
		$this->text_control( 'note', esc_html__( 'Aviso bajo los vídeos', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'privacy_note', esc_html__( 'Aviso sobre YouTube', 'parkinson-bahia-core' ), esc_html__( 'Al pulsar un vídeo se carga desde YouTube, que puede guardar cookies en tu navegador.', 'parkinson-bahia-core' ), true );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$tag      = $this->heading_tag( $settings );
		// Los nombres de los vídeos van un nivel por debajo del título de la sección.
		$item_tag = 'h3' === $tag ? 'h4' : 'h3';
		$count    = max( 1, min( self::MAX_ITEMS, absint( $settings['count'] ?? 3 ) ) );
		$videos   = [];

		foreach ( $this->items( Video::POST_TYPE, self::MAX_ITEMS ) as $item ) {
			$id = Video::youtube_id( $item->ID );
			if ( '' !== $id ) {
				$videos[] = [ $item, $id ];
			}
			if ( count( $videos ) >= $count ) {
				break;
			}
		}

		$this->prime_thumbnails( array_column( $videos, 0 ) );

		$channel = $this->link_value( $settings, 'channel_link' );
		if ( $channel ) {
			$this->add_link_attributes( 'channel', $channel );
		}
		$this->add_render_attribute( 'channel', 'class', 'pbc-videos__channel' );
		?>
		<section class="pbc-videos"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-videos__inner">
				<div class="pbc-videos__header">
					<?php $this->render_heading( $tag, (string) $settings['title'], 'pbc-videos__title', $title_id ); ?>
					<?php if ( $channel && '' !== trim( (string) $settings['channel_label'] ) ) : ?>
						<a <?php $this->print_render_attribute_string( 'channel' ); ?>><?php echo esc_html( $settings['channel_label'] ); ?></a>
					<?php endif; ?>
				</div>

				<?php if ( $videos ) : ?>
					<ul class="pbc-videos__list">
						<?php foreach ( $videos as [ $item, $youtube_id ] ) : ?>
							<?php
							$name  = get_the_title( $item );
							$thumb = has_post_thumbnail( $item ) ? (string) get_the_post_thumbnail(
								$item,
								'pbc-card',
								[
									'class'   => 'pbc-videos__image',
									'alt'     => '',
									'sizes'   => '(min-width: 1000px) 380px, 100vw',
									'loading' => 'lazy',
								]
							) : '';
							?>
							<li class="pbc-videos__item">
								<div class="pbc-videos__frame" data-pbc-video-frame>
									<a class="pbc-videos__facade" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $youtube_id ); ?>" data-pbc-video="<?php echo esc_attr( $youtube_id ); ?>" data-pbc-video-title="<?php echo esc_attr( $name ); ?>">
										<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por get_the_post_thumbnail(). ?>
										<span class="pbc-videos__play">
											<svg class="pbc-videos__play-icon" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M6 4l10 6-10 6z" fill="currentColor"/></svg>
											<?php esc_html_e( 'Ver el vídeo', 'parkinson-bahia-core' ); ?>
											<span class="screen-reader-text"><?php echo esc_html( '«' . $name . '»' ); ?></span>
										</span>
									</a>
								</div>
								<?php $this->render_heading( $item_tag, $name, 'pbc-videos__name' ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php $this->render_empty_notice( __( 'Todavía no hay vídeos con dirección de YouTube. Añádelos en «Familias → Vídeos de ejercicios».', 'parkinson-bahia-core' ) ); ?>
				<?php endif; ?>

				<?php $this->render_text( (string) $settings['note'], 'pbc-videos__note' ); ?>
				<?php if ( $videos ) : ?>
					<?php $this->render_text( (string) $settings['privacy_note'], 'pbc-videos__privacy' ); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
