<?php
/**
 * Atajos para la página de la política de cookies:
 *
 * - [pbc_cookies_tabla nivel="3"]: los servicios declarados, agrupados por
 *   categoría, con quién los pone, para qué, qué guardan y cuánto duran.
 *   «nivel» es el nivel del título de cada categoría (3 por defecto).
 * - [pbc_cookies_ajustes]: el botón «Configurar cookies». Es también el
 *   destino de los enlaces «…#configurar-cookies» cuando no hay JavaScript.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class Shortcodes {

	public function __construct(
		private readonly Settings $settings,
		private readonly Services $services
	) {}

	public function register(): void {
		add_shortcode( 'pbc_cookies_tabla', [ $this, 'table' ] );
		add_shortcode( 'pbc_cookies_ajustes', [ $this, 'button' ] );
	}

	/**
	 * @param array<string, string>|string $atts Atributos del atajo.
	 */
	public function table( $atts ): string {
		$atts  = shortcode_atts( [ 'nivel' => '3' ], (array) $atts, 'pbc_cookies_tabla' );
		$level = min( 6, max( 2, absint( $atts['nivel'] ) ) );
		$tag   = 'h' . $level;
		$html  = '';

		foreach ( $this->services->categories() as $id => $category ) {
			$services = $this->services->in_category( $id );
			if ( ! $services ) {
				continue;
			}

			$html .= '<div class="pbc-cookies-list">';
			$html .= sprintf( '<%1$s class="pbc-cookies-list__title">%2$s</%1$s>', $tag, esc_html( $category['label'] ) );
			$html .= '<p class="pbc-cookies-list__description">' . esc_html( $category['description'] ) . '</p>';
			$html .= '<ul class="pbc-cookies-list__items">';

			foreach ( $services as $service ) {
				$html .= '<li class="pbc-cookies-list__item">';
				$html .= '<p class="pbc-cookies-list__name">' . esc_html( $service['name'] ) . '</p>';
				$html .= '<dl class="pbc-cookies-list__facts">';
				$html .= $this->fact( __( 'Quién la pone', 'parkinson-cookies' ), esc_html( $service['provider'] ) );
				$html .= $this->fact( __( 'Para qué sirve', 'parkinson-cookies' ), esc_html( $service['purpose'] ) );
				$html .= $this->fact( __( 'Qué guarda', 'parkinson-cookies' ), esc_html( $service['cookies'] ) );
				$html .= $this->fact( __( 'Cuánto dura', 'parkinson-cookies' ), esc_html( $service['duration'] ) );

				if ( '' !== $service['policy'] ) {
					$link  = sprintf(
						'<a href="%1$s" target="_blank" rel="noopener">%2$s <span class="pbc-cookies-list__new">%3$s</span></a>',
						esc_url( $service['policy'] ),
						/* translators: %s: nombre del proveedor. */
						esc_html( sprintf( __( 'Política de privacidad de %s', 'parkinson-cookies' ), $service['provider'] ) ),
						esc_html__( '(se abre en una pestaña nueva)', 'parkinson-cookies' )
					);
					$html .= $this->fact( __( 'Más información', 'parkinson-cookies' ), $link );
				}

				$html .= '</dl></li>';
			}

			$html .= '</ul></div>';
		}

		return $html;
	}

	/**
	 * Un dato de la lista; el valor ya viene escapado.
	 */
	private function fact( string $term, string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		return '<div class="pbc-cookies-list__fact"><dt>' . esc_html( $term ) . '</dt><dd>' . $value . '</dd></div>';
	}

	public function button(): string {
		return sprintf(
			'<div class="pbc-cookies-settings" id="%1$s"><button class="pbc-cookies__button" type="button" data-pbc-cookies-open hidden>%2$s</button><noscript><p>%3$s</p></noscript></div>',
			esc_attr( Frontend::ANCHOR ),
			esc_html__( 'Configurar cookies', 'parkinson-cookies' ),
			esc_html__( 'Para cambiar tu elección hace falta tener JavaScript activado. Sin JavaScript esta web no carga ningún contenido opcional, así que no se guarda ninguna cookie que necesite tu permiso.', 'parkinson-cookies' )
		);
	}
}
