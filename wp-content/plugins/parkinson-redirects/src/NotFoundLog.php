<?php
/**
 * Registro de direcciones que no se encuentran (404), para descubrir enlaces
 * antiguos que aún no tienen redirección.
 *
 * Solo se guarda la ruta, cuántas veces se ha pedido y cuándo: ni IP, ni
 * navegador, ni página de procedencia, ni parámetros de la dirección. No hay
 * datos personales. El registro se limita a las 500 rutas más recientes.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Tabla propia de registro.

final class NotFoundLog {

	public const TABLE = 'prd_404';

	private const KEEP     = 500;
	private const ORDER_BY = [ 'path', 'hits', 'last_seen', 'first_seen' ];

	/** Rutas de robots que buscan fallos de seguridad: no aportan nada. */
	private const NOISE = '#(\.(php\d?|env|ini|log|sql|bak|old|swp|ya?ml|git|ds_store)(/|$)|^/(wp-admin|wp-includes|wp-json|wp-login|xmlrpc|cgi-bin|\.)|/wp-content/(plugins|themes)/)#i';

	public function __construct( private readonly Settings $settings ) {}

	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	public function record( string $path ): void {
		global $wpdb;

		if ( '' === $path || '/' === $path || preg_match( self::NOISE, $path ) ) {
			return;
		}

		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE path = %s LIMIT 1", $path ) );

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
			$wpdb->query( $wpdb->prepare( "UPDATE {$this->table()} SET hits = hits + 1, last_seen = %s WHERE id = %d", $now, $id ) );
			return;
		}

		$wpdb->insert(
			$this->table(),
			[
				'path'       => $path,
				'hits'       => 1,
				'first_seen' => $now,
				'last_seen'  => $now,
			],
			[ '%s', '%d', '%s', '%s' ]
		);

		if ( 1 === wp_rand( 1, 20 ) ) {
			$this->prune();
		}
	}

	public function prune(): void {
		global $wpdb;

		$table = $this->table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id NOT IN ( SELECT id FROM ( SELECT id FROM {$table} ORDER BY last_seen DESC LIMIT %d ) AS keep_rows )", self::KEEP ) );
	}

	/**
	 * @param array{search?: string, orderby?: string, order?: string, per_page?: int, page?: int} $args Consulta.
	 * @return array{items: object[], total: int}
	 */
	public function search( array $args = [] ): array {
		global $wpdb;

		$where  = '1=1';
		$params = [];
		$search = trim( (string) ( $args['search'] ?? '' ) );
		if ( '' !== $search ) {
			$where   .= ' AND path LIKE %s';
			$params[] = '%' . $wpdb->esc_like( mb_strtolower( $search, 'UTF-8' ) ) . '%';
		}

		$orderby  = in_array( $args['orderby'] ?? '', self::ORDER_BY, true ) ? $args['orderby'] : 'hits';
		$order    = 'asc' === strtolower( (string) ( $args['order'] ?? '' ) ) ? 'ASC' : 'DESC';
		$per_page = max( 1, min( 500, (int) ( $args['per_page'] ?? 50 ) ) );
		$offset   = ( max( 1, (int) ( $args['page'] ?? 1 ) ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";
		$list_sql  = "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY {$orderby} {$order}, id ASC LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabla propia, orden de una lista cerrada y valores con marcadores.
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( $list_sql, [ ...$params, $per_page, $offset ] ) );
		// phpcs:enable

		return [
			'items' => (array) $rows,
			'total' => $total,
		];
	}

	public function delete( int $id ): void {
		global $wpdb;

		$wpdb->delete( $this->table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Borra del registro una ruta (y sus versiones con /page/2, /feed…) cuando
	 * se le crea una redirección.
	 */
	public function forget( string $source, bool $prefix = false ): void {
		global $wpdb;

		if ( $prefix ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table()} WHERE path = %s OR path LIKE %s", $source, $wpdb->esc_like( rtrim( $source, '/' ) ) . '/%' ) );
			return;
		}

		$wpdb->delete( $this->table(), [ 'path' => $source ], [ '%s' ] );
	}

	public function clear(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$wpdb->query( "TRUNCATE TABLE {$this->table()}" );
	}

	public function count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" );
	}

	public function enabled(): bool {
		return $this->settings->log_404();
	}
}
