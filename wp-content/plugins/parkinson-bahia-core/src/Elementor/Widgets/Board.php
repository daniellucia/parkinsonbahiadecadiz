<?php
/**
 * Widget «Junta directiva»: las personas de la junta y su cargo (tipo de contenido Directivo).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Directivo;

defined( 'ABSPATH' ) || exit;

final class Board extends Widget {

	protected function slug(): string {
		return 'board';
	}

	public function get_title(): string {
		return esc_html__( 'Junta directiva', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las personas de la junta se añaden en «La asociación → Junta directiva» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Junta directiva', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$items    = $this->items( Directivo::POST_TYPE );

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay nadie en la junta. Añade a sus miembros en «La asociación → Junta directiva».', 'parkinson-bahia-core' ) );
			return;
		}
		?>
		<section class="pbc-board" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-board__inner">
				<div class="pbc-board__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-board__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-board__text' ); ?>
				</div>
				<ul class="pbc-board__list">
					<?php foreach ( $items as $item ) : ?>
						<?php $role = (string) get_post_meta( $item->ID, Directivo::META_ROLE, true ); ?>
						<li class="pbc-board__item">
							<?php if ( '' !== $role ) : ?>
								<span class="pbc-board__role"><?php echo esc_html( $role ); ?></span>
							<?php endif; ?>
							<span class="pbc-board__name"><?php echo esc_html( get_the_title( $item ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}
}
