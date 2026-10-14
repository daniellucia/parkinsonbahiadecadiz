<?php
/**
 * Lista de redirecciones en Herramientas → Redirecciones.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects\Admin;

use DanielLucia\Redirects\Repository;
use DanielLucia\Redirects\Rule;
use DanielLucia\Redirects\Target;

defined( 'ABSPATH' ) || exit;

final class RulesTable extends \WP_List_Table {

	private const PER_PAGE = 50;

	public function __construct( private readonly Repository $rules ) {
		parent::__construct(
			[
				'singular' => 'redireccion',
				'plural'   => 'redirecciones',
				'ajax'     => false,
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'       => '<input type="checkbox">',
			'source'   => __( 'Dirección antigua', 'parkinson-redirects' ),
			'target'   => __( 'Lleva a', 'parkinson-redirects' ),
			'code'     => __( 'Tipo', 'parkinson-redirects' ),
			'hits'     => __( 'Visitas', 'parkinson-redirects' ),
			'last_hit' => __( 'Última visita', 'parkinson-redirects' ),
			'note'     => __( 'Nota', 'parkinson-redirects' ),
		];
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return [
			'source'   => [ 'source', false ],
			'code'     => [ 'code', false ],
			'hits'     => [ 'hits', true ],
			'last_hit' => [ 'last_hit', true ],
		];
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return [ 'prd-delete' => __( 'Borrar', 'parkinson-redirects' ) ];
	}

	public function prepare_items(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo lectura: búsqueda, orden y página de la lista.
		$result = $this->rules->search(
			[
				'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
				'orderby'  => isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'source',
				'order'    => isset( $_REQUEST['order'] ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'asc',
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
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), 'source' ];
	}

	public function no_items(): void {
		esc_html_e( 'Todavía no hay redirecciones.', 'parkinson-redirects' );
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<label class="screen-reader-text" for="prd-rule-%1$d">%2$s</label><input type="checkbox" id="prd-rule-%1$d" name="ids[]" value="%1$d">',
			$item->id,
			/* translators: %s: dirección antigua. */
			esc_html( sprintf( __( 'Elegir %s', 'parkinson-redirects' ), $item->label() ) )
		);
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_source( $item ): string {
		$edit   = add_query_arg( 'editar', $item->id, Page::url() ) . '#prd-form';
		$delete = wp_nonce_url( add_query_arg( [ 'action' => 'prd_delete', 'id' => $item->id ], admin_url( 'admin-post.php' ) ), 'prd_delete_' . $item->id );

		$out = sprintf( '<strong><a class="row-title" href="%s"><code>%s</code></a></strong>', esc_url( $edit ), esc_html( $item->label() ) );
		if ( $item->is_prefix() ) {
			$out .= '<br><span class="description">' . esc_html__( 'Y todo lo que cuelga, si no existe en la web nueva', 'parkinson-redirects' ) . '</span>';
		}

		return $out . $this->row_actions(
			[
				'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit ), esc_html__( 'Editar', 'parkinson-redirects' ) ),
				'delete' => sprintf(
					'<a class="submitdelete" href="%s" onclick="return confirm(this.dataset.confirm)" data-confirm="%s">%s</a>',
					esc_url( $delete ),
					esc_attr__( '¿Borrar esta redirección?', 'parkinson-redirects' ),
					esc_html__( 'Borrar', 'parkinson-redirects' )
				),
			]
		);
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_target( $item ): string {
		if ( $item->is_gone() ) {
			return esc_html__( '— (se avisa de que ya no existe)', 'parkinson-redirects' );
		}

		$url = Target::url( $item );
		if ( null === $url ) {
			return '<strong>' . esc_html__( 'El destino ya no está publicado: esta redirección no actúa.', 'parkinson-redirects' ) . '</strong>';
		}

		$out = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( Target::label( $item ) ) );
		if ( $item->target_post > 0 ) {
			$out .= '<br><span class="description">' . esc_html( get_the_title( $item->target_post ) ) . '</span>';
		}

		return $out;
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_code( $item ): string {
		return esc_html( Page::code_label( $item->code ) );
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_hits( $item ): string {
		return esc_html( number_format_i18n( $item->hits ) );
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_last_hit( $item ): string {
		return esc_html( Page::date( $item->last_hit ) );
	}

	/**
	 * @param Rule $item Regla.
	 */
	protected function column_note( $item ): string {
		return esc_html( $item->note );
	}
}
