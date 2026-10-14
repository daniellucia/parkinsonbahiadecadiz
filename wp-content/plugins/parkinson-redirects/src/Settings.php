<?php
/**
 * Ajustes del plugin (opción pequeña, cargada siempre).
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION = 'prd_settings';

	/**
	 * @return array{log_404: bool, prefer_posts: bool}
	 */
	public function all(): array {
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		return [
			'log_404'      => (bool) ( $saved['log_404'] ?? true ),
			'prefer_posts' => (bool) ( $saved['prefer_posts'] ?? true ),
		];
	}

	public function log_404(): bool {
		return $this->all()['log_404'];
	}

	public function prefer_posts(): bool {
		return $this->all()['prefer_posts'];
	}

	/**
	 * @param array{log_404: bool, prefer_posts: bool} $values Valores ya validados.
	 */
	public function save( array $values ): void {
		update_option(
			self::OPTION,
			[
				'log_404'      => (bool) $values['log_404'],
				'prefer_posts' => (bool) $values['prefer_posts'],
			],
			true
		);
	}
}
