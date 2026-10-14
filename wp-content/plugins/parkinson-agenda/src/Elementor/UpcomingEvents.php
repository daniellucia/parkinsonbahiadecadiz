<?php
/**
 * Widget «Próximos eventos»: las siguientes actividades de la agenda, para la
 * portada u otras páginas.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda\Elementor;

use DanielLucia\ParkinsonBahia\Agenda\Markup;
use DanielLucia\ParkinsonBahia\Agenda\Plugin;
use DanielLucia\ParkinsonBahia\Agenda\Query;
use DanielLucia\ParkinsonBahia\Core\Elementor\Widgets\Widget;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class UpcomingEvents extends Widget {

	use TypeControl;

	protected function slug(): string {
		return 'upcoming-events';
	}

	public function get_title(): string {
		return esc_html__( 'Próximos eventos', 'parkinson-agenda' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_keywords(): array {
		return [ 'agenda', 'eventos', 'actividades', 'talleres' ];
	}

	public function get_script_depends(): array {
		return [];
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-agenda' ) ] );
		$this->source_note( __( 'Los eventos se escriben en el menú «Agenda» del escritorio. Aquí salen los siguientes, del más cercano al más lejano; los que ya han pasado desaparecen solos.', 'parkinson-agenda' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-agenda' ), esc_html__( 'Próximas actividades', 'parkinson-agenda' ) );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto (opcional)', 'parkinson-agenda' ), '', true );
		$this->add_control(
			'count',
			[
				'label'   => esc_html__( 'Cuántos mostrar', 'parkinson-agenda' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 3,
			]
		);
		$this->type_control();
		$this->text_control( 'more_label', esc_html__( 'Texto del enlace a la agenda', 'parkinson-agenda' ), esc_html__( 'Ver toda la agenda', 'parkinson-agenda' ) );
		$this->link_controls( $this, 'more', esc_html__( 'Enlace a la agenda', 'parkinson-agenda' ) );
		$this->add_control(
			'more_help',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Si no eliges nada, enlaza con la página «Agenda».', 'parkinson-agenda' ),
				'content_classes' => 'elementor-descriptor',
			]
		);
		$this->add_control(
			'empty',
			[
				'label'   => esc_html__( 'Si no hay actividades', 'parkinson-agenda' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'message' => esc_html__( 'Mostrar un mensaje con el teléfono', 'parkinson-agenda' ),
					'hide'    => esc_html__( 'Ocultar la sección', 'parkinson-agenda' ),
				],
				'default' => 'message',
			]
		);
		$this->text_control( 'empty_text', esc_html__( 'Mensaje si no hay actividades', 'parkinson-agenda' ), esc_html__( 'Ahora mismo no hay actividades programadas. Llámanos y te contamos las próximas.', 'parkinson-agenda' ), true );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$events   = Query::upcoming( max( 1, min( 6, absint( $settings['count'] ?? 3 ) ) ), $this->chosen_type( $settings ) );

		if ( ! $events && 'hide' === ( $settings['empty'] ?? '' ) ) {
			$this->render_empty_notice( __( 'No hay próximos eventos: la sección no se ve en la web.', 'parkinson-agenda' ) );
			return;
		}

		$title_id = $this->title_id();
		$tag      = $this->heading_tag( $settings );
		$item_tag = 'h3' === $tag ? 'h4' : 'h3';
		$more     = $this->link_value( $settings, 'more' );
		if ( ! $more && '' !== Plugin::page_url() ) {
			$more = [ 'url' => Plugin::page_url() ];
		}
		$label = trim( (string) ( $settings['more_label'] ?? '' ) );
		?>
		<section class="pbc-upcoming-events"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ya escapado. ?><?php echo '' !== trim( (string) $settings['title'] ) ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>
			<div class="pbc-upcoming-events__inner">
				<div class="pbc-upcoming-events__header">
					<div class="pbc-upcoming-events__intro">
						<?php
						$this->render_heading( $tag, (string) $settings['title'], 'pbc-upcoming-events__title', $title_id );
						$this->render_text( (string) ( $settings['text'] ?? '' ), 'pbc-upcoming-events__text' );
						?>
					</div>
					<?php if ( $more && '' !== $label ) : ?>
						<?php
						$this->add_link_attributes( 'more', $more );
						$this->add_render_attribute( 'more', 'class', 'pbc-upcoming-events__more' );
						?>
						<a <?php $this->print_render_attribute_string( 'more' ); ?>><?php echo esc_html( $label ); ?><span class="pbc-upcoming-events__arrow" aria-hidden="true">→</span></a>
					<?php endif; ?>
				</div>
				<?php if ( $events ) : ?>
					<ul class="pbc-upcoming-events__list">
						<?php foreach ( $events as $event ) : ?>
							<li class="pbc-upcoming-events__item"><?php echo Markup::item( $event, $item_tag ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escapa cada valor. ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php echo Markup::empty_message( (string) $settings['empty_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escapa cada valor. ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
