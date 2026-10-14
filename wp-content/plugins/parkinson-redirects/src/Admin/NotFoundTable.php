<?php
/**
 * Lista de direcciones no encontradas (404).
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects\Admin;

use DanielLucia\Redirects\NotFoundLog;

defined( 'ABSPATH' ) || exit;

final class NotFoundTable extends \WP_List_Table {

	private const PER_PAGE = 50;

	public function __construct( private readonly NotFoundLog $log ) {
		parent::__construct(
			[
				'singular' => 'error',
				'plural'   => 'errores',
				'ajax'     => false,
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'path'       => __( 'Dirección', 'parkinson-redirects' ),
			'hits'       => __( 'Veces', 'parkinson-redirects' ),
			'last_seen'  => __( 'Última vez', 'parkinson-redirects' ),
			'first_seen' => __( 'Primera vez', 'parkinson-redirects' ),
		];
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return [
			'path'      => [ 'path', false ],
			'hits'      => [ 'hits', true ],
			'last_seen' => [ 'last_seen', true ],
		];
	}

	public function prepare_items(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo lectura: búsqueda, orden y página de la lista.
		$result = $this->log->search(
			[
				'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'orderby'  => isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'hits',
				'order'    => isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'desc',
				'per_page' => self::PER_PAGE,
				'page'     => $this->get_pagenum(),
			]
		);
		// phpcs:enable

		$this->items = $result['items'];
		$this->set_pagination_args(
			[
				'total_items' => $result['total'],
				'per_page'    => self::PER_PAGE,
			]
		);
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), 'path' ];
	}

	public function no_items(): void {
		esc_html_e( 'No hay direcciones no encontradas. ¡Bien!', 'parkinson-redirects' );
	}

	/**
	 * @param object $item Fila del registro.
	 */
	protected function column_path( $item ): string {
		$path   = (string) $item->path;
		$create = add_query_arg( 'origen', rawurlencode( $path ), Page::url() ) . '#prd-form';
		$remove = wp_nonce_url( add_query_arg( [ 'action' => 'prd_404_delete', 'id' => (int) $item->id ], admin_url( 'admin-post.php' ) ), 'prd_404_delete_' . (int) $item->id );

		return sprintf( '<strong><code>%s</code></strong>', esc_html( $path ) ) . $this->row_actions(
			[
				'create' => sprintf( '<a href="%s">%s</a>', esc_url( $create ), esc_html__( 'Crear redirección', 'parkinson-redirects' ) ),
				'view'   => sprintf( '<a href="%s">%s</a>', esc_url( home_url( $path ) ), esc_html__( 'Abrir', 'parkinson-redirects' ) ),
				'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $remove ), esc_html__( 'Quitar de la lista', 'parkinson-redirects' ) ),
			],
			true
		);
	}

	/**
	 * @param object $item Fila del registro.
	 */
	protected function column_hits( $item ): string {
		return esc_html( number_format_i18n( (int) $item->hits ) );
	}

	/**
	 * @param object $item Fila del registro.
	 */
	protected function column_last_seen( $item ): string {
		return esc_html( Page::date( (string) $item->last_seen ) );
	}

	/**
	 * @param object $item Fila del registro.
	 */
	protected function column_first_seen( $item ): string {
		return esc_html( Page::date( (string) $item->first_seen ) );
	}
}
