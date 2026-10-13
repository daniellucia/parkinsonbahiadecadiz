<?php
/**
 * Base de todos los widgets del sitio.
 *
 * Reglas comunes: un bloque BEM por widget (.pbc-<slug>), su propia hoja de
 * estilo, sin envoltorio interior de Elementor y todos los ajustes escapados
 * al pintar (los ajustes de un widget son datos no fiables).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\Elementor\Integration;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

abstract class Widget extends Widget_Base {

	protected const CUSTOM_LINK = 'custom';

	/**
	 * Opciones del desplegable de páginas, calculadas una vez por petición.
	 *
	 * @var array<string, string>|null
	 */
	private static ?array $page_options = null;

	/**
	 * Identificador corto: nombre del widget (pbc-<slug>), del bloque BEM y de la hoja de estilo.
	 */
	abstract protected function slug(): string;

	public function get_name(): string {
		return 'pbc-' . $this->slug();
	}

	public function get_categories(): array {
		return [ Integration::CATEGORY ];
	}

	public function get_style_depends(): array {
		return [ 'pbc-widget-' . $this->slug() ];
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Los widgets de texto fijo se pueden cachear; los que consultan contenidos
	 * sobrescriben este método y devuelven true.
	 */
	protected function is_dynamic_content(): bool {
		return false;
	}

	/* ---------- Controles ---------- */

	protected function text_control( string $id, string $label, string $default = '', bool $multiline = false ): void {
		$this->add_control(
			$id,
			[
				'label'       => $label,
				'type'        => $multiline ? Controls_Manager::TEXTAREA : Controls_Manager::TEXT,
				'default'     => $default,
				'label_block' => true,
			]
		);
	}

	/**
	 * Enlace: un desplegable con las páginas del sitio y, como última opción,
	 * «Enlace personalizado», que muestra un campo para escribir cualquier URL.
	 * Así un enlace a una página sigue funcionando aunque cambie su dirección.
	 *
	 * @param Widget_Base|\Elementor\Repeater $target Widget o repetidor donde se añaden los controles.
	 */
	protected function link_controls( $target, string $id, string $label ): void {
		$target->add_control(
			$id . '_page',
			[
				'label'       => $label,
				'type'        => Controls_Manager::SELECT,
				'options'     => self::page_options(),
				'default'     => '',
				'label_block' => true,
			]
		);
		$target->add_control(
			$id,
			[
				'label'       => esc_html__( 'Dirección del enlace', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'condition'   => [ $id . '_page' => self::CUSTOM_LINK ],
			]
		);
	}

	/**
	 * Opciones del desplegable de enlace: sin enlace, páginas publicadas y enlace personalizado.
	 *
	 * @return array<string, string>
	 */
	private static function page_options(): array {
		if ( null !== self::$page_options ) {
			return self::$page_options;
		}

		$options = [ '' => esc_html__( '— Sin enlace —', 'parkinson-bahia-core' ) ];

		$pages = get_pages(
			[
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			]
		);

		foreach ( $pages ? $pages : [] as $page ) {
			$depth = count( get_post_ancestors( $page ) );
			// La clave lleva prefijo para que el desplegable conserve este orden.
			$options[ 'page-' . $page->ID ] = str_repeat( '— ', $depth ) . wp_strip_all_tags( get_the_title( $page ) );
		}

		$options[ self::CUSTOM_LINK ] = esc_html__( 'Enlace personalizado', 'parkinson-bahia-core' );

		self::$page_options = $options;

		return $options;
	}

	/**
	 * Enlace elegido en link_controls(), con el formato de un control URL de Elementor.
	 *
	 * @param array<string, mixed> $settings Ajustes del widget o de una fila del repetidor.
	 * @return array<string, mixed> Vacío si no hay enlace.
	 */
	protected function link_value( array $settings, string $id ): array {
		$choice = (string) ( $settings[ $id . '_page' ] ?? '' );

		if ( self::CUSTOM_LINK === $choice ) {
			$link = $settings[ $id ] ?? null;

			return is_array( $link ) && '' !== trim( (string) ( $link['url'] ?? '' ) ) ? $link : [];
		}

		if ( ! str_starts_with( $choice, 'page-' ) ) {
			return [];
		}

		$page_id = absint( substr( $choice, 5 ) );

		if ( 0 === $page_id || 'publish' !== get_post_status( $page_id ) || 'page' !== get_post_type( $page_id ) ) {
			return [];
		}

		return [
			'url'         => (string) get_permalink( $page_id ),
			'is_external' => '',
			'nofollow'    => '',
		];
	}

	protected function image_control( string $id, string $label ): void {
		$this->add_control(
			$id,
			[
				'label'       => $label,
				'description' => esc_html__( 'El texto alternativo se escribe en la biblioteca de medios.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => [ 'image' ],
			]
		);
	}

	/**
	 * Nivel del título: solo los niveles que mantienen el orden de encabezados.
	 */
	protected function heading_tag_control( string $default = 'h2' ): void {
		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__( 'Nivel del título', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
				],
				'default' => $default,
			]
		);
	}

	/* ---------- Pintado ---------- */

	/**
	 * Etiqueta de encabezado validada.
	 *
	 * @param array<string, mixed> $settings Ajustes del widget.
	 */
	protected function heading_tag( array $settings, string $fallback = 'h2' ): string {
		$tag = Utils::validate_html_tag( (string) ( $settings['title_tag'] ?? $fallback ) );

		return in_array( $tag, [ 'h1', 'h2', 'h3' ], true ) ? $tag : $fallback;
	}

	/**
	 * Pinta un encabezado si tiene texto.
	 */
	protected function render_heading( string $tag, string $text, string $class_name, string $id = '' ): void {
		if ( '' === trim( $text ) ) {
			return;
		}

		printf(
			'<%1$s class="%2$s"%3$s>%4$s</%1$s>',
			esc_html( $tag ),
			esc_attr( $class_name ),
			'' !== $id ? ' id="' . esc_attr( $id ) . '"' : '',
			esc_html( $text )
		);
	}

	/**
	 * Pinta un párrafo si tiene texto.
	 */
	protected function render_text( string $text, string $class_name ): void {
		if ( '' === trim( $text ) ) {
			return;
		}

		printf( '<p class="%s">%s</p>', esc_attr( $class_name ), nl2br( esc_html( $text ) ) );
	}

	/**
	 * Imagen de la biblioteca con srcset, tamaño y carga diferida.
	 *
	 * @param mixed                $media Valor del control de imagen.
	 * @param array<string, mixed> $attrs Atributos extra (class, sizes, loading, fetchpriority).
	 */
	protected function image_html( $media, string $size, array $attrs = [], bool $decorative = false ): string {
		$id = is_array( $media ) ? absint( $media['id'] ?? 0 ) : 0;

		if ( 0 === $id ) {
			return '';
		}

		if ( $decorative ) {
			$attrs['alt'] = '';
		}

		return wp_get_attachment_image( $id, $size, false, $attrs );
	}

	/**
	 * Identificador único para enlazar un título con su sección (aria-labelledby).
	 */
	protected function title_id(): string {
		return 'pbc-title-' . $this->get_id();
	}
}
