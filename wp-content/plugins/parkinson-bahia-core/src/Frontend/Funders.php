<?php
/**
 * Banda «Con el apoyo de» del pie (gancho del tema «parkinson_bahia/footer_funders»).
 *
 * Se engancha solo si hay financiadores publicados, para que el tema no
 * pinte una banda vacía. El HTML se guarda en caché y se rehace al cambiar
 * cualquier financiador.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Frontend;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Financiador;

defined( 'ABSPATH' ) || exit;

final class Funders {

	private const CACHE_KEY = 'pbc_funders_html';
	private const MAX_ITEMS = 40;

	private string $html = '';

	public function register(): void {
		add_action( 'wp', [ $this, 'maybe_hook' ] );
		add_action( 'save_post_' . Financiador::POST_TYPE, [ $this, 'flush' ] );
		add_action( 'deleted_post', [ $this, 'flush' ] );
		add_action( 'trashed_post', [ $this, 'flush' ] );
		add_action( 'edit_attachment', [ $this, 'flush' ] );
	}

	public function maybe_hook(): void {
		if ( is_admin() ) {
			return;
		}

		$this->html = $this->html();

		if ( '' !== $this->html ) {
			add_action( 'parkinson_bahia/footer_funders', [ $this, 'render' ] );
		}
	}

	public function render(): void {
		echo $this->html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Construido en build() con todo escapado.
	}

	public function flush(): void {
		delete_transient( self::CACHE_KEY );
	}

	private function html(): string {
		$cached = get_transient( self::CACHE_KEY );

		if ( is_string( $cached ) ) {
			return $cached;
		}

		$html = $this->build();
		set_transient( self::CACHE_KEY, $html, DAY_IN_SECONDS );

		return $html;
	}

	private function build(): string {
		$query = new \WP_Query(
			[
				'post_type'              => Financiador::POST_TYPE,
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => self::MAX_ITEMS,
				'orderby'                => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			]
		);

		if ( ! $query->posts ) {
			return '';
		}

		update_post_thumbnail_cache( $query );

		$items = '';
		foreach ( $query->posts as $post ) {
			$name = get_the_title( $post );
			$url  = (string) get_post_meta( $post->ID, Financiador::META_URL, true );
			$logo = has_post_thumbnail( $post ) ? (string) wp_get_attachment_image(
				(int) get_post_thumbnail_id( $post ),
				'medium',
				false,
				[
					'class'   => 'pbc-footer__funder-logo',
					'alt'     => $name,
					'loading' => 'lazy',
					'sizes'   => '160px',
				]
			) : '';
			$body = '' !== $logo ? $logo : '<span class="pbc-footer__funder-name">' . esc_html( $name ) . '</span>';

			if ( '' !== $url ) {
				$body = '<a class="pbc-footer__funder-link" href="' . esc_url( $url ) . '">' . $body . '</a>';
			}

			$items .= '<li class="pbc-footer__funder">' . $body . '</li>';
		}

		return '<ul class="pbc-footer__funders-list">' . $items . '</ul>';
	}
}
