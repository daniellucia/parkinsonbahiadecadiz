<?php
/**
 * Ajustes del plugin (opción «pck_settings»): la página de la política de
 * cookies y un número de revisión que, al subir, vuelve a pedir el
 * consentimiento a todo el mundo.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION = 'pck_settings';

	/**
	 * @return array{policy_page: int, revision: int}
	 */
	public function all(): array {
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		return [
			'policy_page' => absint( $saved['policy_page'] ?? 0 ),
			'revision'    => absint( $saved['revision'] ?? 1 ),
		];
	}

	/**
	 * @param array{policy_page?: int, revision?: int} $values Valores que cambian.
	 */
	public function update( array $values ): void {
		update_option( self::OPTION, array_merge( $this->all(), $values ), true );
	}

	/**
	 * Página de la política de cookies, solo si está publicada.
	 */
	public function policy_page_id(): int {
		$id = $this->all()['policy_page'];

		return $id > 0 && 'publish' === get_post_status( $id ) ? $id : 0;
	}

	public function policy_url(): string {
		$id = $this->policy_page_id();

		return $id > 0 ? (string) get_permalink( $id ) : '';
	}

	public function revision(): int {
		return max( 1, $this->all()['revision'] );
	}
}
