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
	 * Widget que tiene el h1 de cada página: ID del documento => ID del widget.
	 *
	 * @var array<int, string>
	 */
	private static array $h1_owner = [];

	/**
	 * Widgets cuyo título es h1 si no se ha elegido otro nivel.
	 */
	private const H1_BY_DEFAULT = [ 'pbc-hero', 'pbc-page-header' ];

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

	/**
	 * Los widgets con JavaScript propio lo declaran en Integration::SCRIPTS.
	 */
	public function get_script_depends(): array {
		return in_array( $this->slug(), \DanielLucia\ParkinsonBahia\Core\Elementor\Integration::SCRIPTS, true ) ? [ 'pbc-widget-' . $this->slug() ] : [];
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

		// En la web pública Elementor descarta las opciones de los controles: no hace falta consultar las páginas.
		if ( \Elementor\Core\Frontend\Performance::should_optimize_controls() ) {
			self::$page_options = [];

			return [];
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
	 * Ancla de la sección, para enlazarla desde otra parte con «#ancla».
	 */
	protected function anchor_control(): void {
		$this->add_control(
			'anchor',
			[
				'label'       => esc_html__( 'Ancla (opcional)', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Una palabra sin espacios, por ejemplo «socio». Permite enlazar a esta sección con #socio.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
			]
		);
	}

	/**
	 * Atributo id de la sección a partir del ancla, ya escapado (o cadena vacía).
	 *
	 * @param array<string, mixed> $settings Ajustes del widget.
	 */
	protected function anchor_attr( array $settings ): string {
		$anchor = sanitize_title( (string) ( $settings['anchor'] ?? '' ) );

		return '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '';
	}

	/**
	 * Parte un texto en párrafos: una línea en blanco separa cada uno.
	 *
	 * @return string[]
	 */
	protected function paragraphs( string $text ): array {
		$parts = preg_split( '/\R{2,}/', $text );

		return array_values( array_filter( array_map( 'trim', is_array( $parts ) ? $parts : [] ) ) );
	}

	/**
	 * Nivel del título: solo los niveles que mantienen el orden de encabezados.
	 */
	protected function heading_tag_control( string $default = 'h2' ): void {
		$this->add_control(
			'title_tag',
			[
				'label'       => esc_html__( 'Nivel del título', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Cada página lleva un único H1: el título principal. Si ya hay uno, este se mostrará como H2.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
				],
				'default'     => $default,
			]
		);
	}

	/* ---------- Pintado ---------- */

	/**
	 * Etiqueta de encabezado validada.
	 *
	 * Solo puede haber un h1 por página: lo tiene el primer widget del
	 * documento que lo elige y los demás que lo pidan bajan a h2.
	 *
	 * @param array<string, mixed> $settings Ajustes del widget.
	 */
	protected function heading_tag( array $settings, string $fallback = 'h2' ): string {
		$tag = Utils::validate_html_tag( (string) ( $settings['title_tag'] ?? $fallback ) );
		$tag = in_array( $tag, [ 'h1', 'h2', 'h3' ], true ) ? $tag : $fallback;

		if ( 'h1' === $tag && $this->h1_owner() !== (string) $this->get_id() ) {
			return 'h2';
		}

		return $tag;
	}

	/**
	 * ID del widget al que le corresponde el h1 de la página actual: el primero,
	 * en el orden del documento, que lo tiene elegido.
	 *
	 * Se decide leyendo los datos guardados de la página y no según qué widget
	 * se pinta antes: Elementor guarda en caché el HTML de los widgets de texto
	 * fijo y puede pintarlos más de una vez o ninguna, así que el resultado no
	 * puede depender del orden de ejecución.
	 */
	private function h1_owner(): string {
		$document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $document ) {
			return (string) $this->get_id();
		}

		$document_id = (int) $document->get_main_id();

		if ( ! isset( self::$h1_owner[ $document_id ] ) ) {
			$elements                       = $document->get_elements_data();
			self::$h1_owner[ $document_id ] = self::find_h1_widget( is_array( $elements ) ? $elements : [] ) ?? (string) $this->get_id();
		}

		return self::$h1_owner[ $document_id ];
	}

	/**
	 * @param array<int, array<string, mixed>> $elements Elementos de Elementor.
	 */
	private static function find_h1_widget( array $elements ): ?string {
		foreach ( $elements as $element ) {
			$type = (string) ( $element['widgetType'] ?? '' );

			if ( str_starts_with( $type, 'pbc-' ) ) {
				$chosen = (string) ( $element['settings']['title_tag'] ?? ( in_array( $type, self::H1_BY_DEFAULT, true ) ? 'h1' : 'h2' ) );
				if ( 'h1' === $chosen ) {
					return (string) ( $element['id'] ?? '' );
				}
			}

			$found = self::find_h1_widget( is_array( $element['elements'] ?? null ) ? $element['elements'] : [] );
			if ( null !== $found ) {
				return $found;
			}
		}

		return null;
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
	 * Elementos publicados de un tipo de contenido, por «Orden» y título.
	 * Una sola consulta, sin paginación y con los campos extra ya cargados.
	 *
	 * @return \WP_Post[]
	 */
	protected function items( string $post_type, int $limit = 60 ): array {
		$query = new \WP_Query(
			[
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => $limit,
				'orderby'                => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			]
		);

		return $query->posts;
	}

	/**
	 * Aviso para quien edita cuando un listado todavía no tiene contenido.
	 * En la web pública no se muestra nada.
	 */
	protected function render_empty_notice( string $message ): void {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			printf( '<p class="pbc-empty-notice">%s</p>', esc_html( $message ) );
		}
	}

	/**
	 * Aviso fijo en el panel: de dónde salen los datos del widget.
	 */
	protected function source_note( string $message ): void {
		$this->add_control(
			'source_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html( $message ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
	}

	/**
	 * Identificador único para enlazar un título con su sección (aria-labelledby).
	 */
	protected function title_id(): string {
		return 'pbc-title-' . $this->get_id();
	}
}
