<?php
/**
 * Línea de protección de datos en el pie de todas las páginas, a través del
 * filtro del tema «parkinson_bahia/footer_privacy»: quién es el responsable,
 * enlace a la política de privacidad y forma de ejercer los derechos.
 * El tema pinta el marcado; aquí solo se dan los datos.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class Footer {

	public function __construct( private readonly Settings $settings ) {}

	public function register(): void {
		add_filter( 'parkinson_bahia/footer_privacy', [ $this, 'data' ] );
	}

	/**
	 * @param array<string, mixed> $data Datos que ya traiga el filtro.
	 * @return array{text: string, links: array<int, array{label: string, url: string}>}
	 */
	public function data( array $data ): array {
		$controller = $this->settings->controller();
		$links      = [];
		$policy     = (string) get_privacy_policy_url();

		if ( '' !== $policy ) {
			$links[] = [
				'label' => __( 'Cómo tratamos tus datos', 'parkinson-rgpd' ),
				'url'   => $policy,
			];
		}

		if ( is_email( $controller['email'] ) ) {
			$links[] = [
				'label' => __( 'Ejercer tus derechos', 'parkinson-rgpd' ),
				'url'   => 'mailto:' . $controller['email'] . '?subject=' . rawurlencode( __( 'Protección de datos: ejercicio de derechos', 'parkinson-rgpd' ) ),
			];
		}

		return [
			/* translators: %s: nombre del responsable del tratamiento. */
			'text'  => '' !== $controller['name'] ? sprintf( __( 'Responsable de tus datos: %s.', 'parkinson-rgpd' ), $controller['name'] ) : '',
			'links' => $links,
		];
	}
}
