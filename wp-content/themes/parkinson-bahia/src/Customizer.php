<?php
/**
 * Datos de contacto y textos comunes editables desde Apariencia > Personalizar.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

use WP_Customize_Manager;

defined( 'ABSPATH' ) || exit;

final class Customizer {

	/**
	 * Campos: clave => [ etiqueta, tipo, valor por defecto, sección ].
	 * Los valores por defecto son los datos de contacto confirmados del proyecto.
	 *
	 * @return array<string, array{0:string,1:string,2:string,3:string}>
	 */
	public static function fields(): array {
		return [
			'pbc_phone'         => [ __( 'Teléfono', 'parkinson-bahia' ), 'text', '956 59 19 28', 'pbc_contact' ],
			'pbc_email'         => [ __( 'Correo electrónico', 'parkinson-bahia' ), 'email', 'unidad@parkinsonbahiadecadiz.org', 'pbc_contact' ],
			'pbc_address'       => [ __( 'Dirección', 'parkinson-bahia' ), 'textarea', "C/ Milongas s/n\n11100 San Fernando (Cádiz)", 'pbc_contact' ],
			'pbc_hours_short'   => [ __( 'Horario (barra superior)', 'parkinson-bahia' ), 'text', 'Lunes a viernes, de 9:00 a 17:00', 'pbc_contact' ],
			'pbc_hours'         => [ __( 'Horario (completo)', 'parkinson-bahia' ), 'textarea', 'Lunes a viernes, de 9:00 a 17:00, preferentemente con cita previa. Cerrado en agosto.', 'pbc_contact' ],
			'pbc_map_url'       => [ __( 'Enlace «Cómo llegar»', 'parkinson-bahia' ), 'url', 'https://www.google.com/maps/dir/?api=1&destination=36.44795,-6.21460', 'pbc_contact' ],
			'pbc_contact_title' => [ __( 'Título del bloque de contacto', 'parkinson-bahia' ), 'text', 'Ven a conocernos', 'pbc_contact' ],
			'pbc_contact_text'  => [ __( 'Texto del bloque de contacto', 'parkinson-bahia' ), 'textarea', 'Lo más fácil es llamar por teléfono. Si prefieres venir, pide cita antes y te atenderemos con calma.', 'pbc_contact' ],

			'pbc_cta_label'     => [ __( 'Texto del botón destacado', 'parkinson-bahia' ), 'text', 'Hazte socio', 'pbc_header' ],
			'pbc_cta_url'       => [ __( 'Enlace del botón destacado', 'parkinson-bahia' ), 'url', '', 'pbc_header' ],
			'pbc_blog_intro'    => [ __( 'Introducción de Actualidad', 'parkinson-bahia' ), 'textarea', 'Lo que pasa en la asociación y consejos de nuestro equipo.', 'pbc_header' ],

			'pbc_facebook'      => [ 'Facebook', 'url', 'https://www.facebook.com/parkinsonbahiadecadiz', 'pbc_footer' ],
			'pbc_instagram'     => [ 'Instagram', 'url', 'https://www.instagram.com/parkinsonbahiadecadiz', 'pbc_footer' ],
			'pbc_x'             => [ 'X', 'url', 'https://x.com/parkinsonbahia', 'pbc_footer' ],
			'pbc_youtube'       => [ 'YouTube', 'url', 'https://www.youtube.com/channel/UC7-c1fm0in6KnCjeZzd5Mlg', 'pbc_footer' ],
			'pbc_legal_name'    => [ __( 'Nombre legal y CIF', 'parkinson-bahia' ), 'textarea', 'Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz. CIF G-11591302.', 'pbc_footer' ],
			'pbc_footer_note'   => [ __( 'Nota final del pie', 'parkinson-bahia' ), 'textarea', 'Miembro de FEP, FANDEP, FEGADI, COCEMFE y EPDA.', 'pbc_footer' ],
		];
	}

	public function register(): void {
		add_action( 'customize_register', [ $this, 'customize' ] );
	}

	public function customize( WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_panel(
			'pbc_panel',
			[
				'title'    => __( 'Párkinson Bahía de Cádiz', 'parkinson-bahia' ),
				'priority' => 30,
			]
		);

		$sections = [
			'pbc_contact' => __( 'Datos de contacto', 'parkinson-bahia' ),
			'pbc_header'  => __( 'Cabecera y Actualidad', 'parkinson-bahia' ),
			'pbc_footer'  => __( 'Pie de página', 'parkinson-bahia' ),
		];

		foreach ( $sections as $id => $title ) {
			$wp_customize->add_section(
				$id,
				[
					'title' => $title,
					'panel' => 'pbc_panel',
				]
			);
		}

		foreach ( self::fields() as $key => [ $label, $type, $default, $section ] ) {
			$wp_customize->add_setting(
				$key,
				[
					'default'           => $default,
					'sanitize_callback' => self::sanitizer( $type ),
				]
			);
			$wp_customize->add_control(
				$key,
				[
					'label'   => $label,
					'section' => $section,
					'type'    => $type,
				]
			);
		}
	}

	private static function sanitizer( string $type ): callable {
		return match ( $type ) {
			'email'    => 'sanitize_email',
			'url'      => 'esc_url_raw',
			'textarea' => 'sanitize_textarea_field',
			default    => 'sanitize_text_field',
		};
	}
}
