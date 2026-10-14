<?php
/**
 * Punto de entrada: registra cada módulo. Aquí solo se enganchan acciones.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public function register(): void {
		$settings  = new Settings();
		$rules     = new Repository();
		$not_found = new NotFoundLog( $settings );

		add_action( 'plugins_loaded', [ Schema::class, 'maybe_upgrade' ] );

		// Una ruta que ya tiene redirección deja de aparecer entre los 404.
		add_action(
			'parkinson_redirects/saved',
			static function ( int $id ) use ( $rules, $not_found ): void {
				$rule = $rules->find( $id );
				if ( $rule ) {
					$not_found->forget( $rule->source, $rule->is_prefix() );
				}
			}
		);

		$redirector = new Redirector( $rules, $settings, $not_found );
		$redirector->register();

		if ( is_admin() ) {
			( new Admin\Page( $rules, $not_found, $settings, new Importer( $rules ), $redirector ) )->register();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'redirecciones', new Cli\Command( $rules, new Importer( $rules ), $redirector ) );
		}
	}
}
