<?php
/**
 * Parte pública: estilos, script y la plantilla del aviso y de las ventanas.
 *
 * El aviso y las ventanas se imprimen dentro de un <template>: no se ven ni
 * los leen los buscadores; el script los pone en la página solo cuando hacen
 * falta. Así el HTML es igual para todo el mundo y la página se puede guardar
 * en caché. Sin JavaScript no hay aviso, pero tampoco se carga nada opcional:
 * el mapa y los vídeos necesitan JavaScript para cargarse dentro de la web.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	public const HANDLE = 'pbc-cookies';

	/** Ancla de los enlaces que abren la configuración («Configurar cookies»). */
	public const ANCHOR = 'configurar-cookies';

	public function __construct(
		private readonly Settings $settings,
		private readonly Services $services
	) {}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_footer', [ $this, 'render' ], 5 );
	}

	/**
	 * No se muestra en el escritorio, en la vista previa de Elementor ni en el editor.
	 */
	private function active(): bool {
		if ( is_admin() || is_feed() || is_robots() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo se comprueba si existe el parámetro de la vista previa de Elementor.
		return ! isset( $_GET['elementor-preview'] );
	}

	public function enqueue(): void {
		if ( ! $this->active() ) {
			return;
		}

		$css = 'assets/css/cookies.css';
		$js  = 'assets/js/cookies.js';

		wp_enqueue_style( self::HANDLE, PCK_URL . $css, [], (string) filemtime( PCK_DIR . $css ) );
		wp_enqueue_script(
			self::HANDLE,
			PCK_URL . $js,
			[],
			(string) filemtime( PCK_DIR . $js ),
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);

		$config = [
			'cookie'     => Services::COOKIE,
			'maxAge'     => Services::MONTHS * 30 * DAY_IN_SECONDS,
			'version'    => $this->services->version(),
			'categories' => $this->services->optional_categories(),
			'anchor'     => self::ANCHOR,
			'saved'      => __( 'Hemos guardado tu elección sobre las cookies.', 'parkinson-cookies' ),
		];

		wp_add_inline_script( self::HANDLE, 'window.pbcCookiesConfig = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}

	public function render(): void {
		if ( ! $this->active() ) {
			return;
		}

		$categories = $this->services->categories();
		$optional   = $this->services->optional_categories();
		$policy     = $this->settings->policy_url();
		?>
		<template id="pbc-cookies-ui">
			<?php if ( $optional ) : ?>
				<section class="pbc-cookies" aria-labelledby="pbc-cookies-title" data-pbc-cookies-banner>
					<div class="pbc-cookies__inner">
						<div class="pbc-cookies__body">
							<p class="pbc-cookies__title" id="pbc-cookies-title"><?php esc_html_e( 'Cookies en esta web', 'parkinson-cookies' ); ?></p>
							<p class="pbc-cookies__text">
								<?php echo esc_html( $this->summary( $optional ) ); ?>
								<?php if ( '' !== $policy ) : ?>
									<a class="pbc-cookies__link" href="<?php echo esc_url( $policy ); ?>"><?php esc_html_e( 'Política de cookies', 'parkinson-cookies' ); ?></a>
								<?php endif; ?>
							</p>
						</div>
						<div class="pbc-cookies__actions">
							<button class="pbc-cookies__button" type="button" data-pbc-cookies-accept><?php esc_html_e( 'Aceptar', 'parkinson-cookies' ); ?></button>
							<button class="pbc-cookies__button" type="button" data-pbc-cookies-reject><?php esc_html_e( 'Rechazar', 'parkinson-cookies' ); ?></button>
							<button class="pbc-cookies__button pbc-cookies__button--outline" type="button" data-pbc-cookies-open><?php esc_html_e( 'Elegir', 'parkinson-cookies' ); ?></button>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<dialog class="pbc-cookies-dialog" aria-labelledby="pbc-cookies-dialog-title" data-pbc-cookies-dialog>
				<form class="pbc-cookies-dialog__form" method="dialog">
					<div class="pbc-cookies-dialog__head">
						<h2 class="pbc-cookies-dialog__title" id="pbc-cookies-dialog-title"><?php esc_html_e( 'Configurar cookies', 'parkinson-cookies' ); ?></h2>
						<button class="pbc-cookies__button pbc-cookies__button--outline pbc-cookies__button--small" type="submit" value="close"><?php esc_html_e( 'Cerrar', 'parkinson-cookies' ); ?></button>
					</div>
					<p class="pbc-cookies-dialog__text">
						<?php esc_html_e( 'Marca lo que permites y pulsa «Guardar mi elección». Puedes cambiarlo cuando quieras desde «Configurar cookies», al final de cada página.', 'parkinson-cookies' ); ?>
					</p>

					<?php foreach ( $categories as $id => $category ) : ?>
						<?php
						$services = $this->services->in_category( $id );
						if ( ! $services ) {
							continue;
						}
						$required = Services::REQUIRED === $id;
						$field    = 'pbc-cookies-cat-' . $id;
						?>
						<div class="pbc-cookies-dialog__category">
							<div class="pbc-cookies-dialog__row">
								<input class="pbc-cookies-dialog__check" type="checkbox" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $id ); ?>" value="1" aria-describedby="<?php echo esc_attr( $field . '-desc' ); ?>"<?php echo $required ? ' checked disabled' : ''; ?> data-pbc-cookies-category="<?php echo esc_attr( $id ); ?>">
								<label class="pbc-cookies-dialog__label" for="<?php echo esc_attr( $field ); ?>">
									<?php echo esc_html( $category['label'] ); ?>
									<?php if ( $required ) : ?>
										<span class="pbc-cookies-dialog__always"><?php esc_html_e( '(siempre activas)', 'parkinson-cookies' ); ?></span>
									<?php endif; ?>
								</label>
							</div>
							<p class="pbc-cookies-dialog__description" id="<?php echo esc_attr( $field . '-desc' ); ?>"><?php echo esc_html( $category['description'] ); ?></p>
							<details class="pbc-cookies-dialog__details">
								<summary class="pbc-cookies-dialog__summary"><?php esc_html_e( 'Qué servicios son', 'parkinson-cookies' ); ?></summary>
								<ul class="pbc-cookies-dialog__services">
									<?php foreach ( $services as $service ) : ?>
										<li><strong><?php echo esc_html( $service['name'] ); ?></strong><?php echo '' !== $service['purpose'] ? ': ' . esc_html( $service['purpose'] ) : ''; ?></li>
									<?php endforeach; ?>
								</ul>
							</details>
						</div>
					<?php endforeach; ?>

					<div class="pbc-cookies-dialog__actions">
						<button class="pbc-cookies__button" type="submit" value="save"><?php esc_html_e( 'Guardar mi elección', 'parkinson-cookies' ); ?></button>
						<?php if ( $optional ) : ?>
							<button class="pbc-cookies__button pbc-cookies__button--outline" type="submit" value="all"><?php esc_html_e( 'Aceptar todo', 'parkinson-cookies' ); ?></button>
							<button class="pbc-cookies__button pbc-cookies__button--outline" type="submit" value="none"><?php esc_html_e( 'Rechazar todo', 'parkinson-cookies' ); ?></button>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $policy ) : ?>
						<p class="pbc-cookies-dialog__more"><a href="<?php echo esc_url( $policy ); ?>"><?php esc_html_e( 'Leer la política de cookies completa', 'parkinson-cookies' ); ?></a></p>
					<?php endif; ?>
				</form>
			</dialog>

			<dialog class="pbc-cookies-dialog pbc-cookies-dialog--ask" aria-labelledby="pbc-cookies-ask-title" aria-describedby="pbc-cookies-ask-text" data-pbc-cookies-ask>
				<form class="pbc-cookies-dialog__form" method="dialog">
					<h2 class="pbc-cookies-dialog__title" id="pbc-cookies-ask-title"><?php esc_html_e( '¿Cargamos este contenido?', 'parkinson-cookies' ); ?></h2>
					<p class="pbc-cookies-dialog__text" id="pbc-cookies-ask-text">
						<?php
						printf(
							/* translators: %s: nombre del servicio, por ejemplo YouTube. */
							esc_html__( 'Para verlo, la web tiene que conectarse con %s, que recibirá tu dirección IP y puede guardar sus propias cookies en tu navegador.', 'parkinson-cookies' ),
							'<strong data-pbc-cookies-service></strong>'
						);
						?>
					</p>
					<p class="pbc-cookies-dialog__note"><?php esc_html_e( 'Si lo permites, lo recordaremos para el resto de vídeos y mapas. Puedes cambiarlo en «Configurar cookies».', 'parkinson-cookies' ); ?></p>
					<div class="pbc-cookies-dialog__actions">
						<button class="pbc-cookies__button" type="submit" value="allow"><?php esc_html_e( 'Permitir y continuar', 'parkinson-cookies' ); ?></button>
						<button class="pbc-cookies__button pbc-cookies__button--outline" type="submit" value="cancel"><?php esc_html_e( 'Ahora no', 'parkinson-cookies' ); ?></button>
					</div>
				</form>
			</dialog>
		</template>
		<?php
	}

	/**
	 * Frase del aviso: qué se usa siempre y qué solo con permiso.
	 *
	 * @param string[] $optional Categorías opcionales en uso.
	 */
	private function summary( array $optional ): string {
		$categories = $this->services->categories();
		$parts      = [];

		foreach ( $optional as $category ) {
			$names   = array_unique( array_column( $this->services->in_category( $category ), 'name' ) );
			$parts[] = sprintf( '%1$s (%2$s)', mb_strtolower( $categories[ $category ]['label'] ), wp_sprintf_l( '%l', $names ) );
		}

		return sprintf(
			/* translators: %s: lista de usos opcionales, por ejemplo «contenido de otras webs (YouTube y OpenStreetMap)». */
			__( 'Usamos cookies necesarias para que la web funcione. Solo si nos das permiso usaremos también %s, que pueden guardar sus propias cookies.', 'parkinson-cookies' ),
			wp_sprintf_l( '%l', $parts )
		);
	}
}
