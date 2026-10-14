<?php
/**
 * Un evento de la agenda, con sus datos ya leídos y saneados, y los textos
 * que se muestran («martes, 21 de octubre», «de 17:00 a 18:30»…).
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

use DateTimeImmutable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

final class Event {

	private function __construct(
		public readonly WP_Post $post,
		public readonly string $date,
		public readonly string $end_date,
		public readonly string $start,
		public readonly string $end,
		public readonly string $place,
		public readonly string $price,
		public readonly string $signup,
		public readonly string $signup_url,
		public readonly bool $cancelled
	) {}

	public static function from_post( WP_Post $post ): self {
		$meta = static fn ( string $key ): string => trim( (string) get_post_meta( $post->ID, $key, true ) );

		$date     = $meta( PostType::META_DATE );
		$end_date = $meta( PostType::META_END_DATE );

		return new self(
			$post,
			$date,
			'' !== $end_date && $end_date > $date ? $end_date : '',
			$meta( PostType::META_START ),
			$meta( PostType::META_END ),
			$meta( PostType::META_PLACE ),
			$meta( PostType::META_PRICE ),
			$meta( PostType::META_SIGNUP ),
			$meta( PostType::META_SIGNUP_URL ),
			'1' === $meta( PostType::META_CANCELLED )
		);
	}

	public function title(): string {
		return get_the_title( $this->post );
	}

	public function url(): string {
		return (string) get_permalink( $this->post );
	}

	public function has_date(): bool {
		return '' !== $this->date;
	}

	/**
	 * Fecha y hora de inicio en la zona horaria de la web.
	 */
	public function starts(): ?DateTimeImmutable {
		if ( ! $this->has_date() ) {
			return null;
		}

		$value = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $this->date . ' ' . ( '' !== $this->start ? $this->start : '00:00' ), wp_timezone() );

		return $value ?: null;
	}

	public function ends(): ?DateTimeImmutable {
		$day  = '' !== $this->end_date ? $this->end_date : $this->date;
		$time = '' !== $this->end ? $this->end : '';
		if ( '' === $day || '' === $time ) {
			return null;
		}

		$value = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $day . ' ' . $time, wp_timezone() );

		return $value ?: null;
	}

	/**
	 * ¿Ya ha terminado? Un evento sin hora de fin dura hasta el final del día.
	 */
	public function is_past(): bool {
		$last = '' !== $this->end_date ? $this->end_date : $this->date;

		return '' !== $last && $last < wp_date( 'Y-m-d' );
	}

	/** Número del día («21»). */
	public function day(): string {
		return $this->format( 'j' );
	}

	/** Mes corto («oct.»), para el calendario de la tarjeta. */
	public function month_short(): string {
		return $this->format( 'M' );
	}

	/** Día de la semana corto («mar.»). */
	public function weekday_short(): string {
		return $this->format( 'D' );
	}

	/**
	 * «Martes, 21 de octubre» (con el año si no es el actual) o, si dura
	 * varios días, «Del 21 al 23 de octubre».
	 */
	public function date_label(): string {
		if ( ! $this->has_date() ) {
			return __( 'Fecha por confirmar', 'parkinson-agenda' );
		}

		$ts        = $this->timestamp( $this->date );
		$this_year = wp_date( 'Y' ) === wp_date( 'Y', $ts );

		if ( '' !== $this->end_date ) {
			$end = $this->timestamp( $this->end_date );

			return sprintf(
				/* translators: 1: día de inicio, 2: día de fin (con mes). */
				__( 'Del %1$s al %2$s', 'parkinson-agenda' ),
				wp_date( wp_date( 'm', $ts ) === wp_date( 'm', $end ) ? 'j' : 'j \d\e F', $ts ),
				wp_date( $this_year ? 'j \d\e F' : 'j \d\e F \d\e Y', $end )
			);
		}

		return ucfirst( (string) wp_date( $this_year ? 'l, j \d\e F' : 'l, j \d\e F \d\e Y', $ts ) );
	}

	/**
	 * «De 17:00 a 18:30», «A las 17:00» o vacío si no tiene hora.
	 */
	public function time_label(): string {
		if ( '' === $this->start ) {
			return '';
		}

		return '' !== $this->end
			/* translators: 1: hora de inicio, 2: hora de fin. */
			? sprintf( __( 'De %1$s a %2$s', 'parkinson-agenda' ), $this->start, $this->end )
			/* translators: %s: hora de inicio. */
			: sprintf( __( 'A las %s', 'parkinson-agenda' ), $this->start );
	}

	/**
	 * Fecha para el atributo datetime de <time>.
	 */
	public function datetime_attr(): string {
		$starts = $this->starts();
		if ( null === $starts ) {
			return '';
		}

		return '' !== $this->start ? $starts->format( 'Y-m-d\TH:i' ) : $this->date;
	}

	/**
	 * Tipos del evento (Taller, Charla…).
	 *
	 * @return \WP_Term[]
	 */
	public function types(): array {
		$terms = get_the_terms( $this->post, PostType::TAXONOMY );

		return is_array( $terms ) ? $terms : [];
	}

	public function type_label(): string {
		return implode( ' · ', array_map( static fn ( \WP_Term $term ): string => $term->name, $this->types() ) );
	}

	private function format( string $format ): string {
		return $this->has_date() ? (string) wp_date( $format, $this->timestamp( $this->date ) ) : '';
	}

	private function timestamp( string $day ): int {
		$value = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, wp_timezone() );

		return $value ? $value->getTimestamp() : 0;
	}
}
