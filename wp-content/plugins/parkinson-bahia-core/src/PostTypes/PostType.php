<?php
/**
 * Base de los tipos de contenido propios.
 *
 * Cada tipo declara su identificador, sus argumentos y, si los necesita,
 * campos extra. La base registra el tipo, los campos (post meta), su caja
 * de edición y el guardado con nonce, permisos y saneado.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\PostTypes;

use WP_Post;

defined( 'ABSPATH' ) || exit;

abstract class PostType {

	/**
	 * Menú del escritorio que agrupa los datos de la asociación.
	 */
	public const ADMIN_MENU = 'pbc-asociacion';

	/**
	 * Identificador del tipo de contenido (máx. 20 caracteres).
	 */
	abstract public function name(): string;

	/**
	 * Argumentos de register_post_type().
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function args(): array;

	/**
	 * Campos extra: clave de meta => [ 'label', 'type' (text|number|url|file), 'description' ].
	 *
	 * @return array<string, array{label:string, type:string, description?:string}>
	 */
	protected function fields(): array {
		return [];
	}

	/**
	 * Texto de ayuda dentro del campo del título.
	 */
	protected function title_placeholder(): string {
		return '';
	}

	/**
	 * Solo engancha acciones: aquí no se puede llamar a fields() ni a
	 * title_placeholder(), porque usan traducciones y WordPress no permite
	 * cargarlas antes de «init».
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes_' . $this->name(), [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . $this->name(), [ $this, 'save' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, 'filter_title_placeholder' ], 10, 2 );
	}

	public function register_post_type(): void {
		register_post_type( $this->name(), $this->args() );

		foreach ( $this->fields() as $key => $field ) {
			register_post_meta(
				$this->name(),
				$key,
				[
					'type'              => in_array( $field['type'], [ 'number', 'file' ], true ) ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => fn ( $value ) => $this->sanitize( $field['type'], $value ),
					'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
				]
			);
		}
	}

	/**
	 * Tipo de contenido que solo alimenta listados: se edita en el escritorio
	 * pero no tiene página propia ni aparece en buscadores.
	 *
	 * @param array<string, string> $labels   Etiquetas.
	 * @param string[]              $supports Funciones del editor.
	 * @return array<string, mixed>
	 */
	protected function list_only_args( array $labels, array $supports ): array {
		return [
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => self::ADMIN_MENU,
			'show_in_rest'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => $supports,
		];
	}

	/**
	 * Etiquetas del escritorio a partir del singular y el plural.
	 *
	 * @return array<string, string>
	 */
	protected function labels( string $singular, string $plural, string $add ): array {
		return [
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'all_items'          => $plural,
			'add_new'            => $add,
			'add_new_item'       => $add,
			/* translators: %s: nombre del tipo de contenido en singular. */
			'edit_item'          => sprintf( __( 'Editar: %s', 'parkinson-bahia-core' ), $singular ),
			'new_item'           => $add,
			/* translators: %s: nombre del tipo de contenido en plural. */
			'search_items'       => sprintf( __( 'Buscar en %s', 'parkinson-bahia-core' ), $plural ),
			'not_found'          => __( 'No hay nada todavía.', 'parkinson-bahia-core' ),
			'not_found_in_trash' => __( 'La papelera está vacía.', 'parkinson-bahia-core' ),
		];
	}

	public function filter_title_placeholder( string $text, WP_Post $post ): string {
		if ( $this->name() !== $post->post_type ) {
			return $text;
		}

		$placeholder = $this->title_placeholder();

		return '' !== $placeholder ? $placeholder : $text;
	}

	public function add_meta_box(): void {
		if ( ! $this->fields() ) {
			return;
		}

		add_meta_box(
			$this->name() . '_fields',
			__( 'Datos', 'parkinson-bahia-core' ),
			[ $this, 'render_meta_box' ],
			$this->name(),
			'normal',
			'high'
		);
	}

	public function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( $this->name() . '_save', $this->name() . '_nonce' );

		foreach ( $this->fields() as $key => $field ) {
			$id    = 'pbc-field-' . ltrim( $key, '_' );
			$value = get_post_meta( $post->ID, $key, true );
			?>
			<p>
				<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong></label><br>
				<?php if ( 'file' === $field['type'] ) : ?>
					<?php $file_id = absint( $value ); ?>
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $file_id ); ?>" data-pbc-file-input>
					<span data-pbc-file-name><?php echo $file_id > 0 ? esc_html( wp_basename( (string) get_attached_file( $file_id ) ) ) : esc_html__( 'Ningún archivo elegido', 'parkinson-bahia-core' ); ?></span><br>
					<button type="button" class="button" data-pbc-file-choose><?php esc_html_e( 'Elegir archivo', 'parkinson-bahia-core' ); ?></button>
					<button type="button" class="button-link" data-pbc-file-clear><?php esc_html_e( 'Quitar', 'parkinson-bahia-core' ); ?></button>
				<?php else : ?>
					<input
						type="<?php echo esc_attr( 'number' === $field['type'] ? 'number' : ( 'url' === $field['type'] ? 'url' : 'text' ) ); ?>"
						class="<?php echo esc_attr( 'number' === $field['type'] ? 'small-text' : 'large-text' ); ?>"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( (string) $value ); ?>"
						<?php echo 'number' === $field['type'] ? 'min="0" step="1"' : ''; ?>
					>
				<?php endif; ?>
				<?php if ( ! empty( $field['description'] ) ) : ?>
					<br><span class="description"><?php echo esc_html( $field['description'] ); ?></span>
				<?php endif; ?>
			</p>
			<?php
		}
	}

	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ $this->name() . '_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->name() . '_nonce' ] ) ) : '';

		if (
			! wp_verify_nonce( $nonce, $this->name() . '_save' )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		foreach ( $this->fields() as $key => $field ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Se sanea en sanitize() según el tipo de campo.
			$raw   = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$value = $this->sanitize( $field['type'], is_scalar( $raw ) ? (string) $raw : '' );

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	/**
	 * @param mixed $value Valor recibido.
	 * @return string|int
	 */
	private function sanitize( string $type, $value ) {
		return match ( $type ) {
			'number', 'file' => absint( $value ),
			'url'            => esc_url_raw( (string) $value ),
			default          => sanitize_text_field( (string) $value ),
		};
	}
}
