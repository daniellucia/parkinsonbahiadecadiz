<?php
/**
 * Envíos de formularios: contenido privado que solo ven los administradores.
 *
 * No se pueden crear a mano ni tienen dirección pública. Los datos sensibles
 * se guardan cifrados (ver Crypto) y solo se descifran al abrir un envío.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class Submissions {

	public const POST_TYPE    = 'pbc_envio';
	public const META_TYPE    = '_pbc_form';
	public const META_DATA    = '_pbc_data';
	public const META_STATUS  = '_pbc_status';
	public const META_NOTES   = '_pbc_notes';
	public const META_CONSENT = '_pbc_consent';
	public const META_SECRET  = '_pbc_secret_';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
	}

	public function register_post_type(): void {
		// Todas las capacidades piden ser administrador; crear a mano no está permitido.
		$capabilities = array_fill_keys(
			[ 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ],
			'manage_options'
		);
		$capabilities['create_posts'] = 'do_not_allow';

		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'               => __( 'Formularios recibidos', 'parkinson-bahia-core' ),
					'singular_name'      => __( 'Formulario recibido', 'parkinson-bahia-core' ),
					'menu_name'          => __( 'Formularios', 'parkinson-bahia-core' ),
					'all_items'          => __( 'Formularios recibidos', 'parkinson-bahia-core' ),
					'edit_item'          => __( 'Formulario recibido', 'parkinson-bahia-core' ),
					'search_items'       => __( 'Buscar formularios', 'parkinson-bahia-core' ),
					'not_found'          => __( 'Todavía no se ha recibido ningún formulario.', 'parkinson-bahia-core' ),
					'not_found_in_trash' => __( 'La papelera está vacía.', 'parkinson-bahia-core' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'can_export'          => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-email-alt',
				'supports'            => [ 'title' ],
				'capabilities'        => $capabilities,
				'map_meta_cap'        => false,
			]
		);
	}

	/**
	 * Guarda un envío. Devuelve su ID, o 0 si falla.
	 *
	 * @param array<string, mixed>  $data    Datos no sensibles.
	 * @param array<string, string> $secrets Datos sensibles en claro; se cifran aquí.
	 */
	public static function store( string $type, array $data, array $secrets ): int {
		$encrypted = [];

		foreach ( $secrets as $name => $value ) {
			$cipher = Crypto::encrypt( $value );
			if ( null === $cipher ) {
				// Sin cifrado disponible no se guarda nada: mejor fallar que dejar el dato en claro.
				return 0;
			}
			$encrypted[ $name ] = $cipher;
		}

		$label   = Definitions::types()[ $type ] ?? $type;
		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s: %s', $label, (string) ( $data['nombre'] ?? '' ) ),
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		update_post_meta( $post_id, self::META_TYPE, $type );
		update_post_meta( $post_id, self::META_DATA, $data );
		update_post_meta( $post_id, self::META_STATUS, Crm::DEFAULT_STATUS );
		update_post_meta( $post_id, self::META_CONSENT, self::consent_proof( $type ) );

		foreach ( $encrypted as $name => $cipher ) {
			update_post_meta( $post_id, self::META_SECRET . $name, $cipher );
		}

		return (int) $post_id;
	}

	/**
	 * Prueba del consentimiento (art. 7.1 RGPD): cuándo se aceptó, qué versión
	 * de la política de privacidad estaba publicada y el aviso que se mostró.
	 *
	 * @return array{time: int, policy_url: string, policy_version: string, notice: string}
	 */
	private static function consent_proof( string $type ): array {
		$policy = (int) get_option( 'wp_page_for_privacy_policy' );

		return [
			'time'           => time(),
			'policy_url'     => $policy > 0 ? (string) get_permalink( $policy ) : '',
			'policy_version' => $policy > 0 ? (string) get_post_field( 'post_modified_gmt', $policy ) : '',
			'notice'         => Definitions::legal_notice( $type ),
		];
	}

	/**
	 * Valores sensibles descifrados de un envío (vacío si falta la clave).
	 *
	 * @return array<string, string|null> Campo => valor, o null si no se puede descifrar.
	 */
	public static function secrets( int $post_id ): array {
		$secrets = [];

		foreach ( array_keys( self::labels() ) as $name ) {
			$stored = (string) get_post_meta( $post_id, self::META_SECRET . $name, true );
			if ( '' !== $stored ) {
				$secrets[ $name ] = Crypto::decrypt( $stored );
			}
		}

		return $secrets;
	}

	/**
	 * Nombre legible de cada campo guardado.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return [
			'cuota'  => __( 'Aportación (euros al mes)', 'parkinson-bahia-core' ),
			'nombre' => __( 'Nombre', 'parkinson-bahia-core' ),
			'dni'    => __( 'DNI o NIE', 'parkinson-bahia-core' ),
			'tel'    => __( 'Teléfono', 'parkinson-bahia-core' ),
			'email'  => __( 'Correo electrónico', 'parkinson-bahia-core' ),
			'iban'   => __( 'Número de cuenta (IBAN)', 'parkinson-bahia-core' ),
			'areas'  => __( 'En qué quiere ayudar', 'parkinson-bahia-core' ),
			'motivo' => __( 'Sobre qué quiere hablar', 'parkinson-bahia-core' ),
			'mensaje' => __( 'Mensaje', 'parkinson-bahia-core' ),
		];
	}

	/**
	 * Datos no sensibles de un envío.
	 *
	 * @return array<string, mixed>
	 */
	public static function data( int $post_id ): array {
		$data = get_post_meta( $post_id, self::META_DATA, true );

		return is_array( $data ) ? $data : [];
	}

	public function add_meta_box(): void {
		add_meta_box( 'pbc-envio-datos', __( 'Datos recibidos', 'parkinson-bahia-core' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
	}

	public function render_meta_box( WP_Post $post ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$type = (string) get_post_meta( $post->ID, self::META_TYPE, true );
		$data = self::data( $post->ID );

		// Se muestran todos los campos del formulario, también los sensibles aunque ahora no haya clave.
		$labels = self::labels();
		?>
		<p><strong><?php esc_html_e( 'Formulario:', 'parkinson-bahia-core' ); ?></strong> <?php echo esc_html( Definitions::types()[ $type ] ?? $type ); ?></p>
		<table class="widefat striped">
			<tbody>
				<?php foreach ( $labels as $name => $label ) : ?>
					<?php
					$secret = (string) get_post_meta( $post->ID, self::META_SECRET . $name, true );

					if ( '' !== $secret ) {
						$plain = Crypto::decrypt( $secret );
						$value = null !== $plain ? $plain : __( 'No se puede mostrar: falta la clave de cifrado (PBC_FORMS_KEY) con la que se guardó.', 'parkinson-bahia-core' );
					} elseif ( array_key_exists( $name, $data ) ) {
						$value = is_array( $data[ $name ] ) ? implode( ', ', array_map( 'strval', $data[ $name ] ) ) : (string) $data[ $name ];
					} else {
						continue;
					}
					?>
					<tr>
						<th scope="row" style="width:16em"><?php echo esc_html( $label ); ?></th>
						<td><?php echo '' !== $value ? nl2br( esc_html( $value ) ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado con esc_html() antes de nl2br(). ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $consent = get_post_meta( $post->ID, self::META_CONSENT, true ); ?>
		<?php if ( is_array( $consent ) && ! empty( $consent['time'] ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Consentimiento:', 'parkinson-bahia-core' ); ?></strong>
				<?php
				printf(
					/* translators: 1: fecha y hora, 2: fecha de la versión de la política. */
					esc_html__( 'aceptó la política de privacidad el %1$s (versión de la política del %2$s).', 'parkinson-bahia-core' ),
					esc_html( wp_date( 'j/m/Y H:i', (int) $consent['time'] ) ),
					esc_html( '' !== (string) ( $consent['policy_version'] ?? '' ) ? wp_date( 'j/m/Y', (int) strtotime( $consent['policy_version'] . ' UTC' ) ) : '—' )
				);
				?>
			</p>
			<?php if ( ! empty( $consent['notice'] ) ) : ?>
				<details>
					<summary><?php esc_html_e( 'Aviso que vio al aceptar', 'parkinson-bahia-core' ); ?></summary>
					<p><?php echo esc_html( (string) $consent['notice'] ); ?></p>
				</details>
			<?php endif; ?>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'La persona aceptó la política de privacidad al enviar el formulario (envío anterior a que se guardara la fecha exacta).', 'parkinson-bahia-core' ); ?></p>
		<?php endif; ?>
		<p>
			<a class="button" href="<?php echo esc_url( Privacy::download_url( $post->ID ) ); ?>"><?php esc_html_e( 'Descargar sus datos', 'parkinson-bahia-core' ); ?></a>
			<span class="description"><?php esc_html_e( 'Para atender una petición de acceso o portabilidad. Incluye el DNI y el número de cuenta: guárdalo con cuidado y bórralo cuando lo hayas entregado.', 'parkinson-bahia-core' ); ?></span>
		</p>
		<p class="description"><?php esc_html_e( 'Borra el envío cuando ya no haga falta conservarlo (Mover a la papelera y vaciarla). Los envíos cerrados se borran solos cuando pasa su plazo de conservación (Ajustes → Protección de datos).', 'parkinson-bahia-core' ); ?></p>
		<?php
	}
}
