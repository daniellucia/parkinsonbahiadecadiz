<?php
/**
 * Ajustes del plugin: lectura con valores por defecto y saneado al guardar.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION     = 'pllms_settings';
	public const MAX_LIMIT  = 200;
	public const META_SKIP  = '_pllms_exclude';
	public const META_DESCR = '_pllms_description';

	/**
	 * Ajustes guardados combinados con los valores por defecto.
	 *
	 * Sin configurar nada, el fichero ya sale completo: nombre y descripción
	 * del sitio, y una sección por cada tipo de contenido público.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		$settings = [
			'enabled'  => array_key_exists( 'enabled', $saved ) ? (bool) $saved['enabled'] : true,
			'title'    => (string) ( $saved['title'] ?? '' ),
			'summary'  => (string) ( $saved['summary'] ?? '' ),
			'details'  => (string) ( $saved['details'] ?? '' ),
			'extra'    => (string) ( $saved['extra'] ?? '' ),
			'markdown' => array_key_exists( 'markdown', $saved ) ? (bool) $saved['markdown'] : true,
			'full'     => array_key_exists( 'full', $saved ) ? (bool) $saved['full'] : true,
			'sections' => [],
		];

		$saved_sections = is_array( $saved['sections'] ?? null ) ? $saved['sections'] : [];

		foreach ( $this->post_types() as $name => $object ) {
			$section = is_array( $saved_sections[ $name ] ?? null ) ? $saved_sections[ $name ] : [];

			$settings['sections'][ $name ] = [
				'enabled'  => array_key_exists( 'enabled', $section ) ? (bool) $section['enabled'] : true,
				'heading'  => '' !== trim( (string) ( $section['heading'] ?? '' ) ) ? (string) $section['heading'] : $this->default_heading( $name, $object ),
				'limit'    => isset( $section['limit'] ) ? max( 1, min( self::MAX_LIMIT, (int) $section['limit'] ) ) : ( 'post' === $name ? 20 : 50 ),
				'optional' => array_key_exists( 'optional', $section ) ? (bool) $section['optional'] : false,
			];
		}

		return $settings;
	}

	/**
	 * Título del fichero: el ajuste o, si está vacío, el nombre del sitio.
	 */
	public function title(): string {
		$title = trim( (string) $this->all()['title'] );

		return '' !== $title ? $title : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

	/**
	 * Resumen: el ajuste o, si está vacío, la descripción corta del sitio.
	 */
	public function summary(): string {
		$summary = trim( (string) $this->all()['summary'] );

		return '' !== $summary ? $summary : wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES );
	}

	/**
	 * Tipos de contenido que tienen dirección pública propia.
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public function post_types(): array {
		$types = get_post_types(
			[
				'public'             => true,
				'publicly_queryable' => true,
			],
			'objects'
		);

		// Las páginas son públicas pero no «publicly_queryable».
		$page = get_post_type_object( 'page' );
		if ( $page ) {
			$types = [ 'page' => $page ] + $types;
		}

		// Adjuntos y plantillas de constructores no son contenido para leer.
		unset( $types['attachment'], $types['elementor_library'], $types['e-floating-buttons'], $types['e-landing-page'] );

		return $types;
	}

	private function default_heading( string $name, \WP_Post_Type $post_type ): string {
		if ( 'post' === $name ) {
			$posts_page = (int) get_option( 'page_for_posts' );

			return $posts_page > 0 ? get_the_title( $posts_page ) : (string) $post_type->labels->name;
		}

		return (string) $post_type->labels->name;
	}

	/**
	 * Saneado de los ajustes al guardar.
	 *
	 * @param mixed $input Datos del formulario.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : [];

		$clean = [
			'enabled'  => ! empty( $input['enabled'] ),
			'title'    => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'summary'  => sanitize_textarea_field( (string) ( $input['summary'] ?? '' ) ),
			'details'  => sanitize_textarea_field( (string) ( $input['details'] ?? '' ) ),
			'extra'    => sanitize_textarea_field( (string) ( $input['extra'] ?? '' ) ),
			'markdown' => ! empty( $input['markdown'] ),
			'full'     => ! empty( $input['full'] ),
			'sections' => [],
		];

		$sections = is_array( $input['sections'] ?? null ) ? $input['sections'] : [];

		foreach ( array_keys( $this->post_types() ) as $name ) {
			$section = is_array( $sections[ $name ] ?? null ) ? $sections[ $name ] : [];

			$clean['sections'][ $name ] = [
				'enabled'  => ! empty( $section['enabled'] ),
				'heading'  => sanitize_text_field( (string) ( $section['heading'] ?? '' ) ),
				'limit'    => max( 1, min( self::MAX_LIMIT, absint( $section['limit'] ?? 50 ) ) ),
				'optional' => ! empty( $section['optional'] ),
			];
		}

		return $clean;
	}
}
