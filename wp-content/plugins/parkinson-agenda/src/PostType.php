<?php
/**
 * Tipo de contenido «Evento» y su tipo de actividad (Taller, Charla…).
 *
 * Cada evento tiene su ficha en /agenda/<nombre>. Los datos van en una caja
 * «Fecha, lugar e inscripción» con campos sencillos; al guardar se calculan
 * dos campos internos para ordenar y filtrar sin consultas pesadas:
 * _pbc_sort («AAAA-MM-DD HH:MM») y _pbc_until (último día del evento).
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Agenda;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class PostType {

	public const POST_TYPE = 'pbc_evento';
	public const TAXONOMY  = 'pbc_tipo_evento';
	public const SLUG      = 'agenda';

	public const META_DATE       = '_pbc_date';
	public const META_END_DATE   = '_pbc_end_date';
	public const META_START      = '_pbc_start';
	public const META_END        = '_pbc_end';
	public const META_PLACE      = '_pbc_place';
	public const META_PRICE      = '_pbc_price';
	public const META_SIGNUP     = '_pbc_signup';
	public const META_SIGNUP_URL = '_pbc_signup_url';
	public const META_CANCELLED  = '_pbc_cancelled';
	public const META_SORT       = '_pbc_sort';
	public const META_UNTIL      = '_pbc_until';

	/** Lugar que se propone al crear un evento (se puede cambiar). */
	public const DEFAULT_PLACE = 'Centro de día de la asociación (C/ Milongas s/n, San Fernando)';

	private const NONCE = 'pag_event_nonce';

	public function register(): void {
		add_action( 'init', [ $this, 'register_types' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );
	}

	public function register_types(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'        => [
					'name'                  => __( 'Agenda', 'parkinson-agenda' ),
					'singular_name'         => __( 'Evento', 'parkinson-agenda' ),
					'menu_name'             => __( 'Agenda', 'parkinson-agenda' ),
					'all_items'             => __( 'Todos los eventos', 'parkinson-agenda' ),
					'add_new'               => __( 'Añadir evento', 'parkinson-agenda' ),
					'add_new_item'          => __( 'Añadir evento', 'parkinson-agenda' ),
					'edit_item'             => __( 'Editar evento', 'parkinson-agenda' ),
					'new_item'              => __( 'Nuevo evento', 'parkinson-agenda' ),
					'view_item'             => __( 'Ver evento', 'parkinson-agenda' ),
					'search_items'          => __( 'Buscar eventos', 'parkinson-agenda' ),
					'not_found'             => __( 'No hay eventos todavía.', 'parkinson-agenda' ),
					'not_found_in_trash'    => __( 'La papelera está vacía.', 'parkinson-agenda' ),
					'featured_image'        => __( 'Foto del evento', 'parkinson-agenda' ),
					'set_featured_image'    => __( 'Elegir foto', 'parkinson-agenda' ),
					'remove_featured_image' => __( 'Quitar foto', 'parkinson-agenda' ),
					'item_published'        => __( 'Evento publicado.', 'parkinson-agenda' ),
					'item_updated'          => __( 'Evento guardado.', 'parkinson-agenda' ),
				],
				'description'   => __( 'Talleres, charlas, celebraciones y salidas de la asociación.', 'parkinson-agenda' ),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 6,
				'has_archive'   => false,
				'rewrite'       => [
					'slug'       => self::SLUG,
					'with_front' => false,
				],
				'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
			]
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			[
				'labels'            => [
					'name'          => __( 'Tipos de actividad', 'parkinson-agenda' ),
					'singular_name' => __( 'Tipo de actividad', 'parkinson-agenda' ),
					'menu_name'     => __( 'Tipos de actividad', 'parkinson-agenda' ),
					'all_items'     => __( 'Todos los tipos', 'parkinson-agenda' ),
					'add_new_item'  => __( 'Añadir tipo', 'parkinson-agenda' ),
					'edit_item'     => __( 'Editar tipo', 'parkinson-agenda' ),
					'search_items'  => __( 'Buscar tipos', 'parkinson-agenda' ),
					'not_found'     => __( 'No hay tipos todavía.', 'parkinson-agenda' ),
				],
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => false,
			]
		);

		foreach ( self::fields() as $key => $type ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => static fn ( $value ): string => self::sanitize( $type, $value ),
					'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
				]
			);
		}
	}

	/**
	 * Campos del evento y su tipo.
	 *
	 * @return array<string, string>
	 */
	public static function fields(): array {
		return [
			self::META_DATE       => 'date',
			self::META_END_DATE   => 'date',
			self::META_START      => 'time',
			self::META_END        => 'time',
			self::META_PLACE      => 'text',
			self::META_PRICE      => 'text',
			self::META_SIGNUP     => 'text',
			self::META_SIGNUP_URL => 'url',
			self::META_CANCELLED  => 'bool',
		];
	}

	public static function sanitize( string $type, mixed $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		return match ( $type ) {
			'date' => preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '',
			'time' => preg_match( '/^([01]\d|2[0-3]):([0-5]\d)/', $value, $m ) ? $m[1] . ':' . $m[2] : '',
			'url'  => esc_url_raw( $value, [ 'http', 'https' ] ),
			'bool' => '' !== $value && '0' !== $value ? '1' : '',
			default => sanitize_text_field( $value ),
		};
	}

	public function title_placeholder( string $text, WP_Post $post ): string {
		return self::POST_TYPE === $post->post_type ? __( 'Nombre de la actividad (por ejemplo, Taller de memoria)', 'parkinson-agenda' ) : $text;
	}

	public function add_meta_box(): void {
		add_meta_box(
			'pag_event_details',
			__( 'Fecha, lugar e inscripción', 'parkinson-agenda' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'pag_event_save', self::NONCE );

		$value = static fn ( string $key ): string => (string) get_post_meta( $post->ID, $key, true );
		$place = $value( self::META_PLACE );
		if ( '' === $place && 'auto-draft' === $post->post_status ) {
			$place = self::DEFAULT_PLACE;
		}
		?>
		<style>
			.pag-fields { display: grid; gap: 4px 24px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin: 0 0 12px; padding: 0; border: 0; }
			.pag-fields legend { font-size: 14px; font-weight: 600; padding: 12px 0 4px; }
			.pag-fields p { margin: 4px 0 8px; }
			.pag-fields input[type="text"], .pag-fields input[type="url"] { width: 100%; }
			.pag-fields--wide { grid-template-columns: 1fr; }
		</style>

		<fieldset class="pag-fields">
			<legend><?php esc_html_e( 'Cuándo', 'parkinson-agenda' ); ?></legend>
			<p>
				<label for="pag-date"><strong><?php esc_html_e( 'Fecha', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( '(obligatoria)', 'parkinson-agenda' ); ?></label><br>
				<input type="date" id="pag-date" name="<?php echo esc_attr( self::META_DATE ); ?>" value="<?php echo esc_attr( $value( self::META_DATE ) ); ?>" aria-describedby="pag-date-help">
				<br><span class="description" id="pag-date-help"><?php esc_html_e( 'Sin fecha, el evento no sale en la agenda.', 'parkinson-agenda' ); ?></span>
			</p>
			<p>
				<label for="pag-start"><strong><?php esc_html_e( 'Hora de inicio', 'parkinson-agenda' ); ?></strong></label><br>
				<input type="time" id="pag-start" name="<?php echo esc_attr( self::META_START ); ?>" value="<?php echo esc_attr( $value( self::META_START ) ); ?>" step="300">
			</p>
			<p>
				<label for="pag-end"><strong><?php esc_html_e( 'Hora de fin', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( '(opcional)', 'parkinson-agenda' ); ?></label><br>
				<input type="time" id="pag-end" name="<?php echo esc_attr( self::META_END ); ?>" value="<?php echo esc_attr( $value( self::META_END ) ); ?>" step="300">
			</p>
			<p>
				<label for="pag-end-date"><strong><?php esc_html_e( 'Si dura varios días, último día', 'parkinson-agenda' ); ?></strong></label><br>
				<input type="date" id="pag-end-date" name="<?php echo esc_attr( self::META_END_DATE ); ?>" value="<?php echo esc_attr( $value( self::META_END_DATE ) ); ?>">
			</p>
		</fieldset>

		<fieldset class="pag-fields pag-fields--wide">
			<legend><?php esc_html_e( 'Dónde y cuánto cuesta', 'parkinson-agenda' ); ?></legend>
			<p>
				<label for="pag-place"><strong><?php esc_html_e( 'Lugar', 'parkinson-agenda' ); ?></strong></label><br>
				<input type="text" id="pag-place" name="<?php echo esc_attr( self::META_PLACE ); ?>" value="<?php echo esc_attr( $place ); ?>">
			</p>
			<p>
				<label for="pag-price"><strong><?php esc_html_e( 'Precio', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( '(opcional)', 'parkinson-agenda' ); ?></label><br>
				<input type="text" id="pag-price" name="<?php echo esc_attr( self::META_PRICE ); ?>" value="<?php echo esc_attr( $value( self::META_PRICE ) ); ?>" placeholder="<?php esc_attr_e( 'Gratuito', 'parkinson-agenda' ); ?>">
			</p>
		</fieldset>

		<fieldset class="pag-fields pag-fields--wide">
			<legend><?php esc_html_e( 'Cómo apuntarse', 'parkinson-agenda' ); ?></legend>
			<p>
				<label for="pag-signup"><strong><?php esc_html_e( 'Instrucciones', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( '(opcional)', 'parkinson-agenda' ); ?></label><br>
				<input type="text" id="pag-signup" name="<?php echo esc_attr( self::META_SIGNUP ); ?>" value="<?php echo esc_attr( $value( self::META_SIGNUP ) ); ?>" placeholder="<?php esc_attr_e( 'Plazas limitadas: apúntate antes del 20 de octubre', 'parkinson-agenda' ); ?>" aria-describedby="pag-signup-help">
				<br><span class="description" id="pag-signup-help"><?php esc_html_e( 'El botón para llamar a la asociación sale siempre en la ficha del evento.', 'parkinson-agenda' ); ?></span>
			</p>
			<p>
				<label for="pag-signup-url"><strong><?php esc_html_e( 'Enlace para apuntarse', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( '(opcional)', 'parkinson-agenda' ); ?></label><br>
				<input type="url" id="pag-signup-url" name="<?php echo esc_attr( self::META_SIGNUP_URL ); ?>" value="<?php echo esc_attr( $value( self::META_SIGNUP_URL ) ); ?>" placeholder="https://">
			</p>
			<p>
				<label><input type="checkbox" name="<?php echo esc_attr( self::META_CANCELLED ); ?>" value="1" <?php checked( '1', $value( self::META_CANCELLED ) ); ?>> <strong><?php esc_html_e( 'Evento cancelado', 'parkinson-agenda' ); ?></strong></label>
				<br><span class="description"><?php esc_html_e( 'Sigue en la agenda con el aviso «Cancelado», para que nadie vaya por error.', 'parkinson-agenda' ); ?></span>
			</p>
		</fieldset>
		<p class="description"><?php esc_html_e( 'El texto de arriba (editor) es la descripción completa. El extracto, si lo escribes, es el resumen que sale en la agenda.', 'parkinson-agenda' ); ?></p>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';

		if (
			! wp_verify_nonce( $nonce, 'pag_event_save' )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		foreach ( self::fields() as $key => $type ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Se sanea en sanitize() según el tipo de campo.
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			self::store( $post_id, $key, self::sanitize( $type, $raw ) );
		}

		self::refresh_index( $post_id );
	}

	/**
	 * Guarda o borra un campo (los vacíos no se guardan).
	 */
	public static function store( int $post_id, string $key, string $value ): void {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Recalcula los campos internos de orden y fin. Un último día anterior al
	 * primero se descarta.
	 */
	public static function refresh_index( int $post_id ): void {
		$date     = (string) get_post_meta( $post_id, self::META_DATE, true );
		$end_date = (string) get_post_meta( $post_id, self::META_END_DATE, true );
		$start    = (string) get_post_meta( $post_id, self::META_START, true );

		if ( '' !== $end_date && ( '' === $date || $end_date <= $date ) ) {
			delete_post_meta( $post_id, self::META_END_DATE );
			$end_date = '';
		}

		self::store( $post_id, self::META_SORT, '' !== $date ? $date . ' ' . ( '' !== $start ? $start : '00:00' ) : '' );
		self::store( $post_id, self::META_UNTIL, '' !== $end_date ? $end_date : $date );
	}
}
