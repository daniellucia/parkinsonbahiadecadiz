<?php
/**
 * Página de ajustes: Ajustes → llms.txt.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private const SLUG  = 'parkinson-llms-txt';
	private const GROUP = 'pllms_settings_group';

	public function __construct(
		private readonly Settings $settings,
		private readonly Generator $generator
	) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( PLLMS_FILE ), [ $this, 'action_links' ] );
	}

	public function add_page(): void {
		add_options_page(
			__( 'llms.txt', 'parkinson-llms-txt' ),
			__( 'llms.txt', 'parkinson-llms-txt' ),
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
				'sanitize_callback' => [ $this->settings, 'sanitize' ],
				'default'           => [],
			]
		);
	}

	/**
	 * @param string[] $links Enlaces de la fila del plugin.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ), esc_html__( 'Ajustes', 'parkinson-llms-txt' ) )
		);

		return $links;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		$name     = Settings::OPTION;
		$types    = $this->settings->post_types();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'llms.txt', 'parkinson-llms-txt' ); ?></h1>
			<p>
				<?php esc_html_e( 'llms.txt es un fichero de texto que resume la web para los asistentes de inteligencia artificial: quiénes sois y qué páginas son importantes. Este plugin lo genera solo y lo mantiene al día cada vez que se publica o se cambia un contenido.', 'parkinson-llms-txt' ); ?>
				<a href="https://llmstxt.org/"><?php esc_html_e( 'Más información sobre el formato', 'parkinson-llms-txt' ); ?></a>.
			</p>

			<?php if ( file_exists( ABSPATH . 'llms.txt' ) ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Hay un fichero llms.txt en la carpeta raíz de la web. El servidor mostrará ese fichero en lugar del que genera este plugin; bórralo para que funcione la generación automática.', 'parkinson-llms-txt' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $settings['enabled'] ) : ?>
				<p>
					<strong><?php esc_html_e( 'Direcciones:', 'parkinson-llms-txt' ); ?></strong>
					<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>"><?php echo esc_html( home_url( '/llms.txt' ) ); ?></a>
					<?php if ( $settings['full'] ) : ?>
						· <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>"><?php echo esc_html( home_url( '/llms-full.txt' ) ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2><?php esc_html_e( 'General', 'parkinson-llms-txt' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Estado', 'parkinson-llms-txt' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>> <?php esc_html_e( 'Publicar llms.txt', 'parkinson-llms-txt' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pllms-title"><?php esc_html_e( 'Nombre', 'parkinson-llms-txt' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="pllms-title" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $settings['title'] ); ?>" placeholder="<?php echo esc_attr( $this->settings->title() ); ?>">
							<p class="description"><?php esc_html_e( 'Si lo dejas vacío se usa el título del sitio.', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pllms-summary"><?php esc_html_e( 'Resumen', 'parkinson-llms-txt' ); ?></label></th>
						<td>
							<textarea class="large-text" rows="3" id="pllms-summary" name="<?php echo esc_attr( $name ); ?>[summary]" placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $settings['summary'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Una o dos frases: quiénes sois, a quién atendéis y dónde. Si lo dejas vacío se usa la descripción corta del sitio.', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pllms-details"><?php esc_html_e( 'Información importante', 'parkinson-llms-txt' ); ?></label></th>
						<td>
							<textarea class="large-text code" rows="7" id="pllms-details" name="<?php echo esc_attr( $name ); ?>[details]"><?php echo esc_textarea( $settings['details'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Opcional. Datos que un asistente debe conocer siempre: teléfono, dirección, horario, cómo pedir cita. Admite Markdown (por ejemplo, una lista con guiones).', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Qué contenidos aparecen', 'parkinson-llms-txt' ); ?></h2>
				<p><?php esc_html_e( 'Cada tipo de contenido es una sección del fichero. Solo se incluyen contenidos publicados, sin contraseña y que no estén marcados como «no indexar». Además, cada página o entrada tiene una casilla para dejarla fuera y un campo para escribir su descripción.', 'parkinson-llms-txt' ); ?></p>
				<table class="widefat striped" style="max-width:960px">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Tipo de contenido', 'parkinson-llms-txt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Incluir', 'parkinson-llms-txt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Título de la sección', 'parkinson-llms-txt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Máximo', 'parkinson-llms-txt' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Secundario', 'parkinson-llms-txt' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $types as $type => $object ) : ?>
							<?php
							$section = $settings['sections'][ $type ];
							$field   = $name . '[sections][' . $type . ']';
							$id      = 'pllms-section-' . $type;
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $object->labels->name ); ?></th>
								<td>
									<input type="checkbox" id="<?php echo esc_attr( $id . '-enabled' ); ?>" name="<?php echo esc_attr( $field ); ?>[enabled]" value="1" <?php checked( $section['enabled'] ); ?>>
									<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-enabled' ); ?>"><?php echo esc_html( sprintf( /* translators: %s: tipo de contenido. */ __( 'Incluir %s', 'parkinson-llms-txt' ), $object->labels->name ) ); ?></label>
								</td>
								<td>
									<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-heading' ); ?>"><?php echo esc_html( sprintf( /* translators: %s: tipo de contenido. */ __( 'Título de la sección de %s', 'parkinson-llms-txt' ), $object->labels->name ) ); ?></label>
									<input type="text" class="regular-text" id="<?php echo esc_attr( $id . '-heading' ); ?>" name="<?php echo esc_attr( $field ); ?>[heading]" value="<?php echo esc_attr( $section['heading'] ); ?>">
								</td>
								<td>
									<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-limit' ); ?>"><?php echo esc_html( sprintf( /* translators: %s: tipo de contenido. */ __( 'Número máximo de %s', 'parkinson-llms-txt' ), $object->labels->name ) ); ?></label>
									<input type="number" class="small-text" min="1" max="<?php echo esc_attr( (string) Settings::MAX_LIMIT ); ?>" id="<?php echo esc_attr( $id . '-limit' ); ?>" name="<?php echo esc_attr( $field ); ?>[limit]" value="<?php echo esc_attr( (string) $section['limit'] ); ?>">
								</td>
								<td>
									<input type="checkbox" id="<?php echo esc_attr( $id . '-optional' ); ?>" name="<?php echo esc_attr( $field ); ?>[optional]" value="1" <?php checked( $section['optional'] ); ?>>
									<label class="screen-reader-text" for="<?php echo esc_attr( $id . '-optional' ); ?>"><?php echo esc_html( sprintf( /* translators: %s: tipo de contenido. */ __( 'Marcar %s como secundario', 'parkinson-llms-txt' ), $object->labels->name ) ); ?></label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( '«Secundario» pasa esos enlaces a la sección final «Optional», que los asistentes pueden saltarse si tienen poco espacio. Las noticias se ordenan de la más reciente a la más antigua; el resto, por su campo «Orden».', 'parkinson-llms-txt' ); ?></p>

				<h2><?php esc_html_e( 'Extras', 'parkinson-llms-txt' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Páginas en Markdown', 'parkinson-llms-txt' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[markdown]" value="1" <?php checked( $settings['markdown'] ); ?>> <?php esc_html_e( 'Ofrecer una versión en texto limpio de cada página', 'parkinson-llms-txt' ); ?></label>
							<p class="description"><?php esc_html_e( 'Recomendado. Cada contenido tendrá una copia sin diseño (su dirección terminada en «index.html.md») y los enlaces de llms.txt apuntarán a ella, que es lo que mejor leen los asistentes.', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Fichero completo', 'parkinson-llms-txt' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[full]" value="1" <?php checked( $settings['full'] ); ?>> <?php esc_html_e( 'Publicar también llms-full.txt', 'parkinson-llms-txt' ); ?></label>
							<p class="description"><?php esc_html_e( 'Un único fichero con el texto de todos los contenidos incluidos, para quien quiera leerlo todo de una vez.', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pllms-extra"><?php esc_html_e( 'Secciones añadidas a mano', 'parkinson-llms-txt' ); ?></label></th>
						<td>
							<textarea class="large-text code" rows="7" id="pllms-extra" name="<?php echo esc_attr( $name ); ?>[extra]" placeholder="## Redes sociales&#10;&#10;- [Facebook](https://www.facebook.com/…): novedades y fotos"><?php echo esc_textarea( $settings['extra'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Opcional. Markdown que se añade después de las secciones automáticas: un título con «## » y debajo una lista de enlaces.', 'parkinson-llms-txt' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Así se ve ahora', 'parkinson-llms-txt' ); ?></h2>
			<p><?php esc_html_e( 'Contenido actual de llms.txt con los ajustes guardados.', 'parkinson-llms-txt' ); ?></p>
			<label class="screen-reader-text" for="pllms-preview"><?php esc_html_e( 'Contenido actual de llms.txt', 'parkinson-llms-txt' ); ?></label>
			<textarea class="large-text code" rows="22" id="pllms-preview" readonly><?php echo esc_textarea( $this->generator->llms_txt() ); ?></textarea>
		</div>
		<?php
	}
}
