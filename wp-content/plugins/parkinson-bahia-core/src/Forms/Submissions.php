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
	private const META_TYPE   = '_pbc_form';
	private const META_DATA   = '_pbc_data';
	private const META_SECRET = '_pbc_secret_';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
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

		foreach ( $encrypted as $name => $cipher ) {
			update_post_meta( $post_id, self::META_SECRET . $name, $cipher );
		}

		return (int) $post_id;
	}

	/**
	 * @param array<string, string> $columns Columnas.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		return [
			'cb'       => $columns['cb'] ?? '',
			'title'    => __( 'Formulario y nombre', 'parkinson-bahia-core' ),
			'pbc_tel'  => __( 'Teléfono', 'parkinson-bahia-core' ),
			'date'     => __( 'Recibido', 'parkinson-bahia-core' ),
		];
	}

	public function column( string $column, int $post_id ): void {
		if ( 'pbc_tel' === $column ) {
			$data = get_post_meta( $post_id, self::META_DATA, true );
			echo esc_html( is_array( $data ) ? (string) ( $data['tel'] ?? '' ) : '' );
		}
	}

	public function add_meta_box(): void {
		add_meta_box( 'pbc-envio-datos', __( 'Datos recibidos', 'parkinson-bahia-core' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
	}

	public function render_meta_box( WP_Post $post ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$type = (string) get_post_meta( $post->ID, self::META_TYPE, true );
		$data = get_post_meta( $post->ID, self::META_DATA, true );
		$data = is_array( $data ) ? $data : [];

		// Se muestran todos los campos del formulario, también los sensibles aunque ahora no haya clave.
		$labels = [
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
		<p class="description"><?php esc_html_e( 'La persona aceptó la política de privacidad al enviar el formulario. Borra el envío cuando ya no haga falta conservarlo.', 'parkinson-bahia-core' ); ?></p>
		<?php
	}
}
