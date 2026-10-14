<?php
/**
 * Widget «Ideas clave»: unas pocas ideas cortas, cada una con su título y
 * una frase, y hasta dos botones. Dos formas de verse:
 *
 * - «Ideas en columnas»: para explicar algo en pocas palabras (por ejemplo,
 *   qué es el párkinson).
 * - «Lista para llevar»: una hoja con casillas dibujadas, para listas que la
 *   persona se lleva (por ejemplo, preguntas para el neurólogo). Las casillas
 *   son decorativas; la versión para marcar es el PDF que se descarga.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class KeyPoints extends Widget {

	protected function slug(): string {
		return 'key-points';
	}

	public function get_title(): string {
		return esc_html__( 'Ideas clave', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'intro', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__( 'Idea', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'text',
			[
				'label' => esc_html__( 'Explicación (opcional)', 'parkinson-bahia-core' ),
				'type'  => Controls_Manager::TEXTAREA,
			]
		);
		$this->add_control(
			'items',
			[
				'label'       => esc_html__( 'Ideas', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ title }}',
				'default'     => [],
			]
		);

		$this->add_control(
			'layout',
			[
				'label'   => esc_html__( 'Cómo se ve', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'columns'   => esc_html__( 'Ideas en columnas', 'parkinson-bahia-core' ),
					'checklist' => esc_html__( 'Lista para llevar (con casillas)', 'parkinson-bahia-core' ),
				],
				'default' => 'columns',
			]
		);
		$this->add_control(
			'variant',
			[
				'label'   => esc_html__( 'Fondo', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => esc_html__( 'Claro', 'parkinson-bahia-core' ),
					'mist' => esc_html__( 'Azul claro', 'parkinson-bahia-core' ),
				],
				'default' => '',
			]
		);
		$this->text_control( 'note', esc_html__( 'Nota al pie (opcional)', 'parkinson-bahia-core' ), '', true );
		$this->text_control( 'button_label', esc_html__( 'Texto del botón (opcional)', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button_link', esc_html__( 'Enlace del botón', 'parkinson-bahia-core' ) );
		$this->text_control( 'button2_label', esc_html__( 'Texto del segundo botón (opcional)', 'parkinson-bahia-core' ) );
		$this->link_controls( $this, 'button2_link', esc_html__( 'Enlace del segundo botón', 'parkinson-bahia-core' ) );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$title_id  = $this->title_id();
		$checklist = 'checklist' === ( $settings['layout'] ?? '' );
		$items     = array_filter(
			is_array( $settings['items'] ?? null ) ? $settings['items'] : [],
			static fn ( $item ): bool => is_array( $item ) && '' !== trim( (string) ( $item['title'] ?? '' ) )
		);

		$classes = [ 'pbc-key-points' ];
		if ( $checklist ) {
			$classes[] = 'pbc-key-points--checklist';
		}
		if ( 'mist' === ( $settings['variant'] ?? '' ) ) {
			$classes[] = 'pbc-key-points--mist';
		}

		$buttons = [];
		foreach ( [ 'button' => 'pbc-button--primary', 'button2' => 'pbc-button--outline' ] as $key => $style ) {
			$link = $this->link_value( $settings, $key . '_link' );
			if ( '' !== trim( (string) ( $settings[ $key . '_label' ] ?? '' ) ) && $link ) {
				$this->add_link_attributes( $key, $link );
				$this->add_render_attribute( $key, 'class', 'pbc-button ' . $style . ' pbc-key-points__button' );
				$buttons[ $key ] = (string) $settings[ $key . '_label' ];
			}
		}
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-key-points__inner">
				<div class="pbc-key-points__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-key-points__title', $title_id ); ?>
					<?php foreach ( $this->paragraphs( (string) $settings['intro'] ) as $paragraph ) : ?>
						<p class="pbc-key-points__intro"><?php echo esc_html( $paragraph ); ?></p>
					<?php endforeach; ?>
				</div>

				<?php if ( $items ) : ?>
					<ul class="pbc-key-points__list">
						<?php foreach ( $items as $item ) : ?>
							<li class="pbc-key-points__item">
								<?php if ( $checklist ) : ?>
									<span class="pbc-key-points__box" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="pbc-key-points__body">
									<span class="pbc-key-points__name"><?php echo esc_html( $item['title'] ); ?></span>
									<?php if ( '' !== trim( (string) ( $item['text'] ?? '' ) ) ) : ?>
										<span class="pbc-key-points__text"><?php echo esc_html( $item['text'] ); ?></span>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $buttons || '' !== trim( (string) $settings['note'] ) ) : ?>
					<div class="pbc-key-points__footer">
						<?php if ( $buttons ) : ?>
							<div class="pbc-key-points__actions">
								<?php foreach ( $buttons as $key => $label ) : ?>
									<a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $label ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php $this->render_text( (string) $settings['note'], 'pbc-key-points__note' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
