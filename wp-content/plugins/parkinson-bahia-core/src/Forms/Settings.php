<?php
/**
 * Ajustes comunes a todos los formularios: Formularios → Ajustes.
 *
 * Aquí se escriben una sola vez las claves de Cloudflare Turnstile y el correo
 * que recibe los avisos; todos los formularios del sitio los usan.
 *
 * Las claves también se pueden definir en wp-config.php con las constantes
 * PBC_TURNSTILE_SITE_KEY y PBC_TURNSTILE_SECRET_KEY; en ese caso mandan sobre
 * lo escrito en esta página.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION = 'pbc_forms_settings';
	private const GROUP = 'pbc_forms_settings_group';
	private const SLUG  = 'pbc-forms-settings';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
	}

	/**
	 * @return array{turnstile_enabled: bool, site_key: string, secret_key: string, email: string}
	 */
	public static function all(): array {
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		return [
			'turnstile_enabled' => ! empty( $saved['turnstile_enabled'] ),
			'site_key'          => defined( 'PBC_TURNSTILE_SITE_KEY' ) ? (string) constant( 'PBC_TURNSTILE_SITE_KEY' ) : (string) ( $saved['site_key'] ?? '' ),
			'secret_key'        => defined( 'PBC_TURNSTILE_SECRET_KEY' ) ? (string) constant( 'PBC_TURNSTILE_SECRET_KEY' ) : (string) ( $saved['secret_key'] ?? '' ),
			'email'             => (string) ( $saved['email'] ?? '' ),
		];
	}

	/**
	 * Turnstile está en uso: activado y con las dos claves.
	 */
	public static function turnstile_active(): bool {
		$settings = self::all();

		return $settings['turnstile_enabled'] && '' !== $settings['site_key'] && '' !== $settings['secret_key'];
	}

	public static function site_key(): string {
		return self::all()['site_key'];
	}

	/**
	 * Correo que recibe los avisos: el de esta página o, si está vacío, el de administración.
	 */
	public static function notification_email(): string {
		$email = sanitize_email( self::all()['email'] );

		return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
	}

	/**
	 * Comprueba con Cloudflare la respuesta de Turnstile de un envío.
	 */
	public static function verify_turnstile( string $response, string $ip ): bool {
		if ( '' === $response ) {
			return false;
		}

		$result = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			[
				'timeout' => 8,
				'body'    => [
					'secret'   => self::all()['secret_key'],
					'response' => $response,
					'remoteip' => $ip,
				],
			]
		);

		if ( is_wp_error( $result ) || 200 !== wp_remote_retrieve_response_code( $result ) ) {
			return false;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $result ), true );

		return is_array( $body ) && ! empty( $body['success'] );
	}

	public function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . Submissions::POST_TYPE,
			__( 'Ajustes de los formularios', 'parkinson-bahia-core' ),
			__( 'Ajustes', 'parkinson-bahia-core' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function register_setting(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => [],
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * @param mixed $input Datos del formulario.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : [];
		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		// La clave secreta no se vuelve a enviar al navegador: si el campo llega vacío se conserva la guardada.
		$secret = trim( sanitize_text_field( (string) ( $input['secret_key'] ?? '' ) ) );
		if ( '' === $secret && empty( $input['secret_key_clear'] ) ) {
			$secret = (string) ( $saved['secret_key'] ?? '' );
		}

		return [
			'turnstile_enabled' => ! empty( $input['turnstile_enabled'] ),
			'site_key'          => trim( sanitize_text_field( (string) ( $input['site_key'] ?? '' ) ) ),
			'secret_key'        => $secret,
			'email'             => sanitize_email( (string) ( $input['email'] ?? '' ) ),
		];
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings      = self::all();
		$name          = self::OPTION;
		$site_locked   = defined( 'PBC_TURNSTILE_SITE_KEY' );
		$secret_locked = defined( 'PBC_TURNSTILE_SECRET_KEY' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ajustes de los formularios', 'parkinson-bahia-core' ); ?></h1>
			<p><?php esc_html_e( 'Estos ajustes se escriben una sola vez y valen para todos los formularios de la web.', 'parkinson-bahia-core' ); ?></p>

			<?php if ( ! Crypto::is_available() ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Falta la clave de cifrado (PBC_FORMS_KEY en wp-config.php). Mientras no exista, el formulario de socios no pide el DNI ni el número de cuenta: esos datos habrá que pedirlos por teléfono.', 'parkinson-bahia-core' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2><?php esc_html_e( 'Avisos por correo', 'parkinson-bahia-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pbc-forms-email"><?php esc_html_e( 'Correo que recibe los avisos', 'parkinson-bahia-core' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="pbc-forms-email" name="<?php echo esc_attr( $name ); ?>[email]" value="<?php echo esc_attr( $settings['email'] ); ?>" placeholder="<?php echo esc_attr( (string) get_option( 'admin_email' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Cada vez que alguien envía un formulario llega un aviso a esta dirección con el nombre y el teléfono. El resto de los datos se consulta en «Formularios recibidos». Si lo dejas vacío se usa el correo de administración.', 'parkinson-bahia-core' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Protección contra robots: Cloudflare Turnstile', 'parkinson-bahia-core' ); ?></h2>
				<p>
					<?php esc_html_e( 'Turnstile comprueba que quien envía el formulario es una persona, sin pedirle que resuelva ningún acertijo. Las dos claves se crean gratis en el panel de Cloudflare, apartado Turnstile.', 'parkinson-bahia-core' ); ?>
					<a href="https://dash.cloudflare.com/?to=/:account/turnstile"><?php esc_html_e( 'Abrir el panel de Cloudflare', 'parkinson-bahia-core' ); ?></a>
				</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Estado', 'parkinson-bahia-core' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[turnstile_enabled]" value="1" <?php checked( $settings['turnstile_enabled'] ); ?>> <?php esc_html_e( 'Usar Turnstile en todos los formularios', 'parkinson-bahia-core' ); ?></label>
							<?php if ( $settings['turnstile_enabled'] && ! self::turnstile_active() ) : ?>
								<p class="description" style="color:#b32d2e"><?php esc_html_e( 'Está activado pero falta alguna de las dos claves, así que todavía no se usa.', 'parkinson-bahia-core' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pbc-forms-site-key"><?php esc_html_e( 'Clave del sitio', 'parkinson-bahia-core' ); ?></label></th>
						<td>
							<?php if ( $site_locked ) : ?>
								<p><?php esc_html_e( 'Definida en wp-config.php.', 'parkinson-bahia-core' ); ?></p>
							<?php else : ?>
								<input type="text" class="regular-text code" id="pbc-forms-site-key" name="<?php echo esc_attr( $name ); ?>[site_key]" value="<?php echo esc_attr( $settings['site_key'] ); ?>" autocomplete="off" spellcheck="false">
								<p class="description"><?php esc_html_e( 'En Cloudflare se llama «Site Key». Es pública.', 'parkinson-bahia-core' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pbc-forms-secret-key"><?php esc_html_e( 'Clave secreta', 'parkinson-bahia-core' ); ?></label></th>
						<td>
							<?php if ( $secret_locked ) : ?>
								<p><?php esc_html_e( 'Definida en wp-config.php.', 'parkinson-bahia-core' ); ?></p>
							<?php else : ?>
								<input type="password" class="regular-text code" id="pbc-forms-secret-key" name="<?php echo esc_attr( $name ); ?>[secret_key]" value="" autocomplete="new-password" spellcheck="false" placeholder="<?php echo esc_attr( '' !== $settings['secret_key'] ? __( 'Guardada. Escribe otra solo para cambiarla.', 'parkinson-bahia-core' ) : '' ); ?>">
								<p class="description"><?php esc_html_e( 'En Cloudflare se llama «Secret Key». No se muestra nunca una vez guardada.', 'parkinson-bahia-core' ); ?></p>
								<?php if ( '' !== $settings['secret_key'] ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[secret_key_clear]" value="1"> <?php esc_html_e( 'Borrar la clave secreta guardada', 'parkinson-bahia-core' ); ?></label>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
