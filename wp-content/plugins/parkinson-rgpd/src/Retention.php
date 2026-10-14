<?php
/**
 * Plazos de conservación (art. 5.1.e RGPD): una vez al día se borra lo que
 * ha superado su plazo. Cada tipo de dato lo declara quien lo guarda con el
 * filtro «parkinson_rgpd/retention» (ver Registry) y los meses se eligen en
 * Ajustes → Protección de datos. 0 meses = no se borra solo.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Retention {

	public const HOOK     = 'prgpd_purge';
	public const LAST_RUN = 'prgpd_last_run';

	public function __construct(
		private readonly Settings $settings,
		private readonly Registry $registry
	) {}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'run' ] );
		// Por si el plugin se copió sin activarlo o se perdió la tarea.
		add_action( 'init', [ self::class, 'schedule' ] );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Borra lo caducado. Devuelve cuántos elementos ha borrado de cada tipo.
	 *
	 * @return array<string, int>
	 */
	public function run(): array {
		$deleted = [];

		foreach ( $this->registry->retention() as $id => $item ) {
			$months = $this->settings->months( $id, $item['default'] );
			if ( $months <= 0 ) {
				continue;
			}

			$cutoff         = (int) strtotime( '-' . $months . ' months', time() );
			$deleted[ $id ] = max( 0, (int) call_user_func( $item['purge'], $cutoff ) );
		}

		update_option(
			self::LAST_RUN,
			[
				'time'    => time(),
				'deleted' => $deleted,
			],
			false
		);

		return $deleted;
	}

	/**
	 * Última pasada: fecha y borrados.
	 *
	 * @return array{time: int, deleted: array<string, int>}|null
	 */
	public static function last_run(): ?array {
		$last = get_option( self::LAST_RUN );

		return is_array( $last ) && isset( $last['time'] ) ? [
			'time'    => (int) $last['time'],
			'deleted' => array_map( 'intval', (array) ( $last['deleted'] ?? [] ) ),
		] : null;
	}
}
