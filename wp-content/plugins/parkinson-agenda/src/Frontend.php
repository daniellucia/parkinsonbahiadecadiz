<?php
/**
 * Web pública: plantilla de la ficha del evento (el tema puede tener la suya,
 * single-pbc_evento.php), su hoja de estilo, las migas de pan
 * «Inicio › Agenda › evento» y los datos estructurados Event de schema.org.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	public function register(): void {
		add_filter( 'single_template', [ $this, 'template' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'parkinson_bahia/breadcrumb_items', [ $this, 'breadcrumb' ] );
		add_action( 'wp_footer', [ $this, 'structured_data' ] );
	}

	public function template( string $template ): string {
		if ( ! is_singular( PostType::POST_TYPE ) ) {
			return $template;
		}

		$theme = locate_template( [ 'single-' . PostType::POST_TYPE . '.php' ] );

		return '' !== $theme ? $theme : PAG_DIR . 'templates/single-' . PostType::POST_TYPE . '.php';
	}

	public function enqueue(): void {
		if ( is_singular( PostType::POST_TYPE ) ) {
			wp_enqueue_style( 'pag-event-page' );
		}
	}

	/**
	 * @param array<int, array{label: string, url: string}> $items Migas de pan del tema.
	 * @return array<int, array{label: string, url: string}>
	 */
	public function breadcrumb( array $items ): array {
		if ( ! is_singular( PostType::POST_TYPE ) || count( $items ) < 2 ) {
			return $items;
		}

		$page = Plugin::page_id();
		if ( 0 === $page ) {
			return $items;
		}

		array_splice(
			$items,
			count( $items ) - 1,
			0,
			[
				[
					'label' => get_the_title( $page ),
					'url'   => (string) get_permalink( $page ),
				],
			]
		);

		return $items;
	}

	public function structured_data(): void {
		if ( ! is_singular( PostType::POST_TYPE ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$event  = Event::from_post( $post );
		$starts = $event->starts();
		if ( null === $starts ) {
			return;
		}

		$data = [
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => $event->title(),
			'url'                 => $event->url(),
			'startDate'           => '' !== $event->start ? $starts->format( 'c' ) : $event->date,
			'eventStatus'         => $event->cancelled ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'organizer'           => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			],
		];

		$ends = $event->ends();
		if ( null !== $ends ) {
			$data['endDate'] = $ends->format( 'c' );
		} elseif ( '' !== $event->end_date ) {
			$data['endDate'] = $event->end_date;
		}

		if ( has_excerpt( $post ) ) {
			$data['description'] = get_the_excerpt( $post );
		}

		if ( '' !== $event->place ) {
			$data['location'] = [
				'@type'   => 'Place',
				'name'    => $event->place,
				'address' => $event->place,
			];
		}

		$image = get_the_post_thumbnail_url( $post, 'pbc-wide' );
		if ( $image ) {
			$data['image'] = $image;
		}

		if ( preg_match( '/gratu|gratis/i', $event->price ) ) {
			$data['isAccessibleForFree'] = true;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}
}
