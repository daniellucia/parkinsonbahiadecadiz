<?php
/**
 * Control «Tipo de actividad» compartido por los widgets de la agenda.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda\Elementor;

use DanielLucia\ParkinsonBahia\Agenda\PostType;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

trait TypeControl {

	protected function type_control(): void {
		$options = [ '' => esc_html__( 'Todos los tipos', 'parkinson-agenda' ) ];

		// En la web pública Elementor no usa las opciones: no hace falta consultar los tipos.
		if ( ! \Elementor\Core\Frontend\Performance::should_optimize_controls() ) {
			$terms = get_terms(
				[
					'taxonomy'   => PostType::TAXONOMY,
					'hide_empty' => false,
				]
			);
			foreach ( is_array( $terms ) ? $terms : [] as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		$this->add_control(
			'type',
			[
				'label'       => esc_html__( 'Tipo de actividad', 'parkinson-agenda' ),
				'description' => esc_html__( 'Para mostrar solo un tipo (por ejemplo, solo talleres).', 'parkinson-agenda' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $options,
				'default'     => '',
			]
		);
	}

	/**
	 * @param array<string, mixed> $settings Ajustes del widget.
	 */
	protected function chosen_type( array $settings ): string {
		$slug = sanitize_title( (string) ( $settings['type'] ?? '' ) );

		return '' !== $slug && term_exists( $slug, PostType::TAXONOMY ) ? $slug : '';
	}
}
