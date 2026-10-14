<?php
/**
 * Widget «Agenda»: todas las próximas actividades agrupadas por mes, con
 * accesos por tipo de actividad y, debajo, las ya celebradas.
 *
 * Los accesos por tipo son enlaces normales («?tipo=talleres»): funcionan sin
 * JavaScript, se pueden guardar y compartir, y el tipo elegido se marca con
 * aria-current.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda\Elementor;

use DanielLucia\ParkinsonBahia\Agenda\Event;
use DanielLucia\ParkinsonBahia\Agenda\Markup;
use DanielLucia\ParkinsonBahia\Agenda\Query;
use DanielLucia\ParkinsonBahia\Core\Elementor\Widgets\Widget;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Agenda extends Widget {

	use TypeControl;

	public const QUERY_VAR = 'tipo';

	protected function slug(): string {
		return 'agenda';
	}

	public function get_title(): string {
		return esc_html__( 'Agenda', 'parkinson-agenda' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_keywords(): array {
		return [ 'agenda', 'eventos', 'actividades', 'calendario' ];
	}

	public function get_script_depends(): array {
		return [];
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-agenda' ) ] );
		$this->source_note( __( 'Los eventos se escriben en el menú «Agenda» del escritorio. Aquí salen todos los próximos, agrupados por mes. Los títulos de los meses son H2, así que el widget va después de la cabecera de la página.', 'parkinson-agenda' ) );
		$this->add_control(
			'filter',
			[
				'label'       => esc_html__( 'Botones por tipo de actividad', 'parkinson-agenda' ),
				'description' => esc_html__( 'Permite ver solo talleres, solo charlas… Salen si hay más de un tipo con actividades.', 'parkinson-agenda' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			]
		);
		$this->type_control();
		$this->text_control( 'empty_text', esc_html__( 'Mensaje si no hay actividades', 'parkinson-agenda' ), esc_html__( 'Ahora mismo no hay actividades programadas. Llámanos y te contamos las próximas.', 'parkinson-agenda' ), true );
		$this->add_control(
			'past',
			[
				'label'       => esc_html__( 'Mostrar las ya celebradas', 'parkinson-agenda' ),
				'description' => esc_html__( 'Una lista plegada con las actividades de los últimos doce meses.', 'parkinson-agenda' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			]
		);
		$this->text_control( 'past_title', esc_html__( 'Título de las ya celebradas', 'parkinson-agenda' ), esc_html__( 'Actividades ya celebradas', 'parkinson-agenda' ) );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$fixed    = $this->chosen_type( $settings );
		$types    = '' === $fixed && 'yes' === ( $settings['filter'] ?? '' ) ? Query::types() : [];
		$current  = '' !== $fixed ? $fixed : $this->requested_type( $types );
		$events   = Query::upcoming( Query::MAX, $current );
		$anchor   = sanitize_title( (string) ( $settings['anchor'] ?? '' ) );
		?>
		<section class="pbc-agenda"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ya escapado. ?> aria-label="<?php esc_attr_e( 'Agenda de actividades', 'parkinson-agenda' ); ?>">
			<div class="pbc-agenda__inner">
				<?php if ( count( $types ) > 1 ) : ?>
					<nav class="pbc-agenda__filter" aria-label="<?php esc_attr_e( 'Tipo de actividad', 'parkinson-agenda' ); ?>">
						<ul class="pbc-agenda__chips">
							<?php
							$this->chip( __( 'Todas', 'parkinson-agenda' ), '', $current, $anchor );
							foreach ( $types as $type ) {
								$this->chip( $type->name, $type->slug, $current, $anchor );
							}
							?>
						</ul>
					</nav>
				<?php endif; ?>

				<?php if ( $events ) : ?>
					<?php foreach ( $this->by_month( $events ) as $month => $items ) : ?>
						<?php $month_id = $this->title_id() . '-' . sanitize_key( $month ); ?>
						<section class="pbc-agenda__month" aria-labelledby="<?php echo esc_attr( $month_id ); ?>">
							<h2 class="pbc-agenda__month-title" id="<?php echo esc_attr( $month_id ); ?>"><?php echo esc_html( $this->month_label( $month ) ); ?></h2>
							<ul class="pbc-agenda__list">
								<?php foreach ( $items as $event ) : ?>
									<li class="pbc-agenda__item"><?php echo Markup::item( $event, 'h3', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escapa cada valor. ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				<?php else : ?>
					<?php
					$text = '' !== $current
						? __( 'No hay actividades de este tipo programadas ahora mismo. Mira las demás o llámanos y te contamos.', 'parkinson-agenda' )
						: (string) $settings['empty_text'];
					echo Markup::empty_message( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escapa cada valor.
					?>
				<?php endif; ?>

				<?php $this->render_past( $settings, $current ); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings Ajustes del widget.
	 */
	private function render_past( array $settings, string $type ): void {
		if ( 'yes' !== ( $settings['past'] ?? '' ) ) {
			return;
		}

		$past = Query::past( 20, $type );
		if ( ! $past ) {
			return;
		}
		?>
		<details class="pbc-agenda__past">
			<summary class="pbc-agenda__past-summary"><?php echo esc_html( (string) $settings['past_title'] ); ?></summary>
			<ul class="pbc-agenda__past-list">
				<?php foreach ( $past as $event ) : ?>
					<li class="pbc-agenda__past-item">
						<time class="pbc-agenda__past-date" datetime="<?php echo esc_attr( $event->datetime_attr() ); ?>"><?php echo esc_html( $event->date_label() ); ?></time>
						<a class="pbc-agenda__past-link" href="<?php echo esc_url( $event->url() ); ?>"><?php echo esc_html( $event->title() ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
		<?php
	}

	private function chip( string $label, string $slug, string $current, string $anchor ): void {
		$url = remove_query_arg( self::QUERY_VAR, (string) get_permalink() );
		if ( '' !== $slug ) {
			$url = add_query_arg( self::QUERY_VAR, $slug, $url );
		}
		if ( '' !== $anchor ) {
			$url .= '#' . $anchor;
		}

		printf(
			'<li><a class="pbc-agenda__chip" href="%1$s"%2$s>%3$s</a></li>',
			esc_url( $url ),
			$slug === $current ? ' aria-current="true"' : '',
			esc_html( $label )
		);
	}

	/**
	 * Tipo pedido en la dirección, solo si es uno de los que tienen actividades.
	 *
	 * @param \WP_Term[] $types Tipos disponibles.
	 */
	private function requested_type( array $types ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtro público de solo lectura.
		$slug = isset( $_GET[ self::QUERY_VAR ] ) ? sanitize_title( wp_unslash( (string) $_GET[ self::QUERY_VAR ] ) ) : '';

		foreach ( $types as $type ) {
			if ( $type->slug === $slug ) {
				return $slug;
			}
		}

		return '';
	}

	/**
	 * @param Event[] $events Eventos ordenados por fecha.
	 * @return array<string, Event[]> «AAAA-MM» => eventos.
	 */
	private function by_month( array $events ): array {
		$groups = [];
		$today  = wp_date( 'Y-m' );

		foreach ( $events as $event ) {
			// Un evento de varios días que ya ha empezado se agrupa en el mes actual.
			$month              = max( substr( $event->date, 0, 7 ), $today );
			$groups[ $month ][] = $event;
		}

		return $groups;
	}

	private function month_label( string $month ): string {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m', $month, wp_timezone() );
		if ( ! $date ) {
			return $month;
		}

		$format = wp_date( 'Y' ) === $date->format( 'Y' ) ? 'F' : 'F \d\e Y';

		return ucfirst( (string) wp_date( $format, $date->getTimestamp() ) );
	}
}
