<?php
/**
 * Protección de datos de los formularios recibidos.
 *
 * - Herramientas de privacidad de WordPress (Herramientas → Exportar / Borrar
 *   datos personales): encuentran los envíos por el correo de la persona.
 *   La exportación muestra el DNI y el IBAN enmascarados, porque el archivo
 *   se envía por correo; completos solo con «Descargar sus datos».
 * - «Descargar sus datos» en cada envío (JSON con todo, también lo cifrado),
 *   para quien solo dio su teléfono.
 * - Para el plugin «Protección de datos (RGPD)»: el registro de tratamientos
 *   y los plazos de conservación de cada formulario (borra los envíos
 *   cerrados cuando superan su plazo).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class Privacy {

	private const DOWNLOAD = 'pbc_envio_download';
	private const PER_PAGE = 50;

	/** Meses por defecto de cada formulario (pendientes de confirmar con la asociación). */
	private const DEFAULT_MONTHS = [
		Definitions::CONTACT   => 12,
		Definitions::VOLUNTEER => 12,
		Definitions::MEMBER    => 6,
	];

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'exporters' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'erasers' ] );
		add_filter( 'parkinson_rgpd/treatments', [ $this, 'treatments' ] );
		add_filter( 'parkinson_rgpd/retention', [ $this, 'retention' ] );
		add_action( 'admin_post_' . self::DOWNLOAD, [ $this, 'download' ] );
		add_filter( 'post_row_actions', [ $this, 'row_action' ], 10, 2 );
	}

	/* ---------- Herramientas de privacidad de WordPress ---------- */

	/**
	 * @param array<string, array<string, mixed>> $exporters Exportadores.
	 * @return array<string, array<string, mixed>>
	 */
	public function exporters( array $exporters ): array {
		$exporters['pbc-formularios'] = [
			'exporter_friendly_name' => __( 'Formularios de la web', 'parkinson-bahia-core' ),
			'callback'               => [ $this, 'export' ],
		];

		return $exporters;
	}

	/**
	 * @param array<string, array<string, mixed>> $erasers Borradores.
	 * @return array<string, array<string, mixed>>
	 */
	public function erasers( array $erasers ): array {
		$erasers['pbc-formularios'] = [
			'eraser_friendly_name' => __( 'Formularios de la web', 'parkinson-bahia-core' ),
			'callback'             => [ $this, 'erase' ],
		];

		return $erasers;
	}

	/**
	 * Envíos con ese correo (página a página).
	 *
	 * @return array{0: int[], 1: bool} IDs y si quedan más.
	 */
	private function by_email( string $email, int $page ): array {
		$email = sanitize_email( $email );
		if ( '' === $email ) {
			return [ [], false ];
		}

		// El correo va dentro de un array serializado: se busca el texto y se confirma después.
		$ids = get_posts(
			[
				'post_type'      => Submissions::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => self::PER_PAGE,
				'paged'          => max( 1, $page ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Solo en el escritorio, al atender un derecho.
					[
						'key'     => Submissions::META_DATA,
						'value'   => $email,
						'compare' => 'LIKE',
					],
				],
			]
		);

		$matching = array_values(
			array_filter(
				array_map( 'intval', $ids ),
				static fn ( int $id ): bool => 0 === strcasecmp( (string) ( Submissions::data( $id )['email'] ?? '' ), $email )
			)
		);

		return [ $matching, count( $ids ) === self::PER_PAGE ];
	}

	/**
	 * @return array{data: array<int, array<string, mixed>>, done: bool}
	 */
	public function export( string $email, int $page = 1 ): array {
		[ $ids, $more ] = $this->by_email( $email, $page );
		$items          = [];
		$labels         = Submissions::labels();

		foreach ( $ids as $id ) {
			$fields = [
				[
					'name'  => __( 'Formulario', 'parkinson-bahia-core' ),
					'value' => Definitions::types()[ (string) get_post_meta( $id, Submissions::META_TYPE, true ) ] ?? '',
				],
				[
					'name'  => __( 'Fecha', 'parkinson-bahia-core' ),
					'value' => (string) get_the_date( 'j/m/Y H:i', $id ),
				],
			];

			foreach ( Submissions::data( $id ) as $name => $value ) {
				$fields[] = [
					'name'  => $labels[ $name ] ?? $name,
					'value' => is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value,
				];
			}

			foreach ( Submissions::secrets( $id ) as $name => $value ) {
				$fields[] = [
					'name'  => $labels[ $name ] ?? $name,
					'value' => null !== $value ? self::mask( $value ) . ' ' . __( '(se muestra en parte por seguridad; pídenos el dato completo si lo necesitas)', 'parkinson-bahia-core' ) : '',
				];
			}

			$items[] = [
				'group_id'    => 'pbc-formularios',
				'group_label' => __( 'Formularios enviados desde la web', 'parkinson-bahia-core' ),
				'item_id'     => 'pbc-envio-' . $id,
				'data'        => $fields,
			];
		}

		return [
			'data' => $items,
			'done' => ! $more,
		];
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: string[], done: bool}
	 */
	public function erase( string $email, int $page = 1 ): array {
		// Siempre la primera página: lo borrado ya no aparece en la siguiente consulta.
		[ $ids, $more ] = $this->by_email( $email, 1 );
		$removed        = false;

		foreach ( $ids as $id ) {
			$removed = (bool) wp_delete_post( $id, true ) || $removed;
		}

		return [
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => [],
			'done'           => ! $more || ! $removed,
		];
	}

	/**
	 * «12345678Z» → «******78Z»; «ES12 3456 …» → «ES************1234».
	 */
	private static function mask( string $value ): string {
		$clean = (string) preg_replace( '/\s+/', '', $value );
		$keep  = strlen( $clean ) > 12 ? 4 : 3;
		$start = str_starts_with( strtoupper( $clean ), 'ES' ) ? 2 : 0;

		return substr( $clean, 0, $start ) . str_repeat( '*', max( 0, strlen( $clean ) - $keep - $start ) ) . substr( $clean, -$keep );
	}

	/* ---------- Descargar los datos de un envío ---------- */

	public static function download_url( int $post_id ): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => self::DOWNLOAD,
					'post'   => $post_id,
				],
				admin_url( 'admin-post.php' )
			),
			self::DOWNLOAD . '_' . $post_id
		);
	}

	/**
	 * @param array<string, string> $actions Acciones de la fila.
	 * @return array<string, string>
	 */
	public function row_action( array $actions, WP_Post $post ): array {
		if ( Submissions::POST_TYPE === $post->post_type && current_user_can( 'manage_options' ) ) {
			$actions['pbc-download'] = '<a href="' . esc_url( self::download_url( (int) $post->ID ) ) . '">' . esc_html__( 'Descargar sus datos', 'parkinson-bahia-core' ) . '</a>';
		}

		return $actions;
	}

	public function download(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Se comprueba justo después con check_admin_referer().
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'parkinson-bahia-core' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( self::DOWNLOAD . '_' . $post_id );

		if ( Submissions::POST_TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Ese envío no existe.', 'parkinson-bahia-core' ), '', [ 'response' => 404 ] );
		}

		$labels = Submissions::labels();
		$data   = [];
		foreach ( Submissions::data( $post_id ) + Submissions::secrets( $post_id ) as $name => $value ) {
			$data[ $labels[ $name ] ?? $name ] = $value;
		}

		$export = [
			'responsable'   => Definitions::controller()['name'],
			'formulario'    => Definitions::types()[ (string) get_post_meta( $post_id, Submissions::META_TYPE, true ) ] ?? '',
			'fecha'         => get_post_time( 'c', true, $post_id ),
			'datos'         => $data,
			'consentimiento' => get_post_meta( $post_id, Submissions::META_CONSENT, true ) ?: null,
		];

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="datos-formulario-' . $post_id . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/* ---------- Plugin «Protección de datos (RGPD)» ---------- */

	/**
	 * @param array<int, array<string, string>> $treatments Tratamientos declarados.
	 * @return array<int, array<string, string>>
	 */
	public function treatments( array $treatments ): array {
		$common = [
			'transfers' => __( 'No hay. Si los formularios usan Cloudflare Turnstile, Cloudflare puede tratar datos técnicos (IP) en EE. UU. bajo el Marco de Privacidad de Datos UE-EE. UU.', 'parkinson-bahia-core' ),
			'security'  => __( 'Solo los administradores ven los envíos; la web va por https; el aviso por correo lleva solo nombre y teléfono; la IP no se guarda; copias de seguridad del alojamiento.', 'parkinson-bahia-core' ),
		];

		$treatments[] = $common + [
			'id'         => 'pbc-socios',
			'name'       => __( 'Socios (formulario «Hazte socio»)', 'parkinson-bahia-core' ),
			'purpose'    => __( 'Dar de alta a la persona como socia y cobrar su cuota por domiciliación.', 'parkinson-bahia-core' ),
			'basis'      => __( 'Relación de la persona con la asociación como socia, que ella solicita (art. 6.1.b RGPD); obligaciones contables y fiscales (art. 6.1.c).', 'parkinson-bahia-core' ),
			'people'     => __( 'Personas que piden hacerse socias.', 'parkinson-bahia-core' ),
			'data'       => __( 'Nombre y apellidos, DNI o NIE, teléfono, correo (opcional), número de cuenta (IBAN) y cuota elegida.', 'parkinson-bahia-core' ),
			'recipients' => __( 'El banco de la asociación, para cobrar la cuota. Administraciones públicas cuando lo exige la ley. Empresa de alojamiento web, como encargada del tratamiento.', 'parkinson-bahia-core' ),
			'retention'  => __( 'El envío de la web se borra cuando se ha dado de alta (estado «Cerrado») y pasa su plazo. Los datos de socio se guardan mientras lo sea y después el tiempo que exige la ley.', 'parkinson-bahia-core' ),
			'security'   => $common['security'] . ' ' . __( 'DNI e IBAN cifrados (libsodium) con una clave fuera de la base de datos; no van en los correos.', 'parkinson-bahia-core' ),
		];

		$treatments[] = $common + [
			'id'         => 'pbc-voluntariado',
			'name'       => __( 'Voluntariado (formulario «Hazte voluntario»)', 'parkinson-bahia-core' ),
			'purpose'    => __( 'Contactar con la persona y organizar su voluntariado.', 'parkinson-bahia-core' ),
			'basis'      => __( 'Consentimiento de la persona (art. 6.1.a RGPD).', 'parkinson-bahia-core' ),
			'people'     => __( 'Personas que se ofrecen como voluntarias.', 'parkinson-bahia-core' ),
			'data'       => __( 'Nombre, teléfono, correo (opcional) y áreas en las que quiere ayudar.', 'parkinson-bahia-core' ),
			'recipients' => __( 'Nadie, salvo obligación legal. Empresa de alojamiento web, como encargada del tratamiento.', 'parkinson-bahia-core' ),
			'retention'  => __( 'Hasta que se atiende la solicitud (estado «Cerrado») y pasa su plazo.', 'parkinson-bahia-core' ),
		];

		$treatments[] = $common + [
			'id'         => 'pbc-contacto',
			'name'       => __( 'Consultas (formulario de contacto)', 'parkinson-bahia-core' ),
			'purpose'    => __( 'Llamar a la persona y responder a su consulta, por ejemplo sobre una plaza en el centro de día.', 'parkinson-bahia-core' ),
			'basis'      => __( 'Consentimiento de la persona (art. 6.1.a RGPD).', 'parkinson-bahia-core' ),
			'people'     => __( 'Personas que escriben a la asociación (personas con párkinson, familiares, otras).', 'parkinson-bahia-core' ),
			'data'       => __( 'Nombre, teléfono, correo (opcional), tema y mensaje (opcional). Se pide no escribir datos de salud.', 'parkinson-bahia-core' ),
			'recipients' => __( 'Nadie, salvo obligación legal. Empresa de alojamiento web, como encargada del tratamiento.', 'parkinson-bahia-core' ),
			'retention'  => __( 'Hasta que se atiende la consulta (estado «Cerrado») y pasa su plazo.', 'parkinson-bahia-core' ),
		];

		return $treatments;
	}

	/**
	 * @param array<int, array<string, mixed>> $items Plazos declarados.
	 * @return array<int, array<string, mixed>>
	 */
	public function retention( array $items ): array {
		$labels = [
			Definitions::CONTACT   => __( 'Consultas del formulario de contacto', 'parkinson-bahia-core' ),
			Definitions::VOLUNTEER => __( 'Solicitudes de voluntariado', 'parkinson-bahia-core' ),
			Definitions::MEMBER    => __( 'Solicitudes para hacerse socio', 'parkinson-bahia-core' ),
		];

		foreach ( $labels as $type => $label ) {
			$items[] = [
				'id'          => 'pbc-envios-' . $type,
				'label'       => $label,
				'description' => __( 'una vez atendidas (marcadas «Cerrado» en Formularios),', 'parkinson-bahia-core' ),
				'default'     => self::DEFAULT_MONTHS[ $type ],
				'purge'       => static fn ( int $cutoff ): int => self::purge( $type, $cutoff ),
			];
		}

		return $items;
	}

	/**
	 * Borra para siempre los envíos cerrados de un formulario recibidos antes de la fecha límite.
	 */
	private static function purge( string $type, int $cutoff ): int {
		$ids = get_posts(
			[
				'post_type'      => Submissions::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
				'date_query'     => [
					[
						'column' => 'post_date_gmt',
						'before' => gmdate( 'Y-m-d H:i:s', $cutoff ),
					],
				],
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Tarea diaria, no en la web pública.
					[
						'key'   => Submissions::META_TYPE,
						'value' => $type,
					],
					[
						'key'   => Submissions::META_STATUS,
						'value' => 'cerrado',
					],
				],
			]
		);

		$deleted = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}
}
