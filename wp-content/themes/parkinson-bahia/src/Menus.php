<?php
/**
 * Clases BEM en los menús de wp_nav_menu().
 *
 * Uso: wp_nav_menu( [ 'pbc_item_class' => 'pbc-nav__item', 'pbc_link_class' => 'pbc-nav__link' ] ).
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class Menus {

	public function register(): void {
		add_filter( 'nav_menu_css_class', [ $this, 'item_class' ], 10, 3 );
		add_filter( 'nav_menu_link_attributes', [ $this, 'link_class' ], 10, 3 );
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
	}

	/**
	 * @param string[] $classes Clases del elemento.
	 * @param object   $item    Elemento del menú.
	 * @param object   $args    Argumentos de wp_nav_menu().
	 * @return string[]
	 */
	public function item_class( array $classes, $item, $args ): array {
		if ( empty( $args->pbc_item_class ) ) {
			return $classes;
		}

		return [ (string) $args->pbc_item_class ];
	}

	/**
	 * @param array<string, mixed> $atts Atributos del enlace.
	 * @param object               $item Elemento del menú.
	 * @param object               $args Argumentos de wp_nav_menu().
	 * @return array<string, mixed>
	 */
	public function link_class( array $atts, $item, $args ): array {
		if ( ! empty( $args->pbc_link_class ) ) {
			$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' ' . $args->pbc_link_class );
		}

		return $atts;
	}
}
