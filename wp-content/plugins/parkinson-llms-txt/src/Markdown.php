<?php
/**
 * Conversor sencillo de HTML a Markdown, pensado para el contenido de una página.
 *
 * Conserva la estructura que ayuda a leer (títulos, párrafos, listas, enlaces,
 * citas, tablas simples) y descarta lo que no es contenido (scripts, estilos,
 * formularios, menús, iconos).
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

defined( 'ABSPATH' ) || exit;

final class Markdown {

	private const SKIP = [ 'script', 'style', 'noscript', 'svg', 'form', 'nav', 'button', 'iframe', 'template', 'input', 'select', 'textarea', 'video', 'audio', 'canvas', 'object' ];

	public function from_html( string $html ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$document = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$markdown = $this->children( $document );

		// Espacios al final de línea y saltos de más.
		$markdown = (string) preg_replace( '/[ \t]+\n/', "\n", $markdown );
		$markdown = (string) preg_replace( '/\n{3,}/', "\n\n", $markdown );

		return trim( $markdown );
	}

	private function children( DOMNode $node ): string {
		$output = '';

		foreach ( $node->childNodes as $child ) {
			$output .= $this->node( $child );
		}

		return $output;
	}

	private function inline( DOMNode $node ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $this->children( $node ) ) );
	}

	private function node( DOMNode $node ): string {
		if ( $node instanceof DOMText ) {
			return (string) preg_replace( '/\s+/u', ' ', $node->nodeValue ?? '' );
		}

		if ( ! $node instanceof DOMElement ) {
			return $node->hasChildNodes() ? $this->children( $node ) : '';
		}

		$tag = strtolower( $node->nodeName );

		if ( in_array( $tag, self::SKIP, true ) || 'true' === $node->getAttribute( 'aria-hidden' ) || $node->hasAttribute( 'hidden' ) ) {
			return '';
		}

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$text = $this->inline( $node );
				return '' === $text ? '' : "\n\n" . str_repeat( '#', (int) $tag[1] ) . ' ' . $text . "\n\n";

			case 'p':
			case 'summary':
			case 'figcaption':
				$text = $this->inline( $node );
				if ( '' === $text ) {
					return '';
				}
				return "\n\n" . ( 'summary' === $tag ? '**' . $text . '**' : $text ) . "\n\n";

			case 'br':
				return "\n";

			case 'hr':
				return "\n\n---\n\n";

			case 'strong':
			case 'b':
				$text = $this->inline( $node );
				return '' === $text ? '' : '**' . $text . '**';

			case 'em':
			case 'i':
				$text = $this->inline( $node );
				return '' === $text ? '' : '*' . $text . '*';

			case 'code':
				return '`' . trim( $node->textContent ) . '`';

			case 'pre':
				return "\n\n```\n" . trim( $node->textContent ) . "\n```\n\n";

			case 'a':
				$text = $this->inline( $node );
				$href = trim( $node->getAttribute( 'href' ) );
				if ( '' === $text ) {
					return '';
				}
				if ( '' === $href || str_starts_with( $href, '#' ) || str_starts_with( strtolower( $href ), 'javascript:' ) ) {
					return $text;
				}
				return '[' . $text . '](' . str_replace( [ ' ', ')' ], [ '%20', '%29' ], $href ) . ')';

			case 'img':
				// Para un lector automático lo útil es la descripción de la imagen.
				$alt = trim( $node->getAttribute( 'alt' ) );
				return '' === $alt ? '' : ' (Imagen: ' . $alt . ') ';

			case 'ul':
			case 'ol':
				return "\n\n" . $this->list_items( $node, 'ol' === $tag, 0 ) . "\n\n";

			case 'blockquote':
				$text  = trim( $this->children( $node ) );
				$lines = array_map( static fn ( string $line ): string => '> ' . $line, explode( "\n", (string) preg_replace( '/\n{2,}/', "\n", $text ) ) );
				return '' === $text ? '' : "\n\n" . implode( "\n", $lines ) . "\n\n";

			case 'dt':
				$text = $this->inline( $node );
				return '' === $text ? '' : "\n\n**" . $text . ':** ';

			case 'dd':
				return $this->inline( $node ) . "\n";

			case 'table':
				return "\n\n" . $this->table( $node ) . "\n\n";

			case 'time':
				return $this->inline( $node );

			case 'div':
			case 'section':
			case 'article':
			case 'header':
			case 'footer':
			case 'main':
			case 'aside':
			case 'figure':
			case 'details':
			case 'dl':
			case 'li':
				return "\n\n" . $this->children( $node ) . "\n\n";

			default:
				return $this->children( $node );
		}
	}

	private function list_items( DOMElement $list, bool $ordered, int $depth ): string {
		$lines = [];
		$index = 0;

		foreach ( $list->childNodes as $item ) {
			if ( ! $item instanceof DOMElement || 'li' !== strtolower( $item->nodeName ) ) {
				continue;
			}

			++$index;
			$text   = '';
			$nested = '';

			foreach ( $item->childNodes as $child ) {
				if ( $child instanceof DOMElement && in_array( strtolower( $child->nodeName ), [ 'ul', 'ol' ], true ) ) {
					$nested .= "\n" . $this->list_items( $child, 'ol' === strtolower( $child->nodeName ), $depth + 1 );
				} else {
					$text .= $this->node( $child );
				}
			}

			$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

			if ( '' === $text && '' === $nested ) {
				continue;
			}

			$lines[] = str_repeat( '  ', $depth ) . ( $ordered ? $index . '. ' : '- ' ) . $text . $nested;
		}

		return implode( "\n", $lines );
	}

	private function table( DOMElement $table ): string {
		$rows = [];

		foreach ( $table->getElementsByTagName( 'tr' ) as $row ) {
			$cells = [];
			foreach ( $row->childNodes as $cell ) {
				if ( $cell instanceof DOMElement && in_array( strtolower( $cell->nodeName ), [ 'th', 'td' ], true ) ) {
					$cells[] = str_replace( '|', '\|', $this->inline( $cell ) );
				}
			}
			if ( $cells ) {
				$rows[] = $cells;
			}
		}

		if ( ! $rows ) {
			return '';
		}

		$lines = [];
		foreach ( $rows as $index => $cells ) {
			$lines[] = '| ' . implode( ' | ', $cells ) . ' |';
			if ( 0 === $index ) {
				$lines[] = '|' . str_repeat( ' --- |', count( $cells ) );
			}
		}

		return implode( "\n", $lines );
	}
}
