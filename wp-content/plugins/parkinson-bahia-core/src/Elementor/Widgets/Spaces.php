<?php
/**
 * Widget «Instalaciones»: las salas del centro con su foto (tipo de contenido Espacio).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Espacio;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Spaces extends Widget {

	protected function slug(): string {
		return 'spaces';
	}

	public function get_title(): string {
		return esc_html__( 'Instalaciones', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las salas y sus fotos se añaden en «Centro de día → Instalaciones» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Las instalaciones', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'footnote', esc_html__( 'Nota al pie', 'parkinson-bahia-core' ), '', true );
		$this->add_control(
			'count',
			[
				'label'       => esc_html__( 'Cuántas mostrar', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Déjalo en 0 para mostrarlas todas.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 60,
				'default'     => 0,
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$count    = absint( $settings['count'] ?? 0 );
		$items    = array_filter( $this->items( Espacio::POST_TYPE, $count > 0 ? $count : 60 ), 'has_post_thumbnail' );

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay instalaciones con foto. Añádelas en «Centro de día → Instalaciones».', 'parkinson-bahia-core' ) );
			return;
		}

		// Una sola consulta para todas las fotos.
		update_post_thumbnail_cache( new \WP_Query( [ 'post__in' => wp_list_pluck( $items, 'ID' ), 'post_type' => Espacio::POST_TYPE, 'posts_per_page' => count( $items ), 'no_found_rows' => true ] ) );
		?>
		<section class="pbc-spaces" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-spaces__inner">
				<div class="pbc-spaces__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-spaces__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-spaces__text' ); ?>
				</div>
				<ul class="pbc-spaces__list">
					<?php foreach ( $items as $item ) : ?>
						<li class="pbc-spaces__item">
							<figure class="pbc-spaces__figure">
								<?php
								echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por WordPress.
									$item,
									'pbc-card',
									[
										'class'   => 'pbc-spaces__image',
										'sizes'   => '(min-width: 1100px) 290px, (min-width: 620px) 50vw, 100vw',
										'loading' => 'lazy',
									]
								);
								?>
								<figcaption class="pbc-spaces__caption"><?php echo esc_html( get_the_title( $item ) ); ?></figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php $this->render_text( (string) $settings['footnote'], 'pbc-spaces__footnote' ); ?>
			</div>
		</section>
		<?php
	}
}
