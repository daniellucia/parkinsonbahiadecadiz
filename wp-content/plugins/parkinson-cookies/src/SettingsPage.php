<?php
/**
 * Ajustes → Cookies: página de la política, servicios declarados y botón
 * para volver a pedir el consentimiento a todo el mundo.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private const SLUG  = 'pck-cookies';
	private const GROUP = 'pck_settings_group';

	public function __construct(
		private readonly Settings $settings,
		private readonly Services $services
	) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
	}

	public function add_page(): void {
		add_options_page(
			__( 'Cookies', 'parkinson-cookies' ),
			__( 'Cookies', 'parkinson-cookies' ),
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
	 * @return array{policy_page: int, revision: int}
	 */
	public function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : [];
		$current  = $this->settings->all();
		$page     = absint( $input['policy_page'] ?? 0 );
		$revision = $current['revision'];

		if ( ! empty( $input['ask_again'] ) ) {
			++$revision;
			add_settings_error( Settings::OPTION, 'pck-ask-again', __( 'Se volverá a pedir el consentimiento a todas las personas en su próxima visita.', 'parkinson-cookies' ), 'updated' );
		}

		return [
			'policy_page' => $page > 0 && 'page' === get_post_type( $page ) ? $page : 0,
			'revision'    => max( 1, $revision ),
		];
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings   = $this->settings->all();
		$categories = $this->services->categories();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cookies', 'parkinson-cookies' ); ?></h1>
			<p><?php esc_html_e( 'El aviso de cookies aparece solo si la web usa algo que necesita permiso. Aceptar, rechazar y elegir cuestan lo mismo, y nada opcional se carga hasta que la persona lo permite. La elección se guarda 12 meses en su navegador.', 'parkinson-cookies' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pck-policy-page"><?php esc_html_e( 'Página de la política de cookies', 'parkinson-cookies' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages(
								[
									'name'              => esc_attr( Settings::OPTION ) . '[policy_page]',
									'id'                => 'pck-policy-page',
									'selected'          => (int) $settings['policy_page'],
									'show_option_none'  => esc_html__( '— Sin elegir —', 'parkinson-cookies' ),
									'option_none_value' => '0',
								]
							);
							?>
							<p class="description"><?php esc_html_e( 'Se enlaza desde el aviso. Escribe en ella [pbc_cookies_tabla] para la lista de cookies y [pbc_cookies_ajustes] para el botón «Configurar cookies». Para un enlace permanente en el pie, añade al menú un enlace a esta página terminado en #configurar-cookies.', 'parkinson-cookies' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Volver a preguntar', 'parkinson-cookies' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[ask_again]" value="1"> <?php esc_html_e( 'Pedir de nuevo el consentimiento a todo el mundo al guardar', 'parkinson-cookies' ); ?></label>
							<p class="description"><?php esc_html_e( 'Úsalo si cambia la política de cookies. Si se añade o se quita un servicio que necesita permiso, se vuelve a preguntar solo.', 'parkinson-cookies' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Guardar cambios', 'parkinson-cookies' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Servicios declarados', 'parkinson-cookies' ); ?></h2>
			<p><?php esc_html_e( 'Los declaran el tema y los plugins de la web con el filtro «parkinson_cookies/services». Esta lista es la que se muestra en el aviso y en la política.', 'parkinson-cookies' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Servicio', 'parkinson-cookies' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Tipo', 'parkinson-cookies' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Qué guarda', 'parkinson-cookies' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Duración', 'parkinson-cookies' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->services->all() as $service ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $service['name'] ); ?></strong><br><?php echo esc_html( $service['provider'] ); ?></td>
							<td><?php echo esc_html( $categories[ $service['category'] ]['label'] ); ?></td>
							<td><code><?php echo esc_html( $service['cookies'] ); ?></code></td>
							<td><?php echo esc_html( $service['duration'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<?php
				printf(
					/* translators: %s: versión del consentimiento. */
					esc_html__( 'Versión actual del consentimiento: %s', 'parkinson-cookies' ),
					'<code>' . esc_html( $this->services->version() ) . '</code>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
