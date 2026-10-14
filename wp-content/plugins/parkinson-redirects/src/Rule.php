<?php
/**
 * Una redirección: de qué ruta antigua, a qué destino y con qué código.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Rule {

	public const EXACT  = 'exact';
	public const PREFIX = 'prefix';

	/** Códigos permitidos: movida para siempre, temporal y eliminada. */
	public const CODES = [ 301, 302, 410 ];

	public function __construct(
		public readonly int $id,
		public readonly string $source,
		public readonly string $match,
		public readonly int $target_post,
		public readonly string $target_url,
		public readonly string $anchor,
		public readonly int $code,
		public readonly string $note = '',
		public readonly int $hits = 0,
		public readonly ?string $last_hit = null
	) {}

	public static function from_row( object $row ): self {
		return new self(
			(int) $row->id,
			(string) $row->source,
			self::PREFIX === $row->match_type ? self::PREFIX : self::EXACT,
			(int) $row->target_post,
			(string) $row->target_url,
			(string) $row->anchor,
			(int) $row->code,
			(string) $row->note,
			(int) $row->hits,
			null !== $row->last_hit ? (string) $row->last_hit : null
		);
	}

	public function is_prefix(): bool {
		return self::PREFIX === $this->match;
	}

	public function is_gone(): bool {
		return 410 === $this->code;
	}

	/**
	 * La ruta antigua tal como se muestra («/category/*» si incluye lo que cuelga).
	 */
	public function label(): string {
		return $this->is_prefix() ? rtrim( $this->source, '/' ) . '/*' : $this->source;
	}
}
