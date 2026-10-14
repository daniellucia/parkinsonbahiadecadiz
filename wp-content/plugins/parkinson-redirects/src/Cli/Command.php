<?php
/**
 * Redirecciones desde WP-CLI.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects\Cli;

use DanielLucia\Redirects\Importer;
use DanielLucia\Redirects\Path;
use DanielLucia\Redirects\Redirector;
use DanielLucia\Redirects\Repository;
use DanielLucia\Redirects\Target;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

final class Command {

	public function __construct(
		private readonly Repository $rules,
		private readonly Importer $importer,
		private readonly Redirector $redirector
	) {}

	/**
	 * Lista las redirecciones.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, csv o json.
	 * ---
	 * default: table
	 * ---
	 *
	 * @subcommand listar
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function listar( array $args, array $assoc_args ): void {
		$items = [];
		foreach ( $this->rules->all() as $rule ) {
			$items[] = [
				'id'      => $rule->id,
				'origen'  => $rule->label(),
				'destino' => Target::label( $rule ) ?: '(destino no publicado)',
				'codigo'  => $rule->code,
				'visitas' => $rule->hits,
			];
		}

		WP_CLI\Utils\format_items( (string) ( $assoc_args['format'] ?? 'table' ), $items, [ 'id', 'origen', 'destino', 'codigo', 'visitas' ] );
	}

	/**
	 * Dice qué pasaría al visitar una dirección.
	 *
	 * ## OPTIONS
	 *
	 * <ruta>...
	 * : Una o varias rutas o direcciones, por ejemplo /hazte-socio/.
	 *
	 * [--existe]
	 * : Trata la ruta como si existiera en la web nueva (no se aplican las reglas generales «/*»).
	 *
	 * @subcommand probar
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function probar( array $args, array $assoc_args ): void {
		foreach ( $args as $raw ) {
			$path   = Path::normalize( $raw );
			$result = $this->redirector->resolve( $path, empty( $assoc_args['existe'] ) );

			if ( null === $result ) {
				WP_CLI::log( "{$raw}  →  sin redirección" );
				continue;
			}

			[ $rule, $url ] = $result;
			WP_CLI::log( sprintf( '%s  →  %s %s  (regla %s)', $raw, $rule->code, '' !== $url ? $url : '', $rule->label() ) );
		}
	}

	/**
	 * Importa redirecciones de un CSV (origen; destino; codigo; nota).
	 *
	 * ## OPTIONS
	 *
	 * <archivo>
	 * : Ruta del CSV.
	 *
	 * [--sobrescribir]
	 * : Cambia las redirecciones que ya existen para el mismo origen.
	 *
	 * @subcommand importar
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function importar( array $args, array $assoc_args ): void {
		$rows = $this->importer->read_csv( $args[0] );
		if ( is_wp_error( $rows ) ) {
			WP_CLI::error( $rows->get_error_message() );
		}

		self::report( $this->importer->import( $rows, ! empty( $assoc_args['sobrescribir'] ) ) );
	}

	/**
	 * Exporta las redirecciones a CSV (a un archivo o a la pantalla).
	 *
	 * ## OPTIONS
	 *
	 * [<archivo>]
	 * : Archivo de salida.
	 *
	 * @subcommand exportar
	 *
	 * @param string[] $args Argumentos posicionales.
	 */
	public function exportar( array $args ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Salida de WP-CLI.
		$handle = fopen( $args[0] ?? 'php://stdout', 'wb' );
		if ( false === $handle ) {
			WP_CLI::error( 'No se pudo escribir el archivo.' );
		}

		foreach ( $this->importer->export_rows() as $row ) {
			fputcsv( $handle, $row, ';', '"', '' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Pareja del fopen anterior.
		fclose( $handle );
	}

	/**
	 * @param array{created: int, updated: int, kept: int, errors: string[]} $report Resultado.
	 */
	public static function report( array $report ): void {
		foreach ( $report['errors'] as $error ) {
			WP_CLI::warning( $error );
		}

		WP_CLI::success( sprintf( 'Creadas: %d. Actualizadas: %d. Sin cambios (ya existían): %d. Con errores: %d.', $report['created'], $report['updated'], $report['kept'], count( $report['errors'] ) ) );
	}
}
