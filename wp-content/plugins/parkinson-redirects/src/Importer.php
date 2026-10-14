<?php
/**
 * Importa y exporta redirecciones en CSV, y crea reglas a partir de textos
 * sencillos («/hazte-socio» → «/colabora#socio»).
 *
 * Columnas: origen; destino; codigo; nota. El origen puede terminar en «/*»
 * para incluir todo lo que cuelga de él. El destino es una ruta de la web
 * («/centro-de-dia», con ancla opcional), una dirección https://, «post:ID»
 * o nada si el código es 410. Si el destino es una página, entrada o archivo
 * de esta web se guarda su ID, para que sobreviva a un cambio de slug.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class Importer {

	public const HEADER   = [ 'origen', 'destino', 'codigo', 'nota' ];
	private const MAX_ROWS = 5000;

	public function __construct( private readonly Repository $rules ) {}

	/**
	 * @param iterable<array<int, string>> $rows      Filas [origen, destino, código, nota].
	 * @param bool                         $overwrite Cambiar las reglas que ya existen para el mismo origen.
	 * @return array{created: int, updated: int, kept: int, errors: string[]}
	 */
	public function import( iterable $rows, bool $overwrite ): array {
		$report = [
			'created' => 0,
			'updated' => 0,
			'kept'    => 0,
			'errors'  => [],
		];

		$line = 0;
		foreach ( $rows as $row ) {
			++$line;
			$source = trim( (string) ( $row[0] ?? '' ) );
			if ( '' === $source || str_starts_with( $source, '#' ) ) {
				continue;
			}

			$result = $this->upsert(
				$source,
				(string) ( $row[1] ?? '' ),
				(int) ( $row[2] ?? 301 ) ?: 301,
				(string) ( $row[3] ?? '' ),
				$overwrite
			);

			if ( is_wp_error( $result ) ) {
				/* translators: 1: número de fila, 2: dirección, 3: motivo. */
				$report['errors'][] = sprintf( __( 'Fila %1$d (%2$s): %3$s', 'parkinson-redirects' ), $line, $source, $result->get_error_message() );
				continue;
			}

			++$report[ $result ];
		}

		return $report;
	}

	/**
	 * Crea la regla o, si ya hay una para el mismo origen, la actualiza (solo con $overwrite).
	 *
	 * @return 'created'|'updated'|'kept'|WP_Error
	 */
	public function upsert( string $source, string $target, int $code, string $note, bool $overwrite ): string|WP_Error {
		[ $path, $match ] = self::parse_source( $source );
		$existing         = '' !== $path ? $this->rules->find_by_source( $path, $match ) : null;

		if ( $existing && ! $overwrite ) {
			return 'kept';
		}

		$rule = $this->build( $existing ? $existing->id : 0, $source, $target, $code, $note );
		if ( is_wp_error( $rule ) ) {
			return $rule;
		}

		$saved = $this->rules->save( $rule );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return $existing ? 'updated' : 'created';
	}

	public function build( int $id, string $source, string $target, int $code, string $note ): Rule|WP_Error {
		[ $path, $match ] = self::parse_source( $source );

		if ( 410 === $code ) {
			return new Rule( $id, $path, $match, 0, '', '', 410, sanitize_text_field( $note ) );
		}

		$resolved = $this->resolve_target( $target );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		return new Rule( $id, $path, $match, $resolved['post'], $resolved['url'], $resolved['anchor'], $code, sanitize_text_field( $note ) );
	}

	/**
	 * @return array{0: string, 1: string} Ruta normalizada y tipo de coincidencia.
	 */
	public static function parse_source( string $source ): array {
		$source = trim( $source );
		$match  = Rule::EXACT;

		if ( str_ends_with( $source, '*' ) ) {
			$match  = Rule::PREFIX;
			$source = rtrim( $source, '*' );
		}

		return [ Path::normalize( $source ), $match ];
	}

	/**
	 * @return array{post: int, url: string, anchor: string}|WP_Error
	 */
	public function resolve_target( string $target ): array|WP_Error {
		$target = trim( $target );
		$anchor = '';

		if ( str_contains( $target, '#' ) ) {
			[ $target, $anchor ] = explode( '#', $target, 2 );
			$anchor              = Target::sanitize_anchor( $anchor );
		}

		if ( preg_match( '/^post:(\d+)$/', $target, $m ) ) {
			$post_id = (int) $m[1];
			if ( ! get_post( $post_id ) ) {
				return new WP_Error( 'prd_target', __( 'El contenido de destino no existe.', 'parkinson-redirects' ) );
			}

			return [
				'post'   => $post_id,
				'url'    => '',
				'anchor' => $anchor,
			];
		}

		if ( '' === $target ) {
			return new WP_Error( 'prd_target', __( 'Falta el destino.', 'parkinson-redirects' ) );
		}

		if ( Path::is_local( $target ) ) {
			$full    = str_starts_with( $target, '/' ) ? home_url( $target ) : $target;
			$post_id = url_to_postid( $full );
			if ( 0 === $post_id ) {
				$post_id = attachment_url_to_postid( $full );
			}

			if ( $post_id > 0 ) {
				return [
					'post'   => $post_id,
					'url'    => '',
					'anchor' => $anchor,
				];
			}

			$path   = wp_parse_url( $full, PHP_URL_PATH );
			$target = is_string( $path ) ? $path : '/';
		}

		$url = Target::sanitize_url( $target );
		if ( '' === $url ) {
			return new WP_Error( 'prd_target', __( 'El destino tiene que ser una ruta que empiece por / o una dirección https://.', 'parkinson-redirects' ) );
		}

		return [
			'post'   => 0,
			'url'    => $url,
			'anchor' => $anchor,
		];
	}

	/**
	 * Lee un CSV separado por comas o por punto y coma (como lo guarda Excel en español).
	 *
	 * @return array<int, array<int, string>>|WP_Error
	 */
	public function read_csv( string $file ): array|WP_Error {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Lectura local de un CSV subido o indicado en WP-CLI.
		$handle = is_readable( $file ) ? fopen( $file, 'rb' ) : false;
		if ( false === $handle ) {
			return new WP_Error( 'prd_csv', __( 'No se pudo leer el archivo.', 'parkinson-redirects' ) );
		}

		$first     = (string) fgets( $handle );
		$delimiter = substr_count( $first, ';' ) > substr_count( $first, ',' ) ? ';' : ',';
		rewind( $handle );

		$rows = [];
		while ( ( $row = fgetcsv( $handle, 4096, $delimiter, '"', '' ) ) !== false ) {
			if ( count( $rows ) >= self::MAX_ROWS ) {
				break;
			}

			$row = array_map( static fn ( $cell ): string => trim( (string) $cell ), $row );
			if ( ! $rows && isset( $row[0] ) ) {
				$row[0] = (string) preg_replace( '/^\xEF\xBB\xBF/', '', $row[0] );
				if ( 'origen' === strtolower( $row[0] ) ) {
					$rows[] = [];
					continue;
				}
			}
			$rows[] = $row;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Pareja del fopen anterior.
		fclose( $handle );

		return array_values( array_filter( $rows ) );
	}

	/**
	 * @return array<int, array<int, string>> Cabecera y una fila por regla.
	 */
	public function export_rows(): array {
		$rows = [ self::HEADER ];

		foreach ( $this->rules->all() as $rule ) {
			$rows[] = [
				$rule->label(),
				$rule->is_gone() ? '' : ( Target::label( $rule ) ?: ( $rule->target_post > 0 ? 'post:' . $rule->target_post : $rule->target_url ) ),
				(string) $rule->code,
				$rule->note,
			];
		}

		return $rows;
	}
}
