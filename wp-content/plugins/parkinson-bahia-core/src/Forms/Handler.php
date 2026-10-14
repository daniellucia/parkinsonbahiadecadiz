<?php
/**
 * Recibe los envíos de los formularios públicos.
 *
 * Protección sin acertijos: campo trampa, marca de tiempo firmada, comprobación
 * de origen, límite de envíos por dirección IP y, si está configurado en
 * Formularios → Ajustes, Cloudflare Turnstile. Valida en el servidor, guarda
 * el envío como contenido privado (datos sensibles cifrados) y avisa por correo
 * sin incluir datos sensibles.
 *
 * Responde en JSON a las peticiones hechas con fetch y con una redirección a
 * las hechas sin JavaScript.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Forms;

defined( 'ABSPATH' ) || exit;

final class Handler {

	public const ACTION        = 'pbc_form';
	public const SENT_QUERY    = 'enviado';
	private const MIN_SECONDS  = 3;
	private const MAX_AGE      = WEEK_IN_SECONDS;
	private const RATE_LIMIT   = 6;
	private const TRY_LIMIT    = 40;
	private const RATE_WINDOW  = 10 * MINUTE_IN_SECONDS;
	private const MAX_AMOUNT   = 1000;
	private const MAX_CHOICES  = 12;

	public function register(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, [ $this, 'handle' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * Marca de tiempo firmada que acompaña a cada formulario.
	 *
	 * No es un nonce de WordPress a propósito: las páginas públicas se guardan
	 * en caché y un nonce caducaría dentro del HTML guardado.
	 */
	public static function token( string $type ): string {
		$time = (string) time();

		return $time . '.' . hash_hmac( 'sha256', $type . '|' . $time, wp_salt( 'nonce' ) );
	}

	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Formulario público: se protege con marca de tiempo firmada, campo trampa, origen y límite de envíos (ver la cabecera de la clase).
		$type   = isset( $_POST['pbc_type'] ) ? sanitize_key( wp_unslash( $_POST['pbc_type'] ) ) : '';
		$fields = Definitions::fields( $type );

		if ( ! $fields ) {
			$this->fail( [ '_form' => __( 'No reconocemos este formulario. Recarga la página e inténtalo de nuevo.', 'parkinson-bahia-core' ) ], 400 );
		}

		if ( ! $this->same_origin() || ! $this->valid_token( $type, isset( $_POST['pbc_token'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_token'] ) ) : '' ) ) {
			$this->fail( [ '_form' => __( 'No hemos podido comprobar el envío. Recarga la página e inténtalo de nuevo.', 'parkinson-bahia-core' ) ], 400 );
		}

		// Campo trampa: las personas no lo ven; si viene relleno es un robot. Se responde como si todo hubiera ido bien.
		if ( '' !== trim( isset( $_POST['pbc_web'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_web'] ) ) : '' ) ) {
			$this->succeed( $type );
		}

		if ( Settings::turnstile_active() ) {
			$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
			$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			if ( ! Settings::verify_turnstile( $response, $ip ) ) {
				$this->fail( [ '_form' => __( 'No hemos podido comprobar que eres una persona. Espera a que termine la comprobación que aparece encima del botón y vuelve a enviar. Si no aparece, llámanos por teléfono.', 'parkinson-bahia-core' ) ], 400 );
			}
		}

		// Tope alto de intentos (alguien que corrige errores envía varias veces) y tope bajo de envíos guardados.
		if ( ! $this->within_rate_limit( 'try', self::TRY_LIMIT, true ) || ! $this->within_rate_limit( 'ok', self::RATE_LIMIT, false ) ) {
			$this->fail( [ '_form' => __( 'Hemos recibido varios envíos seguidos desde tu conexión. Espera unos minutos o llámanos por teléfono.', 'parkinson-bahia-core' ) ], 429 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cada valor se sanea según su tipo en validate().
		$input = isset( $_POST['pbc'] ) && is_array( $_POST['pbc'] ) ? wp_unslash( $_POST['pbc'] ) : [];

		[ $data, $secrets, $errors ] = $this->validate( $fields, $input );

		if ( empty( $_POST['pbc_consent'] ) ) {
			$errors['consent'] = __( 'Para enviar el formulario tienes que aceptar la política de privacidad.', 'parkinson-bahia-core' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( $errors ) {
			$this->fail( $errors, 422 );
		}

		$post_id = Submissions::store( $type, $data, $secrets );

		if ( 0 === $post_id ) {
			$this->fail( [ '_form' => __( 'No hemos podido guardar tus datos. Inténtalo de nuevo o llámanos por teléfono.', 'parkinson-bahia-core' ) ], 500 );
		}

		$this->within_rate_limit( 'ok', self::RATE_LIMIT, true );
		$this->notify( $type, $data, $post_id );
		$this->succeed( $type );
	}

	/**
	 * @param array<string, array<string, mixed>> $fields Campos del formulario.
	 * @param array<string, mixed>                $input  Valores recibidos.
	 * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, string>} Datos, datos sensibles y errores.
	 */
	private function validate( array $fields, array $input ): array {
		$data    = [];
		$secrets = [];
		$errors  = [];

		foreach ( $fields as $name => $field ) {
			$raw      = $input[ $name ] ?? '';
			$required = ! empty( $field['required'] );
			$value    = is_scalar( $raw ) ? trim( sanitize_text_field( (string) $raw ) ) : '';

			switch ( $field['type'] ) {
				case 'select':
					$options = array_map( 'strval', (array) ( $field['options'] ?? [] ) );
					if ( '' === $value && ! $required ) {
						$data[ $name ] = '';
					} elseif ( ! in_array( $value, $options, true ) ) {
						$errors[ $name ] = __( 'Elige una de las opciones de la lista.', 'parkinson-bahia-core' );
					} else {
						$data[ $name ] = $value;
					}
					break;

				case 'textarea':
					$text = is_scalar( $raw ) ? trim( sanitize_textarea_field( (string) $raw ) ) : '';
					if ( $required && mb_strlen( $text ) < 2 ) {
						/* translators: %s: nombre del campo. */
						$errors[ $name ] = sprintf( __( 'Rellena el campo «%s».', 'parkinson-bahia-core' ), $field['label'] );
					} elseif ( mb_strlen( $text ) > Definitions::TEXTAREA_MAX ) {
						/* translators: %d: número máximo de caracteres. */
						$errors[ $name ] = sprintf( __( 'El mensaje es demasiado largo. Escribe como mucho %d caracteres o llámanos por teléfono.', 'parkinson-bahia-core' ), Definitions::TEXTAREA_MAX );
					} else {
						$data[ $name ] = $text;
					}
					break;

				case 'amount':
					if ( 'otra' === $value ) {
						$other = $input[ $name . '_otra' ] ?? '';
						$value = is_scalar( $other ) ? trim( sanitize_text_field( (string) $other ) ) : '';
					}
					$amount = (int) round( (float) str_replace( ',', '.', $value ) );
					if ( $amount < 1 || $amount > self::MAX_AMOUNT ) {
						$errors[ $name ] = __( 'Elige una cantidad o escribe otra entre 1 y 1000 euros.', 'parkinson-bahia-core' );
					} else {
						$data[ $name ] = $amount;
					}
					break;

				case 'choices':
					$choices = array_slice( array_filter( array_map( static fn ( $choice ): string => is_scalar( $choice ) ? mb_substr( sanitize_text_field( (string) $choice ), 0, 80 ) : '', is_array( $raw ) ? $raw : [] ) ), 0, self::MAX_CHOICES );
					if ( $required && ! $choices ) {
						$errors[ $name ] = __( 'Marca al menos una opción.', 'parkinson-bahia-core' );
					}
					$data[ $name ] = array_values( $choices );
					break;

				case 'email':
					$email = sanitize_email( $value );
					if ( '' === $value && $required ) {
						$errors[ $name ] = __( 'Escribe tu correo electrónico.', 'parkinson-bahia-core' );
					} elseif ( '' !== $value && ! is_email( $email ) ) {
						$errors[ $name ] = __( 'El correo no parece correcto. Debe tener esta forma: nombre@ejemplo.com', 'parkinson-bahia-core' );
					} else {
						$data[ $name ] = $email;
					}
					break;

				case 'tel':
					$digits = (string) preg_replace( '/\D+/', '', $value );
					if ( strlen( $digits ) < 9 || strlen( $digits ) > 15 ) {
						$errors[ $name ] = __( 'Escribe un teléfono de 9 cifras, por ejemplo 956 59 19 28.', 'parkinson-bahia-core' );
					} else {
						$data[ $name ] = mb_substr( $value, 0, 30 );
					}
					break;

				case 'dni':
					if ( ! Definitions::is_valid_dni( $value ) ) {
						$errors[ $name ] = __( 'El DNI o NIE no es correcto. Escribe los números y la letra, por ejemplo 12345678Z.', 'parkinson-bahia-core' );
					} else {
						$secrets[ $name ] = strtoupper( (string) preg_replace( '/[\s.\-]/', '', $value ) );
					}
					break;

				case 'iban':
					if ( ! Definitions::is_valid_iban( $value ) ) {
						$errors[ $name ] = __( 'El número de cuenta no es correcto. Empieza por ES y tiene 24 caracteres; lo encuentras en tu libreta o en la app de tu banco.', 'parkinson-bahia-core' );
					} else {
						$secrets[ $name ] = strtoupper( (string) preg_replace( '/\s+/', '', $value ) );
					}
					break;

				default:
					if ( $required && mb_strlen( $value ) < 2 ) {
						/* translators: %s: nombre del campo. */
						$errors[ $name ] = sprintf( __( 'Rellena el campo «%s».', 'parkinson-bahia-core' ), $field['label'] );
					} else {
						$data[ $name ] = mb_substr( $value, 0, 120 );
					}
			}
		}

		return [ $data, $secrets, $errors ];
	}

	private function valid_token( string $type, string $token ): bool {
		[ $time, $signature ] = array_pad( explode( '.', $token, 2 ), 2, '' );
		$age                  = time() - (int) $time;

		return ctype_digit( $time )
			&& hash_equals( hash_hmac( 'sha256', $type . '|' . $time, wp_salt( 'nonce' ) ), $signature )
			&& $age >= self::MIN_SECONDS
			&& $age <= self::MAX_AGE;
	}

	/**
	 * Si el navegador indica el origen, tiene que ser este sitio.
	 */
	private function same_origin(): bool {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

		if ( '' === $origin ) {
			return true;
		}

		return strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Límite amplio a propósito: un centro o una residencia comparten una misma IP.
	 * La IP no se guarda: solo un resumen irreversible durante unos minutos.
	 */
	private function within_rate_limit( string $bucket, int $limit, bool $count ): bool {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'pbc_form_' . $bucket . '_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 32 );
		$tally = (int) get_transient( $key );

		if ( $tally >= $limit ) {
			return false;
		}

		if ( $count ) {
			set_transient( $key, $tally + 1, self::RATE_WINDOW );
		}

		return true;
	}

	/**
	 * Aviso por correo a la asociación. Sin DNI ni IBAN: para verlos hay que entrar al escritorio.
	 *
	 * @param array<string, mixed> $data Datos no sensibles.
	 */
	private function notify( string $type, array $data, int $post_id ): void {
		/**
		 * Dirección que recibe los avisos de formularios.
		 *
		 * @param string $email La de Formularios → Ajustes o, si está vacía, la de administración.
		 * @param string $type  Tipo de formulario.
		 */
		$to = sanitize_email( (string) apply_filters( 'parkinson_bahia/forms_notification_email', Settings::notification_email(), $type ) );

		if ( ! is_email( $to ) ) {
			return;
		}

		$name    = Definitions::types()[ $type ] ?? $type;
		$lines   = [];
		$lines[] = sprintf( 'Nuevo formulario recibido: %s', $name );
		$lines[] = '';
		$lines[] = 'Nombre: ' . str_replace( [ "\r", "\n" ], ' ', (string) ( $data['nombre'] ?? '' ) );
		$lines[] = 'Teléfono: ' . str_replace( [ "\r", "\n" ], ' ', (string) ( $data['tel'] ?? '' ) );
		$lines[] = '';
		$lines[] = 'El resto de los datos está en el escritorio de la web:';
		$lines[] = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		wp_mail( $to, sprintf( '[%s] Nuevo formulario: %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $name ), implode( "\n", $lines ) );
	}

	private function wants_json(): bool {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';

		return str_contains( $accept, 'application/json' );
	}

	/**
	 * @param array<string, string> $errors Campo => mensaje.
	 * @return never
	 */
	private function fail( array $errors, int $status ): void {
		if ( $this->wants_json() ) {
			wp_send_json(
				[
					'ok'     => false,
					'errors' => $errors,
				],
				$status
			);
		}

		$items = '';
		foreach ( $errors as $message ) {
			$items .= '<li>' . esc_html( $message ) . '</li>';
		}

		wp_die(
			'<h1>' . esc_html__( 'Falta algo por corregir', 'parkinson-bahia-core' ) . '</h1><ul>' . $items . '</ul><p>' . esc_html__( 'Vuelve atrás con el navegador: lo que habías escrito sigue ahí.', 'parkinson-bahia-core' ) . '</p>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cada parte está escapada arriba.
			esc_html__( 'Falta algo por corregir', 'parkinson-bahia-core' ),
			[
				'response'  => $status, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Código de estado HTTP, no salida.
				'back_link' => true,
			]
		);
	}

	/**
	 * @return never
	 */
	private function succeed( string $type ): void {
		if ( $this->wants_json() ) {
			wp_send_json( [ 'ok' => true ] );
		}

		$back = wp_get_referer();
		$back = $back ? $back : home_url( '/' );

		wp_safe_redirect( add_query_arg( self::SENT_QUERY, rawurlencode( $type ), remove_query_arg( self::SENT_QUERY, $back ) ) . '#pbc-form-' . $type, 303 );
		exit;
	}
}
