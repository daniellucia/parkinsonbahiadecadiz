<?php
/**
 * Funciones de ayuda para las plantillas.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class TemplateTags {

	/**
	 * Valor de un campo del Personalizador, con su valor por defecto.
	 */
	public static function mod( string $key ): string {
		$fields  = Customizer::fields();
		$default = $fields[ $key ][2] ?? '';

		return trim( (string) get_theme_mod( $key, $default ) );
	}

	/**
	 * Enlace tel: a partir del teléfono escrito para personas.
	 */
	public static function phone_href(): string {
		$digits = preg_replace( '/\D+/', '', self::mod( 'pbc_phone' ) ) ?? '';

		if ( '' === $digits ) {
			return '';
		}

		return 'tel:' . ( 9 === strlen( $digits ) ? '+34' . $digits : '+' . $digits );
	}

	public static function is_built_with_elementor( int $post_id ): bool {
		if ( $post_id <= 0 || ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_id );

		return $document ? (bool) $document->is_built_with_elementor() : false;
	}

	/**
	 * Accesos rápidos para el 404 y la búsqueda sin resultados: los enlaces del
	 * menú principal (con su descripción, si la tienen), filtrables.
	 *
	 * @return array<int, array{title: string, url: string, text: string}>
	 */
	public static function helpful_links(): array {
		$links     = [];
		$locations = get_nav_menu_locations();

		if ( ! empty( $locations['primary'] ) ) {
			foreach ( (array) wp_get_nav_menu_items( (int) $locations['primary'] ) as $item ) {
				if ( empty( $item->url ) || ! empty( $item->menu_item_parent ) ) {
					continue;
				}
				$links[] = [
					'title' => wp_strip_all_tags( (string) $item->title ),
					'url'   => (string) $item->url,
					'text'  => wp_strip_all_tags( (string) $item->description ),
				];
			}
		}

		/**
		 * Accesos rápidos del 404 y de la búsqueda sin resultados.
		 *
		 * @param array<int, array{title: string, url: string, text: string}> $links Enlaces.
		 */
		$links = (array) apply_filters( 'parkinson_bahia/helpful_links', $links );

		return array_values(
			array_filter(
				array_map(
					static fn ( $link ): array => [
						'title' => (string) ( $link['title'] ?? '' ),
						'url'   => (string) ( $link['url'] ?? '' ),
						'text'  => (string) ( $link['text'] ?? '' ),
					],
					$links
				),
				static fn ( array $link ): bool => '' !== $link['title'] && '' !== $link['url']
			)
		);
	}

	/**
	 * Palabras de la dirección pedida, para sugerir páginas en el 404
	 * («/terapias/logopedia-adultos/» → «logopedia adultos»).
	 *
	 * @return string[]
	 */
	public static function words_from_request(): array {
		$path  = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Se limpia palabra a palabra.
		$path  = (string) preg_replace( '/\.(html?|php|pdf|aspx?)$/i', '', rawurldecode( $path ) );
		$parts = preg_split( '/[\/\-_+.\s]+/u', mb_strtolower( $path ) ) ?: [];
		$skip  = [ 'archivos', 'category', 'categoria', 'page', 'tag', 'wp', 'content', 'uploads', 'index', 'para', 'con', 'los', 'las', 'del', 'que', 'una' ];
		$words = [];

		foreach ( $parts as $part ) {
			$part = sanitize_text_field( $part );
			if ( mb_strlen( $part ) > 2 && ! ctype_digit( $part ) && ! in_array( $part, $skip, true ) && ! preg_match( '/^c\d+$/', $part ) ) {
				$words[] = $part;
			}
		}

		return array_slice( array_values( array_unique( $words ) ), 0, 5 );
	}

	/**
	 * URL de la página de noticias (o la portada si las noticias están en ella).
	 */
	public static function news_url(): string {
		$page_id = (int) get_option( 'page_for_posts' );

		return $page_id > 0 ? (string) get_permalink( $page_id ) : home_url( '/' );
	}

	public static function news_title(): string {
		$page_id = (int) get_option( 'page_for_posts' );

		return $page_id > 0 ? get_the_title( $page_id ) : __( 'Actualidad', 'parkinson-bahia' );
	}

	/**
	 * Migas de pan.
	 *
	 * @return array<int, array{label:string, url:string}> El último elemento es la página actual.
	 */
	public static function breadcrumb_items(): array {
		$items = [
			[
				'label' => __( 'Inicio', 'parkinson-bahia' ),
				'url'   => home_url( '/' ),
			],
		];

		$news = [
			'label' => self::news_title(),
			'url'   => self::news_url(),
		];

		if ( is_home() ) {
			$items[] = $news;
		} elseif ( is_singular( 'post' ) ) {
			$items[] = $news;
			$items[] = [
				'label' => get_the_title(),
				'url'   => '',
			];
		} elseif ( is_page() ) {
			foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $ancestor ) {
				$items[] = [
					'label' => get_the_title( $ancestor ),
					'url'   => (string) get_permalink( $ancestor ),
				];
			}
			$items[] = [
				'label' => get_the_title(),
				'url'   => '',
			];
		} elseif ( is_category() || is_tag() || is_date() || is_author() ) {
			$items[] = $news;
			$items[] = [
				'label' => wp_strip_all_tags( get_the_archive_title() ),
				'url'   => '',
			];
		} elseif ( is_search() ) {
			$items[] = [
				'label' => __( 'Resultados de búsqueda', 'parkinson-bahia' ),
				'url'   => '',
			];
		} elseif ( is_404() ) {
			$items[] = [
				'label' => __( 'Página no encontrada', 'parkinson-bahia' ),
				'url'   => '',
			];
		} elseif ( is_singular() || is_archive() ) {
			$items[] = [
				'label' => is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ),
				'url'   => '',
			];
		}

		return $items;
	}

	/**
	 * Primera categoría de una noticia, sin contar «Sin categoría».
	 */
	public static function primary_category( int $post_id ): ?\WP_Term {
		$default = (int) get_option( 'default_category' );

		foreach ( get_the_category( $post_id ) as $category ) {
			if ( $category->term_id !== $default ) {
				return $category;
			}
		}

		return null;
	}

	/**
	 * Enlaces para compartir una noticia.
	 *
	 * @return array<string, string> Etiqueta => URL.
	 */
	public static function share_links( int $post_id ): array {
		$url   = (string) get_permalink( $post_id );
		$title = wp_strip_all_tags( get_the_title( $post_id ) );

		return [
			'WhatsApp' => 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ),
			'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
		];
	}
}
