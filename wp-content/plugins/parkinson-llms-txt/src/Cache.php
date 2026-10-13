<?php
/**
 * Caché de los ficheros generados.
 *
 * Cada clave incluye un número de versión. Cuando cambia cualquier contenido
 * o ajuste, la versión sube y todo se regenera en la siguiente visita; las
 * copias antiguas caducan solas.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

defined( 'ABSPATH' ) || exit;

final class Cache {

	private const VERSION_OPTION = 'pllms_cache_version';
	private const TTL            = DAY_IN_SECONDS;

	public function register(): void {
		// Cualquier cambio de contenido puede alterar un listado o una página.
		foreach ( [ 'save_post', 'deleted_post', 'trashed_post', 'edited_term', 'delete_term', 'wp_update_nav_menu', 'switch_theme' ] as $hook ) {
			add_action( $hook, [ $this, 'flush' ] );
		}
		add_action( 'update_option_blogname', [ $this, 'flush' ] );
		add_action( 'update_option_blogdescription', [ $this, 'flush' ] );
		add_action( 'update_option_' . Settings::OPTION, [ $this, 'flush' ] );
		add_action( 'add_option_' . Settings::OPTION, [ $this, 'flush' ] );
	}

	public function flush(): void {
		update_option( self::VERSION_OPTION, (int) get_option( self::VERSION_OPTION, 1 ) + 1, false );
	}

	/**
	 * Devuelve el valor guardado o lo calcula y lo guarda.
	 *
	 * @param callable():string $build Función que genera el contenido.
	 */
	public function remember( string $key, callable $build ): string {
		$name   = 'pllms_' . (int) get_option( self::VERSION_OPTION, 1 ) . '_' . $key;
		$cached = get_transient( $name );

		if ( is_string( $cached ) ) {
			return $cached;
		}

		$value = $build();
		set_transient( $name, $value, self::TTL );

		return $value;
	}
}
