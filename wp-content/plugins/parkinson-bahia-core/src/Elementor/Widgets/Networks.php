<?php
/**
 * Widget «Redes y federaciones»: entidades de las que forma parte la asociación (tipo de contenido Entidad).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Entidad;

defined( 'ABSPATH' ) || exit;

final class Networks extends Widget {

	protected function slug(): string {
		return 'networks';
	}

	public function get_title(): string {
		return esc_html__( 'Redes y federaciones', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-global-settings';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las entidades se añaden en «La asociación → Redes y federaciones» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Formamos parte de', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$items    = $this->items( Entidad::POST_TYPE );

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay entidades. Añádelas en «La asociación → Redes y federaciones».', 'parkinson-bahia-core' ) );
			return;
		}
		?>
		<section class="pbc-networks" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-networks__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-networks__title', $title_id ); ?>
				<?php $this->render_text( (string) $settings['text'], 'pbc-networks__text' ); ?>
				<ul class="pbc-networks__list">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$short = (string) get_post_meta( $item->ID, Entidad::META_SHORT, true );
						$url   = (string) get_post_meta( $item->ID, Entidad::META_URL, true );
						$tag   = '' !== $url ? 'a' : 'div';
						?>
						<li class="pbc-networks__item">
							<<?php echo esc_html( $tag ); ?> class="pbc-networks__card"<?php echo '' !== $url ? ' href="' . esc_url( $url ) . '" rel="noopener"' : ''; ?>>
								<?php if ( '' !== $short ) : ?>
									<span class="pbc-networks__short"><?php echo esc_html( $short ); ?></span>
								<?php endif; ?>
								<span class="pbc-networks__name"><?php echo esc_html( get_the_title( $item ) ); ?></span>
							</<?php echo esc_html( $tag ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}
}
