<?php
/**
 * Lectura y escritura de las redirecciones.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

use WP_Error;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Tabla propia; las lecturas frecuentes se guardan en la caché de objetos.

final class Repository {

	public const TABLE = 'prd_redirects';

	private const CACHE_GROUP = 'prd';
	private const ORDER_BY    = [ 'source', 'code', 'hits', 'last_hit', 'created' ];

	/** @var Rule[]|null */
	private ?array $prefix_rules = null;

	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Regla exacta para la primera ruta de la lista que tenga una.
	 *
	 * @param string[] $paths Rutas normalizadas, de la más concreta a la más general.
	 */
	public function match_exact( array $paths ): ?Rule {
		global $wpdb;

		$paths = array_values( array_unique( array_filter( $paths ) ) );
		if ( ! $paths ) {
			return null;
		}

		$placeholders = implode( ',', array_fill( 0, count( $paths ), '%s' ) );
		$rows         = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Nombre de tabla propio y marcadores generados arriba.
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE match_type = 'exact' AND source IN ({$placeholders})", $paths )
		);

		$by_source = [];
		foreach ( (array) $rows as $row ) {
			$by_source[ mb_strtolower( (string) $row->source, 'UTF-8' ) ] = Rule::from_row( $row );
		}

		foreach ( $paths as $path ) {
			if ( isset( $by_source[ $path ] ) ) {
				return $by_source[ $path ];
			}
		}

		return null;
	}

	/**
	 * Regla «y todo lo que cuelga» más larga que contenga la ruta.
	 */
	public function match_prefix( string $path ): ?Rule {
		foreach ( $this->prefix_rules() as $rule ) {
			if ( $path === $rule->source || str_starts_with( $path, rtrim( $rule->source, '/' ) . '/' ) ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * @return Rule[] De la ruta más larga a la más corta.
	 */
	private function prefix_rules(): array {
		global $wpdb;

		if ( null !== $this->prefix_rules ) {
			return $this->prefix_rules;
		}

		$rows = wp_cache_get( 'prefix', self::CACHE_GROUP );
		if ( ! is_array( $rows ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio, sin datos externos.
			$rows = (array) $wpdb->get_results( "SELECT * FROM {$this->table()} WHERE match_type = 'prefix' ORDER BY CHAR_LENGTH(source) DESC" );
			wp_cache_set( 'prefix', $rows, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		$this->prefix_rules = array_map( [ Rule::class, 'from_row' ], $rows );

		return $this->prefix_rules;
	}

	public function find( int $id ): ?Rule {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ) );

		return $row ? Rule::from_row( $row ) : null;
	}

	public function find_by_source( string $source, string $match ): ?Rule {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE source = %s AND match_type = %s", $source, $match ) );

		return $row ? Rule::from_row( $row ) : null;
	}

	/**
	 * Crea o actualiza una regla (según su ID). Devuelve el ID.
	 */
	public function save( Rule $rule ): int|WP_Error {
		global $wpdb;

		$error = $this->validate( $rule );
		if ( $error ) {
			return $error;
		}

		$data    = [
			'source'      => $rule->source,
			'match_type'  => $rule->match,
			'target_post' => $rule->is_gone() ? 0 : $rule->target_post,
			'target_url'  => $rule->is_gone() || $rule->target_post > 0 ? '' : $rule->target_url,
			'anchor'      => $rule->is_gone() ? '' : $rule->anchor,
			'code'        => $rule->code,
			'note'        => mb_substr( $rule->note, 0, 255 ),
		];
		$formats = [ '%s', '%s', '%d', '%s', '%s', '%d', '%s' ];

		if ( $rule->id > 0 ) {
			$ok = $wpdb->update( $this->table(), $data, [ 'id' => $rule->id ], $formats, [ '%d' ] );
			$id = $rule->id;
		} else {
			$data['created'] = current_time( 'mysql', true );
			$formats[]       = '%s';
			$ok              = $wpdb->insert( $this->table(), $data, $formats );
			$id              = (int) $wpdb->insert_id;
		}

		if ( false === $ok ) {
			return new WP_Error( 'prd_db', __( 'No se pudo guardar la redirección en la base de datos.', 'parkinson-redirects' ) );
		}

		$this->flush();

		/**
		 * Se ha guardado una redirección.
		 *
		 * @param int    $id     ID de la regla.
		 * @param string $source Ruta antigua normalizada.
		 */
		do_action( 'parkinson_redirects/saved', $id, $rule->source );

		return $id;
	}

	private function validate( Rule $rule ): ?WP_Error {
		if ( '' === $rule->source || '/' === $rule->source ) {
			return new WP_Error( 'prd_source', __( 'Escribe la dirección antigua (por ejemplo, /hazte-socio). La portada no se puede redirigir.', 'parkinson-redirects' ) );
		}

		if ( ! in_array( $rule->code, Rule::CODES, true ) ) {
			return new WP_Error( 'prd_code', __( 'Elige el tipo de redirección.', 'parkinson-redirects' ) );
		}

		$existing = $this->find_by_source( $rule->source, $rule->match );
		if ( $existing && $existing->id !== $rule->id ) {
			return new WP_Error( 'prd_duplicate', __( 'Ya hay una redirección para esa dirección antigua. Edítala en lugar de crear otra.', 'parkinson-redirects' ) );
		}

		if ( $rule->is_gone() ) {
			return null;
		}

		$url = Target::url( $rule );
		if ( null === $url ) {
			return new WP_Error( 'prd_target', __( 'Elige a qué página se lleva a la persona, o escribe un enlace que empiece por / o por https://.', 'parkinson-redirects' ) );
		}

		if ( Path::is_local( $url ) && Path::normalize( $url ) === $rule->source ) {
			return new WP_Error( 'prd_loop', __( 'El destino es la misma dirección antigua: la página nunca terminaría de cargar.', 'parkinson-redirects' ) );
		}

		return null;
	}

	public function delete( int $id ): bool {
		global $wpdb;

		$ok = (bool) $wpdb->delete( $this->table(), [ 'id' => $id ], [ '%d' ] );
		$this->flush();

		return $ok;
	}

	public function hit( int $id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		$wpdb->query( $wpdb->prepare( "UPDATE {$this->table()} SET hits = hits + 1, last_hit = %s WHERE id = %d", current_time( 'mysql', true ), $id ) );
	}

	/**
	 * @param array{search?: string, orderby?: string, order?: string, per_page?: int, page?: int} $args Consulta.
	 * @return array{items: Rule[], total: int}
	 */
	public function search( array $args = [] ): array {
		global $wpdb;

		$where  = '1=1';
		$params = [];
		$search = trim( (string) ( $args['search'] ?? '' ) );
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (source LIKE %s OR target_url LIKE %s OR note LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$orderby  = in_array( $args['orderby'] ?? '', self::ORDER_BY, true ) ? $args['orderby'] : 'source';
		$order    = 'desc' === strtolower( (string) ( $args['order'] ?? '' ) ) ? 'DESC' : 'ASC';
		$per_page = max( 1, min( 500, (int) ( $args['per_page'] ?? 50 ) ) );
		$offset   = ( max( 1, (int) ( $args['page'] ?? 1 ) ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";
		$list_sql  = "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY {$orderby} {$order}, id ASC LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabla propia, orden de una lista cerrada y valores con marcadores.
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( $list_sql, [ ...$params, $per_page, $offset ] ) );
		// phpcs:enable

		return [
			'items' => array_map( [ Rule::class, 'from_row' ], (array) $rows ),
			'total' => $total,
		];
	}

	/**
	 * @return Rule[]
	 */
	public function all(): array {
		$items = [];
		$page  = 1;
		do {
			$batch = $this->search(
				[
					'per_page' => 500,
					'page'     => $page++,
				]
			)['items'];
			$items = [ ...$items, ...$batch ];
		} while ( 500 === count( $batch ) );

		return $items;
	}

	public function count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla propio.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" );
	}

	private function flush(): void {
		$this->prefix_rules = null;
		wp_cache_delete( 'prefix', self::CACHE_GROUP );
	}
}
