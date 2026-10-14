<?php
/**
 * Consultas de la agenda: próximos eventos y eventos ya celebrados.
 * Una sola consulta cada una, sin paginación y con un máximo.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Query {

	public const MAX = 60;

	/**
	 * Eventos que no han terminado, del más cercano al más lejano.
	 *
	 * @return Event[]
	 */
	public static function upcoming( int $limit, string $type = '' ): array {
		return self::run( $limit, $type, '>=', 'ASC' );
	}

	/**
	 * Eventos ya celebrados en los últimos meses, del más reciente al más antiguo.
	 *
	 * @return Event[]
	 */
	public static function past( int $limit, string $type = '', int $months = 12 ): array {
		$since = wp_date( 'Y-m-d', strtotime( "-{$months} months" ) );

		return self::run( $limit, $type, '<', 'DESC', $since );
	}

	/**
	 * @return Event[]
	 */
	private static function run( int $limit, string $type, string $compare, string $order, string $since = '' ): array {
		$meta = [
			[
				'key'     => PostType::META_UNTIL,
				'value'   => wp_date( 'Y-m-d' ),
				'compare' => $compare,
				'type'    => 'CHAR',
			],
		];
		if ( '' !== $since ) {
			$meta[] = [
				'key'     => PostType::META_UNTIL,
				'value'   => $since,
				'compare' => '>=',
				'type'    => 'CHAR',
			];
		}

		$args = [
			'post_type'              => PostType::POST_TYPE,
			'post_status'            => 'publish',
			'has_password'           => false,
			'posts_per_page'         => max( 1, min( self::MAX, $limit ) ),
			'no_found_rows'          => true,
			'meta_key'               => PostType::META_SORT, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Orden por fecha del evento.
			'orderby'                => 'meta_value',
			'order'                  => $order,
			'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtro por fecha, acotado por MAX.
			'update_post_term_cache' => true,
		];

		if ( '' !== $type ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtro por tipo elegido.
				[
					'taxonomy' => PostType::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $type,
				],
			];
		}

		$query = new WP_Query( $args );

		return array_map( [ Event::class, 'from_post' ], $query->posts );
	}

	/**
	 * Tipos de actividad que tienen al menos un evento publicado.
	 *
	 * @return \WP_Term[]
	 */
	public static function types(): array {
		$terms = get_terms(
			[
				'taxonomy'   => PostType::TAXONOMY,
				'hide_empty' => true,
				'orderby'    => 'name',
			]
		);

		return is_array( $terms ) ? $terms : [];
	}
}
