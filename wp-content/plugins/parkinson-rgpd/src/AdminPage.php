<?php
/**
 * Ajustes → Protección de datos: responsable, plazos de conservación,
 * registro de actividades de tratamiento, derechos, brechas y medidas activas.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

namespace DanielLucia\Rgpd;

defined( 'ABSPATH' ) || exit;

final class AdminPage {

	private const SLUG   = 'prgpd';
	private const GROUP  = 'prgpd_settings_group';
	private const ACTION = 'prgpd_run';

	public function __construct(
		private readonly Settings $settings,
		private readonly Registry $registry
	) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'run_now' ] );
	}

	public function add_page(): void {
		add_options_page(
			__( 'Protección de datos', 'parkinson-rgpd' ),
			__( 'Protección de datos', 'parkinson-rgpd' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function register_setting(): void {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * @param mixed $input Valores enviados.
	 * @return array{controller: array<string, string>, retention: array<string, int>}
	 */
	public function sanitize( $input ): array {
		$input      = is_array( $input ) ? $input : [];
		$controller = [];

		foreach ( Settings::CONTROLLER_FIELDS as $field ) {
			$value                = (string) ( $input['controller'][ $field ] ?? '' );
			$controller[ $field ] = 'email' === $field ? sanitize_email( $value ) : sanitize_textarea_field( $value );
		}

		$retention = [];
		foreach ( array_keys( $this->registry->retention() ) as $id ) {
			$retention[ $id ] = min( 120, absint( $input['retention'][ $id ] ?? 0 ) );
		}

		return [
			'controller' => $controller,
			'retention'  => $retention,
		];
	}

	public function run_now(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permiso para hacer esto.', 'parkinson-rgpd' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( self::ACTION );

		$deleted = ( new Retention( $this->settings, $this->registry ) )->run();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::SLUG,
					'borrado' => array_sum( $deleted ),
				],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved      = $this->settings->controller_saved();
		$controller = $this->settings->controller();
		$retention  = $this->registry->retention();
		$treatments = $this->registry->treatments();
		$last       = Retention::last_run();
		$next       = wp_next_scheduled( Retention::HOOK );
		$name       = Settings::OPTION;
		$labels     = [
			'name'    => __( 'Nombre o razón social', 'parkinson-rgpd' ),
			'nif'     => __( 'NIF / CIF', 'parkinson-rgpd' ),
			'address' => __( 'Dirección postal', 'parkinson-rgpd' ),
			'email'   => __( 'Correo para ejercer derechos', 'parkinson-rgpd' ),
			'phone'   => __( 'Teléfono', 'parkinson-rgpd' ),
			'dpo'     => __( 'Delegado de protección de datos (si lo hay)', 'parkinson-rgpd' ),
		];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Protección de datos', 'parkinson-rgpd' ); ?></h1>
			<p><?php esc_html_e( 'Lo que la web hace con datos personales, en un solo sitio: quién es el responsable, cuánto tiempo se guarda cada cosa, el registro de tratamientos y qué hacer si alguien pide sus datos o si hay un problema de seguridad.', 'parkinson-rgpd' ); ?></p>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo muestra el resultado de una acción ya comprobada. ?>
			<?php if ( isset( $_GET['borrado'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo un número para el aviso.
					$count = absint( $_GET['borrado'] );
					/* translators: %d: número de elementos borrados. */
					echo esc_html( sprintf( _n( 'Revisión hecha: se ha borrado %d elemento caducado.', 'Revisión hecha: se han borrado %d elementos caducados.', $count, 'parkinson-rgpd' ), $count ) );
					?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2><?php esc_html_e( 'Responsable del tratamiento', 'parkinson-rgpd' ); ?></h2>
				<p><?php esc_html_e( 'Sale en el pie de la web y en el registro de tratamientos. Lo que dejes vacío se toma de los datos de contacto del tema (se ve en gris).', 'parkinson-rgpd' ); ?></p>
				<table class="form-table" role="presentation">
					<?php foreach ( $labels as $field => $label ) : ?>
						<tr>
							<th scope="row"><label for="prgpd-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<?php if ( 'address' === $field ) : ?>
									<textarea class="regular-text" rows="2" id="prgpd-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $name . '[controller][' . $field . ']' ); ?>" placeholder="<?php echo esc_attr( $controller[ $field ] ); ?>"><?php echo esc_textarea( $saved[ $field ] ); ?></textarea>
								<?php else : ?>
									<input class="regular-text" type="<?php echo 'email' === $field ? 'email' : 'text'; ?>" id="prgpd-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $name . '[controller][' . $field . ']' ); ?>" value="<?php echo esc_attr( $saved[ $field ] ); ?>" placeholder="<?php echo esc_attr( $controller[ $field ] ); ?>">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'Plazos de conservación', 'parkinson-rgpd' ); ?></h2>
				<p><?php esc_html_e( 'Los datos no se pueden guardar más tiempo del necesario. Una vez al día la web borra para siempre lo que ha superado su plazo. 0 = no se borra solo (habrá que borrarlo a mano).', 'parkinson-rgpd' ); ?></p>
				<?php if ( ! $retention ) : ?>
					<p><em><?php esc_html_e( 'Ningún plugin ha declarado datos con plazo de conservación.', 'parkinson-rgpd' ); ?></em></p>
				<?php else : ?>
					<table class="form-table" role="presentation">
						<?php foreach ( $retention as $id => $item ) : ?>
							<tr>
								<th scope="row"><label for="prgpd-ret-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $item['label'] ); ?></label></th>
								<td>
									<input class="small-text" type="number" min="0" max="120" step="1" id="prgpd-ret-<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name . '[retention][' . $id . ']' ); ?>" value="<?php echo esc_attr( (string) $this->settings->months( $id, $item['default'] ) ); ?>" aria-describedby="prgpd-ret-<?php echo esc_attr( $id ); ?>-desc">
									<?php esc_html_e( 'meses', 'parkinson-rgpd' ); ?>
									<p class="description" id="prgpd-ret-<?php echo esc_attr( $id ); ?>-desc"><?php echo esc_html( $item['description'] ); ?></p>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Guardar cambios', 'parkinson-rgpd' ) ); ?>
			</form>

			<p>
				<?php
				if ( $last ) {
					printf(
						/* translators: 1: fecha, 2: número de elementos borrados. */
						esc_html__( 'Última revisión: %1$s (%2$d borrados).', 'parkinson-rgpd' ),
						esc_html( wp_date( get_option( 'date_format' ) . ' H:i', $last['time'] ) ),
						(int) array_sum( $last['deleted'] )
					);
					echo ' ';
				}
				if ( $next ) {
					printf(
						/* translators: %s: fecha. */
						esc_html__( 'Próxima: %s.', 'parkinson-rgpd' ),
						esc_html( wp_date( get_option( 'date_format' ) . ' H:i', $next ) )
					);
				}
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<?php submit_button( __( 'Borrar ahora lo caducado', 'parkinson-rgpd' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Registro de actividades de tratamiento', 'parkinson-rgpd' ); ?></h2>
			<p><?php esc_html_e( 'Lo exige el artículo 30 del RGPD. Lo declaran los plugins que recogen datos; si la asociación trata datos fuera de la web (usuarios del centro, nóminas…), esos tratamientos van en su propio registro.', 'parkinson-rgpd' ); ?></p>
			<p>
				<strong><?php esc_html_e( 'Responsable:', 'parkinson-rgpd' ); ?></strong>
				<?php echo esc_html( implode( ' · ', array_filter( [ $controller['name'], $controller['nif'], str_replace( "\n", ', ', $controller['address'] ), $controller['email'], $controller['phone'] ] ) ) ); ?>
				<?php if ( '' !== $controller['dpo'] ) : ?>
					<br><strong><?php esc_html_e( 'Delegado de protección de datos:', 'parkinson-rgpd' ); ?></strong> <?php echo esc_html( $controller['dpo'] ); ?>
				<?php endif; ?>
			</p>
			<?php
			$columns = [
				'purpose'    => __( 'Finalidad', 'parkinson-rgpd' ),
				'basis'      => __( 'Base jurídica', 'parkinson-rgpd' ),
				'people'     => __( 'Personas afectadas', 'parkinson-rgpd' ),
				'data'       => __( 'Datos', 'parkinson-rgpd' ),
				'recipients' => __( 'Destinatarios', 'parkinson-rgpd' ),
				'transfers'  => __( 'Transferencias internacionales', 'parkinson-rgpd' ),
				'retention'  => __( 'Plazo de conservación', 'parkinson-rgpd' ),
				'security'   => __( 'Medidas de seguridad', 'parkinson-rgpd' ),
			];
			?>
			<?php foreach ( $treatments as $treatment ) : ?>
				<h3><?php echo esc_html( $treatment['name'] ); ?></h3>
				<table class="widefat striped" style="max-width:60rem">
					<tbody>
						<?php foreach ( $columns as $field => $label ) : ?>
							<?php if ( '' !== $treatment[ $field ] ) : ?>
								<tr>
									<th scope="row" style="width:14em"><?php echo esc_html( $label ); ?></th>
									<td><?php echo esc_html( $treatment[ $field ] ); ?></td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Si alguien pide sus datos', 'parkinson-rgpd' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Comprueba que es esa persona (por ejemplo, que escribe desde el correo que dio o que confirma sus datos por teléfono). No pidas más datos de los necesarios.', 'parkinson-rgpd' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: 1: enlace a exportar, 2: enlace a borrar. */
						esc_html__( 'Si dio su correo: %1$s o %2$s. WordPress le envía un correo para que confirme la petición.', 'parkinson-rgpd' ),
						'<a href="' . esc_url( admin_url( 'export-personal-data.php' ) ) . '">' . esc_html__( 'Exportar datos personales', 'parkinson-rgpd' ) . '</a>',
						'<a href="' . esc_url( admin_url( 'erase-personal-data.php' ) ) . '">' . esc_html__( 'Borrar datos personales', 'parkinson-rgpd' ) . '</a>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Si solo dio su teléfono: búscalo en Formularios. Desde cada envío puedes descargar sus datos o borrarlo para siempre.', 'parkinson-rgpd' ); ?></li>
				<li><?php esc_html_e( 'Responde en un mes como máximo (ampliable dos meses más si es complicado, avisando a la persona). Es gratis.', 'parkinson-rgpd' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Si hay una brecha de seguridad', 'parkinson-rgpd' ); ?></h2>
			<p><?php esc_html_e( 'Por ejemplo: alguien entra en el escritorio sin permiso, se pierde la clave de cifrado o se envían datos a quien no debía.', 'parkinson-rgpd' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Corta el problema: cambia las contraseñas, cierra las sesiones abiertas y avisa a quien lleva la web y el alojamiento.', 'parkinson-rgpd' ); ?></li>
				<li><?php esc_html_e( 'Apunta qué ha pasado, cuándo, qué datos y de cuántas personas, y qué has hecho. Hay que guardar ese registro aunque no se notifique.', 'parkinson-rgpd' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %s: enlace a la sede de la AEPD. */
						esc_html__( 'Si puede haber riesgo para las personas, notifícalo a la AEPD en 72 horas desde que lo sepas: %s.', 'parkinson-rgpd' ),
						'<a href="https://sedeagpd.gob.es/">' . esc_html__( 'sede electrónica de la AEPD (Brechas de datos personales)', 'parkinson-rgpd' ) . '</a>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Si el riesgo es alto (por ejemplo, DNI o números de cuenta), avisa también a las personas afectadas, con palabras sencillas y qué pueden hacer.', 'parkinson-rgpd' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Medidas que aplica este plugin', 'parkinson-rgpd' ); ?></h2>
			<?php $measures = Exposure::measures(); ?>
			<ul style="list-style:disc;padding-left:1.5em">
				<li><?php echo esc_html( $measures['comments'] ? __( 'Comentarios y pingbacks cerrados en toda la web.', 'parkinson-rgpd' ) : __( 'Comentarios: sin cambios (medida desactivada).', 'parkinson-rgpd' ) ); ?></li>
				<li><?php echo esc_html( $measures['users'] ? __( 'La lista de usuarios no se ve por la API REST sin iniciar sesión.', 'parkinson-rgpd' ) : __( 'Usuarios en la API REST: sin cambios (medida desactivada).', 'parkinson-rgpd' ) ); ?></li>
				<li><?php echo esc_html( $measures['authors'] ? __( 'Sin páginas de autor ni nombres de usuario en el mapa del sitio, oEmbed o los datos estructurados.', 'parkinson-rgpd' ) : __( 'Autores: sin cambios (medida desactivada).', 'parkinson-rgpd' ) ); ?></li>
				<li><?php echo esc_html( $measures['gravatar'] ? __( 'Sin Gravatar: no se envía a terceros el correo (cifrado) de nadie para mostrar su foto.', 'parkinson-rgpd' ) : __( 'Gravatar: sin cambios (medida desactivada).', 'parkinson-rgpd' ) ); ?></li>
				<li><?php esc_html_e( 'Borrado automático de los datos caducados, una vez al día.', 'parkinson-rgpd' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
