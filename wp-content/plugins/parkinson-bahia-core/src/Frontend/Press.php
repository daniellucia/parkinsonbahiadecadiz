<?php
/**
 * Datos de «En los medios» para la plantilla de Actualidad del tema.
 *
 * El tema pinta la sección (filtro «parkinson_bahia/press_items»); aquí solo
 * se consultan las apariciones en prensa publicadas y con enlace.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Frontend;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Prensa;

defined( 'ABSPATH' ) || exit;

final class Press {

	private const MAX_ITEMS = 12;

	public function register(): void {
		add_filter( 'parkinson_bahia/press_items', [ $this, 'items' ] );
	}

	/**
	 * @param array<int, array<string, string>> $items Apariciones ya añadidas por otros.
	 * @return array<int, array{media: string, date: string, datetime: string, title: string, url: string}>
	 */
	public function items( array $items ): array {
		$query = new \WP_Query(
			[
				'post_type'              => Prensa::POST_TYPE,
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => self::MAX_ITEMS,
				'meta_key'               => Prensa::META_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Lista corta, solo en Actualidad.
				'orderby'                => [
					'meta_value' => 'DESC',
					'title'      => 'ASC',
				],
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			]
		);

		foreach ( $query->posts as $post ) {
			$url = (string) get_post_meta( $post->ID, Prensa::META_URL, true );

			if ( '' === $url ) {
				continue;
			}

			$date    = (string) get_post_meta( $post->ID, Prensa::META_DATE, true );
			$time    = '' !== $date ? strtotime( $date . ' 12:00:00' ) : false;
			$items[] = [
				'media'    => (string) get_post_meta( $post->ID, Prensa::META_MEDIA, true ),
				'date'     => false !== $time ? (string) wp_date( 'j \d\e F \d\e Y', $time ) : '',
				'datetime' => false !== $time ? $date : '',
				'title'    => get_the_title( $post ),
				'url'      => $url,
			];
		}

		return $items;
	}
}
