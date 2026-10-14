<?php
/**
 * Widget «Últimas noticias»: las entradas más recientes del blog.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use WP_Query;

defined( 'ABSPATH' ) || exit;

final class LatestNews extends Widget {

	protected function slug(): string {
		return 'latest-news';
	}

	public function get_title(): string {
		return esc_html__( 'Últimas noticias', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->add_control(
			'source_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Las noticias se escriben en el menú «Entradas». Aquí se muestran las más recientes.', 'parkinson-bahia-core' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Actualidad', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->add_control(
			'count',
			[
				'label'   => esc_html__( 'Cuántas mostrar', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 3,
			]
		);
		$this->text_control( 'more_label', esc_html__( 'Texto del enlace a todas', 'parkinson-bahia-core' ), esc_html__( 'Ver todas las noticias', 'parkinson-bahia-core' ) );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$page_id  = (int) get_option( 'page_for_posts' );
		$more_url = $page_id > 0 ? (string) get_permalink( $page_id ) : '';
		$default  = (int) get_option( 'default_category' );

		$query = new WP_Query(
			[
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => max( 1, min( 6, absint( $settings['count'] ?? 3 ) ) ),
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			]
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		// Una sola consulta para todas las miniaturas.
		update_post_thumbnail_cache( $query );
		?>
		<section class="pbc-latest-news" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-latest-news__inner">
				<div class="pbc-latest-news__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-latest-news__title', $title_id ); ?>
					<?php if ( '' !== $more_url && '' !== trim( (string) $settings['more_label'] ) ) : ?>
						<a class="pbc-latest-news__more" href="<?php echo esc_url( $more_url ); ?>"><?php echo esc_html( $settings['more_label'] ); ?></a>
					<?php endif; ?>
				</div>
				<div class="pbc-latest-news__grid">
					<?php foreach ( $query->posts as $news ) : ?>
						<?php
						$category = null;
						foreach ( get_the_category( $news->ID ) as $term ) {
							if ( $term->term_id !== $default ) {
								$category = $term;
								break;
							}
						}
						?>
						<article class="pbc-latest-news__item">
							<?php
							if ( has_post_thumbnail( $news ) ) {
								echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por WordPress.
									$news,
									'pbc-card',
									[
										'class'   => 'pbc-latest-news__image',
										'sizes'   => '(min-width: 1040px) 440px, (min-width: 700px) 50vw, 100vw',
										'loading' => 'lazy',
									]
								);
							}
							?>
							<p class="pbc-latest-news__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c', $news ) ); ?>"><?php echo esc_html( get_the_date( '', $news ) ); ?></time>
								<?php if ( $category ) : ?>
									<span class="pbc-latest-news__category"><?php echo esc_html( $category->name ); ?></span>
								<?php endif; ?>
							</p>
							<h3 class="pbc-latest-news__item-title">
								<a class="pbc-latest-news__link" href="<?php echo esc_url( get_permalink( $news ) ); ?>"><?php echo esc_html( get_the_title( $news ) ); ?></a>
							</h3>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
