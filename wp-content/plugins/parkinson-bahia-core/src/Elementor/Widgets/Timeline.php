<?php
/**
 * Widget «Historia»: los hitos de la asociación ordenados por año (tipo de contenido Hito).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Hito;

defined( 'ABSPATH' ) || exit;

final class Timeline extends Widget {

	protected function slug(): string {
		return 'timeline';
	}

	public function get_title(): string {
		return esc_html__( 'Historia', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-time-line';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Los hitos se añaden en «La asociación → Historia» del escritorio. Aquí solo se muestran, ordenados por año.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Nuestra historia', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$items    = $this->items( Hito::POST_TYPE );

		usort( $items, static fn ( $a, $b ): int => (int) get_post_meta( $a->ID, Hito::META_YEAR, true ) <=> (int) get_post_meta( $b->ID, Hito::META_YEAR, true ) );

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay hitos. Añádelos en «La asociación → Historia».', 'parkinson-bahia-core' ) );
			return;
		}
		?>
		<section class="pbc-timeline" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-timeline__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-timeline__title', $title_id ); ?>
				<ol class="pbc-timeline__list">
					<?php foreach ( $items as $item ) : ?>
						<?php $year = (int) get_post_meta( $item->ID, Hito::META_YEAR, true ); ?>
						<li class="pbc-timeline__item">
							<?php if ( $year > 0 ) : ?>
								<span class="pbc-timeline__year"><?php echo esc_html( (string) $year ); ?></span>
							<?php endif; ?>
							<span class="pbc-timeline__item-title"><?php echo esc_html( get_the_title( $item ) ); ?></span>
							<?php if ( has_excerpt( $item ) ) : ?>
								<span class="pbc-timeline__item-text"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $item ) ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
		<?php
	}
}
