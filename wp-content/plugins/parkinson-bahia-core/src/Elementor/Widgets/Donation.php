<?php
/**
 * Widget «Formas de donar»: texto y una tarjeta por cada medio de donación.
 *
 * No procesa pagos: cada medio muestra instrucciones o lleva a una página de
 * pago externa. Los datos de tarjeta nunca pasan por esta web.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Donation extends Widget {

	protected function slug(): string {
		return 'donation';
	}

	public function get_title(): string {
		return esc_html__( 'Formas de donar', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-price-list';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Haz un donativo', 'parkinson-bahia-core' ), true );
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

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Medio', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Por ejemplo: Bizum, Tarjeta, Transferencia.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'text',
			[
				'label'       => esc_html__( 'Cómo se hace', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Instrucciones claras: a qué número, con qué concepto…', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXTAREA,
			]
		);
		$repeater->add_control(
			'button_label',
			[
				'label'       => esc_html__( 'Texto del botón (opcional)', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$this->link_controls( $repeater, 'link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->add_control(
			'methods',
			[
				'label'       => esc_html__( 'Medios de donación', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ title }}',
				'default'     => [],
			]
		);
		$this->sticky_control();
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$tag      = $this->heading_tag( $settings );
		$card_tag = 'h3' === $tag ? 'h4' : 'h3';
		$methods  = array_filter(
			is_array( $settings['methods'] ?? null ) ? $settings['methods'] : [],
			static fn ( $method ): bool => '' !== trim( (string) ( $method['title'] ?? '' ) )
		);
		?>
		<section class="pbc-donation<?php echo $this->is_sticky( $settings ) ? ' pbc-donation--sticky' : ''; ?>"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-donation__inner">
				<div class="pbc-donation__body">
					<?php $this->render_heading( $tag, (string) $settings['title'], 'pbc-donation__title', $title_id ); ?>
					<?php foreach ( $this->paragraphs( (string) $settings['text'] ) as $paragraph ) : ?>
						<?php $this->render_text( $paragraph, 'pbc-donation__text' ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( $methods ) : ?>
					<ul class="pbc-donation__list">
						<?php foreach ( $methods as $index => $method ) : ?>
							<?php
							$link = $this->link_value( $method, 'link' );
							$key  = 'method-' . $index;
							if ( $link ) {
								$this->add_link_attributes( $key, $link );
								$this->add_render_attribute( $key, 'class', 'pbc-button pbc-button--accent' );
							}
							?>
							<li class="pbc-donation__method">
								<?php $this->render_heading( $card_tag, (string) $method['title'], 'pbc-donation__method-title' ); ?>
								<?php $this->render_text( (string) ( $method['text'] ?? '' ), 'pbc-donation__method-text' ); ?>
								<?php if ( $link && '' !== trim( (string) ( $method['button_label'] ?? '' ) ) ) : ?>
									<a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $method['button_label'] ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
