<?php
/**
 * Tipo de contenido «Espacio»: una sala o instalación del centro, con su foto.
 *
 * Título = nombre de la sala, imagen destacada = foto (su texto alternativo se
 * escribe en la biblioteca de medios), orden = campo «Orden».
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

final class Espacio extends PostType {

	public const POST_TYPE = 'pbc_espacio';

	public function name(): string {
		return self::POST_TYPE;
	}

	protected function admin_menu(): string {
		return self::ADMIN_MENU_CENTRE;
	}

	protected function title_placeholder(): string {
		return __( 'Nombre de la sala (por ejemplo: Comedor)', 'parkinson-bahia-core' );
	}

	protected function args(): array {
		return $this->list_only_args(
			$this->labels(
				__( 'Instalación', 'parkinson-bahia-core' ),
				__( 'Instalaciones', 'parkinson-bahia-core' ),
				__( 'Añadir instalación', 'parkinson-bahia-core' )
			),
			[ 'title', 'thumbnail', 'page-attributes' ]
		);
	}
}
