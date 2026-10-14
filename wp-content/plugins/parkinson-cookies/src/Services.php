<?php
/**
 * Registro de servicios que guardan algo en el navegador de quien visita.
 *
 * Cada tema o plugin declara los suyos con el filtro
 * «parkinson_cookies/services». Con esa lista se escriben el aviso, la
 * ventana de configuración y la tabla de la política de cookies, así que lo
 * que se cuenta y lo que se carga no se pueden desincronizar.
 *
 * Un servicio es un array con:
 * - id:       identificador corto (letras, números y guiones).
 * - category: «necesarias», «externos» o «analiticas».
 * - name:     nombre del servicio («YouTube»).
 * - provider: quién lo pone («Google Ireland Ltd.» o «Esta web»).
 * - purpose:  para qué sirve, en lenguaje llano.
 * - cookies:  nombres de las cookies o del almacenamiento local.
 * - duration: cuánto duran.
 * - policy:   enlace a la política de privacidad del proveedor (opcional).
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

namespace DanielLucia\Cookies;

defined( 'ABSPATH' ) || exit;

final class Services {

	public const COOKIE  = 'pbc_cookies';
	public const MONTHS  = 12;
	public const REQUIRED = 'necesarias';

	/** @var array<int, array<string, string>>|null */
	private ?array $cache = null;

	public function __construct( private readonly Settings $settings ) {}

	/**
	 * Categorías en el orden en que se muestran.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	public function categories(): array {
		return [
			self::REQUIRED => [
				'label'       => __( 'Necesarias', 'parkinson-cookies' ),
				'description' => __( 'Hacen que la web funcione y recuerdan lo que tú eliges, como el tamaño del texto o esta misma decisión sobre las cookies. No se pueden desactivar y no sirven para seguirte.', 'parkinson-cookies' ),
			],
			'externos'     => [
				'label'       => __( 'Contenido de otras webs', 'parkinson-cookies' ),
				'description' => __( 'Vídeos, mapas y otros contenidos que se cargan desde otras webs. Esas webs reciben tu dirección IP y pueden guardar sus propias cookies.', 'parkinson-cookies' ),
			],
			'analiticas'   => [
				'label'       => __( 'Medición de visitas', 'parkinson-cookies' ),
				'description' => __( 'Cuentan cuántas personas visitan la web y qué páginas leen, para mejorarla. No se usan para publicidad.', 'parkinson-cookies' ),
			],
		];
	}

	/**
	 * Todos los servicios declarados, limpios y con su categoría válida.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		/**
		 * Servicios que guardan algo en el navegador de quien visita.
		 *
		 * @param array<int, array<string, string>> $services Servicios declarados.
		 */
		$declared   = (array) apply_filters( 'parkinson_cookies/services', $this->defaults() );
		$categories = $this->categories();
		$clean      = [];

		foreach ( $declared as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$id       = sanitize_key( (string) ( $service['id'] ?? '' ) );
			$category = (string) ( $service['category'] ?? '' );

			if ( '' === $id || ! isset( $categories[ $category ] ) ) {
				continue;
			}

			$clean[ $id ] = [
				'id'       => $id,
				'category' => $category,
				'name'     => (string) ( $service['name'] ?? $id ),
				'provider' => (string) ( $service['provider'] ?? '' ),
				'purpose'  => (string) ( $service['purpose'] ?? '' ),
				'cookies'  => (string) ( $service['cookies'] ?? '' ),
				'duration' => (string) ( $service['duration'] ?? '' ),
				'policy'   => esc_url_raw( (string) ( $service['policy'] ?? '' ) ),
			];
		}

		$this->cache = array_values( $clean );

		return $this->cache;
	}

	/**
	 * Servicios de una categoría.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function in_category( string $category ): array {
		return array_values( array_filter( $this->all(), static fn ( array $s ): bool => $s['category'] === $category ) );
	}

	/**
	 * Categorías que necesitan permiso y tienen al menos un servicio.
	 *
	 * @return string[]
	 */
	public function optional_categories(): array {
		$used = array_unique( array_column( $this->all(), 'category' ) );

		return array_values(
			array_filter(
				array_keys( $this->categories() ),
				static fn ( string $category ): bool => self::REQUIRED !== $category && in_array( $category, $used, true )
			)
		);
	}

	/**
	 * Versión del consentimiento: cambia si se añade o quita un servicio
	 * opcional o si se sube la revisión en los ajustes. Una elección guardada
	 * con otra versión ya no vale y se vuelve a preguntar.
	 */
	public function version(): string {
		$optional = array_column(
			array_filter( $this->all(), static fn ( array $s ): bool => self::REQUIRED !== $s['category'] ),
			'id'
		);
		sort( $optional );

		return substr( md5( (string) wp_json_encode( [ $optional, $this->settings->revision() ] ) ), 0, 8 );
	}

	/**
	 * Servicios que trae el propio plugin: la cookie que guarda la elección y
	 * las de inicio de sesión de WordPress.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function defaults(): array {
		return [
			[
				'id'       => 'eleccion-cookies',
				'category' => self::REQUIRED,
				'name'     => __( 'Tu elección sobre las cookies', 'parkinson-cookies' ),
				'provider' => __( 'Esta web', 'parkinson-cookies' ),
				'purpose'  => __( 'Recuerda si has aceptado o rechazado cada tipo de cookie, para no volver a preguntarte en cada página.', 'parkinson-cookies' ),
				'cookies'  => self::COOKIE,
				/* translators: %d: número de meses. */
				'duration' => sprintf( _n( '%d mes', '%d meses', self::MONTHS, 'parkinson-cookies' ), self::MONTHS ),
			],
			[
				'id'       => 'wordpress',
				'category' => self::REQUIRED,
				'name'     => __( 'Acceso de quien gestiona la web', 'parkinson-cookies' ),
				'provider' => __( 'Esta web (WordPress)', 'parkinson-cookies' ),
				'purpose'  => __( 'Solo para las personas de la asociación que entran a gestionar la web: mantienen abierta su sesión. A quien solo visita la web no se le guardan.', 'parkinson-cookies' ),
				'cookies'  => 'wordpress_logged_in_*, wordpress_sec_*, wordpress_test_cookie, wp-settings-*',
				'duration' => __( 'Hasta que se cierra la sesión (o 14 días si se marca «Recuérdame»); las de ajustes, 1 año', 'parkinson-cookies' ),
			],
		];
	}
}
