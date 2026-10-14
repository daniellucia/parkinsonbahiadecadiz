<?php
/**
 * Herramientas → Redirecciones: lista, alta y edición, probar una dirección,
 * direcciones no encontradas (404), importar/exportar CSV y ajustes.
 *
 * Todo lo que cambia datos pasa por admin-post.php con nonce y
 * «manage_options».
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects\Admin;

use DanielLucia\Redirects\Importer;
use DanielLucia\Redirects\NotFoundLog;
use DanielLucia\Redirects\Path;
use DanielLucia\Redirects\Redirector;
use DanielLucia\Redirects\Repository;
use DanielLucia\Redirects\Rule;
use DanielLucia\Redirects\Settings;
use DanielLucia\Redirects\Target;

defined( 'ABSPATH' ) || exit;

final class Page {

	public const SLUG = 'prd-redirecciones';

	private const CAP        = 'manage_options';
	private const NOTICE     = 'prd_notice_';
	private const MAX_UPLOAD = 1048576;

	private string $hook = '';

	public function __construct(
		private readonly Repository $rules,
		private readonly NotFoundLog $log,
		private readonly Settings $settings,
		private readonly Importer $importer,
		private readonly Redirector $redirector
	) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );

		foreach ( [ 'save', 'delete', 'export', 'import', 'settings', '404_delete', '404_clear' ] as $action ) {
			add_action( 'admin_post_prd_' . $action, [ $this, 'handle_' . $action ] );
		}
	}

	public static function url( string $tab = '' ): string {
		$args = [ 'page' => self::SLUG ];
		if ( '' !== $tab ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'tools.php' ) );
	}

	public static function code_label( int $code ): string {
		return match ( $code ) {
			301     => __( 'Se ha movido (301)', 'parkinson-redirects' ),
			302     => __( 'Temporal (302)', 'parkinson-redirects' ),
			410     => __( 'Ya no existe (410)', 'parkinson-redirects' ),
			default => (string) $code,
		};
	}

	public static function date( ?string $gmt ): string {
		if ( null === $gmt || '' === $gmt || str_starts_with( $gmt, '0000' ) ) {
			return '—';
		}

		return (string) mysql2date( get_option( 'date_format' ) . ' H:i', get_date_from_gmt( $gmt ) );
	}

	public function add_page(): void {
		$this->hook = (string) add_management_page(
			__( 'Redirecciones', 'parkinson-redirects' ),
			__( 'Redirecciones', 'parkinson-redirects' ),
			self::CAP,
			self::SLUG,
			[ $this, 'render' ]
		);

		add_action( 'load-' . $this->hook, [ $this, 'handle_bulk' ] );
	}

	/* ------------------------------------------------------------------ */
	/* Acciones                                                            */
	/* ------------------------------------------------------------------ */

	public function handle_bulk(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Se comprueba justo abajo.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( 'prd-delete' !== $action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Se comprueba justo abajo.
			$action = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';
		}
		if ( 'prd-delete' !== $action ) {
			return;
		}

		check_admin_referer( 'bulk-redirecciones' );
		$this->authorize();

		$ids = isset( $_REQUEST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['ids'] ) ) : [];
		foreach ( array_filter( $ids ) as $id ) {
			$this->rules->delete( $id );
		}

		/* translators: %d: número de redirecciones borradas. */
		$this->back( self::url(), 'success', sprintf( _n( 'Se ha borrado %d redirección.', 'Se han borrado %d redirecciones.', count( $ids ), 'parkinson-redirects' ), count( $ids ) ) );
	}

	public function handle_save(): void {
		check_admin_referer( 'prd_save' );
		$this->authorize();

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cada campo se limpia al construir la regla.
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$prefix = ! empty( $_POST['prefix'] );
		$code   = isset( $_POST['code'] ) ? absint( $_POST['code'] ) : 301;
		$choice = isset( $_POST['target_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['target_choice'] ) ) : '';
		$custom = isset( $_POST['target_url'] ) ? trim( (string) wp_unslash( $_POST['target_url'] ) ) : '';
		$anchor = isset( $_POST['anchor'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor'] ) ) : '';
		$note   = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
		// phpcs:enable

		$target = preg_match( '/^post-(\d+)$/', $choice, $m ) ? 'post:' . $m[1] : $custom;
		if ( '' !== $target && '' !== Target::sanitize_anchor( $anchor ) ) {
			$target = strtok( $target, '#' ) . '#' . Target::sanitize_anchor( $anchor );
		}

		$form = compact( 'id', 'source', 'prefix', 'code', 'choice', 'custom', 'anchor', 'note' );
		$rule = $this->importer->build( $id, $source . ( $prefix ? '*' : '' ), $target, $code, $note );
		$back = $id > 0 ? add_query_arg( 'editar', $id, self::url() ) : self::url();

		if ( ! is_wp_error( $rule ) ) {
			$rule = $this->rules->save( $rule );
		}

		if ( is_wp_error( $rule ) ) {
			$this->back( $back . '#prd-form', 'error', $rule->get_error_message(), $form );
		}

		$this->back( self::url(), 'success', $id > 0 ? __( 'Redirección guardada.', 'parkinson-redirects' ) : __( 'Redirección creada.', 'parkinson-redirects' ) );
	}

	public function handle_delete(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'prd_delete_' . $id );
		$this->authorize();

		$this->rules->delete( $id );
		$this->back( self::url(), 'success', __( 'Redirección borrada.', 'parkinson-redirects' ) );
	}

	public function handle_export(): void {
		check_admin_referer( 'prd_export' );
		$this->authorize();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="redirecciones-' . gmdate( 'Y-m-d' ) . '.csv"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Salida directa de la descarga.
		$out = fopen( 'php://output', 'wb' );
		if ( false === $out ) {
			exit;
		}
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM para que Excel lea bien las tildes.
		foreach ( $this->importer->export_rows() as $row ) {
			fputcsv( $out, $row, ';', '"', '' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Pareja del fopen anterior.
		exit;
	}

	public function handle_import(): void {
		check_admin_referer( 'prd_import' );
		$this->authorize();

		$back = self::url( 'importar' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Se valida error, tamaño, extensión y que sea una subida real.
		$file = $_FILES['csv'] ?? null;

		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			$this->back( $back, 'error', __( 'Elige un archivo CSV para importar.', 'parkinson-redirects' ) );
		}

		$name = sanitize_file_name( (string) $file['name'] );
		if ( (int) $file['size'] > self::MAX_UPLOAD || ! preg_match( '/\.(csv|txt)$/i', $name ) ) {
			$this->back( $back, 'error', __( 'El archivo tiene que ser un CSV de menos de 1 MB.', 'parkinson-redirects' ) );
		}

		$rows = $this->importer->read_csv( (string) $file['tmp_name'] );
		if ( is_wp_error( $rows ) ) {
			$this->back( $back, 'error', $rows->get_error_message() );
		}

		$report = $this->importer->import( $rows, ! empty( $_POST['overwrite'] ) );
		$text   = sprintf(
			/* translators: 1: creadas, 2: actualizadas, 3: sin cambios, 4: con errores. */
			__( 'Importación terminada. Creadas: %1$d. Actualizadas: %2$d. Sin cambios (ya existían): %3$d. Con errores: %4$d.', 'parkinson-redirects' ),
			$report['created'],
			$report['updated'],
			$report['kept'],
			count( $report['errors'] )
		);

		$this->back( $back, $report['errors'] ? 'warning' : 'success', $text, [ 'errors' => array_slice( $report['errors'], 0, 50 ) ] );
	}

	public function handle_settings(): void {
		check_admin_referer( 'prd_settings' );
		$this->authorize();

		$this->settings->save(
			[
				'log_404'      => ! empty( $_POST['log_404'] ),
				'prefer_posts' => ! empty( $_POST['prefer_posts'] ),
			]
		);

		$this->back( self::url( 'ajustes' ), 'success', __( 'Ajustes guardados.', 'parkinson-redirects' ) );
	}

	public function handle_404_delete(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'prd_404_delete_' . $id );
		$this->authorize();

		$this->log->delete( $id );
		$this->back( self::url( '404' ), 'success', __( 'Dirección quitada de la lista.', 'parkinson-redirects' ) );
	}

	public function handle_404_clear(): void {
		check_admin_referer( 'prd_404_clear' );
		$this->authorize();

		$this->log->clear();
		$this->back( self::url( '404' ), 'success', __( 'Se ha vaciado la lista de direcciones no encontradas.', 'parkinson-redirects' ) );
	}

	private function authorize(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso para cambiar las redirecciones.', 'parkinson-redirects' ), '', [ 'response' => 403 ] );
		}
	}

	/**
	 * Vuelve a la pantalla con un aviso (y los datos del formulario si hubo error).
	 *
	 * @param array<string, mixed> $data Datos extra para la pantalla.
	 */
	private function back( string $url, string $type, string $text, array $data = [] ): never {
		set_transient(
			self::NOTICE . get_current_user_id(),
			[
				'type' => $type,
				'text' => $text,
				'data' => $data,
			],
			MINUTE_IN_SECONDS
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @return array{type: string, text: string, data: array<string, mixed>}|null
	 */
	private function take_notice(): ?array {
		$key    = self::NOTICE . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );

		return is_array( $notice ) ? $notice : null;
	}

	/* ------------------------------------------------------------------ */
	/* Pantalla                                                            */
	/* ------------------------------------------------------------------ */

	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo elige la pestaña.
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$tabs   = [
			''         => __( 'Redirecciones', 'parkinson-redirects' ),
			'404'      => __( 'No encontradas (404)', 'parkinson-redirects' ),
			'importar' => __( 'Importar y exportar', 'parkinson-redirects' ),
			'ajustes'  => __( 'Ajustes', 'parkinson-redirects' ),
		];
		$tab    = array_key_exists( $tab, $tabs ) ? $tab : '';
		$notice = $this->take_notice();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Redirecciones', 'parkinson-redirects' ); ?></h1>

			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Secciones de redirecciones', 'parkinson-redirects' ); ?>">
				<?php
				foreach ( $tabs as $key => $label ) :
					$key = (string) $key; // «404» como clave de array se convierte en número.
					?>
					<a href="<?php echo esc_url( self::url( $key ) ); ?>" class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>"<?php echo $key === $tab ? ' aria-current="page"' : ''; ?>>
						<?php
						echo esc_html( $label );
						if ( '404' === $key && $this->log->count() > 0 ) {
							echo ' <span class="count">(' . esc_html( number_format_i18n( $this->log->count() ) ) . ')</span>';
						}
						?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php
			if ( $notice ) {
				$this->print_notice( $notice );
			}

			match ( $tab ) {
				'404'      => $this->render_404(),
				'importar' => $this->render_import(),
				'ajustes'  => $this->render_settings(),
				default    => $this->render_rules( $notice ),
			};
			?>
		</div>
		<?php
	}

	/**
	 * @param array{type: string, text: string, data: array<string, mixed>} $notice Aviso.
	 */
	private function print_notice( array $notice ): void {
		$type   = in_array( $notice['type'], [ 'success', 'error', 'warning' ], true ) ? $notice['type'] : 'info';
		$errors = (array) ( $notice['data']['errors'] ?? [] );
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?>" role="<?php echo 'error' === $type ? 'alert' : 'status'; ?>">
			<p><?php echo esc_html( $notice['text'] ); ?></p>
			<?php if ( $errors ) : ?>
				<ul class="ul-disc">
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( (string) $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array{type: string, text: string, data: array<string, mixed>}|null $notice Aviso con los datos del formulario si hubo un error.
	 */
	private function render_rules( ?array $notice ): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		$table = new RulesTable( $this->rules );
		$table->prepare_items();
		?>
		<p><?php esc_html_e( 'Cuando alguien entra en una dirección de la web antigua (desde Google, un enlace guardado o un papel), se le lleva a la página nueva que corresponde. Así no se pierden visitas ni posiciones en Google.', 'parkinson-redirects' ); ?></p>

		<?php $this->render_tester(); ?>
		<?php $this->render_form( 'error' === ( $notice['type'] ?? '' ) ? (array) $notice['data'] : [] ); ?>

		<h2><?php esc_html_e( 'Todas las redirecciones', 'parkinson-redirects' ); ?></h2>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<?php
			$table->search_box( __( 'Buscar redirecciones', 'parkinson-redirects' ), 'prd-search' );
			$table->display();
			?>
		</form>
		<?php
	}

	private function render_tester(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lectura.
		$raw = isset( $_GET['probar'] ) ? sanitize_text_field( wp_unslash( $_GET['probar'] ) ) : '';
		?>
		<h2><?php esc_html_e( 'Probar una dirección', 'parkinson-redirects' ); ?></h2>
		<form method="get" class="prd-tester">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<p>
				<label for="prd-probar"><?php esc_html_e( 'Dirección antigua', 'parkinson-redirects' ); ?></label><br>
				<input type="text" id="prd-probar" name="probar" class="regular-text code" value="<?php echo esc_attr( $raw ); ?>" placeholder="/hazte-socio/" aria-describedby="prd-probar-help">
				<?php submit_button( __( 'Probar', 'parkinson-redirects' ), 'secondary', '', false ); ?>
			</p>
			<p class="description" id="prd-probar-help"><?php esc_html_e( 'Escribe una ruta o pega una dirección completa de la web antigua para ver adónde lleva.', 'parkinson-redirects' ); ?></p>
		</form>
		<?php
		if ( '' === $raw ) {
			return;
		}

		$path     = Path::normalize( $raw );
		$post_id  = url_to_postid( home_url( $path ) );
		$exists   = $post_id > 0;
		$result   = $this->redirector->resolve( $path, ! $exists );
		$messages = [];

		if ( null === $result ) {
			$messages[] = $exists
				/* translators: %s: título de la página. */
				? sprintf( __( 'Sin redirección: la dirección existe en la web nueva («%s») y se muestra tal cual.', 'parkinson-redirects' ), get_the_title( $post_id ) )
				: __( 'Sin redirección: esta dirección no lleva a ninguna parte (la persona vería «Página no encontrada»).', 'parkinson-redirects' );
		} else {
			[ $rule, $url ] = $result;
			$messages[]     = $rule->is_gone()
				? __( 'Se avisa de que la página ya no existe (410).', 'parkinson-redirects' )
				/* translators: 1: tipo de redirección, 2: destino. */
				: sprintf( __( '%1$s → %2$s', 'parkinson-redirects' ), self::code_label( $rule->code ), $url );
			/* translators: %s: dirección antigua de la regla. */
			$messages[] = sprintf( __( 'Por la regla %s.', 'parkinson-redirects' ), $rule->label() );
			if ( $rule->is_prefix() ) {
				$messages[] = __( 'Es una regla general: solo actúa si la dirección no existe en la web nueva. Si existe (por ejemplo, una categoría nueva), se muestra tal cual.', 'parkinson-redirects' );
			}
		}
		?>
		<div class="notice notice-info inline" role="status">
			<p><code><?php echo esc_html( $path ); ?></code> · <a href="<?php echo esc_url( home_url( $path ) ); ?>"><?php esc_html_e( 'Abrirla en la web', 'parkinson-redirects' ); ?></a></p>
			<?php foreach ( $messages as $message ) : ?>
				<p><?php echo esc_html( $message ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $old Datos enviados que no se pudieron guardar.
	 */
	private function render_form( array $old ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo rellena el formulario.
		$edit_id = isset( $_GET['editar'] ) ? absint( $_GET['editar'] ) : 0;
		$prefill = isset( $_GET['origen'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['origen'] ) ) ) : '';
		// phpcs:enable

		$rule = $edit_id > 0 ? $this->rules->find( $edit_id ) : null;

		$values = [
			'id'     => $rule ? $rule->id : 0,
			'source' => $rule ? $rule->source : $prefill,
			'prefix' => $rule ? $rule->is_prefix() : false,
			'code'   => $rule ? $rule->code : 301,
			'choice' => $rule && $rule->target_post > 0 ? 'post-' . $rule->target_post : ( $rule && '' !== $rule->target_url ? 'custom' : '' ),
			'custom' => $rule ? $rule->target_url : '',
			'anchor' => $rule ? $rule->anchor : '',
			'note'   => $rule ? $rule->note : '',
		];
		if ( $old ) {
			$values = array_merge( $values, array_intersect_key( $old, $values ) );
			if ( '' !== (string) $values['custom'] && ! str_starts_with( (string) $values['choice'], 'post-' ) ) {
				$values['choice'] = 'custom';
			}
		}

		$pages   = get_pages(
			[
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			]
		);
		$page_id = [];
		foreach ( $pages as $page ) {
			$page_id[ 'post-' . $page->ID ] = true;
		}

		$editing = $values['id'] > 0;
		?>
		<h2 id="prd-form"><?php echo $editing ? esc_html__( 'Editar redirección', 'parkinson-redirects' ) : esc_html__( 'Añadir una redirección', 'parkinson-redirects' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="prd_save">
			<input type="hidden" name="id" value="<?php echo esc_attr( (string) $values['id'] ); ?>">
			<?php wp_nonce_field( 'prd_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="prd-source"><?php esc_html_e( 'Dirección antigua', 'parkinson-redirects' ); ?></label></th>
					<td>
						<input type="text" id="prd-source" name="source" class="regular-text code" required value="<?php echo esc_attr( (string) $values['source'] ); ?>" placeholder="/hazte-socio/" aria-describedby="prd-source-help">
						<p class="description" id="prd-source-help"><?php esc_html_e( 'Solo la ruta, por ejemplo /hazte-socio/. También puedes pegar la dirección completa. No importan las mayúsculas ni la barra final.', 'parkinson-redirects' ); ?></p>
						<p><label><input type="checkbox" name="prefix" value="1" <?php checked( (bool) $values['prefix'] ); ?>> <?php esc_html_e( 'Incluir también todo lo que cuelga de esta ruta (por ejemplo, todas las direcciones que empiezan por /category/). Solo actúa cuando la página no existe en la web nueva.', 'parkinson-redirects' ); ?></label></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="prd-code"><?php esc_html_e( 'Tipo', 'parkinson-redirects' ); ?></label></th>
					<td>
						<select id="prd-code" name="code" aria-describedby="prd-code-help">
							<?php foreach ( Rule::CODES as $code ) : ?>
								<option value="<?php echo esc_attr( (string) $code ); ?>" <?php selected( (int) $values['code'], $code ); ?>><?php echo esc_html( self::code_label( $code ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description" id="prd-code-help"><?php esc_html_e( 'Casi siempre «Se ha movido (301)»: Google pasa la posición de la página antigua a la nueva. «Ya no existe» avisa de que el contenido se ha quitado a propósito y no necesita destino.', 'parkinson-redirects' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="prd-target"><?php esc_html_e( 'Llevar a', 'parkinson-redirects' ); ?></label></th>
					<td>
						<select id="prd-target" name="target_choice">
							<option value=""><?php esc_html_e( '— Elige una página —', 'parkinson-redirects' ); ?></option>
							<?php if ( str_starts_with( (string) $values['choice'], 'post-' ) && ! isset( $page_id[ $values['choice'] ] ) ) : ?>
								<?php $current = (int) substr( (string) $values['choice'], 5 ); ?>
								<option value="<?php echo esc_attr( (string) $values['choice'] ); ?>" selected>
									<?php
									/* translators: 1: título, 2: tipo de contenido. */
									echo esc_html( sprintf( __( 'Actual: %1$s (%2$s)', 'parkinson-redirects' ), get_the_title( $current ) ?: '#' . $current, get_post_type_object( (string) get_post_type( $current ) )?->labels->singular_name ?? '' ) );
									?>
								</option>
							<?php endif; ?>
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( 'post-' . $page->ID ); ?>" <?php selected( (string) $values['choice'], 'post-' . $page->ID ); ?>><?php echo esc_html( str_repeat( '— ', count( get_post_ancestors( $page ) ) ) . ( $page->post_title ?: '#' . $page->ID ) ); ?></option>
							<?php endforeach; ?>
							<option value="custom" <?php selected( (string) $values['choice'], 'custom' ); ?>><?php esc_html_e( 'Enlace personalizado', 'parkinson-redirects' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="prd-target-url"><?php esc_html_e( 'Enlace personalizado', 'parkinson-redirects' ); ?></label></th>
					<td>
						<input type="text" id="prd-target-url" name="target_url" class="regular-text code" value="<?php echo esc_attr( (string) $values['custom'] ); ?>" placeholder="/terapias/fisioterapia/" aria-describedby="prd-target-url-help">
						<p class="description" id="prd-target-url-help"><?php esc_html_e( 'Solo si has elegido «Enlace personalizado»: una ruta de esta web (empieza por /) o una dirección completa https://. Si es una página, entrada o archivo de esta web, se guarda el contenido y no la dirección, así que seguirá funcionando aunque cambie.', 'parkinson-redirects' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="prd-anchor"><?php esc_html_e( 'Ir a una sección (opcional)', 'parkinson-redirects' ); ?></label></th>
					<td>
						<input type="text" id="prd-anchor" name="anchor" class="regular-text code" value="<?php echo esc_attr( (string) $values['anchor'] ); ?>" placeholder="socio" aria-describedby="prd-anchor-help">
						<p class="description" id="prd-anchor-help"><?php esc_html_e( 'El ancla de la sección, sin #. Por ejemplo «socio» lleva a la parte «Hazte socio» de Colabora.', 'parkinson-redirects' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="prd-note"><?php esc_html_e( 'Nota', 'parkinson-redirects' ); ?></label></th>
					<td><input type="text" id="prd-note" name="note" class="regular-text" maxlength="255" value="<?php echo esc_attr( (string) $values['note'] ); ?>"></td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( $editing ? __( 'Guardar redirección', 'parkinson-redirects' ) : __( 'Añadir redirección', 'parkinson-redirects' ), 'primary', 'submit', false ); ?>
				<?php if ( $editing ) : ?>
					<a class="button" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Cancelar', 'parkinson-redirects' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
		<?php
	}

	private function render_404(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		$table = new NotFoundTable( $this->log );
		$table->prepare_items();
		?>
		<p><?php esc_html_e( 'Direcciones que alguien ha pedido y que no existen en la web. Si alguna era de la web antigua, crea una redirección para ella y desaparecerá de esta lista. Solo se guarda la dirección, cuántas veces se ha pedido y cuándo; nada de quién la pidió. Se guardan las 500 más recientes.', 'parkinson-redirects' ); ?></p>
		<?php if ( ! $this->log->enabled() ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'El registro está apagado en Ajustes: no se apuntan direcciones nuevas.', 'parkinson-redirects' ); ?></p></div>
		<?php endif; ?>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="404">
			<?php
			$table->search_box( __( 'Buscar direcciones', 'parkinson-redirects' ), 'prd-404-search' );
			$table->display();
			?>
		</form>
		<?php if ( $this->log->count() > 0 ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="prd_404_clear">
				<?php wp_nonce_field( 'prd_404_clear' ); ?>
				<?php submit_button( __( 'Vaciar la lista', 'parkinson-redirects' ), 'secondary' ); ?>
			</form>
		<?php endif; ?>
		<?php
	}

	private function render_import(): void {
		?>
		<h2><?php esc_html_e( 'Exportar', 'parkinson-redirects' ); ?></h2>
		<p>
			<?php
			/* translators: %d: número de redirecciones. */
			echo esc_html( sprintf( _n( 'Descarga la %d redirección en un archivo CSV que se abre con Excel o LibreOffice.', 'Descarga las %d redirecciones en un archivo CSV que se abre con Excel o LibreOffice.', $this->rules->count(), 'parkinson-redirects' ), $this->rules->count() ) );
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="prd_export">
			<?php wp_nonce_field( 'prd_export' ); ?>
			<?php submit_button( __( 'Descargar CSV', 'parkinson-redirects' ), 'secondary', 'submit', false ); ?>
		</form>

		<h2><?php esc_html_e( 'Importar', 'parkinson-redirects' ); ?></h2>
		<p><?php esc_html_e( 'Un archivo CSV con las columnas origen, destino, codigo y nota (separadas por coma o por punto y coma), como el que se descarga arriba. El origen puede terminar en /* para incluir todo lo que cuelga de él. El destino es una ruta de esta web (con #ancla si hace falta) o una dirección https://, y puede quedar vacío si el código es 410.', 'parkinson-redirects' ); ?></p>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="prd_import">
			<?php wp_nonce_field( 'prd_import' ); ?>
			<p>
				<label for="prd-csv"><?php esc_html_e( 'Archivo CSV', 'parkinson-redirects' ); ?></label><br>
				<input type="file" id="prd-csv" name="csv" accept=".csv,text/csv" required>
			</p>
			<p><label><input type="checkbox" name="overwrite" value="1"> <?php esc_html_e( 'Cambiar las redirecciones que ya existen para la misma dirección antigua', 'parkinson-redirects' ); ?></label></p>
			<?php submit_button( __( 'Importar', 'parkinson-redirects' ), 'primary', 'submit', false ); ?>
		</form>
		<?php
	}

	private function render_settings(): void {
		$settings = $this->settings->all();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="prd_settings">
			<?php wp_nonce_field( 'prd_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Direcciones no encontradas', 'parkinson-redirects' ); ?></th>
					<td>
						<label><input type="checkbox" name="log_404" value="1" <?php checked( $settings['log_404'] ); ?>> <?php esc_html_e( 'Apuntar las direcciones que no existen (404)', 'parkinson-redirects' ); ?></label>
						<p class="description"><?php esc_html_e( 'Sirve para descubrir enlaces antiguos sin redirección. Solo se guarda la dirección, sin datos de quien la visita.', 'parkinson-redirects' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Noticias recuperadas', 'parkinson-redirects' ); ?></th>
					<td>
						<label><input type="checkbox" name="prefer_posts" value="1" <?php checked( $settings['prefer_posts'] ); ?>> <?php esc_html_e( 'Si se publica una entrada con el mismo nombre que una dirección antigua, llevar a esa entrada', 'parkinson-redirects' ); ?></label>
						<p class="description"><?php esc_html_e( 'Por ejemplo, si se vuelve a publicar la noticia «/dia-mundial-del-parkinson-2024/», su dirección antigua lleva a ella en lugar de a Actualidad, sin tocar la redirección.', 'parkinson-redirects' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Guardar cambios', 'parkinson-redirects' ) ); ?>
		</form>
		<?php
	}
}
