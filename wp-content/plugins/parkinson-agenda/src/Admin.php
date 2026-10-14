<?php
/**
 * Lista de eventos del escritorio: columnas de fecha y lugar, orden por la
 * fecha del evento y accesos «Próximos» / «Ya celebrados».
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Admin {

	private const VIEW = 'pag_cuando';

	public function register(): void {
		$type = PostType::POST_TYPE;

		add_filter( "manage_{$type}_posts_columns", [ $this, 'columns' ] );
		add_action( "manage_{$type}_posts_custom_column", [ $this, 'column' ], 10, 2 );
		add_filter( "manage_edit-{$type}_sortable_columns", [ $this, 'sortable' ] );
		add_filter( "views_edit-{$type}", [ $this, 'views' ] );
		add_action( 'pre_get_posts', [ $this, 'order' ] );
		add_action( 'admin_notices', [ $this, 'missing_date_notice' ] );
	}

	/**
	 * @param array<string, string> $columns Columnas.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$out = [];
		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				continue;
			}
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['pag_when']  = __( 'Cuándo', 'parkinson-agenda' );
				$out['pag_place'] = __( 'Lugar', 'parkinson-agenda' );
			}
		}

		return $out;
	}

	public function column( string $column, int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$event = Event::from_post( $post );

		if ( 'pag_when' === $column ) {
			echo esc_html( $event->date_label() );
			if ( '' !== $event->time_label() ) {
				echo '<br>' . esc_html( $event->time_label() );
			}
			if ( $event->cancelled ) {
				echo '<br><strong>' . esc_html__( 'Cancelado', 'parkinson-agenda' ) . '</strong>';
			} elseif ( $event->is_past() ) {
				echo '<br><span class="description">' . esc_html__( 'Ya celebrado', 'parkinson-agenda' ) . '</span>';
			}
		} elseif ( 'pag_place' === $column ) {
			echo esc_html( $event->place );
		}
	}

	/**
	 * @param array<string, string> $columns Columnas ordenables.
	 * @return array<string, string>
	 */
	public function sortable( array $columns ): array {
		$columns['pag_when'] = 'pag_when';

		return $columns;
	}

	/**
	 * @param array<string, string> $views Accesos de la lista.
	 * @return array<string, string>
	 */
	public function views( array $views ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo elige el filtro de la lista.
		$current = isset( $_GET[ self::VIEW ] ) ? sanitize_key( wp_unslash( $_GET[ self::VIEW ] ) ) : '';
		$base    = admin_url( 'edit.php?post_type=' . PostType::POST_TYPE );

		foreach ( [
			'proximos' => __( 'Próximos', 'parkinson-agenda' ),
			'pasados'  => __( 'Ya celebrados', 'parkinson-agenda' ),
		] as $key => $label ) {
			$views[ 'pag-' . $key ] = sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( add_query_arg( self::VIEW, $key, $base ) ),
				$current === $key ? ' class="current" aria-current="page"' : '',
				esc_html( $label )
			);
		}

		return $views;
	}

	/**
	 * Por defecto, la lista sale por fecha del evento: los próximos, del más
	 * cercano; los ya celebrados, del más reciente.
	 */
	public function order( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || PostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo filtra la lista.
		$view    = isset( $_GET[ self::VIEW ] ) ? sanitize_key( wp_unslash( $_GET[ self::VIEW ] ) ) : '';
		$orderby = (string) $query->get( 'orderby' );

		// Los eventos sin fecha (borradores a medias) también salen: al final o al principio.
		$sort = [
			'relation'  => 'OR',
			'pag_sort'  => [
				'key'     => PostType::META_SORT,
				'compare' => 'EXISTS',
			],
			'pag_empty' => [
				'key'     => PostType::META_SORT,
				'compare' => 'NOT EXISTS',
			],
		];

		if ( in_array( $view, [ 'proximos', 'pasados' ], true ) ) {
			$sort = [
				'relation' => 'AND',
				'pag_sort' => [
					'key'     => PostType::META_SORT,
					'compare' => 'EXISTS',
				],
				[
					'key'     => PostType::META_UNTIL,
					'value'   => wp_date( 'Y-m-d' ),
					'compare' => 'proximos' === $view ? '>=' : '<',
				],
			];
		}

		$query->set( 'meta_query', $sort );

		if ( '' === $orderby || 'pag_when' === $orderby ) {
			$order = '' === $orderby ? ( 'proximos' === $view ? 'ASC' : 'DESC' ) : ( strtoupper( (string) $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC' );
			$query->set( 'orderby', [ 'pag_sort' => $order ] );
		}
	}

	/**
	 * Aviso en la pantalla de edición si el evento publicado no tiene fecha.
	 */
	public function missing_date_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$post = get_post();
		if ( $post && 'publish' === $post->post_status && '' === (string) get_post_meta( $post->ID, PostType::META_DATE, true ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Este evento no tiene fecha, así que no sale en la agenda. Escríbela en «Fecha, lugar e inscripción».', 'parkinson-agenda' )
			);
		}
	}
}
