<?php
/**
 * Piezas de HTML compartidas por los widgets y la ficha: el calendario de la
 * tarjeta, los datos de cada evento y los iconos (SVG en línea, sin fuentes
 * de iconos). Todo sale ya escapado.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

defined( 'ABSPATH' ) || exit;

final class Markup {

	private const ICONS = [
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'place'    => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'price'    => '<path d="M17 7.5A6 6 0 1 0 17 16.5M4 10.5h9M4 13.5h9"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
	];

	public static function icon( string $name ): string {
		if ( ! isset( self::ICONS[ $name ] ) ) {
			return '';
		}

		return '<svg class="pbc-event-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . self::ICONS[ $name ] . '</svg>';
	}

	/**
	 * Hoja de calendario con el día. Es decorativa: la fecha completa se lee
	 * en la línea «Cuándo».
	 */
	public static function date_badge( Event $event ): string {
		if ( ! $event->has_date() ) {
			return '';
		}

		return sprintf(
			'<span class="pbc-event-date" aria-hidden="true"><span class="pbc-event-date__weekday">%1$s</span><span class="pbc-event-date__day">%2$s</span><span class="pbc-event-date__month">%3$s</span></span>',
			esc_html( rtrim( $event->weekday_short(), '.' ) ),
			esc_html( $event->day() ),
			esc_html( rtrim( $event->month_short(), '.' ) )
		);
	}

	/**
	 * Tarjeta de un evento para las listas.
	 *
	 * @param string $tag     Nivel del título del evento (h3 o h4).
	 * @param bool   $details Añadir precio y resumen (agenda completa).
	 */
	public static function item( Event $event, string $tag, bool $details = false ): string {
		$tag     = in_array( $tag, [ 'h2', 'h3', 'h4' ], true ) ? $tag : 'h3';
		$classes = 'pbc-event' . ( $event->cancelled ? ' pbc-event--cancelled' : '' );
		$facts   = [];

		$facts[] = [ 'calendar', sprintf( '<time datetime="%s">%s</time>', esc_attr( $event->datetime_attr() ), esc_html( $event->date_label() ) ) ];
		if ( '' !== $event->time_label() ) {
			$facts[] = [ 'clock', esc_html( $event->time_label() ) ];
		}
		if ( '' !== $event->place ) {
			$facts[] = [ 'place', esc_html( $event->place ) ];
		}
		if ( $details && '' !== $event->price ) {
			$facts[] = [ 'price', esc_html( $event->price ) ];
		}

		$html  = '<article class="' . esc_attr( $classes ) . '">';
		$html .= self::date_badge( $event );
		$html .= '<div class="pbc-event__body">';

		if ( '' !== $event->type_label() || $event->cancelled ) {
			$html .= '<p class="pbc-event__labels">';
			if ( $event->cancelled ) {
				$html .= '<span class="pbc-event__status">' . esc_html__( 'Cancelado', 'parkinson-agenda' ) . '</span>';
			}
			if ( '' !== $event->type_label() ) {
				$html .= '<span class="pbc-event__type">' . esc_html( $event->type_label() ) . '</span>';
			}
			$html .= '</p>';
		}

		$html .= sprintf(
			'<%1$s class="pbc-event__title"><a class="pbc-event__link" href="%2$s">%3$s</a></%1$s>',
			$tag,
			esc_url( $event->url() ),
			esc_html( $event->title() )
		);

		$html .= '<ul class="pbc-event__facts">';
		foreach ( $facts as [ $icon, $text ] ) {
			$html .= '<li class="pbc-event__fact">' . self::icon( $icon ) . '<span>' . $text . '</span></li>';
		}
		$html .= '</ul>';

		if ( $details && has_excerpt( $event->post ) ) {
			$html .= '<p class="pbc-event__summary">' . esc_html( get_the_excerpt( $event->post ) ) . '</p>';
		}

		return $html . '</div></article>';
	}

	/**
	 * Mensaje cuando no hay actividades, con el teléfono para preguntar.
	 */
	public static function empty_message( string $text ): string {
		$phone = Plugin::phone();

		return sprintf(
			'<div class="pbc-event-empty"><p class="pbc-event-empty__text">%1$s</p><a class="pbc-button pbc-button--outline" href="%2$s">%3$s%4$s</a></div>',
			esc_html( $text ),
			esc_url( $phone['href'] ),
			self::icon( 'phone' ),
			/* translators: %s: teléfono. */
			esc_html( sprintf( __( 'Llamar al %s', 'parkinson-agenda' ), $phone['label'] ) )
		);
	}
}
