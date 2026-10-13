<?php
/**
 * Genera llms.txt, llms-full.txt y la versión en Markdown de cada contenido.
 *
 * Formato de llms.txt (https://llmstxt.org): un H1 con el nombre, una cita con
 * el resumen, texto libre opcional y secciones H2 con listas de enlaces
 * «- [título](url): descripción». La sección «Optional» reúne lo secundario.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Generator {

	private const FULL_MAX_ITEMS = 80;

	public function __construct(
		private readonly Settings $settings,
		private readonly Cache $cache,
		private readonly Markdown $markdown
	) {}

	public function llms_txt(): string {
		return $this->cache->remember( 'index', fn (): string => $this->build_index() );
	}

	public function llms_full(): string {
		return $this->cache->remember( 'full', fn (): string => $this->build_full() );
	}

	/**
	 * Versión en Markdown de un contenido, o null si no se puede mostrar.
	 */
	public function post_markdown( int $post_id ): ?string {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! $this->is_listable( $post ) ) {
			return null;
		}

		return $this->cache->remember( 'post_' . $post->ID, fn (): string => $this->build_post( $post ) );
	}

	/**
	 * Dirección de la versión en Markdown: la misma URL con «.md» o, si acaba
	 * en barra, con «index.html.md», como propone llmstxt.org.
	 */
	public function markdown_url( WP_Post $post ): string {
		$url = (string) get_permalink( $post );

		return str_ends_with( $url, '/' ) ? $url . 'index.html.md' : $url . '.md';
	}

	/* ---------- Construcción ---------- */

	private function build_index(): string {
		$settings = $this->settings->all();
		$lines    = $this->header_lines( $settings );
		$optional = [];

		foreach ( $settings['sections'] as $post_type => $section ) {
			if ( ! $section['enabled'] ) {
				continue;
			}

			$items = [];
			foreach ( $this->items( $post_type, (int) $section['limit'] ) as $post ) {
				$description = $this->description( $post );
				$url         = $settings['markdown'] ? $this->markdown_url( $post ) : (string) get_permalink( $post );
				$items[]     = '- [' . $this->escape_link_text( $this->title( $post ) ) . '](' . $url . ')' . ( '' !== $description ? ': ' . $description : '' );
			}

			if ( ! $items ) {
				continue;
			}

			if ( $section['optional'] ) {
				$optional = array_merge( $optional, $items );
				continue;
			}

			$lines[] = '## ' . $section['heading'];
			$lines[] = '';
			$lines   = array_merge( $lines, $items );
			$lines[] = '';
		}

		$extra = trim( (string) $settings['extra'] );
		if ( '' !== $extra ) {
			$lines[] = $extra;
			$lines[] = '';
		}

		if ( $optional ) {
			// «Optional» es el nombre que fija la especificación para lo que se puede omitir.
			$lines[] = '## Optional';
			$lines[] = '';
			$lines   = array_merge( $lines, $optional );
			$lines[] = '';
		}

		/**
		 * Permite ajustar el contenido final de llms.txt.
		 *
		 * @param string $content Contenido generado.
		 */
		return (string) apply_filters( 'parkinson_llms_txt/content', rtrim( implode( "\n", $lines ) ) . "\n" );
	}

	private function build_full(): string {
		$settings = $this->settings->all();
		$lines    = $this->header_lines( $settings );
		$count    = 0;

		foreach ( $settings['sections'] as $post_type => $section ) {
			if ( ! $section['enabled'] ) {
				continue;
			}

			foreach ( $this->items( $post_type, (int) $section['limit'] ) as $post ) {
				if ( ++$count > self::FULL_MAX_ITEMS ) {
					break 2;
				}

				$lines[] = '---';
				$lines[] = '';
				// En el fichero completo cada contenido baja un nivel más: su título es un H2.
				$lines[] = $this->demote_headings( $this->build_post( $post ) );
				$lines[] = '';
			}
		}

		return rtrim( implode( "\n", $lines ) ) . "\n";
	}

	private function build_post( WP_Post $post ): string {
		$description = $this->description( $post );
		$lines       = [ '# ' . $this->title( $post ), '' ];

		if ( '' !== $description ) {
			$lines[] = '> ' . $description;
			$lines[] = '';
		}

		$lines[] = 'URL: ' . get_permalink( $post );
		if ( 'post' === $post->post_type ) {
			$lines[] = 'Fecha: ' . get_the_date( '', $post );
		}
		$lines[] = '';

		$body = $this->demote_headings( $this->markdown->from_html( $this->rendered_content( $post ) ) );
		if ( '' !== $body ) {
			$lines[] = $body;
		}

		return rtrim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * @param array<string, mixed> $settings Ajustes.
	 * @return string[]
	 */
	private function header_lines( array $settings ): array {
		$lines   = [ '# ' . $this->settings->title(), '' ];
		$summary = $this->one_line( $this->settings->summary() );

		if ( '' !== $summary ) {
			$lines[] = '> ' . $summary;
			$lines[] = '';
		}

		$details = trim( (string) $settings['details'] );
		if ( '' !== $details ) {
			$lines[] = $details;
			$lines[] = '';
		}

		return $lines;
	}

	/* ---------- Contenidos ---------- */

	/**
	 * Contenidos publicados de un tipo que pueden aparecer en el fichero.
	 *
	 * @return WP_Post[]
	 */
	private function items( string $post_type, int $limit ): array {
		$query = new WP_Query(
			[
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'has_password'           => false,
				// Margen para compensar los que se descartan después.
				'posts_per_page'         => min( Settings::MAX_LIMIT, $limit + 20 ),
				'orderby'                => 'post' === $post_type ? [ 'date' => 'DESC' ] : [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			]
		);

		$posts = array_values( array_filter( $query->posts, fn ( WP_Post $post ): bool => ! $this->is_excluded( $post ) ) );

		// La portada va la primera de las páginas.
		$front = (int) get_option( 'page_on_front' );
		if ( 'page' === $post_type && $front > 0 ) {
			usort( $posts, static fn ( WP_Post $a, WP_Post $b ): int => ( $b->ID === $front ) <=> ( $a->ID === $front ) );
		}

		return array_slice( $posts, 0, $limit );
	}

	public function is_listable( WP_Post $post ): bool {
		$sections = $this->settings->all()['sections'];

		return 'publish' === $post->post_status
			&& '' === $post->post_password
			&& ! empty( $sections[ $post->post_type ]['enabled'] )
			&& ! $this->is_excluded( $post );
	}

	/**
	 * Excluido a mano o marcado «noindex» en un plugin de SEO.
	 */
	private function is_excluded( WP_Post $post ): bool {
		if ( get_post_meta( $post->ID, Settings::META_SKIP, true ) ) {
			return true;
		}

		if ( '1' === (string) get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return true;
		}

		$rank_math = get_post_meta( $post->ID, 'rank_math_robots', true );
		if ( is_array( $rank_math ) && in_array( 'noindex', $rank_math, true ) ) {
			return true;
		}

		/**
		 * Permite excluir contenidos con otros criterios.
		 *
		 * @param bool    $excluded Si se excluye.
		 * @param WP_Post $post     Contenido.
		 */
		return (bool) apply_filters( 'parkinson_llms_txt/exclude_post', false, $post );
	}

	private function title( WP_Post $post ): string {
		return $this->one_line( wp_specialchars_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES ) );
	}

	/**
	 * Descripción: la escrita a mano, el extracto o el principio del texto.
	 */
	private function description( WP_Post $post ): string {
		$custom = trim( (string) get_post_meta( $post->ID, Settings::META_DESCR, true ) );
		if ( '' !== $custom ) {
			return $this->one_line( $custom );
		}

		if ( '' !== trim( $post->post_excerpt ) ) {
			return $this->one_line( wp_strip_all_tags( $post->post_excerpt ) );
		}

		$text = wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $post->post_content ) ) );

		return $this->one_line( wp_trim_words( $text, 28, '…' ) );
	}

	/**
	 * HTML del contenido tal como se ve en la web, sin cabecera ni pie.
	 */
	private function rendered_content( WP_Post $post ): string {
		// Página de entradas: no tiene contenido propio, se listan las últimas noticias.
		if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
			$html = '<ul>';
			foreach ( $this->items( 'post', 20 ) as $item ) {
				$html .= '<li><a href="' . esc_url( (string) get_permalink( $item ) ) . '">' . esc_html( $this->title( $item ) ) . '</a></li>';
			}
			return $html . '</ul>';
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post->ID );
			if ( $document && $document->is_built_with_elementor() ) {
				return (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post->ID );
			}
		}

		$previous        = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Necesario para que los bloques se pinten con su contexto.
		setup_postdata( $post );

		$html = (string) apply_filters( 'the_content', $post->post_content );

		$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Se restaura el valor anterior.
		if ( $previous instanceof WP_Post ) {
			setup_postdata( $previous );
		}

		return $html;
	}

	/* ---------- Utilidades de texto ---------- */

	private function one_line( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	private function escape_link_text( string $text ): string {
		return str_replace( [ '[', ']' ], [ '(', ')' ], $text );
	}

	/**
	 * Baja un nivel todos los títulos para que el documento tenga un solo H1.
	 */
	private function demote_headings( string $markdown ): string {
		return (string) preg_replace( '/^(#{1,5}) /m', '#$1 ', $markdown );
	}
}
