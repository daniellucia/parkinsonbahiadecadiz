<?php
/**
 * Seguimiento de los formularios recibidos, como un pequeño CRM.
 *
 * En «Formularios» del escritorio: columnas con los datos de contacto,
 * filtros por formulario y por estado, búsqueda por nombre, teléfono, correo
 * o notas, un estado (nuevo, en seguimiento, cerrado) con notas internas y
 * el número de envíos nuevos junto al menú. Los datos cifrados (DNI, IBAN)
 * no se buscan ni se listan: solo se ven al abrir el envío.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Crm {

	public const DEFAULT_STATUS = 'nuevo';

	private const NONCE      = 'pbc_crm_nonce';
	private const NOTES_MAX  = 5000;
	private const ARG_FORM   = 'pbc_form';
	private const ARG_STATUS = 'pbc_status';

	/**
	 * Estados del seguimiento.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return [
			'nuevo'       => __( 'Nuevo', 'parkinson-bahia-core' ),
			'seguimiento' => __( 'En seguimiento', 'parkinson-bahia-core' ),
			'cerrado'     => __( 'Cerrado', 'parkinson-bahia-core' ),
		];
	}

	public static function status( int $post_id ): string {
		$status = (string) get_post_meta( $post_id, Submissions::META_STATUS, true );

		// Los envíos anteriores al seguimiento no tienen estado: cuentan como nuevos.
		return isset( self::statuses()[ $status ] ) ? $status : self::DEFAULT_STATUS;
	}

	public function register(): void {
		$type = Submissions::POST_TYPE;

		add_filter( "manage_{$type}_posts_columns", [ $this, 'columns' ] );
		add_action( "manage_{$type}_posts_custom_column", [ $this, 'column' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'filters' ] );
		add_action( 'pre_get_posts', [ $this, 'apply_filters' ] );
		add_filter( 'posts_search', [ $this, 'search' ], 10, 2 );
		add_filter( "views_edit-{$type}", [ $this, 'views' ] );
		add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );
		add_filter( "bulk_actions-edit-{$type}", [ $this, 'bulk_actions' ] );
		add_filter( "handle_bulk_actions-edit-{$type}", [ $this, 'handle_bulk' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'bulk_notice' ] );
		add_action( "add_meta_boxes_{$type}", [ $this, 'add_meta_box' ] );
		add_action( "save_post_{$type}", [ $this, 'save' ], 10, 2 );
		add_action( 'admin_menu', [ $this, 'menu_bubble' ], 99 );
	}

	/**
	 * @param array<string, string> $columns Columnas.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		return [
			'cb'         => $columns['cb'] ?? '',
			'title'      => __( 'Envío', 'parkinson-bahia-core' ),
			'pbc_form'   => __( 'Formulario', 'parkinson-bahia-core' ),
			'pbc_tel'    => __( 'Teléfono', 'parkinson-bahia-core' ),
			'pbc_email'  => __( 'Correo', 'parkinson-bahia-core' ),
			'pbc_detail' => __( 'Qué pide', 'parkinson-bahia-core' ),
			'pbc_status' => __( 'Estado', 'parkinson-bahia-core' ),
			'date'       => __( 'Recibido', 'parkinson-bahia-core' ),
		];
	}

	public function column( string $column, int $post_id ): void {
		$data = Submissions::data( $post_id );

		switch ( $column ) {
			case 'pbc_form':
				$type = (string) get_post_meta( $post_id, Submissions::META_TYPE, true );
				echo esc_html( Definitions::types()[ $type ] ?? $type );
				break;

			case 'pbc_tel':
				$tel = (string) ( $data['tel'] ?? '' );
				if ( '' !== $tel ) {
					printf( '<a href="%s">%s</a>', esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $tel ) ), esc_html( $tel ) );
				}
				break;

			case 'pbc_email':
				$email = sanitize_email( (string) ( $data['email'] ?? '' ) );
				if ( '' !== $email ) {
					printf( '<a href="%s">%s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}
				break;

			case 'pbc_detail':
				echo esc_html( $this->detail( $data ) );
				break;

			case 'pbc_status':
				$notes = (string) get_post_meta( $post_id, Submissions::META_NOTES, true );
				echo esc_html( self::statuses()[ self::status( $post_id ) ] );
				if ( '' !== $notes ) {
					echo '<br><span class="description">' . esc_html( wp_trim_words( $notes, 12 ) ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Resumen de lo que pide la persona, según el formulario.
	 *
	 * @param array<string, mixed> $data Datos no sensibles.
	 */
	private function detail( array $data ): string {
		if ( isset( $data['cuota'] ) && '' !== (string) $data['cuota'] ) {
			/* translators: %s: cantidad en euros. */
			return sprintf( __( 'Cuota de %s € al mes', 'parkinson-bahia-core' ), (string) $data['cuota'] );
		}

		if ( ! empty( $data['areas'] ) ) {
			return implode( ', ', array_map( 'strval', (array) $data['areas'] ) );
		}

		$parts = array_filter( [ (string) ( $data['motivo'] ?? '' ), wp_trim_words( (string) ( $data['mensaje'] ?? '' ), 10 ) ] );

		return implode( ': ', $parts );
	}

	public function filters( string $post_type ): void {
		if ( Submissions::POST_TYPE !== $post_type ) {
			return;
		}

		$this->select( self::ARG_FORM, __( 'Filtrar por formulario', 'parkinson-bahia-core' ), __( 'Todos los formularios', 'parkinson-bahia-core' ), Definitions::types() );
		$this->select( self::ARG_STATUS, __( 'Filtrar por estado', 'parkinson-bahia-core' ), __( 'Todos los estados', 'parkinson-bahia-core' ), self::statuses() );
	}

	/**
	 * @param array<string, string> $options Opciones.
	 */
	private function select( string $name, string $label, string $all, array $options ): void {
		$current = $this->arg( $name, $options );
		?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
			<option value=""><?php echo esc_html( $all ); ?></option>
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Valor de un filtro de la lista, solo si es una de las opciones válidas.
	 *
	 * @param array<string, string> $options Opciones válidas.
	 */
	private function arg( string $name, array $options ): string {
		$value = isset( $_GET[ $name ] ) ? sanitize_key( wp_unslash( $_GET[ $name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtro de lectura en la lista del escritorio.

		return isset( $options[ $value ] ) ? $value : '';
	}

	private function is_list( WP_Query $query ): bool {
		return is_admin() && $query->is_main_query() && Submissions::POST_TYPE === $query->get( 'post_type' ) && current_user_can( 'manage_options' );
	}

	public function apply_filters( WP_Query $query ): void {
		if ( ! $this->is_list( $query ) ) {
			return;
		}

		$meta = [];
		$form = $this->arg( self::ARG_FORM, Definitions::types() );
		if ( '' !== $form ) {
			$meta[] = [
				'key'   => Submissions::META_TYPE,
				'value' => $form,
			];
		}

		$status = $this->arg( self::ARG_STATUS, self::statuses() );
		if ( self::DEFAULT_STATUS === $status ) {
			$meta[] = [
				'relation' => 'OR',
				[
					'key'   => Submissions::META_STATUS,
					'value' => $status,
				],
				[
					'key'     => Submissions::META_STATUS,
					'compare' => 'NOT EXISTS',
				],
			];
		} elseif ( '' !== $status ) {
			$meta[] = [
				'key'   => Submissions::META_STATUS,
				'value' => $status,
			];
		}

		if ( $meta ) {
			$query->set( 'meta_query', $meta );
		}
	}

	/**
	 * La búsqueda de la lista mira también el teléfono, el correo, el mensaje y las notas.
	 */
	public function search( string $search, WP_Query $query ): string {
		$term = (string) $query->get( 's' );

		if ( '' === $search || '' === $term || ! $this->is_list( $query ) ) {
			return $search;
		}

		global $wpdb;
		$like = '%' . $wpdb->esc_like( $term ) . '%';

		$meta = $wpdb->prepare(
			"{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( %s, %s ) AND meta_value LIKE %s )",
			Submissions::META_DATA,
			Submissions::META_NOTES,
			$like
		);

		// WordPress devuelve « AND ((...))»: se añade la condición como alternativa.
		return preg_replace( '/^\s*AND\s*\(/', ' AND ( ' . $meta . ' OR ', $search, 1 ) ?? $search;
	}

	/**
	 * Accesos rápidos por estado, con su número, encima de la lista.
	 *
	 * @param array<string, string> $views Vistas.
	 * @return array<string, string>
	 */
	public function views( array $views ): array {
		$current = $this->arg( self::ARG_STATUS, self::statuses() );
		$base    = admin_url( 'edit.php?post_type=' . Submissions::POST_TYPE );

		foreach ( self::statuses() as $status => $label ) {
			$count = $this->count( $status );
			$views[ 'pbc-' . $status ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( self::ARG_STATUS, $status, $base ) ),
				$current === $status ? ' class="current" aria-current="page"' : '',
				esc_html( $label ),
				$count
			);
		}

		return $views;
	}

	private function count( string $status ): int {
		$meta = self::DEFAULT_STATUS === $status
			? [
				'relation' => 'OR',
				[
					'key'   => Submissions::META_STATUS,
					'value' => $status,
				],
				[
					'key'     => Submissions::META_STATUS,
					'compare' => 'NOT EXISTS',
				],
			]
			: [
				[
					'key'   => Submissions::META_STATUS,
					'value' => $status,
				],
			];

		$query = new WP_Query(
			[
				'post_type'              => Submissions::POST_TYPE,
				'post_status'            => 'private',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Solo en el escritorio, sobre una tabla pequeña.
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		return (int) $query->found_posts;
	}

	/**
	 * Sin «Edición rápida»: podría cambiar la visibilidad privada del envío.
	 *
	 * @param array<string, string> $actions Acciones.
	 * @return array<string, string>
	 */
	public function row_actions( array $actions, WP_Post $post ): array {
		if ( Submissions::POST_TYPE === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * @param array<string, string> $actions Acciones en lote.
	 * @return array<string, string>
	 */
	public function bulk_actions( array $actions ): array {
		unset( $actions['edit'] );

		foreach ( self::statuses() as $status => $label ) {
			/* translators: %s: estado. */
			$actions[ 'pbc_status_' . $status ] = sprintf( __( 'Marcar como «%s»', 'parkinson-bahia-core' ), $label );
		}

		return $actions;
	}

	/**
	 * WordPress ya ha comprobado el nonce de la lista antes de llamar aquí.
	 *
	 * @param int[] $post_ids IDs elegidos.
	 */
	public function handle_bulk( string $redirect, string $action, array $post_ids ): string {
		$status = str_starts_with( $action, 'pbc_status_' ) ? substr( $action, strlen( 'pbc_status_' ) ) : '';

		if ( ! isset( self::statuses()[ $status ] ) || ! current_user_can( 'manage_options' ) ) {
			return $redirect;
		}

		$done = 0;
		foreach ( $post_ids as $post_id ) {
			if ( Submissions::POST_TYPE === get_post_type( (int) $post_id ) ) {
				update_post_meta( (int) $post_id, Submissions::META_STATUS, $status );
				++$done;
			}
		}

		return add_query_arg( 'pbc_marked', $done, $redirect );
	}

	public function bulk_notice(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-' . Submissions::POST_TYPE !== $screen->id || ! isset( $_GET['pbc_marked'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo muestra un aviso.
			return;
		}

		$done = absint( $_GET['pbc_marked'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo muestra un aviso.
		/* translators: %d: número de envíos. */
		$text = sprintf( _n( 'Se ha cambiado el estado de %d envío.', 'Se ha cambiado el estado de %d envíos.', $done, 'parkinson-bahia-core' ), $done );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	public function add_meta_box(): void {
		add_meta_box( 'pbc-envio-seguimiento', __( 'Seguimiento', 'parkinson-bahia-core' ), [ $this, 'render_meta_box' ], Submissions::POST_TYPE, 'side', 'high' );
	}

	public function render_meta_box( WP_Post $post ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current = self::status( $post->ID );
		wp_nonce_field( 'pbc_crm_save_' . $post->ID, self::NONCE );
		?>
		<p>
			<label for="pbc-crm-status"><strong><?php esc_html_e( 'Estado', 'parkinson-bahia-core' ); ?></strong></label><br>
			<select name="pbc_crm_status" id="pbc-crm-status" class="widefat">
				<?php foreach ( self::statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="pbc-crm-notes"><strong><?php esc_html_e( 'Notas internas', 'parkinson-bahia-core' ); ?></strong></label>
			<textarea name="pbc_crm_notes" id="pbc-crm-notes" class="widefat" rows="6" maxlength="<?php echo esc_attr( (string) self::NOTES_MAX ); ?>" aria-describedby="pbc-crm-notes-help"><?php echo esc_textarea( (string) get_post_meta( $post->ID, Submissions::META_NOTES, true ) ); ?></textarea>
			<span class="description" id="pbc-crm-notes-help"><?php esc_html_e( 'Por ejemplo: cuándo se le llamó y qué se habló. Solo lo ven los administradores. No escribas datos de salud.', 'parkinson-bahia-core' ); ?></span>
		</p>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'pbc_crm_save_' . $post_id ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_POST['pbc_crm_status'] ) ? sanitize_key( wp_unslash( $_POST['pbc_crm_status'] ) ) : '';
		if ( isset( self::statuses()[ $status ] ) ) {
			update_post_meta( $post_id, Submissions::META_STATUS, $status );
		}

		$notes = isset( $_POST['pbc_crm_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pbc_crm_notes'] ) ) : '';
		$notes = mb_substr( $notes, 0, self::NOTES_MAX );

		if ( '' === $notes ) {
			delete_post_meta( $post_id, Submissions::META_NOTES );
		} else {
			update_post_meta( $post_id, Submissions::META_NOTES, $notes );
		}

		// El envío sigue siendo privado aunque se guarde desde la pantalla de edición.
		if ( 'private' !== $post->post_status && 'trash' !== $post->post_status ) {
			remove_action( 'save_post_' . Submissions::POST_TYPE, [ $this, 'save' ] );
			wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => 'private',
				]
			);
			add_action( 'save_post_' . Submissions::POST_TYPE, [ $this, 'save' ], 10, 2 );
		}
	}

	/**
	 * Número de envíos nuevos junto al menú «Formularios», como los comentarios pendientes.
	 */
	public function menu_bubble(): void {
		global $menu;

		if ( ! current_user_can( 'manage_options' ) || ! is_array( $menu ) ) {
			return;
		}

		$count = $this->count( self::DEFAULT_STATUS );

		if ( $count < 1 ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			if ( 'edit.php?post_type=' . Submissions::POST_TYPE === ( $item[2] ?? '' ) ) {
				/* translators: %d: número de envíos nuevos. */
				$label = sprintf( _n( '%d nuevo', '%d nuevos', $count, 'parkinson-bahia-core' ), $count );
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Así añade WordPress los contadores del menú.
				$menu[ $index ][0] .= sprintf( ' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>', $count, esc_html( $label ) );
				break;
			}
		}
	}
}
