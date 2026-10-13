<?php
/**
 * Soportes del tema, menús, tamaños de imagen y ajustes generales.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class Setup {

	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'supports' ] );
		add_action( 'init', [ $this, 'disable_emojis' ] );
		add_filter( 'comments_open', [ $this, 'close_comments_on_pages' ], 10, 2 );
		add_filter( 'excerpt_length', static fn (): int => 28 );
		add_filter( 'excerpt_more', static fn (): string => '…' );
		add_filter( 'body_class', [ $this, 'body_class' ] );
	}

	public function supports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		add_theme_support(
			'custom-logo',
			[
				'height'      => 88,
				'width'       => 400,
				'flex-width'  => true,
				'flex-height' => true,
			]
		);

		register_nav_menus(
			[
				'primary'           => __( 'Menú principal', 'parkinson-bahia' ),
				'footer-asociacion' => __( 'Pie: la asociación', 'parkinson-bahia' ),
				'footer-legal'      => __( 'Pie: información legal', 'parkinson-bahia' ),
			]
		);

		// Tamaños ajustados a los huecos reales del diseño.
		add_image_size( 'pbc-card', 720, 480, true );
		add_image_size( 'pbc-feature', 1200, 750, true );
		add_image_size( 'pbc-wide', 1600, 800, true );
		add_image_size( 'pbc-square', 640, 640, true );
	}

	public function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	public function close_comments_on_pages( bool $open, int $post_id ): bool {
		return 'page' === get_post_type( $post_id ) ? false : $open;
	}

	/**
	 * @param string[] $classes Clases del body.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( is_singular() && TemplateTags::is_built_with_elementor( (int) get_queried_object_id() ) ) {
			$classes[] = 'pbc-is-elementor';
		}
		return $classes;
	}
}
