<?php
/**
 * Widget «Formulario»: texto a un lado y un formulario del sitio al otro.
 *
 * Los campos de cada formulario están definidos en Forms\Definitions; aquí se
 * eligen el formulario y sus textos. El envío lo recibe Forms\Handler.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\Forms\Definitions;
use DanielLucia\ParkinsonBahia\Core\Forms\Handler;
use DanielLucia\ParkinsonBahia\Core\Forms\Settings;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Form extends Widget {

	protected function slug(): string {
		return 'form';
	}

	public function get_title(): string {
		return esc_html__( 'Formulario', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Los envíos se guardan en «Formularios» del escritorio. La protección contra robots y el correo de avisos se configuran una sola vez en «Formularios → Ajustes».', 'parkinson-bahia-core' ) );
		$this->add_control(
			'form',
			[
				'label'   => esc_html__( 'Formulario', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => Definitions::types(),
				'default' => Definitions::MEMBER,
			]
		);
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Escribe aquí el título', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->add_control(
			'text',
			[
				'label'       => esc_html__( 'Texto', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Deja una línea en blanco para separar párrafos.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
			]
		);
		$this->image_control( 'image', esc_html__( 'Foto (opcional)', 'parkinson-bahia-core' ) );
		$this->add_control(
			'phone',
			[
				'label'       => esc_html__( 'Teléfono en grande (opcional)', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Se muestra bajo el texto y se puede pulsar para llamar.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'variant',
			[
				'label'   => esc_html__( 'Fondo', 'parkinson-bahia-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => esc_html__( 'Claro', 'parkinson-bahia-core' ),
					'sand' => esc_html__( 'Arena', 'parkinson-bahia-core' ),
				],
				'default' => '',
			]
		);
		$this->add_control(
			'breadcrumb',
			[
				'label'       => esc_html__( 'Mostrar las migas de pan', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Actívalo cuando el formulario sea lo primero de la página; ponle entonces el título H1.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SWITCHER,
			]
		);
		$this->sticky_control();
		$this->anchor_control();
		$this->end_controls_section();

		$this->start_controls_section( 'form_section', [ 'label' => esc_html__( 'Textos del formulario', 'parkinson-bahia-core' ) ] );
		$this->add_control(
			'amounts',
			[
				'label'       => esc_html__( 'Cantidades (euros al mes)', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Separadas por comas, por ejemplo: 5, 10, 20. Siempre se añade «Otra cantidad».', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '5, 10, 20',
				'condition'   => [ 'form' => Definitions::MEMBER ],
			]
		);
		$this->add_control(
			'areas',
			[
				'label'       => esc_html__( 'Opciones para ayudar', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Una por línea.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXTAREA,
				'condition'   => [ 'form' => Definitions::VOLUNTEER ],
			]
		);
		$this->text_control( 'submit_label', esc_html__( 'Texto del botón', 'parkinson-bahia-core' ), esc_html__( 'Enviar', 'parkinson-bahia-core' ) );
		$this->text_control( 'success_title', esc_html__( 'Mensaje al enviar: título', 'parkinson-bahia-core' ), esc_html__( '¡Gracias!', 'parkinson-bahia-core' ) );
		$this->text_control( 'success_text', esc_html__( 'Mensaje al enviar: texto', 'parkinson-bahia-core' ), esc_html__( 'Hemos recibido tus datos. Te llamaremos pronto.', 'parkinson-bahia-core' ), true );
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$type     = array_key_exists( (string) ( $settings['form'] ?? '' ), Definitions::types() ) ? (string) $settings['form'] : Definitions::MEMBER;
		$fields   = Definitions::fields( $type );
		$title_id = $this->title_id();
		$prefix   = 'pbc-' . $type . '-' . $this->get_id();
		$image    = $this->image_html(
			$settings['image'] ?? null,
			'pbc-feature',
			[
				'class'   => 'pbc-form-block__image',
				'sizes'   => '(min-width: 900px) 660px, 100vw',
				'loading' => 'lazy',
			]
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo decide qué mensaje se muestra tras enviar sin JavaScript.
		$sent     = isset( $_GET[ Handler::SENT_QUERY ] ) && sanitize_key( wp_unslash( $_GET[ Handler::SENT_QUERY ] ) ) === $type;
		// La dirección de envío va sin dominio: así siempre es del mismo origen que la página,
		// aunque un proxy sirva la web por https y WordPress crea que es http.
		$privacy  = get_privacy_policy_url();
		$tiles    = [ 'cuota', 'areas' ];
		$phone    = trim( (string) ( $settings['phone'] ?? '' ) );
		$digits   = (string) preg_replace( '/\D+/', '', $phone );
		$variant  = 'sand' === ( $settings['variant'] ?? '' ) ? ' pbc-form-block--sand' : '';
		$crumbs   = 'yes' === ( $settings['breadcrumb'] ?? '' );
		?>
		<section class="pbc-form-block<?php echo esc_attr( $variant . ( $crumbs ? ' pbc-form-block--page' : '' ) . ( $this->is_sticky( $settings ) ? ' pbc-form-block--sticky' : '' ) ); ?>"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<?php if ( $crumbs ) : ?>
				<div class="pbc-form-block__crumbs"><?php $this->render_breadcrumb(); ?></div>
			<?php endif; ?>
			<div class="pbc-form-block__inner">
				<div class="pbc-form-block__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-form-block__title', $title_id ); ?>
					<?php foreach ( $this->paragraphs( (string) $settings['text'] ) as $paragraph ) : ?>
						<?php $this->render_text( $paragraph, 'pbc-form-block__text' ); ?>
					<?php endforeach; ?>
					<?php if ( strlen( $digits ) >= 9 ) : ?>
						<a class="pbc-form-block__phone" href="<?php echo esc_url( 'tel:' . ( 9 === strlen( $digits ) ? '+34' : '+' ) . $digits ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php endif; ?>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado por wp_get_attachment_image(). ?>
				</div>

				<div class="pbc-form-block__form" id="<?php echo esc_attr( 'pbc-form-' . $type ); ?>">
					<div class="pbc-form__done" role="status" tabindex="-1" data-pbc-form-done<?php echo $sent ? '' : ' hidden'; ?>>
						<strong class="pbc-form__done-title"><?php echo esc_html( $settings['success_title'] ); ?></strong>
						<span class="pbc-form__done-text"><?php echo esc_html( $settings['success_text'] ); ?></span>
					</div>

					<?php if ( ! $sent ) : ?>
						<form class="pbc-form" method="post" action="<?php echo esc_url( wp_make_link_relative( admin_url( 'admin-post.php' ) ) ); ?>" data-pbc-form<?php echo Settings::turnstile_active() ? ' data-pbc-turnstile="' . esc_attr( Settings::site_key() ) . '"' : ''; ?>>
							<input type="hidden" name="action" value="<?php echo esc_attr( Handler::ACTION ); ?>">
							<input type="hidden" name="pbc_type" value="<?php echo esc_attr( $type ); ?>">
							<input type="hidden" name="pbc_token" value="<?php echo esc_attr( Handler::token( $type ) ); ?>">

							<?php // Campo trampa para robots: fuera de la vista y del orden de tabulación. ?>
							<div class="pbc-form__trap" aria-hidden="true">
								<label><?php esc_html_e( 'No rellenar este campo', 'parkinson-bahia-core' ); ?> <input type="text" name="pbc_web" tabindex="-1" autocomplete="off"></label>
							</div>

							<div class="pbc-form__summary" role="alert" tabindex="-1" data-pbc-form-summary hidden></div>

							<div class="pbc-form__grid">
								<?php foreach ( $fields as $name => $field ) : ?>
									<?php
									$id       = $prefix . '-' . $name;
									$required = ! empty( $field['required'] );
									$error_id = $id . '-error';
									$help_id  = $id . '-help';
									$wide     = ! empty( $field['wide'] ) || in_array( $name, $tiles, true );
									?>
									<div class="pbc-form__field<?php echo $wide ? ' pbc-form__field--wide' : ''; ?>" data-pbc-field="<?php echo esc_attr( $name ); ?>">
										<?php if ( 'amount' === $field['type'] ) : ?>
											<?php
											$amounts = array_slice( array_filter( array_map( 'absint', explode( ',', (string) ( $settings['amounts'] ?? '' ) ) ) ), 0, 6 );
											$default = $amounts[ (int) floor( ( count( $amounts ) - 1 ) / 2 ) ] ?? 0;
											?>
											<fieldset class="pbc-form__group" aria-describedby="<?php echo esc_attr( $error_id ); ?>">
												<legend class="pbc-form__label"><?php echo esc_html( $field['label'] ); ?></legend>
												<div class="pbc-form__options">
													<?php foreach ( $amounts as $amount ) : ?>
														<label class="pbc-form__option">
															<input class="pbc-form__check" type="radio" name="pbc[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( (string) $amount ); ?>" <?php checked( $amount, $default ); ?>>
															<?php
															/* translators: %d: cantidad en euros. */
															echo esc_html( sprintf( __( '%d € al mes', 'parkinson-bahia-core' ), $amount ) );
															?>
														</label>
													<?php endforeach; ?>
													<label class="pbc-form__option">
														<input class="pbc-form__check" type="radio" name="pbc[<?php echo esc_attr( $name ); ?>]" value="otra" data-pbc-other-radio <?php checked( ! $amounts ); ?>>
														<?php esc_html_e( 'Otra cantidad', 'parkinson-bahia-core' ); ?>
													</label>
												</div>
												<?php // Solo se ve al elegir «Otra cantidad» (CSS con :has; form.js lleva el foco al campo). ?>
												<div class="pbc-form__other">
													<label class="pbc-form__label pbc-form__label--small" for="<?php echo esc_attr( $id . '-otra' ); ?>"><?php esc_html_e( 'Escribe la cantidad, en euros al mes', 'parkinson-bahia-core' ); ?></label>
													<input class="pbc-form__input" type="number" inputmode="numeric" min="1" max="1000" step="1" id="<?php echo esc_attr( $id . '-otra' ); ?>" name="pbc[<?php echo esc_attr( $name ); ?>_otra]" data-pbc-other-input>
												</div>
											</fieldset>
										<?php elseif ( 'choices' === $field['type'] ) : ?>
											<?php $choices = array_slice( array_filter( array_map( 'trim', explode( "\n", (string) ( $settings['areas'] ?? '' ) ) ) ), 0, 12 ); ?>
											<?php if ( $choices ) : ?>
												<fieldset class="pbc-form__group" aria-describedby="<?php echo esc_attr( $error_id ); ?>">
													<legend class="pbc-form__label">
														<?php echo esc_html( $field['label'] ); ?>
														<?php if ( ! empty( $field['hint'] ) ) : ?>
															<span class="pbc-form__optional"><?php echo esc_html( $field['hint'] ); ?></span>
														<?php endif; ?>
													</legend>
													<div class="pbc-form__options pbc-form__options--stack">
														<?php foreach ( $choices as $choice ) : ?>
															<label class="pbc-form__option pbc-form__option--plain">
																<input class="pbc-form__check" type="checkbox" name="pbc[<?php echo esc_attr( $name ); ?>][]" value="<?php echo esc_attr( $choice ); ?>">
																<?php echo esc_html( $choice ); ?>
															</label>
														<?php endforeach; ?>
													</div>
												</fieldset>
											<?php endif; ?>
										<?php elseif ( in_array( $field['type'], [ 'select', 'textarea' ], true ) ) : ?>
											<label class="pbc-form__label" for="<?php echo esc_attr( $id ); ?>">
												<?php echo esc_html( $field['label'] ); ?>
												<?php if ( ! $required ) : ?>
													<span class="pbc-form__optional"><?php esc_html_e( '(opcional)', 'parkinson-bahia-core' ); ?></span>
												<?php endif; ?>
											</label>
											<?php $described = trim( ( ! empty( $field['help'] ) ? $help_id . ' ' : '' ) . $error_id ); ?>
											<?php if ( 'select' === $field['type'] ) : ?>
												<select class="pbc-form__input pbc-form__select" id="<?php echo esc_attr( $id ); ?>" name="pbc[<?php echo esc_attr( $name ); ?>]" aria-describedby="<?php echo esc_attr( $described ); ?>"<?php echo $required ? ' required' : ''; ?>>
													<?php foreach ( (array) ( $field['options'] ?? [] ) as $option ) : ?>
														<option value="<?php echo esc_attr( (string) $option ); ?>"><?php echo esc_html( (string) $option ); ?></option>
													<?php endforeach; ?>
												</select>
											<?php else : ?>
												<textarea class="pbc-form__input pbc-form__textarea" id="<?php echo esc_attr( $id ); ?>" name="pbc[<?php echo esc_attr( $name ); ?>]" rows="4" maxlength="<?php echo esc_attr( (string) Definitions::TEXTAREA_MAX ); ?>" aria-describedby="<?php echo esc_attr( $described ); ?>"<?php echo $required ? ' required' : ''; ?>></textarea>
											<?php endif; ?>
											<?php if ( ! empty( $field['help'] ) ) : ?>
												<span class="pbc-form__help" id="<?php echo esc_attr( $help_id ); ?>"><?php echo esc_html( $field['help'] ); ?></span>
											<?php endif; ?>
										<?php else : ?>
											<label class="pbc-form__label" for="<?php echo esc_attr( $id ); ?>">
												<?php echo esc_html( $field['label'] ); ?>
												<?php if ( ! $required ) : ?>
													<span class="pbc-form__optional"><?php esc_html_e( '(opcional)', 'parkinson-bahia-core' ); ?></span>
												<?php endif; ?>
											</label>
											<input
												class="pbc-form__input"
												type="<?php echo esc_attr( in_array( $field['type'], [ 'tel', 'email' ], true ) ? $field['type'] : 'text' ); ?>"
												id="<?php echo esc_attr( $id ); ?>"
												name="pbc[<?php echo esc_attr( $name ); ?>]"
												aria-describedby="<?php echo esc_attr( trim( ( ! empty( $field['help'] ) ? $help_id . ' ' : '' ) . $error_id ) ); ?>"
												<?php echo $required ? 'required' : ''; ?>
												<?php echo ! empty( $field['autocomplete'] ) ? 'autocomplete="' . esc_attr( $field['autocomplete'] ) . '"' : 'autocomplete="off"'; ?>
												<?php echo ! empty( $field['placeholder'] ) ? 'placeholder="' . esc_attr( $field['placeholder'] ) . '"' : ''; ?>
												<?php echo in_array( $field['type'], [ 'dni', 'iban' ], true ) ? 'autocapitalize="characters" spellcheck="false"' : ''; ?>
											>
											<?php if ( ! empty( $field['help'] ) ) : ?>
												<span class="pbc-form__help" id="<?php echo esc_attr( $help_id ); ?>"><?php echo esc_html( $field['help'] ); ?></span>
											<?php endif; ?>
										<?php endif; ?>
										<p class="pbc-form__error" id="<?php echo esc_attr( $error_id ); ?>" data-pbc-error hidden></p>
									</div>
								<?php endforeach; ?>
							</div>

							<div class="pbc-form__field" data-pbc-field="consent">
								<label class="pbc-form__consent" for="<?php echo esc_attr( $prefix . '-consent' ); ?>">
									<input class="pbc-form__check pbc-form__check--large" type="checkbox" id="<?php echo esc_attr( $prefix . '-consent' ); ?>" name="pbc_consent" value="1" required aria-describedby="<?php echo esc_attr( $prefix . '-consent-error' ); ?>">
									<span>
										<?php if ( $privacy ) : ?>
											<?php
											printf(
												/* translators: %s: enlace a la política de privacidad. */
												esc_html__( 'He leído y acepto la %s.', 'parkinson-bahia-core' ),
												// Se abre en otra pestaña para no perder lo que la persona ya ha escrito; se avisa en el propio enlace.
												'<a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'política de privacidad', 'parkinson-bahia-core' ) . ' <span class="pbc-form__optional">' . esc_html__( '(se abre en una pestaña nueva)', 'parkinson-bahia-core' ) . '</span></a>'
											);
											?>
										<?php else : ?>
											<?php esc_html_e( 'He leído y acepto la política de privacidad.', 'parkinson-bahia-core' ); ?>
										<?php endif; ?>
									</span>
								</label>
								<p class="pbc-form__error" id="<?php echo esc_attr( $prefix . '-consent-error' ); ?>" data-pbc-error hidden></p>
							</div>

							<p class="pbc-form__legal">
								<?php
								echo esc_html( Definitions::legal_notice( $type ) );
								if ( $privacy ) {
									echo ' ' . esc_html__( 'Tienes toda la información en la política de privacidad.', 'parkinson-bahia-core' );
								}
								?>
							</p>

							<?php if ( Settings::turnstile_active() ) : ?>
								<div class="pbc-form__turnstile" data-pbc-turnstile-box></div>
								<noscript><p class="pbc-form__help"><?php esc_html_e( 'Para enviar este formulario hace falta tener JavaScript activado. Si lo prefieres, llámanos por teléfono.', 'parkinson-bahia-core' ); ?></p></noscript>
							<?php endif; ?>

							<button class="pbc-button pbc-button--primary pbc-form__submit" type="submit" data-pbc-loading-text="<?php esc_attr_e( 'Enviando…', 'parkinson-bahia-core' ); ?>"><?php echo esc_html( $settings['submit_label'] ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
