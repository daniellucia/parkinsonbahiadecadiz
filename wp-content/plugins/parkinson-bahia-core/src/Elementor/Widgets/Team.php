<?php
/**
 * Widget «Equipo profesional»: texto, fotos opcionales y la lista de puestos (tipo de contenido Puesto).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Puesto;

defined( 'ABSPATH' ) || exit;

final class Team extends Widget {

	protected function slug(): string {
		return 'team';
	}

	public function get_title(): string {
		return esc_html__( 'Equipo profesional', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Los puestos se añaden en «La asociación → Equipo profesional» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Nuestro equipo', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->anchor_control();
		$this->end_controls_section();

		$this->start_controls_section( 'media', [ 'label' => esc_html__( 'Fotos (opcional)', 'parkinson-bahia-core' ) ] );
		foreach ( [ 1, 2, 3 ] as $number ) {
			/* translators: %d: número de la foto. */
			$this->image_control( 'image_' . $number, sprintf( esc_html__( 'Foto %d', 'parkinson-bahia-core' ), $number ) );
		}
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$items    = $this->items( Puesto::POST_TYPE );
		$photos   = [];

		foreach ( [ 1, 2, 3 ] as $number ) {
			$html = $this->image_html(
				$settings[ 'image_' . $number ] ?? null,
				'pbc-square',
				[
					'class'   => 'pbc-team__photo',
					'sizes'   => '(min-width: 900px) 180px, 30vw',
					'loading' => 'lazy',
				]
			);
			if ( '' !== $html ) {
				$photos[] = $html;
			}
		}
		?>
		<section class="pbc-team"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-team__inner">
				<div class="pbc-team__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-team__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-team__text' ); ?>
					<?php if ( $photos ) : ?>
						<div class="pbc-team__photos">
							<?php echo implode( '', $photos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $items ) : ?>
					<ul class="pbc-team__list">
						<?php foreach ( $items as $item ) : ?>
							<?php $count = (int) get_post_meta( $item->ID, Puesto::META_COUNT, true ); ?>
							<li class="pbc-team__item">
								<?php if ( $count > 0 ) : ?>
									<span class="pbc-team__count"><?php echo esc_html( (string) $count ); ?></span>
								<?php endif; ?>
								<span class="pbc-team__role"><?php echo esc_html( get_the_title( $item ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php $this->render_empty_notice( __( 'Todavía no hay puestos. Añádelos en «La asociación → Equipo profesional».', 'parkinson-bahia-core' ) ); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
