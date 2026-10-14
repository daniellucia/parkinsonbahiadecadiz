<?php
/**
 * Punto de entrada: registra cada módulo. Aquí solo se enganchan acciones.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Página de la agenda (la crea `wp pbc seed-agenda`). */
	public const PAGE_OPTION = 'pag_page';

	public function register(): void {
		( new PostType() )->register();
		( new Frontend() )->register();
		( new Elementor\Registrar() )->register();

		if ( is_admin() ) {
			( new Admin() )->register();
		}
	}

	public static function activate(): void {
		( new PostType() )->register_types();
		flush_rewrite_rules();
	}

	/**
	 * ID de la página «Agenda»: la guardada en el ajuste o, si no hay, la de slug «agenda».
	 */
	public static function page_id(): int {
		$id = (int) get_option( self::PAGE_OPTION, 0 );
		if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
			return $id;
		}

		$page = get_page_by_path( PostType::SLUG );

		return $page && 'publish' === $page->post_status ? (int) $page->ID : 0;
	}

	public static function page_url(): string {
		$id = self::page_id();

		return $id > 0 ? (string) get_permalink( $id ) : '';
	}

	/**
	 * Teléfono de la asociación (Personalizador del tema), para «Llama y apúntate».
	 *
	 * @return array{label: string, href: string}
	 */
	public static function phone(): array {
		$tags = \DanielLucia\ParkinsonBahia\Theme\TemplateTags::class;

		if ( class_exists( $tags ) && '' !== $tags::mod( 'pbc_phone' ) ) {
			return [
				'label' => $tags::mod( 'pbc_phone' ),
				'href'  => $tags::phone_href(),
			];
		}

		return [
			'label' => '956 59 19 28',
			'href'  => 'tel:+34956591928',
		];
	}
}
