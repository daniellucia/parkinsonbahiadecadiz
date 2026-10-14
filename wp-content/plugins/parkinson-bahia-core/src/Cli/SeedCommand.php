<?php
/**
 * Comando de WP-CLI que deja montada la portada con el contenido del diseño.
 *
 * Uso: wp pbc seed-home [--force]
 *
 * Es repetible: no duplica páginas, terapias, noticias ni imágenes. La portada
 * solo se reescribe si está vacía o si se pasa --force.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Cli;

use DanielLucia\ParkinsonBahia\Core\Frontend;
use DanielLucia\ParkinsonBahia\Core\PostTypes;
use DanielLucia\ParkinsonBahia\Core\PostTypes\Terapia;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

final class SeedCommand {

	private const SOURCE_META = '_pbc_seed_source';
	private const UPLOADS     = 'https://parkinsonbahiadecadiz.org/wp-content/uploads/';

	/**
	 * Crea la página de inicio con sus widgets y la configura como portada.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la portada aunque ya tenga widgets.
	 *
	 * [--skip-images]
	 * : No descarga las fotos de la web actual.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function home( array $args, array $assoc_args ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			WP_CLI::error( 'Elementor no está activo.' );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No hay ningún usuario administrador.' );
		}
		wp_set_current_user( (int) $admins[0] );

		$with_images = empty( $assoc_args['skip-images'] );

		$pages = $this->seed_pages();
		$this->seed_therapies();
		$this->seed_news( $with_images );
		$this->seed_menu( $pages );

		$home_id = $pages['inicio'];
		$has     = '' !== (string) get_post_meta( $home_id, '_elementor_data', true );

		if ( $has && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La portada ya tiene contenido; no se toca (usa --force para reescribirla).' );
		} else {
			$this->build_home( $home_id, $pages, $with_images );
			WP_CLI::log( 'Portada montada con sus widgets.' );
		}

		// Colores y tipografías los pone el tema, no los estilos por defecto de Elementor.
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
		update_option( 'page_for_posts', $pages['actualidad'] );
		set_theme_mod( 'pbc_cta_url', get_permalink( $pages['colabora'] ) );

		flush_rewrite_rules();

		WP_CLI::success( 'Página de inicio lista: ' . home_url( '/' ) );
	}

	/**
	 * @return array<string, int> slug => ID.
	 */
	private function seed_pages(): array {
		$definitions = [
			'inicio'                => 'Inicio',
			'la-asociacion'         => 'La asociación',
			'centro-de-dia'         => 'Centro de día',
			'terapias'              => 'Terapias',
			'familias-y-cuidadores' => 'Familias y cuidadores',
			'actualidad'            => 'Actualidad',
			'colabora'              => 'Colabora',
			'contacto'              => 'Contacto',
			// Fuera del menú: se llega desde la portada y desde Familias.
			'me-acaban-de-diagnosticar' => 'Me acaban de diagnosticar',
		];

		$ids = [];

		foreach ( $definitions as $slug => $title ) {
			$existing = get_page_by_path( $slug );

			if ( $existing ) {
				$ids[ $slug ] = (int) $existing->ID;
				continue;
			}

			$ids[ $slug ] = (int) wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => in_array( $slug, [ 'inicio', 'actualidad' ], true ) ? '' : '<!-- wp:paragraph --><p>Página en preparación.</p><!-- /wp:paragraph -->',
				]
			);
			WP_CLI::log( "Página creada: {$title}" );
		}

		return $ids;
	}

	private function seed_therapies(): void {
		$therapies = [
			[ 'Fisioterapia', 'Para mantener el equilibrio, la fuerza y la movilidad.', 'El ejercicio es una de las mejores herramientas frente al párkinson. Trabajamos para que puedas seguir moviéndote con seguridad.' ],
			[ 'Logopedia', 'Para la voz, el habla y tragar con seguridad.', 'El párkinson puede hacer que la voz se apague o que cueste tragar. Con ejercicios sencillos se recupera mucho.' ],
			[ 'Psicología', 'Apoyo emocional para la persona y para la familia.', 'Recibir el diagnóstico y convivir con él mueve muchas emociones. Aquí puedes hablar de ellas con calma.' ],
			[ 'Estimulación cognitiva', 'Ejercicios de memoria y atención, también con ordenador.', 'Entrenar la mente también es cuidarse. Ejercicios de memoria, atención y lenguaje, en papel y con ordenador.' ],
			[ 'Musicoterapia', 'La música ayuda a moverse, a recordar y a disfrutar.', 'El ritmo ayuda a caminar, la música despierta recuerdos y cantar en grupo levanta el ánimo.' ],
			[ 'Cineterapia', 'Ver una película juntos y hablar de lo que nos hace sentir.', 'Vemos una película juntos y después hablamos de lo que nos ha hecho sentir y recordar.' ],
			[ 'Trabajo social', 'Ayuda con trámites, dependencia y recursos públicos.', 'Te ayudamos con los papeles para que no tengas que pelearte solo con la administración.' ],
			[ 'Apoyo a familias', 'Grupos de autoayuda y orientación para cuidadores.', 'Cuidar cansa. Las familias y cuidadores también necesitan un espacio para ellos.' ],
		];

		foreach ( $therapies as $order => [ $title, $excerpt, $content ] ) {
			$slug = sanitize_title( $title );

			if ( get_page_by_path( $slug, OBJECT, Terapia::POST_TYPE ) ) {
				continue;
			}

			wp_insert_post(
				[
					'post_type'    => Terapia::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_excerpt' => $excerpt,
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $content ) . '</p><!-- /wp:paragraph -->',
					'menu_order'   => $order + 1,
				]
			);
			WP_CLI::log( "Terapia creada: {$title}" );
		}
	}

	private function seed_news( bool $with_images ): void {
		$news = [
			[
				'title'    => 'Juanma Moreno inaugura el nuevo centro de Párkinson Bahía de Cádiz',
				'date'     => '2026-01-15 12:00:00',
				'category' => 'Institucional',
				'excerpt'  => 'El nuevo edificio de Camposoto duplica las plazas del centro de día y se convierte en un centro de referencia para la atención al párkinson.',
				'image'    => [ '2026/01/d6c265a6-017d-4836-9b33-83fa99872f90_source-aspect-ratio_1600w_0.jpg', 'Acto de inauguración del nuevo centro con autoridades y miembros de la asociación' ],
				'body'     => [
					'El presidente de la Junta de Andalucía, Juanma Moreno, inauguró el 15 de enero de 2026 el nuevo centro de día de Párkinson Bahía de Cádiz, en la calle Milongas de Camposoto, en San Fernando. El centro ya funcionaba desde diciembre de 2025.',
					'## El doble de plazas',
					'El edificio tiene capacidad para 60 personas, el doble de las 30 plazas concertadas que teníamos hasta ahora. Esto nos permitirá atender a más familias y reducir poco a poco la lista de espera.',
					'## Un edificio pensado para el párkinson',
					'Son más de 1.900 m² repartidos en sótano, dos plantas y cubierta transitable, sobre una parcela de 1.237 m² cedida por el Ayuntamiento de San Fernando. Tiene salas de fisioterapia, logopedia, neuropsicología y estimulación cognitiva, una sala multisensorial, un espacio de investigación, comedor y un salón de actos para unas 70 personas.',
					'## Gracias a muchas manos',
					'La obra empezó con la colocación de la primera piedra en noviembre de 2020 y ha supuesto una inversión de más de 2,2 millones de euros. La Junta de Andalucía aportó 1,5 millones a través de los fondos Next Generation EU. El resto lo han puesto la Diputación de Cádiz, el Ayuntamiento de San Fernando y la propia asociación, con el apoyo de entidades como Hidralia.',
				],
			],
			[
				'title'    => 'La Diputación de Cádiz subvenciona el transporte adaptado en 2025',
				'date'     => '2025-11-18 12:00:00',
				'category' => 'Proyectos',
				'excerpt'  => 'La ayuda del Área de Servicios Sociales, Familias e Igualdad permite mantener la recogida a domicilio de las personas usuarias del centro.',
				'image'    => [ '2026/01/IMG-20250414-WA0010.jpg', 'Furgoneta de transporte adaptado de la asociación' ],
				'body'     => [
					'Párkinson Bahía de Cádiz ha recibido una subvención del Área de Servicios Sociales, Familias e Igualdad de la Diputación de Cádiz para el transporte de personas con párkinson durante 2025.',
					'Gracias a esta ayuda, nuestras furgonetas adaptadas pueden seguir recogiendo cada mañana a las personas usuarias en su casa y devolviéndolas por la tarde.',
				],
			],
			[
				'title'    => 'Programa de apoyo psicosocial con el Ayuntamiento de Chiclana',
				'date'     => '2025-11-17 12:00:00',
				'category' => 'Proyectos',
				'excerpt'  => 'El proyecto «Hacia una vida independiente II» ofrece apoyo psicológico y social a personas con párkinson y a sus familias de Chiclana.',
				'image'    => [ '2026/01/DSC2087.jpg', 'Sesión de grupo del programa de apoyo psicosocial' ],
				'body'     => [
					'Con el apoyo del Ayuntamiento de Chiclana desarrollamos el programa de apoyo psicosocial «Hacia una vida independiente II», dirigido a personas con párkinson y a sus familias.',
					'El programa combina atención psicológica, orientación social y actividades en grupo para ganar autonomía en el día a día.',
				],
			],
		];

		foreach ( $news as $item ) {
			$slug = sanitize_title( $item['title'] );

			if ( get_page_by_path( $slug, OBJECT, 'post' ) ) {
				continue;
			}

			$content = '';
			foreach ( $item['body'] as $block ) {
				if ( str_starts_with( $block, '## ' ) ) {
					$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( substr( $block, 3 ) ) . '</h2><!-- /wp:heading -->' . "\n";
				} else {
					$content .= '<!-- wp:paragraph --><p>' . esc_html( $block ) . '</p><!-- /wp:paragraph -->' . "\n";
				}
			}

			$category = term_exists( $item['category'], 'category' );
			if ( ! $category ) {
				$category = wp_insert_term( $item['category'], 'category' );
			}

			$post_id = (int) wp_insert_post(
				[
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $item['title'],
					'post_name'     => $slug,
					'post_date'     => $item['date'],
					'post_excerpt'  => $item['excerpt'],
					'post_content'  => $content,
					'post_category' => is_array( $category ) ? [ (int) $category['term_id'] ] : [],
				]
			);

			if ( $with_images ) {
				$image_id = $this->image( $item['image'][0], $item['image'][1] );
				if ( $image_id > 0 ) {
					set_post_thumbnail( $post_id, $image_id );
				}
			}

			WP_CLI::log( "Noticia creada: {$item['title']}" );
		}

		// La entrada de ejemplo de WordPress no debe salir en la portada.
		$sample = get_page_by_path( 'hola-mundo', OBJECT, 'post' );
		if ( $sample && 'publish' === $sample->post_status ) {
			wp_trash_post( $sample->ID );
			WP_CLI::log( 'Entrada de ejemplo «¡Hola, mundo!» enviada a la papelera.' );
		}
	}

	/**
	 * @param array<string, int> $pages slug => ID.
	 */
	private function seed_menu( array $pages ): void {
		$name = 'Principal';
		$menu = wp_get_nav_menu_object( $name );
		$id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

		// Se rehace para que apunte a las páginas reales y no a enlaces sueltos.
		foreach ( (array) wp_get_nav_menu_items( $id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}

		foreach ( [ 'la-asociacion', 'centro-de-dia', 'terapias', 'familias-y-cuidadores', 'actualidad', 'contacto' ] as $slug ) {
			wp_update_nav_menu_item(
				$id,
				0,
				[
					'menu-item-object-id' => $pages[ $slug ],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				]
			);
		}

		$locations            = (array) get_theme_mod( 'nav_menu_locations', [] );
		$locations['primary'] = $id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Descarga una foto de la web actual a la biblioteca (una sola vez).
	 */
	private function image( string $path, string $alt ): int {
		$found = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Comando puntual.
				'meta_value'     => $path, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Comando puntual.
			]
		);

		if ( $found ) {
			return (int) $found[0];
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$id = media_sideload_image( self::UPLOADS . $path, 0, $alt, 'id' );

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "No se pudo descargar {$path}: " . $id->get_error_message() );
			return 0;
		}

		update_post_meta( (int) $id, self::SOURCE_META, $path );
		update_post_meta( (int) $id, '_wp_attachment_image_alt', $alt );

		return (int) $id;
	}

	/**
	 * @param array<string, int> $pages slug => ID.
	 */
	private function build_home( int $home_id, array $pages, bool $with_images ): void {
		// Los enlaces a páginas se guardan como elección del desplegable, no como URL fija.
		$page  = static fn ( string $slug ): string => 'page-' . $pages[ $slug ];
		$media = function ( string $path, string $alt ) use ( $with_images ): array {
			$id = $with_images ? $this->image( $path, $alt ) : 0;

			return [
				'id'  => $id > 0 ? $id : '',
				'url' => $id > 0 ? (string) wp_get_attachment_url( $id ) : '',
			];
		};
		$rows  = static function ( array $items ): array {
			foreach ( $items as $index => $item ) {
				$items[ $index ]['_id'] = substr( md5( wp_json_encode( $item ) . $index ), 0, 7 );
			}
			return $items;
		};

		$widgets = [
			'pbc-hero'        => [
				'eyebrow'         => 'Asociación de familias y personas con párkinson · desde 2003',
				'title'           => 'Te acompañamos a vivir con párkinson, día a día.',
				'title_tag'       => 'h1',
				'text'            => 'Atendemos a personas con párkinson y a sus familias de toda la provincia de Cádiz. En nuestro centro de día de San Fernando hay terapias, comida, transporte desde casa y, sobre todo, gente que entiende lo que estás viviendo.',
				'primary_label'   => 'Conoce el centro de día',
				'primary_link_page'    => $page( 'centro-de-dia' ),
				'secondary_label' => 'Llamar al 956 59 19 28',
				'secondary_link_page' => 'custom',
				'secondary_link'  => [
					'url'         => 'tel:+34956591928',
					'is_external' => '',
					'nofollow'    => '',
				],
				'image'           => $media( '2026/01/WhatsApp-Image-2025-12-26-at-10.09.29-Editar.jpg', 'Fachada del nuevo centro de día Párkinson Bahía de Cádiz en Camposoto, San Fernando' ),
				'note_title'      => 'Nuestro nuevo centro',
				'note_text'       => 'Abierto en Camposoto. Inaugurado el 15 de enero de 2026.',
			],
			'pbc-notice'      => $this->diagnosis_notice( $pages ),
			'pbc-paths'       => [
				'title'     => '¿En qué podemos ayudarte?',
				'title_tag' => 'h2',
				'intro'     => 'Elige lo que más se parece a tu situación.',
				'items'     => $rows(
					[
						[
							'title' => 'Me acaban de diagnosticar',
							'text'  => 'Qué es el párkinson, qué puedes esperar y por dónde empezar.',
							'cta'   => 'Primeros pasos',
							'link_page'  => $page( 'me-acaban-de-diagnosticar' ),
						],
						[
							'title' => 'Busco plaza en el centro de día',
							'text'  => 'Cómo funciona, quién puede venir y cómo se solicita.',
							'cta'   => 'Ver el centro',
							'link_page'  => $page( 'centro-de-dia' ),
						],
						[
							'title' => 'Cuido de un familiar',
							'text'  => 'Guías, ejercicios para casa y grupos de apoyo para cuidadores.',
							'cta'   => 'Recursos para familias',
							'link_page'  => $page( 'familias-y-cuidadores' ),
						],
						[
							'title' => 'Quiero ayudar',
							'text'  => 'Hazte socio, colabora como voluntario o haz un donativo.',
							'cta'   => 'Formas de colaborar',
							'link_page'  => $page( 'colabora' ),
						],
					]
				),
			],
			'pbc-feature'     => [
				'eyebrow'      => 'El centro de día',
				'title'        => 'Un lugar pensado para el párkinson, de la puerta al comedor.',
				'title_tag'    => 'h2',
				'text'         => 'Desde diciembre de 2025 estamos en un edificio nuevo en Camposoto. Es el único centro especializado en párkinson de la provincia. Tiene salas de fisioterapia, logopedia, psicología y estimulación cognitiva, una sala multisensorial, comedor y jardín.',
				'facts'        => $rows(
					[
						[
							'label' => 'Plazas',
							'value' => 'Hasta 60',
						],
						[
							'label' => 'Transporte',
							'value' => 'Desde casa',
						],
						[
							'label' => 'Comida',
							'value' => 'Incluida',
						],
					]
				),
				'button_label' => 'Cómo es un día en el centro',
				'button_link_page'  => $page( 'centro-de-dia' ),
				'image_1'      => $media( '2026/01/20260108_111217.jpg', 'Sala de rehabilitación funcional con espalderas y aparatos de ejercicio' ),
				'caption_1'    => 'Sala de rehabilitación funcional',
				'image_2'      => $media( '2026/05/20260518_101622.jpg', 'Sala multisensorial con luz tenue y elementos de estimulación' ),
				'caption_2'    => 'Sala multisensorial',
				'image_3'      => $media( '2026/01/20260120_112924.jpg', 'Comedor del centro con mesas preparadas' ),
				'caption_3'    => 'Comedor',
			],
			'pbc-therapies'   => [
				'title'     => 'Terapias para el cuerpo, la voz, la memoria y el ánimo',
				'title_tag' => 'h2',
				'intro'     => 'Las hace un equipo de 19 profesionales que conoce bien el párkinson. Cada persona tiene su propio plan.',
				'count'     => 0,
			],
			'pbc-steps'       => [
				'title'     => 'Pedir plaza es más sencillo de lo que parece',
				'title_tag' => 'h2',
				'intro'     => 'No hace falta tener todos los papeles. Llámanos y te explicamos los pasos, sin compromiso.',
				'items'     => $rows(
					[
						[
							'title' => 'Llámanos',
							'text'  => 'Te contamos cómo funciona el centro y resolvemos tus dudas.',
						],
						[
							'title' => 'Ven a verlo',
							'text'  => 'Pide cita y te enseñamos las instalaciones.',
						],
						[
							'title' => 'Hablamos contigo',
							'text'  => 'Nuestra trabajadora social valora tu caso y te ayuda con los papeles.',
						],
						[
							'title' => 'Empiezas',
							'text'  => 'Preparamos tu plan de terapias y tu ruta de transporte.',
						],
					]
				),
			],
			'pbc-latest-news' => [
				'title'      => 'Actualidad',
				'title_tag'  => 'h2',
				'count'      => 3,
				'more_label' => 'Ver todas las noticias',
			],
			'pbc-support'     => [
				'title'     => 'Este centro existe porque muchas personas lo hicieron posible.',
				'title_tag' => 'h2',
				'text'      => 'Con tu cuota o tu tiempo ayudas a que más familias tengan terapia y compañía cerca de casa.',
				'items'     => $rows(
					[
						[
							'label' => 'Hazte socio o socia',
							'link_page'  => $page( 'colabora' ),
						],
						[
							'label' => 'Haz un donativo',
							'link_page'  => $page( 'colabora' ),
						],
						[
							'label' => 'Hazte voluntario',
							'link_page'  => $page( 'colabora' ),
						],
					]
				),
			],
		];

		$this->save_page( $home_id, $widgets, 'home' );
	}


	/**
	 * Monta la agenda (necesita el plugin «Agenda», parkinson-agenda): los
	 * tipos de actividad, la página «Agenda» (cabecera y widget Agenda), el
	 * widget «Próximos eventos» en la portada, antes de las noticias, y el
	 * enlace en el menú «La asociación» del pie.
	 *
	 * Las actividades reales las escribe la asociación en el menú «Agenda». Con
	 * --ejemplos se crean cuatro de muestra en las próximas semanas (y una ya celebrada), con
	 * «Ejemplo ·» en el título, para ver la página llena; hay que borrarlas
	 * antes de publicar la web.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe la página «Agenda» aunque ya tenga widgets.
	 *
	 * [--ejemplos]
	 * : Crea cinco actividades de ejemplo.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function agenda( array $args, array $assoc_args ): void {
		$this->start();

		if ( ! class_exists( \DanielLucia\ParkinsonBahia\Agenda\PostType::class ) ) {
			WP_CLI::error( 'El plugin «Agenda» (parkinson-agenda) no está activo.' );
		}

		$type_tax = \DanielLucia\ParkinsonBahia\Agenda\PostType::TAXONOMY;
		$types    = [];
		foreach ( [ 'Taller', 'Charla', 'Celebración', 'Salida' ] as $name ) {
			$term           = term_exists( $name, $type_tax );
			$term           = $term ? $term : wp_insert_term( $name, $type_tax );
			$types[ $name ] = is_array( $term ) ? (int) $term['term_id'] : 0;
		}

		$page = get_page_by_path( 'agenda' );
		$page_id = $page ? (int) $page->ID : (int) wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Agenda',
				'post_name'    => 'agenda',
				'post_excerpt' => 'Talleres, charlas, celebraciones y salidas de la asociación, con fecha, hora y lugar.',
			]
		);
		update_option( \DanielLucia\ParkinsonBahia\Agenda\Plugin::PAGE_OPTION, $page_id, false );

		if ( $this->may_write( $page_id, $assoc_args ) ) {
			$this->save_page(
				$page_id,
				[
					'pbc-page-header' => [
						'eyebrow'   => 'Actividades',
						'title'     => 'Agenda',
						'title_tag' => 'h1',
						'text'      => 'Talleres, charlas, celebraciones y salidas de la asociación. Si quieres apuntarte o tienes dudas, llámanos: te lo contamos todo.',
					],
					'pbc-agenda'      => [
						'filter'     => 'yes',
						'past'       => 'yes',
						'past_title' => 'Actividades ya celebradas',
						'empty_text' => 'Ahora mismo no hay actividades programadas. Llámanos y te contamos las próximas.',
					],
				],
				'agenda'
			);
			WP_CLI::log( 'Página «Agenda» montada con sus widgets.' );
		}

		$this->add_upcoming_to_home( $page_id );
		$this->add_agenda_to_footer( $page_id );

		if ( ! empty( $assoc_args['ejemplos'] ) ) {
			$this->agenda_examples( $types );
		}

		flush_rewrite_rules( false );
		WP_CLI::success( 'Agenda lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Añade «Próximos eventos» a la portada, justo antes de «Últimas noticias»,
	 * si todavía no lo tiene.
	 */
	private function add_upcoming_to_home( int $agenda_id ): void {
		$home_id  = (int) get_option( 'page_on_front' );
		$document = $home_id > 0 ? \Elementor\Plugin::$instance->documents->get( $home_id, false ) : null;

		if ( ! $document ) {
			WP_CLI::warning( 'No hay portada de Elementor: añade el widget «Próximos eventos» a mano.' );
			return;
		}

		$data = $document->get_elements_data();
		if ( str_contains( (string) wp_json_encode( $data ), '"pbc-upcoming-events"' ) ) {
			WP_CLI::log( 'La portada ya tiene «Próximos eventos».' );
			return;
		}

		$widget = [
			'id'         => substr( md5( 'inicio-pbc-upcoming-events' ), 0, 7 ),
			'elType'     => 'widget',
			'widgetType' => 'pbc-upcoming-events',
			'settings'   => [
				'title'      => 'Próximas actividades',
				'title_tag'  => 'h2',
				'count'      => 3,
				'more_label' => 'Ver toda la agenda',
				'more_page'  => 'page-' . $agenda_id,
				'empty'      => 'hide',
			],
			'elements'   => [],
		];

		$inserted = false;
		$insert   = static function ( array $elements ) use ( &$insert, &$inserted, $widget ): array {
			$out = [];
			foreach ( $elements as $element ) {
				if ( ! $inserted && 'pbc-latest-news' === ( $element['widgetType'] ?? '' ) ) {
					$out[]    = $widget;
					$inserted = true;
				}
				if ( ! empty( $element['elements'] ) ) {
					$element['elements'] = $insert( $element['elements'] );
				}
				$out[] = $element;
			}

			return $out;
		};
		$data = $insert( $data );

		if ( ! $inserted && isset( $data[0]['elements'] ) ) {
			$data[0]['elements'][] = $widget;
		}

		$document->save( [ 'elements' => $data ] );
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		WP_CLI::log( 'Portada: añadido «Próximos eventos» antes de las noticias (se oculta si no hay actividades).' );
	}

	private function add_agenda_to_footer( int $agenda_id ): void {
		$locations = (array) get_theme_mod( 'nav_menu_locations', [] );
		$menu_id   = (int) ( $locations['footer-asociacion'] ?? 0 );

		if ( 0 === $menu_id ) {
			WP_CLI::warning( 'No hay menú «La asociación» en el pie: ejecuta «wp pbc seed-pie».' );
			return;
		}

		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			if ( 'post_type' === $item->type && $agenda_id === (int) $item->object_id ) {
				return;
			}
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-object-id' => $agenda_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]
		);
		WP_CLI::log( 'Pie: añadido «Agenda» al menú «La asociación».' );
	}

	/**
	 * Cuatro actividades de ejemplo en las próximas semanas y una ya celebrada (no se duplican).
	 *
	 * @param array<string, int> $types Tipo => ID del término.
	 */
	private function agenda_examples( array $types ): void {
		$post_type = \DanielLucia\ParkinsonBahia\Agenda\PostType::class;
		$examples  = [
			[ 'Ejemplo · Taller de memoria para familias', 'Taller', 6, '17:00', '18:30', 'Gratuito', 'Plazas limitadas: llama para apuntarte.', 'Ejercicios sencillos para entrenar la memoria en casa, con el equipo de psicología.' ],
			[ 'Ejemplo · Charla: comer bien con párkinson', 'Charla', 13, '11:00', '12:30', 'Gratuito', '', 'Consejos prácticos sobre alimentación y medicación, con tiempo para preguntas.' ],
			[ 'Ejemplo · Paseo por el Parque Natural Bahía de Cádiz', 'Salida', 20, '10:00', '', '3 € (incluye el transporte)', 'Apúntate llamando antes del jueves anterior.', 'Un paseo tranquilo y adaptado, con paradas para descansar.' ],
			[ 'Ejemplo · Fiesta de Navidad del centro de día', 'Celebración', 45, '12:00', '15:00', '', '', 'Comida, música y sorteo con usuarios, familias y voluntariado.' ],
			// Una ya celebrada, para ver la lista «Actividades ya celebradas».
			[ 'Ejemplo · Taller de risoterapia', 'Taller', -20, '17:00', '18:00', 'Gratuito', '', 'Una tarde para reír juntos y soltar tensiones.' ],
		];

		foreach ( $examples as [ $title, $type, $days, $start, $end, $price, $signup, $excerpt ] ) {
			$existing = get_posts(
				[
					'post_type'      => $post_type::POST_TYPE,
					'post_status'    => 'any',
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);
			if ( $existing ) {
				continue;
			}

			$id = (int) wp_insert_post(
				[
					'post_type'    => $post_type::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $title,
					// Sin «·» en la dirección: WordPress lo dejaría como «%c2%b7».
					'post_name'    => sanitize_title( str_replace( '·', '', $title ) ),
					'post_excerpt' => $excerpt,
					'post_content' => '<!-- wp:paragraph --><p>Actividad de ejemplo para ver cómo queda la agenda. Bórrala antes de publicar la web.</p><!-- /wp:paragraph -->',
				]
			);

			$fields = [
				$post_type::META_DATE   => wp_date( 'Y-m-d', strtotime( sprintf( '%+d days', $days ) ) ),
				$post_type::META_START  => $start,
				$post_type::META_END    => $end,
				$post_type::META_PLACE  => $post_type::DEFAULT_PLACE,
				$post_type::META_PRICE  => $price,
				$post_type::META_SIGNUP => $signup,
			];
			if ( 'Salida' === $type ) {
				$fields[ $post_type::META_PLACE ] = 'Salida desde el centro de día';
			}
			foreach ( $fields as $key => $value ) {
				$post_type::store( $id, $key, $value );
			}
			$post_type::refresh_index( $id );

			if ( ! empty( $types[ $type ] ) ) {
				wp_set_object_terms( $id, [ $types[ $type ] ], $post_type::TAXONOMY );
			}
			WP_CLI::log( "Actividad de ejemplo creada: {$title}" );
		}
	}

	/**
	 * Crea las redirecciones 301 de la web antigua (necesita el plugin
	 * «Redirecciones», parkinson-redirects).
	 *
	 * La tabla está en assets/seed/redirecciones.csv: páginas, entradas,
	 * categorías y PDF de /archivos/ de la web actual, sacados de sus mapas del
	 * sitio. Un destino «entrada:<slug>» es una noticia que sigue en la web
	 * nueva con otro nombre. Las guías que importó seed-familias se redirigen
	 * a su nuevo archivo PDF.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Cambia también las redirecciones que ya existen (por ejemplo, las editadas a mano).
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function redirecciones( array $args, array $assoc_args ): void {
		if ( ! class_exists( \DanielLucia\Redirects\Importer::class ) ) {
			WP_CLI::error( 'El plugin «Redirecciones» (parkinson-redirects) no está activo.' );
		}

		$importer = new \DanielLucia\Redirects\Importer( new \DanielLucia\Redirects\Repository() );
		$rows     = $importer->read_csv( PBC_CORE_DIR . 'assets/seed/redirecciones.csv' );

		if ( is_wp_error( $rows ) ) {
			WP_CLI::error( $rows->get_error_message() );
		}

		foreach ( $rows as &$row ) {
			if ( preg_match( '/^entrada:(.+)$/', $row[1] ?? '', $m ) ) {
				$post   = get_page_by_path( $m[1], OBJECT, 'post' );
				$row[1] = $post && 'publish' === $post->post_status ? 'post:' . $post->ID : '/actualidad';
			}
		}
		unset( $row );

		$guides = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Comando puntual.
				'meta_value'     => 'https://parkinsonbahiadecadiz.org/archivos/', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Comando puntual.
				'meta_compare'   => 'LIKE',
			]
		);
		foreach ( $guides as $guide_id ) {
			$path   = (string) wp_parse_url( (string) get_post_meta( (int) $guide_id, self::SOURCE_META, true ), PHP_URL_PATH );
			$rows[] = [ rawurldecode( $path ), 'post:' . $guide_id, '301', 'Guía de la web antigua, ahora en Familias y cuidadores' ];
		}

		$report = $importer->import( $rows, ! empty( $assoc_args['force'] ) );
		foreach ( $report['errors'] as $error ) {
			WP_CLI::warning( $error );
		}

		WP_CLI::success( sprintf( 'Redirecciones creadas: %d. Actualizadas: %d. Sin cambios: %d. Con errores: %d.', $report['created'], $report['updated'], $report['kept'], count( $report['errors'] ) ) );
	}

	/**
	 * Genera el mapa estático del bloque de contacto: une unas pocas teselas de
	 * OpenStreetMap alrededor del centro, marca su situación y lo guarda en la
	 * biblioteca como WebP. Así el mapa se ve sin pulsar nada y sin conectar
	 * con terceros; el mapa interactivo solo se carga con permiso.
	 *
	 * Se descargan 12 teselas una sola vez, con un User-Agent propio, como pide
	 * la política de uso de OpenStreetMap. La atribución se muestra junto al mapa.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Vuelve a generar la imagen aunque ya exista.
	 *
	 * [--zoom=<zoom>]
	 * : Nivel de zoom (15 a 18).
	 * ---
	 * default: 16
	 * ---
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function mapa( array $args, array $assoc_args ): void {
		$existing = absint( get_option( Frontend\ContactMap::IMAGE_OPTION ) );
		if ( $existing > 0 && wp_attachment_is_image( $existing ) && empty( $assoc_args['force'] ) ) {
			WP_CLI::success( 'El mapa estático ya existe (usa --force para rehacerlo).' );
			return;
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagewebp' ) ) {
			WP_CLI::error( 'PHP no tiene GD con WebP.' );
		}

		[ $lat, $lon ] = Frontend\ContactMap::coords();
		$zoom          = min( 18, max( 15, absint( $assoc_args['zoom'] ?? 16 ) ) );
		$width         = 960;
		$height        = 600;
		$n             = 2 ** $zoom;

		// Posición del centro en píxeles del mapa del mundo a ese zoom.
		$px = ( $lon + 180 ) / 360 * $n * 256;
		$py = ( 1 - log( tan( deg2rad( $lat ) ) + 1 / cos( deg2rad( $lat ) ) ) / M_PI ) / 2 * $n * 256;

		$left   = (int) floor( $px - $width / 2 );
		$top    = (int) floor( $py - $height / 2 );
		$canvas = imagecreatetruecolor( $width, $height );

		for ( $tx = intdiv( $left, 256 ); $tx <= intdiv( $left + $width - 1, 256 ); $tx++ ) {
			for ( $ty = intdiv( $top, 256 ); $ty <= intdiv( $top + $height - 1, 256 ); $ty++ ) {
				$response = wp_remote_get(
					"https://tile.openstreetmap.org/{$zoom}/{$tx}/{$ty}.png",
					[
						'timeout'    => 20,
						'user-agent' => 'ParkinsonBahiaDeCadiz-web/1.0 (+https://parkinsonbahiadecadiz.org; unidad@parkinsonbahiadecadiz.org)',
					]
				);
				$body     = wp_remote_retrieve_body( $response );
				$tile     = '' !== $body ? @imagecreatefromstring( $body ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Una tesela rota no debe parar el comando.
				if ( ! $tile ) {
					imagedestroy( $canvas );
					WP_CLI::error( "No se pudo descargar la tesela {$zoom}/{$tx}/{$ty}." );
				}
				imagecopy( $canvas, $tile, $tx * 256 - $left, $ty * 256 - $top, 0, 0, 256, 256 );
				imagedestroy( $tile );
			}
		}

		// Marca del centro: círculo naranja con borde blanco y contorno oscuro, para verse sobre cualquier calle.
		imageantialias( $canvas, true );
		$cx = (int) round( $px - $left );
		$cy = (int) round( $py - $top );
		// Grande a propósito: en el móvil la imagen se ve a un tercio de su tamaño.
		imagefilledellipse( $canvas, $cx, $cy, 76, 76, imagecolorallocate( $canvas, 28, 37, 43 ) );
		imagefilledellipse( $canvas, $cx, $cy, 66, 66, imagecolorallocate( $canvas, 255, 255, 255 ) );
		imagefilledellipse( $canvas, $cx, $cy, 46, 46, imagecolorallocate( $canvas, 194, 80, 15 ) );

		$tmp = wp_tempnam( 'mapa-centro.webp' );
		imagewebp( $canvas, $tmp, 82 );
		imagedestroy( $canvas );

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$id = media_handle_sideload(
			[
				'name'     => 'mapa-centro-de-dia.webp',
				'tmp_name' => $tmp,
			],
			0,
			'Mapa del centro de día'
		);

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			WP_CLI::error( 'No se pudo guardar el mapa: ' . $id->get_error_message() );
		}

		update_post_meta( (int) $id, '_wp_attachment_image_alt', 'Plano de Camposoto (San Fernando) con la situación del centro de día marcada con un círculo naranja.' );
		wp_update_post(
			[
				'ID'           => (int) $id,
				'post_excerpt' => '© colaboradores de OpenStreetMap',
			]
		);
		if ( $existing > 0 && $existing !== (int) $id ) {
			wp_delete_attachment( $existing, true );
		}
		update_option( Frontend\ContactMap::IMAGE_OPTION, (int) $id, false );

		WP_CLI::success( 'Mapa estático listo: ' . wp_get_attachment_url( (int) $id ) );
	}

	/**
	 * Aviso de la portada que lleva a «Me acaban de diagnosticar».
	 *
	 * @param array<string, int> $pages slug => ID.
	 * @return array<string, mixed>
	 */
	private function diagnosis_notice( array $pages ): array {
		return [
			'lead'             => '¿Te acaban de diagnosticar párkinson?',
			'text'             => 'Te ayudamos a dar los primeros pasos: qué es, qué hacer las primeras semanas y qué preguntar al neurólogo.',
			'button_label'     => 'Por dónde empezar',
			'button_link_page' => 'page-' . $pages['me-acaban-de-diagnosticar'],
		];
	}

	/**
	 * Monta la página «Me acaban de diagnosticar» (fuera del menú), sube sus
	 * dos PDF (guía de bienvenida y preguntas para el neurólogo) como guías del
	 * tema «Primeros pasos» y la enlaza desde la portada: la primera tarjeta de
	 * «¿En qué podemos ayudarte?» y un aviso bajo la portada.
	 *
	 * Los PDF se generan desde docs/guias/ con «node docs/guias/generar-pdf.mjs».
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function diagnostico( array $args, array $assoc_args ): void {
		$this->start();

		$pages   = $this->seed_pages();
		$page_id = $pages['me-acaban-de-diagnosticar'];
		$page    = static fn ( string $slug ): string => 'page-' . $pages[ $slug ];

		// Guías del tema «Primeros pasos»: las dos nuevas y el registro de síntomas que ya estaba en Familias.
		$term = term_exists( 'Primeros pasos', PostTypes\Recurso::TAXONOMY );
		if ( ! $term ) {
			$term = wp_insert_term( 'Primeros pasos', PostTypes\Recurso::TAXONOMY );
		}
		$term_id = is_array( $term ) ? (int) $term['term_id'] : 0;
		$files   = [];

		foreach ( [
			'guia-de-bienvenida'          => 'Guía de bienvenida',
			'preguntas-para-mi-neurologo' => 'Preguntas para mi neurólogo',
		] as $name => $title ) {
			$found    = get_posts(
				[
					'post_type'      => PostTypes\Recurso::POST_TYPE,
					'post_status'    => 'any',
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);
			$guide_id = $found ? (int) $found[0] : (int) wp_insert_post(
				[
					'post_type'   => PostTypes\Recurso::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $title,
					'menu_order'  => 0,
				]
			);
			$file_id  = $this->local_file( PBC_CORE_DIR . 'assets/seed/' . $name . '.pdf', $title );
			if ( $file_id > 0 ) {
				update_post_meta( $guide_id, PostTypes\Recurso::META_FILE, $file_id );
				$files[ $name ] = (string) wp_get_attachment_url( $file_id );
			}
			if ( $term_id > 0 ) {
				wp_set_object_terms( $guide_id, [ $term_id ], PostTypes\Recurso::TAXONOMY, true );
			}
		}

		$symptoms = '';
		foreach ( [ 'Registro de mis síntomas' ] as $title ) {
			$found = get_posts(
				[
					'post_type'      => PostTypes\Recurso::POST_TYPE,
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);
			if ( ! $found ) {
				WP_CLI::warning( "No está la guía «{$title}»: ejecuta antes «wp pbc seed-familias»." );
				continue;
			}
			if ( $term_id > 0 ) {
				wp_set_object_terms( (int) $found[0], [ $term_id ], PostTypes\Recurso::TAXONOMY, true );
			}
			if ( 'Registro de mis síntomas' === $title ) {
				$file_id  = absint( get_post_meta( (int) $found[0], PostTypes\Recurso::META_FILE, true ) );
				$symptoms = $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : '';
			}
		}
		WP_CLI::log( 'Guías de «Primeros pasos» listas.' );

		if ( $this->may_write( $page_id, $assoc_args ) ) {
			$jumps = [];
			if ( isset( $files['guia-de-bienvenida'] ) ) {
				$jumps[] = [
					'label'     => 'Descargar la guía de bienvenida (PDF)',
					'link_page' => 'custom',
					'link'      => $this->custom_link( $files['guia-de-bienvenida'] ),
				];
			}
			foreach ( [
				'que-es'            => 'Qué es el párkinson',
				'primeras-semanas'  => 'Las primeras semanas',
				'como-te-ayudamos'  => 'Cómo te ayudamos',
				'neurologo'         => 'Preguntas para el neurólogo',
			] as $anchor => $label ) {
				$jumps[] = [
					'label'     => $label,
					'link_page' => 'custom',
					'link'      => $this->custom_link( '#' . $anchor ),
				];
			}

			$checklist = [
				'title'         => 'Preguntas para tu neurólogo',
				'title_tag'     => 'h2',
				'intro'         => 'En la consulta el tiempo pasa rápido. Lleva tus dudas escritas y, si puedes, ve con alguien que te acompañe y tome notas. Estas preguntas suelen ayudar:',
				'items'         => $this->rows(
					array_map(
						static fn ( string $question ): array => [ 'title' => $question ],
						[
							'¿Qué tipo de párkinson tengo y cómo puede avanzar en mi caso?',
							'¿Para qué sirve cada medicamento y a qué hora debo tomarlo?',
							'¿Qué efectos secundarios puedo notar y qué hago si aparecen?',
							'¿Puedo tomar mis otros medicamentos, suplementos o infusiones?',
							'¿Qué ejercicio me conviene? ¿Me ayudaría la fisioterapia o la logopedia?',
							'¿Puedo seguir conduciendo y trabajando?',
							'¿Qué síntomas debo vigilar y cuándo debo llamar antes de la próxima cita?',
							'¿Cada cuánto tendré revisión y con quién hablo si tengo dudas entre citas?',
						]
					)
				),
				'layout'        => 'checklist',
				'variant'       => 'mist',
				'note'          => 'Lleva también la lista de todos tus medicamentos, aunque no sean para el párkinson, y tu registro de síntomas.',
				'anchor'        => 'neurologo',
			];
			if ( isset( $files['preguntas-para-mi-neurologo'] ) ) {
				$checklist['button_label']     = 'Descargar la ficha para imprimir (PDF)';
				$checklist['button_link_page'] = 'custom';
				$checklist['button_link']      = $this->custom_link( $files['preguntas-para-mi-neurologo'] );
			}
			if ( '' !== $symptoms ) {
				$checklist['button2_label']     = 'Registro de mis síntomas (PDF)';
				$checklist['button2_link_page'] = 'custom';
				$checklist['button2_link']      = $this->custom_link( $symptoms );
			}

			$this->save_page(
				$page_id,
				[
					'pbc-page-header'   => [
						'eyebrow'   => 'Primeros pasos',
						'title'     => 'Me acaban de diagnosticar',
						'title_tag' => 'h1',
						'text'      => 'Si te acaban de decir que tienes párkinson, o se lo han dicho a alguien que quieres, esta página es para ti. Te contamos qué es, qué hacer las primeras semanas y cómo podemos ayudarte. Con calma: no tienes que entenderlo todo hoy.',
						'variant'     => 'sand',
						'jumps'       => $this->rows( $jumps ),
						'jumps_style' => 'index',
						'index_label' => 'En esta página',
					],
					'pbc-key-points'    => [
						'title'     => 'El párkinson, en pocas palabras',
						'title_tag' => 'h2',
						'intro'     => 'Es una enfermedad del sistema nervioso. Algunas neuronas del cerebro dejan de producir suficiente dopamina, una sustancia que ayuda a que los movimientos sean suaves y controlados.',
						'items'     => $this->rows(
							[
								[
									'title' => 'Afecta sobre todo al movimiento',
									'text'  => 'Temblor (no todas las personas lo tienen), lentitud, rigidez y problemas de equilibrio.',
								],
								[
									'title' => 'Y también a otras cosas',
									'text'  => 'El sueño, el olfato, el estreñimiento, el cansancio, el ánimo o los nervios. Cuéntaselo a tu médico: también se tratan.',
								],
								[
									'title' => 'Avanza despacio',
									'text'  => 'Normalmente a lo largo de muchos años, y de forma distinta en cada persona. No compares tu caso con el de otros.',
								],
								[
									'title' => 'Hay tratamiento',
									'text'  => 'No cura, pero los medicamentos, el ejercicio y las terapias ayudan mucho a seguir haciendo lo que te importa.',
								],
								[
									'title' => 'No es contagioso',
									'text'  => 'Y en la mayoría de los casos tampoco es hereditario.',
								],
								[
									'title' => 'No estás solo',
									'text'  => 'En la provincia de Cádiz hay unas 5.000 personas con párkinson. Desde 2003 acompañamos a muchas de ellas y a sus familias.',
								],
							]
						),
						'layout'    => 'columns',
						'note'      => 'Esta información es general y no sustituye a tu médico. Si tienes dudas sobre tu caso, pregúntale a tu neurólogo.',
						'anchor'    => 'que-es',
					],
					'pbc-steps'         => [
						'title'     => 'Qué hacer las primeras semanas',
						'title_tag' => 'h2',
						'intro'     => 'Sin prisas. Son las cosas que, según nos cuentan las familias, más les ayudaron al principio.',
						'anchor'    => 'primeras-semanas',
						'items'     => $this->rows(
							[
								[
									'title' => 'Date tiempo',
									'text'  => 'Es normal sentir miedo, rabia o tristeza. No tienes que tomar decisiones importantes hoy.',
								],
								[
									'title' => 'Sigue el tratamiento',
									'text'  => 'Toma la medicación a sus horas. No la cambies ni la dejes sin hablar antes con tu médico.',
								],
								[
									'title' => 'Apunta lo que notas',
									'text'  => 'Qué síntomas tienes, a qué hora y cuándo mejoran o empeoran. Te ayudará mucho en la consulta.',
								],
								[
									'title' => 'Muévete cada día',
									'text'  => 'Caminar, estirarse o bailar. Mejor un poco cada día que mucho de vez en cuando.',
								],
								[
									'title' => 'Cuéntalo cuando quieras',
									'text'  => 'Tú decides a quién y cuándo. Hablarlo con la familia y con otras personas que viven lo mismo alivia.',
								],
								[
									'title' => 'Llámanos',
									'text'  => 'Aunque todavía no necesites nada. Te escuchamos y te contamos qué recursos tienes cerca.',
								],
							]
						),
					],
					'pbc-paths'         => [
						'title'     => 'Cómo te ayudamos, aunque no necesites el centro de día',
						'title_tag' => 'h2',
						'intro'     => 'Puedes contar con nosotros desde el primer día. Llámanos y vemos juntos qué te encaja.',
						'anchor'    => 'como-te-ayudamos',
						'items'     => $this->rows(
							[
								[
									'title'     => 'Orientación y papeles',
									'text'      => 'Nuestra trabajadora social te explica tus derechos, la dependencia y los recursos que tienes cerca, y te ayuda con los trámites.',
									'cta'       => 'Pedir una cita',
									'link_page' => $page( 'contacto' ),
								],
								[
									'title'     => 'Apoyo psicológico y terapias',
									'text'      => 'Psicología para ti y tu familia, fisioterapia, logopedia y estimulación cognitiva. Pregúntanos cuáles puedes hacer.',
									'cta'       => 'Ver las terapias',
									'link_page' => $page( 'terapias' ),
								],
								[
									'title'     => 'Guías y ejercicios para casa',
									'text'      => 'Fichas para imprimir, vídeos de ejercicios y apoyo para quien cuida, también en grupo.',
									'cta'       => 'Ver recursos para familias',
									'link_page' => $page( 'familias-y-cuidadores' ),
								],
								[
									'title'     => 'Hacerte socio o socia',
									'text'      => 'Te da acceso a nuestros servicios y actividades, y ayuda a que podamos atender a más familias.',
									'cta'       => 'Hacerme socio',
									'link_page' => 'custom',
									'link'      => $this->custom_link( get_permalink( $pages['colabora'] ) . '#socio' ),
								],
							]
						),
					],
					'pbc-key-points#2'  => $checklist,
					'pbc-resources'     => [
						'title'       => 'Para leer con calma e imprimir',
						'title_tag'   => 'h2',
						'text'        => 'La guía de bienvenida recoge todo lo de esta página en siete hojas. Si prefieres una copia en papel, pídenosla.',
						'topic'       => 'primeros-pasos',
						'show_filter' => '',
						'anchor'      => 'guias',
					],
					'pbc-cta'           => [
						'title'             => 'No tienes que pasar por esto solo',
						'title_tag'         => 'h2',
						'text'              => 'Llámanos aunque todavía no sepas qué preguntar. Te escuchamos y te contamos, sin compromiso, qué podemos hacer por ti y por tu familia.',
						'button_label'      => 'Llamar al 956 59 19 28',
						'button_link_page'  => 'custom',
						'button_link'       => $this->custom_link( 'tel:+34956591928' ),
						'button2_label'     => 'Conocer el centro de día',
						'button2_link_page' => $page( 'centro-de-dia' ),
						'variant'           => 'mist',
					],
				],
				'diagnostico'
			);
			update_post_meta( $page_id, '_pbc_seeded', 1 );
			wp_update_post(
				[
					'ID'           => $page_id,
					'post_excerpt' => 'Qué es el párkinson, qué hacer las primeras semanas, qué preguntar al neurólogo y cómo te ayuda Párkinson Bahía de Cádiz, con una guía de bienvenida para imprimir.',
				]
			);
			WP_CLI::log( 'Página «Me acaban de diagnosticar» montada con sus widgets.' );
		}

		$this->link_home_to_diagnosis( $pages );

		WP_CLI::success( 'Me acaban de diagnosticar lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * En la portada ya montada: la tarjeta «Me acaban de diagnosticar» apunta a
	 * la página nueva y bajo la portada aparece el aviso. Solo toca esos dos
	 * puntos, sin rehacer el resto de la página.
	 *
	 * @param array<string, int> $pages slug => ID.
	 */
	private function link_home_to_diagnosis( array $pages ): void {
		$home_id  = (int) get_option( 'page_on_front' );
		$document = $home_id > 0 ? \Elementor\Plugin::$instance->documents->get( $home_id, false ) : null;

		if ( ! $document ) {
			WP_CLI::warning( 'No hay portada de Elementor: ejecuta «wp pbc seed-home».' );
			return;
		}

		$data    = $document->get_elements_data();
		$target  = 'page-' . $pages['me-acaban-de-diagnosticar'];
		$changed = false;

		foreach ( $data as &$container ) {
			$elements = $container['elements'] ?? [];
			$types    = array_column( $elements, 'widgetType' );

			foreach ( $elements as &$element ) {
				if ( 'pbc-paths' !== ( $element['widgetType'] ?? '' ) ) {
					continue;
				}
				foreach ( $element['settings']['items'] ?? [] as $index => $item ) {
					if ( 'Me acaban de diagnosticar' === ( $item['title'] ?? '' ) && $target !== ( $item['link_page'] ?? '' ) ) {
						$element['settings']['items'][ $index ]['link_page'] = $target;
						$changed = true;
					}
				}
			}
			unset( $element );

			$hero = array_search( 'pbc-hero', $types, true );
			if ( false !== $hero && ! in_array( 'pbc-notice', $types, true ) ) {
				array_splice(
					$elements,
					(int) $hero + 1,
					0,
					[
						[
							'id'         => substr( md5( 'home-pbc-notice' ), 0, 7 ),
							'elType'     => 'widget',
							'widgetType' => 'pbc-notice',
							'settings'   => $this->diagnosis_notice( $pages ),
							'elements'   => [],
						],
					]
				);
				$changed = true;
			}

			$container['elements'] = $elements;
		}
		unset( $container );

		if ( ! $changed ) {
			WP_CLI::log( 'La portada ya enlaza con «Me acaban de diagnosticar».' );
			return;
		}

		$document->save( [ 'elements' => $data ] );
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		WP_CLI::log( 'Portada enlazada con «Me acaban de diagnosticar» (tarjeta y aviso).' );
	}

	/**
	 * Sube a la biblioteca un archivo que viene con el plugin (una sola vez).
	 */
	private function local_file( string $path, string $title ): int {
		$source = 'seed:' . wp_basename( $path );
		$found  = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Comando puntual.
				'meta_value'     => $source, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Comando puntual.
			]
		);

		if ( $found ) {
			return (int) $found[0];
		}

		if ( ! is_readable( $path ) ) {
			WP_CLI::warning( "Falta el archivo {$path}: genéralo con «node docs/guias/generar-pdf.mjs»." );
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( wp_basename( $path ) );
		copy( $path, $tmp );

		$id = media_handle_sideload(
			[
				'name'     => wp_basename( $path ),
				'tmp_name' => $tmp,
			],
			0,
			$title
		);

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			WP_CLI::warning( "No se pudo guardar {$path}: " . $id->get_error_message() );
			return 0;
		}

		update_post_meta( (int) $id, self::SOURCE_META, $source );
		WP_CLI::log( "Archivo subido: {$title}" );

		return (int) $id;
	}

	/**
	 * Crea la página «La asociación» con sus widgets y sus contenidos.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function asociacion( array $args, array $assoc_args ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			WP_CLI::error( 'Elementor no está activo.' );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No hay ningún usuario administrador.' );
		}
		wp_set_current_user( (int) $admins[0] );

		$pages   = $this->seed_pages();
		$page_id = $pages['la-asociacion'];

		$this->seed_items(
			PostTypes\Hito::POST_TYPE,
			[
				[ 'Nace la asociación', 'Un grupo de familias de San Fernando, con Baldomero Guerrero Braza como primer presidente, decide unirse para no afrontar el párkinson en solitario.', [ PostTypes\Hito::META_YEAR => 2003 ] ],
				[ 'Abre el centro de día', 'Ponemos en marcha la Unidad de Estancia Diurna, con plazas concertadas con la Junta de Andalucía.', [ PostTypes\Hito::META_YEAR => 2008 ] ],
				[ 'Primera piedra', 'Empiezan las obras del nuevo centro en una parcela cedida por el Ayuntamiento de San Fernando.', [ PostTypes\Hito::META_YEAR => 2020 ] ],
				[ 'Nos mudamos', 'En diciembre empezamos a trabajar en el edificio nuevo de Camposoto, con capacidad para 60 personas.', [ PostTypes\Hito::META_YEAR => 2025 ] ],
				[ 'Inauguración', 'El 15 de enero se inaugura oficialmente el centro, una referencia en la atención al párkinson.', [ PostTypes\Hito::META_YEAR => 2026 ] ],
			]
		);

		$team = [];
		foreach ( [ 'Directora' => 1, 'Trabajadora social' => 1, 'Psicólogos' => 2, 'Fisioterapeuta' => 1, 'Logopeda' => 1, 'Auxiliares de clínica' => 6, 'Cocinera' => 1, 'Conductores' => 4, 'Contable' => 1, 'Informática' => 1 ] as $role => $count ) {
			$team[] = [ $role, '', [ PostTypes\Puesto::META_COUNT => $count ] ];
		}
		$this->seed_items( PostTypes\Puesto::POST_TYPE, $team );

		$board = [];
		foreach ( [
			[ 'Joaquín Izquierdo Periñán', 'Presidente' ],
			[ 'Antonio Prieto Ramírez', 'Vicepresidente' ],
			[ 'Miguel Ángel Díaz Aguado', 'Secretario' ],
			[ 'John Bénjamin Hackl', 'Tesorero' ],
			[ 'Mª Dolores Garzón Coello', 'Vocal' ],
			[ 'Mª Carmen Nodar Martínez', 'Vocal' ],
			[ 'Mª Isabel Romero González', 'Vocal' ],
			[ 'Manuel Torres Guzmán', 'Vocal' ],
		] as [ $name, $role ] ) {
			$board[] = [ $name, '', [ PostTypes\Directivo::META_ROLE => $role ] ];
		}
		$this->seed_items( PostTypes\Directivo::POST_TYPE, $board );

		$documents = [];
		foreach ( [ 'Memoria de actividades 2025', 'Cuentas anuales 2025', 'Programación 2025', 'Plan de Igualdad', 'Plan de Voluntariado', 'Código ético' ] as $title ) {
			$documents[] = [ $title, '', [] ];
		}
		$this->seed_items( PostTypes\Documento::POST_TYPE, $documents );

		$networks = [];
		foreach ( [
			'FEP'     => 'Federación Española de Párkinson',
			'FANDEP'  => 'Federación Andaluza de Párkinson',
			'EPDA'    => 'European Parkinson\'s Disease Association',
			'FEGADI'  => 'Federación Gaditana de Discapacitados',
			'COCEMFE' => 'Confederación de personas con discapacidad física y orgánica',
		] as $short => $name ) {
			$networks[] = [ $name, '', [ PostTypes\Entidad::META_SHORT => $short ] ];
		}
		$this->seed_items( PostTypes\Entidad::POST_TYPE, $networks );

		$has = '' !== (string) get_post_meta( $page_id, '_elementor_data', true );

		if ( $has && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La página ya tiene contenido; no se toca (usa --force para reescribirla).' );
		} else {
			$rows = static function ( array $items ): array {
				foreach ( $items as $index => $item ) {
					$items[ $index ]['_id'] = substr( md5( wp_json_encode( $item ) . $index ), 0, 7 );
				}
				return $items;
			};

			$this->save_page(
				$page_id,
				[
					'pbc-page-header' => [
						'eyebrow'   => 'Quiénes somos',
						'title'     => 'Somos familias que decidieron no estar solas frente al párkinson.',
						'title_tag' => 'h1',
						'text'      => 'La Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz nació en San Fernando en abril de 2003. Es una entidad sin ánimo de lucro y atiende a personas de toda la provincia de Cádiz, donde unas 5.000 personas viven con un diagnóstico de párkinson.',
					],
					'pbc-mission'     => [
						'title'     => 'Nuestra misión',
						'title_tag' => 'h2',
						'quote'     => 'Mejorar la calidad de vida de las personas afectadas de párkinson y la de sus familiares y/o cuidadores/as.',
						'text'      => 'Lo hacemos con terapias rehabilitadoras, un centro de día especializado, transporte adaptado y apoyo a las familias que cuidan.',
					],
					'pbc-timeline'    => [
						'title'     => 'Más de veinte años de camino',
						'title_tag' => 'h2',
					],
					'pbc-team'        => [
						'title'     => 'Las personas que te atienden',
						'title_tag' => 'h2',
						'anchor'    => 'equipo',
						'text'      => 'Un equipo de 19 profesionales, dirigido por Lola Garzón, que lleva con la asociación desde sus primeros años.',
					],
					'pbc-board'       => [
						'title'     => 'Junta directiva',
						'title_tag' => 'h2',
						'text'      => 'Socios y socias que dedican su tiempo, sin cobrar, a gobernar la asociación. Renovada en enero de 2025.',
					],
					'pbc-documents'   => [
						'title'     => 'Transparencia',
						'title_tag' => 'h2',
						'anchor'    => 'transparencia',
						'text'      => 'Rendimos cuentas a nuestros socios, a las administraciones que nos apoyan y a cualquier persona que quiera saber cómo trabajamos.',
						'facts'     => $rows(
							[
								[
									'label' => 'CIF',
									'value' => 'G-11591302',
								],
								[
									'label' => 'Registro de Asociaciones de Andalucía',
									'value' => 'nº 6.809, inscrita el 23 de junio de 2003',
								],
								[
									'label' => 'Registro de Entidades de Voluntariado de Andalucía',
									'value' => 'nº 1198',
								],
							]
						),
					],
					'pbc-networks'    => [
						'title'     => 'Formamos parte de',
						'title_tag' => 'h2',
						'text'      => 'Trabajamos junto a otras entidades para defender los derechos de las personas con párkinson.',
					],
				],
				'asociacion'
			);
			WP_CLI::log( 'Página «La asociación» montada con sus widgets.' );
		}

		WP_CLI::success( 'La asociación lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Crea la página «Centro de día» con sus widgets y sus contenidos.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * [--skip-images]
	 * : No descarga las fotos de la web actual.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function centro( array $args, array $assoc_args ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			WP_CLI::error( 'Elementor no está activo.' );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No hay ningún usuario administrador.' );
		}
		wp_set_current_user( (int) $admins[0] );

		$with_images = empty( $assoc_args['skip-images'] );
		$pages       = $this->seed_pages();
		$page_id     = $pages['centro-de-dia'];

		// Instalaciones: nombre de la sala, foto y texto alternativo.
		$spaces = [
			[ '2026/01/20260108_120755.jpg', 'Recepción', 'Recepción del centro, amplia y luminosa' ],
			[ '2026/01/20260108_111217.jpg', 'Rehabilitación funcional', 'Sala de rehabilitación funcional con aparatos de ejercicio' ],
			[ '2026/01/20260108_113221.jpg', 'Fisioterapia individual', 'Sala de fisioterapia individual con camilla' ],
			[ '2026/01/20260108_120850.jpg', 'Logopedia', 'Sala de logopedia' ],
			[ '2026/01/20260108_110920.jpg', 'Estimulación cognitiva informatizada', 'Sala con ordenadores para estimulación cognitiva' ],
			[ '2026/01/20260108_111615.jpg', 'Neuropsicología', 'Despacho de neuropsicología' ],
			[ '2026/01/20260108_112732.jpg', 'Psicología', 'Despacho de la unidad de psicología' ],
			[ '2026/05/20260518_101622.jpg', 'Sala multisensorial', 'Sala multisensorial con luces y elementos de estimulación' ],
			[ '2026/01/20260120_112924.jpg', 'Comedor', 'Comedor con mesas preparadas' ],
			[ '2026/01/20260109_091918.jpg', 'Salón de estar', 'Salón de estar con sillones' ],
			[ '2026/01/20260108_113359.jpg', 'Espacio de investigación', 'Espacio de investigación y desarrollo' ],
			[ '2026/01/20260108_112838.jpg', 'Salón de actos', 'Salón de actos con capacidad para unas 70 personas' ],
		];

		$this->seed_items( PostTypes\Espacio::POST_TYPE, array_map( static fn ( array $space ): array => [ $space[1], '', [] ], $spaces ) );

		if ( $with_images ) {
			foreach ( $spaces as [ $path, $title, $alt ] ) {
				$found = get_posts(
					[
						'post_type'      => PostTypes\Espacio::POST_TYPE,
						'title'          => $title,
						'posts_per_page' => 1,
						'fields'         => 'ids',
					]
				);
				if ( $found && ! has_post_thumbnail( $found[0] ) ) {
					$image_id = $this->image( $path, $alt );
					if ( $image_id > 0 ) {
						set_post_thumbnail( $found[0], $image_id );
					}
				}
			}
		}

		// Preguntas frecuentes del tema «Centro de día».
		$topic = term_exists( 'Centro de día', PostTypes\Pregunta::TAXONOMY );
		if ( ! $topic ) {
			$topic = wp_insert_term( 'Centro de día', PostTypes\Pregunta::TAXONOMY );
		}
		$topic_id = is_array( $topic ) ? (int) $topic['term_id'] : 0;

		$faqs = [
			'¿Hace falta tener reconocida la dependencia?' => 'Para una plaza concertada, sí. Si todavía no la tienes, nuestra trabajadora social te explica cómo pedirla y te acompaña en el proceso. Mientras tanto, puedes valorar una plaza privada.',
			'¿Qué incluye la plaza?'                      => 'Las terapias de tu plan, la comida del mediodía y el transporte adaptado de ida y vuelta.',
			'¿Desde qué localidades hay transporte?'      => 'Atendemos a personas de toda la provincia. Las rutas dependen de cada año, así que lo mejor es llamarnos y te confirmamos si pasamos por tu zona.',
			'¿Puedo venir algunos días a la semana?'      => 'Cuéntanos qué necesitas. Buscamos la fórmula que mejor encaje contigo y con tu familia.',
			'¿Las familias también pueden recibir apoyo?' => 'Sí. Tenemos atención psicológica y grupos de autoayuda para familiares y cuidadores, aunque la persona con párkinson no venga al centro de día.',
		];

		$order = 0;
		foreach ( $faqs as $question => $answer ) {
			++$order;
			$existing = get_posts(
				[
					'post_type'      => PostTypes\Pregunta::POST_TYPE,
					'post_status'    => 'any',
					'title'          => $question,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);
			if ( $existing ) {
				continue;
			}
			$faq_id = (int) wp_insert_post(
				[
					'post_type'    => PostTypes\Pregunta::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $question,
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $answer ) . '</p><!-- /wp:paragraph -->',
					'menu_order'   => $order,
				]
			);
			if ( $topic_id > 0 ) {
				wp_set_object_terms( $faq_id, [ $topic_id ], PostTypes\Pregunta::TAXONOMY );
			}
		}
		WP_CLI::log( 'Contenidos comprobados: ' . PostTypes\Pregunta::POST_TYPE );

		$has = '' !== (string) get_post_meta( $page_id, '_elementor_data', true );

		if ( $has && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La página ya tiene contenido; no se toca (usa --force para reescribirla).' );
		} else {
			$rows = static function ( array $items ): array {
				foreach ( $items as $index => $item ) {
					$items[ $index ]['_id'] = substr( md5( wp_json_encode( $item ) . $index ), 0, 7 );
				}
				return $items;
			};

			$day_image_id = $with_images ? $this->image( '2026/01/20260109_091918.jpg', 'Salón de estar del centro, luminoso, con sillones' ) : 0;

			$this->save_page(
				$page_id,
				[
					'pbc-page-header' => [
						'title'     => 'Centro de día para personas con párkinson',
						'title_tag' => 'h1',
						'text'      => 'También lo llamamos Unidad de Estancia Diurna. Vienes por la mañana, haces tus terapias, comes con nosotros y vuelves a casa por la tarde. Nosotros te recogemos y te llevamos.',
						'variant'   => 'sand',
						// El horario y el calendario son los datos confirmados del proyecto (CLAUDE.md), no los del boceto.
						'facts'     => $rows(
							[
								[
									'label' => 'Horario',
									'value' => 'Lunes a viernes, de 9:00 a 17:00',
								],
								[
									'label' => 'Dónde',
									'value' => 'Camposoto, San Fernando',
								],
								[
									'label' => 'Calendario',
									'value' => 'Cerrado en agosto',
								],
							]
						),
					],
					'pbc-notice'      => [
						'lead'             => 'Ahora mismo hay lista de espera.',
						'text'             => 'Llámanos igualmente: te apuntamos, te orientamos y te contamos qué puedes hacer mientras tanto.',
						'button_label'     => 'Llamar al 956 59 19 28',
						'button_link_page' => 'custom',
						'button_link'      => [
							'url'         => 'tel:+34956591928',
							'is_external' => '',
							'nofollow'    => '',
						],
					],
					'pbc-schedule'    => [
						'title'     => 'Así es un día en el centro',
						'title_tag' => 'h2',
						'text'      => 'Cada persona tiene su plan, pero el día suele ir más o menos así. Las terapias se hacen en grupos pequeños o de forma individual.',
						'image'     => [
							'id'  => $day_image_id > 0 ? $day_image_id : '',
							'url' => $day_image_id > 0 ? (string) wp_get_attachment_url( $day_image_id ) : '',
						],
						'items'     => $rows(
							[
								[
									'time'  => '9:00',
									'title' => 'Te recogemos en casa',
									'text'  => 'El transporte adaptado pasa por tu puerta.',
								],
								[
									'time'  => '10:00',
									'title' => 'Terapias de la mañana',
									'text'  => 'Fisioterapia, logopedia o estimulación cognitiva, según tu plan.',
								],
								[
									'time'  => '12:00',
									'title' => 'Un rato de descanso',
									'text'  => 'Charla, café y tiempo en el salón con los compañeros.',
								],
								[
									'time'  => '13:30',
									'title' => 'Comida',
									'text'  => 'Menú elaborado en nuestra cocina, adaptado a cada persona.',
								],
								[
									'time'  => '15:00',
									'title' => 'Talleres de la tarde',
									'text'  => 'Musicoterapia, cineterapia y actividades en grupo.',
								],
								[
									'time'  => '16:30',
									'title' => 'Vuelta a casa',
									'text'  => 'Te dejamos de nuevo en tu puerta.',
								],
							]
						),
					],
					'pbc-steps'       => [
						'title'     => 'Cómo solicitar plaza',
						'title_tag' => 'h2',
						'intro'     => 'Te acompañamos en cada paso. Si algo no lo entiendes, pregúntanos: para eso estamos.',
						'variant'   => 'dark',
						'items'     => $rows(
							[
								[
									'title' => 'Llámanos o escríbenos',
									'text'  => 'Al 956 59 19 28 o a unidad@parkinsonbahiadecadiz.org. También puede llamar un familiar.',
								],
								[
									'title' => 'Visita el centro',
									'text'  => 'Pide cita y ven con quien quieras. Te enseñamos las salas y conoces al equipo.',
								],
								[
									'title' => 'Entrevista con trabajo social',
									'text'  => 'Vemos tu situación y te ayudamos con la solicitud de dependencia si hace falta.',
								],
								[
									'title' => 'Tu plan y tu ruta',
									'text'  => 'El equipo prepara tus terapias y te asignamos el transporte desde casa.',
								],
							]
						),
						'notes'     => $rows(
							[
								[
									'title' => 'Plaza concertada',
									'text'  => 'A través de la Junta de Andalucía, para personas con la situación de dependencia reconocida. Te ayudamos con la solicitud si aún no la tienes.',
								],
								[
									'title' => 'Plaza privada',
									'text'  => 'Para quien no tiene la dependencia reconocida o está esperando la resolución. Pregúntanos el precio y la disponibilidad.',
								],
							]
						),
					],
					'pbc-spaces'      => [
						'title'     => 'Las instalaciones',
						'title_tag' => 'h2',
						'text'      => 'Más de 1.900 m² sin barreras, en dos plantas con ascensor. Puedes venir a verlas: pide cita y te las enseñamos.',
						'footnote'  => 'El equipamiento se ha financiado con la ayuda de la Diputación de Cádiz, el Ayuntamiento de San Fernando, la Fundación "la Caixa", la Fundación Bidafarma y aportación propia.',
						'count'     => 0,
					],
					'pbc-media-text'  => [
						'title'     => 'Te recogemos en casa',
						'title_tag' => 'h2',
						'text'      => "Tenemos furgonetas adaptadas y cuatro conductores que ya conocen a cada persona. Te recogen por la mañana y te dejan en tu puerta por la tarde. El servicio cuenta con el apoyo de la Diputación de Cádiz.\n\n¿Vives fuera de San Fernando? Llámanos y te decimos si tu localidad está en alguna de nuestras rutas.",
						'variant'   => 'sand',
					],
					'pbc-faq'         => [
						'title'     => 'Preguntas frecuentes',
						'title_tag' => 'h2',
						'topic'     => $topic_id > 0 ? 'term-' . $topic_id : '',
					],
				],
				'centro'
			);
			WP_CLI::log( 'Página «Centro de día» montada con sus widgets.' );
		}

		WP_CLI::success( 'Centro de día listo: ' . get_permalink( $page_id ) );
	}

	/**
	 * Crea la página «Colabora» con sus widgets.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function colabora( array $args, array $assoc_args ): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			WP_CLI::error( 'Elementor no está activo.' );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No hay ningún usuario administrador.' );
		}
		wp_set_current_user( (int) $admins[0] );

		$pages   = $this->seed_pages();
		$page_id = $pages['colabora'];
		$has     = '' !== (string) get_post_meta( $page_id, '_elementor_data', true );

		if ( $has && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La página ya tiene contenido; no se toca (usa --force para reescribirla).' );
			WP_CLI::success( 'Colabora lista: ' . get_permalink( $page_id ) );
			return;
		}

		$rows   = static function ( array $items ): array {
			foreach ( $items as $index => $item ) {
				$items[ $index ]['_id'] = substr( md5( wp_json_encode( $item ) . $index ), 0, 7 );
			}
			return $items;
		};
		$custom = static fn ( string $url ): array => [
			'url'         => $url,
			'is_external' => '',
			'nofollow'    => '',
		];

		$this->save_page(
			$page_id,
			[
				'pbc-page-header' => [
					'title'     => 'Con tu ayuda, más familias tienen a quién acudir',
					'title_tag' => 'h1',
					'text'      => 'Hay muchas maneras de colaborar. Elige la que mejor encaje contigo.',
					'variant'   => 'clay',
					'jumps'     => $rows(
						[
							[
								'label'     => 'Hazte socio',
								'link_page' => 'custom',
								'link'      => $custom( '#socio' ),
							],
							[
								'label'     => 'Haz un donativo',
								'link_page' => 'custom',
								'link'      => $custom( '#dona' ),
							],
							[
								'label'     => 'Hazte voluntario',
								'link_page' => 'custom',
								'link'      => $custom( '#voluntariado' ),
							],
						]
					),
				],
				// Dos formularios: cada uno necesita su propia clave en la lista.
				'pbc-form'        => [
					'form'          => 'socio',
					'title'         => 'Hazte socio o socia',
					'title_tag'     => 'h2',
					'text'          => "Tu cuota ayuda a mantener las terapias, el transporte y el apoyo a las familias. Ser socio también te da acceso a todos nuestros servicios y actividades.\n\nRellena el formulario en unos minutos. Si prefieres hacerlo por teléfono, llámanos al 956 59 19 28 y lo hacemos contigo.",
					'anchor'        => 'socio',
					'sticky'        => 'yes',
					// Cuotas del boceto: pendientes de confirmar con la asociación.
					'amounts'       => '5, 10, 20',
					'submit_label'  => 'Quiero hacerme socio',
					'success_title' => '¡Gracias por unirte!',
					'success_text'  => 'Hemos recibido tus datos. Te llamaremos para darte la bienvenida y confirmar la domiciliación.',
				],
				'pbc-donation'    => [
					'title'     => 'Haz un donativo',
					'title_tag' => 'h2',
					'text'      => 'Un donativo puntual también ayuda. Lo destinamos a lo que más falta haga en cada momento: terapias, transporte o equipamiento del centro.',
					'anchor'    => 'dona',
					'sticky'    => 'yes',
					// Bizum y tarjeta se añadirán cuando la asociación decida la pasarela de pago.
					'methods'   => $rows(
						[
							[
								'title'        => 'Por transferencia',
								'text'         => 'Escríbenos a unidad@parkinsonbahiadecadiz.org y te enviamos los datos de la cuenta.',
								'button_label' => 'Pedir los datos por correo',
								'link_page'    => 'custom',
								'link'         => $custom( 'mailto:unidad@parkinsonbahiadecadiz.org?subject=Quiero%20hacer%20un%20donativo' ),
							],
							[
								'title'        => 'Por teléfono',
								'text'         => 'Llámanos de lunes a viernes, de 9:00 a 17:00, y te explicamos cómo hacerlo.',
								'button_label' => 'Llamar al 956 59 19 28',
								'link_page'    => 'custom',
								'link'         => $custom( 'tel:+34956591928' ),
							],
						]
					),
				],
				'pbc-form#2'      => [
					'form'          => 'voluntariado',
					'title'         => 'Hazte voluntario',
					'title_tag'     => 'h2',
					'text'          => 'Unas horas al mes pueden cambiarle el día a alguien. No hace falta experiencia: te formamos y te acompañamos desde el primer día.',
					'anchor'        => 'voluntariado',
					'sticky'        => 'yes',
					'areas'         => "Acompañamiento y ocio\nEventos y sensibilización\nComunicación y redes sociales\nJunta directiva",
					'submit_label'  => 'Quiero ser voluntario',
					'success_title' => '¡Gracias por ofrecerte!',
					'success_text'  => 'Te llamaremos para conocerte y contarte cómo empezar.',
				],
				'pbc-cta'         => [
					'title'            => 'Empresas y entidades',
					'title_tag'        => 'h2',
					'text'             => 'Hidralia, la Fundación "la Caixa" o la Fundación Bidafarma ya nos ayudan. Si tu empresa quiere colaborar con un proyecto, equipamiento o voluntariado corporativo, hablemos.',
					'button_label'     => 'Escríbenos',
					'button_link_page' => 'custom',
					'button_link'      => $custom( 'mailto:unidad@parkinsonbahiadecadiz.org' ),
				],
			],
			'colabora'
		);

		WP_CLI::log( 'Página «Colabora» montada con sus widgets.' );
		WP_CLI::success( 'Colabora lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Completa las terapias (qué trabajamos, quién la hace y foto) y monta la página «Terapias».
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * [--skip-images]
	 * : No descarga las fotos de la web actual.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function terapias( array $args, array $assoc_args ): void {
		$this->start();

		$with_images = empty( $assoc_args['skip-images'] );
		$pages       = $this->seed_pages();
		$page_id     = $pages['terapias'];

		$this->seed_therapies();

		// Nombre => qué trabajamos, quién la hace, foto y texto alternativo (sin foto: '').
		$details = [
			'Fisioterapia'           => [ [ 'Equilibrio y prevención de caídas', 'Marcha, postura y flexibilidad', 'Fuerza y resistencia' ], 'Fisioterapeuta, en sesiones individuales y en grupo', '2026/01/20260108_111217.jpg', 'Sala de rehabilitación funcional con espalderas y aparatos' ],
			'Logopedia'              => [ [ 'Volumen y claridad de la voz', 'Respiración y articulación', 'Deglución segura (disfagia)' ], 'Logopeda, en sesiones individuales', '2026/01/20260108_120850.jpg', 'Sala de logopedia con mesa y espejo' ],
			'Psicología'             => [ [ 'Ansiedad, tristeza y apatía', 'Aceptación del diagnóstico', 'Apoyo a la pareja y a la familia' ], 'Dos psicólogos, en sesiones individuales y grupos', '2026/01/20260108_112732.jpg', 'Despacho de psicología con sillones' ],
			'Estimulación cognitiva' => [ [ 'Memoria y atención', 'Orientación y lenguaje', 'Rehabilitación cognitiva personalizada' ], 'Equipo de psicología y neuropsicología', '2026/01/20260108_110920.jpg', 'Sala con ordenadores para estimulación cognitiva' ],
			'Musicoterapia'          => [ [ 'Ritmo y coordinación del movimiento', 'Voz y respiración a través del canto', 'Relación con los compañeros' ], 'En grupo, dentro del centro de día', '', '' ],
			'Cineterapia'            => [ [ 'Expresión de emociones', 'Atención y memoria', 'Conversación y compañía' ], 'En grupo, en el salón de actos', '2026/01/20260108_112838.jpg', 'Salón de actos del centro con butacas' ],
			'Trabajo social'         => [ [ 'Solicitud de dependencia y discapacidad', 'Ayudas y recursos públicos', 'Orientación a la familia' ], 'Trabajadora social, con cita previa', '2026/01/20260108_112043.jpg', 'Despacho de trabajo social' ],
			'Apoyo a familias'       => [ [ 'Grupos de autoayuda', 'Atención psicológica al cuidador', 'Pautas para el día a día en casa' ], 'Equipo de psicología y trabajo social', '', '' ],
		];

		foreach ( $details as $title => [ $works, $who, $path, $alt ] ) {
			$therapy = get_page_by_path( sanitize_title( $title ), OBJECT, Terapia::POST_TYPE );
			if ( ! $therapy ) {
				continue;
			}
			// Solo se rellena lo que está vacío: no se pisa lo que haya escrito la asociación.
			if ( '' === (string) get_post_meta( $therapy->ID, Terapia::META_WORKS, true ) ) {
				update_post_meta( $therapy->ID, Terapia::META_WORKS, implode( "\n", $works ) );
			}
			if ( '' === (string) get_post_meta( $therapy->ID, Terapia::META_WHO, true ) ) {
				update_post_meta( $therapy->ID, Terapia::META_WHO, $who );
			}
			if ( $with_images && '' !== $path && ! has_post_thumbnail( $therapy ) ) {
				$image_id = $this->image( $path, $alt );
				if ( $image_id > 0 ) {
					set_post_thumbnail( $therapy, $image_id );
				}
			}
		}
		WP_CLI::log( 'Terapias completadas.' );

		if ( ! $this->may_write( $page_id, $assoc_args ) ) {
			WP_CLI::success( 'Terapias lista: ' . get_permalink( $page_id ) );
			return;
		}

		$this->save_page(
			$page_id,
			[
				'pbc-page-header'     => [
					'title'        => 'Terapias para seguir haciendo lo que te importa',
					'title_tag'    => 'h1',
					'text'         => 'El párkinson afecta al movimiento, a la voz, a la memoria y al ánimo. Por eso lo tratamos desde todos los lados a la vez, con un plan hecho para cada persona.',
					'variant'      => 'dark',
					'jumps_source' => 'therapies',
					'chips_label'  => 'Ir a una terapia',
				],
				'pbc-therapy-details' => [
					'works_label' => 'Qué trabajamos',
					'who_label'   => 'Quién la hace:',
				],
				'pbc-cta'             => [
					'title'             => '¿No sabes qué terapia necesitas?',
					'title_tag'         => 'h2',
					'text'              => 'No hace falta saberlo. Llámanos y el equipo te orienta según tu situación.',
					'variant'           => 'mist',
					'button_label'      => 'Llamar al 956 59 19 28',
					'button_link_page'  => 'custom',
					'button_link'       => $this->custom_link( 'tel:+34956591928' ),
					'button2_label'     => 'Ver el centro de día',
					'button2_link_page' => 'page-' . $pages['centro-de-dia'],
				],
			],
			'terapias'
		);

		WP_CLI::log( 'Página «Terapias» montada con sus widgets.' );
		WP_CLI::success( 'Terapias lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Crea las guías para descargar, los vídeos de ejercicios y la página «Familias y cuidadores».
	 *
	 * Las guías son las que ya publica la web actual en /archivos/ y los vídeos,
	 * los del canal de YouTube de la asociación.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * [--skip-images]
	 * : No descarga los PDF de las guías ni las miniaturas de los vídeos.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function familias( array $args, array $assoc_args ): void {
		$this->start();

		$with_files = empty( $assoc_args['skip-images'] );
		$pages      = $this->seed_pages();
		$page_id    = $pages['familias-y-cuidadores'];

		// Tema => [ título, archivo en /archivos/ de la web actual ].
		$guides = [
			'Alimentación' => [
				[ 'Consejos sobre alimentación', 'consejos-alimentacion-pacientes-parkinson.pdf' ],
				[ 'Guía de nutrición para personas con disfagia', 'guia_nutricion_disfalgia.pdf' ],
			],
			'Voz y habla'  => [
				[ 'Ejercicios de habla y voz', 'Ejercicios-fisicos-de-habla-y-voz.pdf' ],
				[ 'Consejos para comunicarse mejor', 'consejos-sobre-correcta-comunicacion-pacientes-parkinson.pdf' ],
			],
			'Memoria'      => [
				[ 'Cuaderno de ejercicios de memoria', 'mayores-ejercicio-memoria-parkison.pdf' ],
				[ 'Cuaderno de estimulación cognitiva (nivel avanzado)', 'Cuaderno-de-Estimulacion-cognitiva-nivel-avanzado.pdf' ],
			],
			'Vida diaria'  => [
				[ 'Cómo preparar la visita al neurólogo', 'Guia-Visita-A-Tu-Neurologo.pdf' ],
				[ 'Registro de mis síntomas', 'Mis sintomas de parkinson.pdf' ],
				[ 'Consejos para las actividades de la vida diaria', 'consejos-para-actividades-vida-diaria-pacientes-enfermedad-parkinson.pdf' ],
				[ 'Antes de decir «no puedo», inténtalo', 'antes-de-decir-no-puedo-intentalo.pdf' ],
				[ 'Cuidados de la piel', 'CUIDADOS_DE_LA_PIEL_FEP.pdf' ],
				[ 'Mi vida con párkinson. Volumen I', 'mi-vida-con-parkinson-vol-1.pdf' ],
				[ 'Mi vida con párkinson. Volumen II', 'mi-vida-con-parkinson-vol-2.pdf' ],
			],
		];

		$order = 0;
		foreach ( $guides as $topic => $items ) {
			$term = term_exists( $topic, PostTypes\Recurso::TAXONOMY );
			if ( ! $term ) {
				$term = wp_insert_term( $topic, PostTypes\Recurso::TAXONOMY );
			}
			$term_id = is_array( $term ) ? (int) $term['term_id'] : 0;

			foreach ( $items as [ $title, $file ] ) {
				++$order;
				$existing = get_posts(
					[
						'post_type'      => PostTypes\Recurso::POST_TYPE,
						'post_status'    => 'any',
						'title'          => $title,
						'posts_per_page' => 1,
						'fields'         => 'ids',
					]
				);
				$guide_id = $existing ? (int) $existing[0] : (int) wp_insert_post(
					[
						'post_type'   => PostTypes\Recurso::POST_TYPE,
						'post_status' => 'publish',
						'post_title'  => $title,
						'menu_order'  => $order,
					]
				);
				if ( $term_id > 0 && ! $existing ) {
					wp_set_object_terms( $guide_id, [ $term_id ], PostTypes\Recurso::TAXONOMY );
				}
				if ( $with_files && 0 === absint( get_post_meta( $guide_id, PostTypes\Recurso::META_FILE, true ) ) ) {
					$file_id = $this->file( 'https://parkinsonbahiadecadiz.org/archivos/' . rawurlencode( $file ), $title );
					if ( $file_id > 0 ) {
						update_post_meta( $guide_id, PostTypes\Recurso::META_FILE, $file_id );
					}
				}
			}
		}
		WP_CLI::log( 'Contenidos comprobados: ' . PostTypes\Recurso::POST_TYPE );

		// Vídeos del canal de la asociación: título => identificador de YouTube.
		$videos = [
			'Equilibrio y reeducación del paso'  => 'QemMkf4iCWM',
			'Ejercicios para mejorar la postura' => 'hiK66dmsUmc',
			'Ejercicios de movilidad fina'       => 'AaTPyhEYOjc',
		];
		$this->seed_items(
			PostTypes\Video::POST_TYPE,
			array_map(
				static fn ( string $title, string $id ): array => [ $title, '', [ PostTypes\Video::META_URL => 'https://www.youtube.com/watch?v=' . $id ] ],
				array_keys( $videos ),
				array_values( $videos )
			)
		);
		if ( $with_files ) {
			foreach ( $videos as $title => $id ) {
				$found = get_posts(
					[
						'post_type'      => PostTypes\Video::POST_TYPE,
						'title'          => $title,
						'posts_per_page' => 1,
						'fields'         => 'ids',
					]
				);
				if ( $found && ! has_post_thumbnail( $found[0] ) ) {
					// La miniatura se copia una vez a la biblioteca: la web no pide nada a YouTube hasta que se pulsa el vídeo.
					$image_id = $this->file( 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg', $title, 'video-' . $id . '.jpg' );
					if ( $image_id > 0 ) {
						update_post_meta( $image_id, '_wp_attachment_image_alt', '' );
						set_post_thumbnail( $found[0], $image_id );
					}
				}
			}
		}

		if ( ! $this->may_write( $page_id, $assoc_args ) ) {
			WP_CLI::success( 'Familias y cuidadores lista: ' . get_permalink( $page_id ) );
			return;
		}

		$this->save_page(
			$page_id,
			[
				'pbc-page-header' => [
					'title'     => 'Para quien vive el párkinson de cerca',
					'title_tag' => 'h1',
					'text'      => 'Guías, ejercicios para casa y apoyo para las familias. Todo gratuito y escrito para que se entienda a la primera.',
					'variant'   => 'sand',
				],
				'pbc-steps'       => [
					'title'     => 'Si te acaban de dar el diagnóstico',
					'title_tag' => 'h2',
					'intro'     => 'Cuatro cosas que nos dicen las familias que les ayudaron al principio.',
					'items'     => $this->rows(
						[
							[
								'title' => 'Date tiempo',
								'text'  => 'Es normal sentir miedo o rabia. Muchas personas llevan años viviendo bien con párkinson. No tienes que entenderlo todo hoy.',
							],
							[
								'title' => 'Pregunta todo lo que necesites',
								'text'  => 'Apunta tus dudas antes de ir al neurólogo. Tenemos una guía para preparar la visita.',
							],
							[
								'title' => 'Muévete cada día',
								'text'  => 'Caminar, estirarse o bailar ayuda. El ejercicio es parte del tratamiento.',
							],
							[
								'title' => 'Habla con nosotros',
								'text'  => 'Te contamos qué recursos tienes cerca y te ponemos en contacto con otras familias.',
							],
						]
					),
				],
				'pbc-resources'   => [
					'title'       => 'Guías y fichas para descargar',
					'title_tag'   => 'h2',
					'text'        => 'Puedes imprimirlas o pedirnos una copia en papel en el centro.',
					'show_filter' => 'yes',
					'all_label'   => 'Todas',
					'anchor'      => 'recursos',
				],
				'pbc-videos'      => [
					'title'             => 'Ejercicios para hacer en casa',
					'title_tag'         => 'h2',
					'count'             => 3,
					'channel_label'     => 'Ver todos en YouTube',
					'channel_link_page' => 'custom',
					'channel_link'      => $this->custom_link( 'https://www.youtube.com/channel/UC7-c1fm0in6KnCjeZzd5Mlg' ),
					'note'              => 'Haz los ejercicios sentado si no te sientes seguro de pie, y para si notas dolor o mareo.',
					'privacy_note'      => 'Al pulsar un vídeo se carga desde YouTube, que puede guardar cookies en tu navegador.',
				],
				'pbc-media-text'  => [
					'title'            => 'Cuidar también cansa',
					'title_tag'        => 'h2',
					'text'             => "Si cuidas de alguien con párkinson, tú también puedes venir. Tenemos atención psicológica para familiares y grupos de autoayuda donde hablar con otras personas que están pasando por lo mismo.\n\nNo hace falta que tu familiar venga al centro de día.",
					'button_label'     => 'Llamar al 956 59 19 28',
					'button_link_page' => 'custom',
					'button_link'      => $this->custom_link( 'tel:+34956591928' ),
					'image_side'       => 'right',
					'variant'          => '',
				],
			],
			'familias'
		);

		WP_CLI::log( 'Página «Familias y cuidadores» montada con sus widgets.' );
		WP_CLI::success( 'Familias y cuidadores lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Monta la página «Contacto»: formulario de contacto con el teléfono en grande.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el contenido de la página aunque ya tenga widgets.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function contacto( array $args, array $assoc_args ): void {
		$this->start();

		$pages   = $this->seed_pages();
		$page_id = $pages['contacto'];

		if ( $this->may_write( $page_id, $assoc_args ) ) {
			$this->save_page(
				$page_id,
				[
					'pbc-form' => [
						'form'          => 'contacto',
						'title'         => 'Cuéntanos qué necesitas',
						'title_tag'     => 'h1',
						'breadcrumb'    => 'yes',
						'variant'       => 'sand',
						'text'          => 'Déjanos tu teléfono y te llamamos nosotros. Si lo prefieres, llámanos directamente de lunes a viernes, de 9:00 a 17:00.',
						'phone'         => '956 59 19 28',
						'sticky'        => 'yes',
						'submit_label'  => 'Enviar',
						'success_title' => 'Mensaje enviado',
						'success_text'  => 'Gracias. Te llamaremos lo antes posible, de lunes a viernes.',
					],
				],
				'contacto'
			);
			WP_CLI::log( 'Página «Contacto» montada con sus widgets.' );
		}

		WP_CLI::success( 'Contacto lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Crea las apariciones en prensa («En los medios», en Actualidad) y los
	 * financiadores de la banda del pie.
	 *
	 * Los financiadores se crean sin logotipo (se ve su nombre): los logotipos
	 * oficiales los tiene que facilitar la asociación.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function actualidad( array $args, array $assoc_args ): void {
		$this->start();

		$press = [
			[ 'San Fernando estrena centro para pacientes de párkinson', 'ESdiario', '2026-01-15', 'https://www.esdiario.com/andalucia/260115/177053/san-fernando-cadiz-estrena-centro-pacientes-parkinson-breve-nuevo-centro-salud.html' ],
			[ 'Moreno resalta la apertura en San Fernando de un centro de referencia nacional para pacientes con párkinson', 'Psiquiatria.com', '2026-01-16', 'https://psiquiatria.com/parkinson-y-trastornos-del-movimiento/moreno-resalta-la-apertura-en-san-fernando-de-un-centro-de-referencia-nacional-para-pacientes-con-parkinson/' ],
			[ 'La Junta concede una ayuda de 17.000 euros a la asociación Párkinson Bahía de Cádiz', 'Andalucía Información', '2025-10-29', 'https://www.andaluciainformacion.es/articulo/san-fernando-local/junta-concede-ayuda-17000-euros-asociacion-parkinson-bahia-cadiz/202510291318513159528.html' ],
			[ 'Colocada la primera piedra de la nueva Unidad de Estancia Diurna de Párkinson Bahía de Cádiz', 'Diario Bahía de Cádiz', '2020-11-03', 'https://www.diariobahiadecadiz.com/san-fernando/colocada-la-primera-piedra-de-la-nueva-unidad-de-estancia-diurna-de-parkinson-bahia-de-cadiz-con-el-objetivo-de-atender-a-mas-enfermos' ],
		];

		$this->seed_items(
			PostTypes\Prensa::POST_TYPE,
			array_map(
				static fn ( array $item ): array => [
					$item[0],
					'',
					[
						PostTypes\Prensa::META_MEDIA => $item[1],
						PostTypes\Prensa::META_DATE  => $item[2],
						PostTypes\Prensa::META_URL   => $item[3],
					],
				],
				$press
			)
		);

		// Financiadores y colaboradores según la web actual (docs/investigacion.md).
		$funders = [
			'Junta de Andalucía',
			'Next Generation EU',
			'Diputación de Cádiz',
			'Ayuntamiento de San Fernando',
			'Ayuntamiento de Chiclana',
			'Fundación «la Caixa»',
			'Fundación Bidafarma',
			'FANDEP',
			'Hidralia',
		];

		$this->seed_items( PostTypes\Financiador::POST_TYPE, array_map( static fn ( string $name ): array => [ $name, '', [] ], $funders ) );
		delete_transient( 'pbc_funders_html' );

		WP_CLI::success( 'Apariciones en prensa y financiadores listos.' );
	}

	/**
	 * Descarga un archivo a la biblioteca de medios (una sola vez por dirección de origen).
	 */
	private function file( string $url, string $title, string $name = '', ?callable $prepare = null ): int {
		$found = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Comando puntual.
				'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Comando puntual.
			]
		);

		if ( $found ) {
			return (int) $found[0];
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 120 );

		if ( is_wp_error( $tmp ) ) {
			WP_CLI::warning( "No se pudo descargar {$url}: " . $tmp->get_error_message() );
			return 0;
		}

		if ( $prepare ) {
			$tmp = $prepare( $tmp );
		}

		$id = media_handle_sideload(
			[
				'name'     => '' !== $name ? $name : sanitize_file_name( rawurldecode( wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) ),
				'tmp_name' => $tmp,
			],
			0,
			$title
		);

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			WP_CLI::warning( "No se pudo guardar {$url}: " . $id->get_error_message() );
			return 0;
		}

		update_post_meta( (int) $id, self::SOURCE_META, $url );
		WP_CLI::log( "Archivo descargado: {$title}" );

		return (int) $id;
	}

	/**
	 * Comprobaciones comunes de los comandos que montan páginas: Elementor
	 * activo y un administrador como usuario actual.
	 */
	private function start(): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			WP_CLI::error( 'Elementor no está activo.' );
		}

		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No hay ningún usuario administrador.' );
		}
		wp_set_current_user( (int) $admins[0] );
	}

	/**
	 * Una página solo se reescribe si está vacía o si se pasa --force.
	 *
	 * @param array<string, mixed> $assoc_args Opciones del comando.
	 */
	private function may_write( int $page_id, array $assoc_args ): bool {
		if ( '' !== (string) get_post_meta( $page_id, '_elementor_data', true ) && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La página ya tiene contenido; no se toca (usa --force para reescribirla).' );
			return false;
		}

		return true;
	}

	/**
	 * Valor de un enlace personalizado (control URL de Elementor).
	 *
	 * @return array<string, string>
	 */
	private function custom_link( string $url ): array {
		return [
			'url'         => $url,
			'is_external' => '',
			'nofollow'    => '',
		];
	}

	/**
	 * Filas de un repetidor con su identificador interno.
	 *
	 * @param array<int, array<string, mixed>> $items Filas.
	 * @return array<int, array<string, mixed>>
	 */
	private function rows( array $items ): array {
		foreach ( $items as $index => $item ) {
			$items[ $index ]['_id'] = substr( md5( (string) wp_json_encode( $item ) . $index ), 0, 7 );
		}

		return $items;
	}

	/**
	 * Completa el pie de todas las páginas como en el diseño: logotipos de los
	 * financiadores, páginas «Declaración de accesibilidad» y «Aviso legal», y
	 * los menús «La asociación» e «Información legal».
	 *
	 * Los logotipos salen de la web actual de la asociación y, los que no están
	 * allí, de Wikimedia Commons. Solo se ponen a los financiadores que todavía
	 * no tienen logotipo. Se recortan los márgenes blancos para que todos se vean
	 * del mismo tamaño dentro de su recuadro.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe las páginas legales y los menús del pie aunque se hayan cambiado a mano.
	 *
	 * [--skip-images]
	 * : No descarga los logotipos.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function pie( array $args, array $assoc_args ): void {
		$this->start();

		if ( empty( $assoc_args['skip-images'] ) ) {
			$this->funder_logos();
		}

		$this->asociacion_anchors();

		$this->prose_page(
			'declaracion-de-accesibilidad',
			'Declaración de accesibilidad',
			'Qué hemos hecho para que esta web sea fácil de usar, qué partes todavía no lo son y cómo avisarnos si encuentras un problema.',
			$this->blocks( $this->accessibility_sections() ),
			$assoc_args
		);
		$this->prose_page(
			'aviso-legal',
			'Aviso legal',
			'Quién es el titular de esta web, para qué sirve y las condiciones para usarla.',
			$this->blocks( $this->legal_notice_sections() ),
			$assoc_args
		);
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( $privacy <= 0 || 'publish' !== get_post_status( $privacy ) ) {
			WP_CLI::warning( 'Falta la política de privacidad: ejecuta «wp pbc seed-privacidad» y vuelve a lanzar este comando.' );
		}
		$this->footer_menu( 'Legal', 'footer-legal', $this->legal_menu_items(), $assoc_args );

		$asociacion = get_page_by_path( 'la-asociacion' );
		if ( $asociacion ) {
			$url = (string) get_permalink( $asociacion );
			$this->footer_menu(
				'Pie: La asociación',
				'footer-asociacion',
				[
					[
						'page'  => (int) $asociacion->ID,
						'title' => 'Quiénes somos',
					],
					[
						'url'   => $url . '#equipo',
						'title' => 'Equipo y junta directiva',
					],
					[
						'url'   => $url . '#transparencia',
						'title' => 'Transparencia',
					],
					...( get_page_by_path( 'agenda' ) ? [ [ 'page' => (int) get_page_by_path( 'agenda' )->ID ] ] : [] ),
				],
				$assoc_args
			);
		} else {
			WP_CLI::warning( 'Falta la página «La asociación»: ejecuta «wp pbc seed-asociacion» y vuelve a lanzar este comando.' );
		}

		WP_CLI::success( 'Pie listo.' );
	}

	/**
	 * Pone el logotipo a cada financiador que todavía no lo tiene.
	 */
	private function funder_logos(): void {
		$commons = 'https://upload.wikimedia.org/wikipedia/commons/';
		$logos   = [
			'Junta de Andalucía'           => $commons . 'thumb/0/0c/Logotipo_de_la_Junta_de_Andaluc%C3%ADa_2020.svg/960px-Logotipo_de_la_Junta_de_Andaluc%C3%ADa_2020.svg.png',
			'Next Generation EU'           => $commons . '4/48/ES_Financiado_por_la_Union_Europea.webp',
			'Diputación de Cádiz'          => self::UPLOADS . '2022/05/logo_color_tintasplanas.png',
			'Ayuntamiento de San Fernando' => self::UPLOADS . '2026/04/548b3cf2-a7ee-45f6-97cd-08e13e61d413_16-9-aspect-ratio_850w_0.webp',
			'Ayuntamiento de Chiclana'     => self::UPLOADS . '2022/05/ayto_chiclana_vertical.jpg',
			'Fundación «la Caixa»'         => self::UPLOADS . '2026/04/fundacionlacaixa.jpeg',
			'Fundación Bidafarma'          => self::UPLOADS . '2026/04/logocabecera.png',
			'FANDEP'                       => 'https://parkinsonbahiadecadiz.org/FAN.jpg',
		];

		foreach ( $logos as $name => $url ) {
			$funder = get_posts(
				[
					'post_type'      => PostTypes\Financiador::POST_TYPE,
					'post_status'    => 'any',
					'title'          => $name,
					'posts_per_page' => 1,
				]
			);

			if ( ! $funder ) {
				WP_CLI::warning( "No existe el financiador «{$name}» (ejecuta «wp pbc seed-actualidad»)." );
				continue;
			}

			if ( has_post_thumbnail( $funder[0] ) ) {
				continue;
			}

			$id = $this->file( $url, 'Logotipo: ' . $name, 'logo-' . sanitize_title( $name ) . '.webp', [ $this, 'trim_logo' ] );

			if ( $id > 0 ) {
				update_post_meta( $id, '_wp_attachment_image_alt', $name );
				set_post_thumbnail( $funder[0], $id );
			}
		}

		delete_transient( 'pbc_funders_html' );
	}

	/**
	 * Recorta el margen blanco o transparente de un logotipo y lo guarda en WebP.
	 *
	 * Si la imagen no se puede leer, devuelve el archivo tal cual.
	 */
	public function trim_logo( string $path ): string {
		$data  = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Archivo temporal local.
		$image = '' !== $data ? @imagecreatefromstring( $data ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Formato no reconocido: se usa el original.

		if ( ! $image || ! function_exists( 'imagewebp' ) ) {
			return $path;
		}

		imagepalettetotruecolor( $image );
		$width  = imagesx( $image );
		$height = imagesy( $image );
		$left   = $width;
		$top    = $height;
		$right  = -1;
		$bottom = -1;

		imagealphablending( $image, false );
		$white = imagecolorallocatealpha( $image, 255, 255, 255, 0 );

		// Fondo = casi blanco o casi transparente. Los casi blancos se pasan a
		// blanco puro: algunos logotipos son fotos con el fondo gris o con ruido.
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$color = imagecolorat( $image, $x, $y );
				$alpha = ( $color >> 24 ) & 0x7F;
				$light = min( ( $color >> 16 ) & 0xFF, ( $color >> 8 ) & 0xFF, $color & 0xFF );

				if ( $alpha < 100 && $light >= 225 ) {
					imagesetpixel( $image, $x, $y, $white );
				} elseif ( $alpha < 100 ) {
					$left   = min( $left, $x );
					$right  = max( $right, $x );
					$top    = min( $top, $y );
					$bottom = max( $bottom, $y );
				}
			}
		}

		if ( $right < 0 ) {
			return $path;
		}

		$margin = (int) round( max( $right - $left, $bottom - $top ) * 0.02 );
		$left   = max( 0, $left - $margin );
		$top    = max( 0, $top - $margin );
		$crop   = imagecrop(
			$image,
			[
				'x'      => $left,
				'y'      => $top,
				'width'  => min( $width - 1, $right + $margin ) - $left + 1,
				'height' => min( $height - 1, $bottom + $margin ) - $top + 1,
			]
		);

		if ( ! $crop ) {
			return $path;
		}

		imagealphablending( $crop, false );
		imagesavealpha( $crop, true );
		$target = $path . '.webp';

		if ( ! imagewebp( $crop, $target, 90 ) ) {
			return $path;
		}

		wp_delete_file( $path );

		return $target;
	}

	/**
	 * Anclas #equipo y #transparencia en «La asociación», para los enlaces del pie.
	 * Solo se rellenan si el widget no tiene ya un ancla.
	 */
	private function asociacion_anchors(): void {
		$page = get_page_by_path( 'la-asociacion' );
		$data = $page ? json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true ) : null;

		if ( ! is_array( $data ) ) {
			return;
		}

		$anchors = [
			'pbc-team'      => 'equipo',
			'pbc-documents' => 'transparencia',
		];
		$changed = false;

		$walk = static function ( array $elements ) use ( &$walk, $anchors, &$changed ): array {
			foreach ( $elements as $index => $element ) {
				$type = (string) ( $element['widgetType'] ?? '' );

				if ( isset( $anchors[ $type ] ) && '' === (string) ( $element['settings']['anchor'] ?? '' ) ) {
					$elements[ $index ]['settings']['anchor'] = $anchors[ $type ];
					$changed                                  = true;
				}

				if ( ! empty( $element['elements'] ) ) {
					$elements[ $index ]['elements'] = $walk( $element['elements'] );
				}
			}

			return $elements;
		};

		$data = $walk( $data );

		if ( $changed ) {
			update_post_meta( $page->ID, '_elementor_data', wp_slash( (string) wp_json_encode( $data ) ) );
			\Elementor\Plugin::$instance->files_manager->clear_cache();
			WP_CLI::log( 'Anclas #equipo y #transparencia añadidas a «La asociación».' );
		}
	}

	/**
	 * Crea o actualiza una página de texto (sin Elementor). No escribe encima de
	 * un texto cambiado a mano salvo con --force.
	 *
	 * @param array<string, mixed> $assoc_args Opciones del comando.
	 */
	private function prose_page( string $slug, string $title, string $excerpt, string $content, array $assoc_args ): int {
		$page    = get_page_by_path( $slug );
		$page_id = $page ? (int) $page->ID : 0;

		if ( $page_id > 0 && ! get_post_meta( $page_id, '_pbc_seeded', true ) && '' !== trim( $page->post_content ) && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( "«{$title}» ya tiene un texto propio; no se toca (usa --force para reescribirla)." );
			return $page_id;
		}

		$page_id = (int) wp_insert_post(
			[
				'ID'             => $page_id,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_name'      => $slug,
				'post_excerpt'   => $excerpt,
				'post_content'   => $content,
				'comment_status' => 'closed',
			]
		);
		update_post_meta( $page_id, '_pbc_seeded', 1 );
		WP_CLI::log( "«{$title}» escrita y publicada." );

		return $page_id;
	}

	/**
	 * Monta un menú del pie y lo asigna a su ubicación. Solo lo rehace si está
	 * vacío o si todos sus elementos son de los que pone este comando (o con --force).
	 *
	 * @param array<int, array{page?:int, url?:string, title?:string}> $items      Elementos.
	 * @param array<string, mixed>                                     $assoc_args Opciones del comando.
	 */
	private function footer_menu( string $name, string $location, array $items, array $assoc_args ): void {
		$menu    = wp_get_nav_menu_object( $name );
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );
		$current = (array) wp_get_nav_menu_items( $menu_id );
		$pages   = array_filter( array_column( $items, 'page' ) );
		$urls    = array_filter( array_column( $items, 'url' ) );
		$ours    = (int) get_option( 'wp_page_for_privacy_policy' );
		$foreign = array_filter(
			$current,
			static fn ( $item ): bool => 'custom' === $item->type
				? ! in_array( $item->url, $urls, true )
				: ! in_array( (int) $item->object_id, array_merge( $pages, [ $ours ] ), true )
		);

		if ( $foreign && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( "El menú «{$name}» tiene enlaces añadidos a mano; no se toca (usa --force para rehacerlo)." );
		} else {
			foreach ( $current as $item ) {
				wp_delete_post( (int) $item->ID, true );
			}

			foreach ( $items as $position => $item ) {
				$fields = [
					'menu-item-position' => $position + 1,
					'menu-item-status'   => 'publish',
				];

				if ( ! empty( $item['page'] ) ) {
					$fields += [
						'menu-item-object-id' => (int) $item['page'],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
					];
				} else {
					$fields += [
						'menu-item-url'  => (string) $item['url'],
						'menu-item-type' => 'custom',
					];
				}

				if ( ! empty( $item['title'] ) ) {
					$fields['menu-item-title'] = $item['title'];
				}

				wp_update_nav_menu_item( $menu_id, 0, $fields );
			}

			WP_CLI::log( "Menú «{$name}» montado." );
		}

		$locations              = (array) get_theme_mod( 'nav_menu_locations', [] );
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Texto de la declaración de accesibilidad.
	 *
	 * @return array<int, array{0:string, 1:string|string[]}>
	 */
	private function accessibility_sections(): array {
		$pending = static fn ( string $text ): string => '<strong>Pendiente de confirmar:</strong> ' . $text;

		return [
			[ 'p', 'Queremos que cualquier persona pueda usar esta web sin ayuda, también quien tiene temblor, rigidez, poca visión o le cuesta concentrarse. Muchas de las personas que nos visitan son mayores, y la hemos pensado para ellas desde el principio.' ],
			[ 'p', 'Última revisión: ' . wp_date( 'j \d\e F \d\e Y' ) . '.' ],

			[ 'h2', 'Qué hemos hecho para que la web sea fácil de usar' ],
			[ 'ul', [
				'<strong>Letra grande y clara</strong>, con mucho contraste entre el texto y el fondo.',
				'Arriba de cada página puedes <strong>agrandar la letra</strong> (botones «A») y activar el <strong>alto contraste</strong>. La web lo recuerda la próxima vez.',
				'<strong>Botones y enlaces grandes</strong>, separados entre sí, para pulsarlos aunque te tiemble la mano.',
				'Todo se puede usar <strong>solo con el teclado</strong>, y siempre se ve dónde estás. El primer enlace de cada página, «Saltar al contenido», lleva directamente a lo importante.',
				'<strong>Nada se mueve solo</strong>: no hay carruseles, animaciones ni vídeos que empiecen sin que los pulses, y no hay tiempo límite para nada.',
				'El <strong>teléfono</strong> está siempre a la vista, arriba, y se puede llamar con un toque desde el móvil.',
				'Los <strong>formularios</strong> tienen el nombre de cada casilla escrito encima, piden pocos datos y, si algo falta, te dicen qué y dónde con palabras sencillas. No hay pruebas para demostrar que no eres un robot.',
				'Las páginas se ven bien en el móvil y con la pantalla ampliada, sin tener que mover la página de lado a lado.',
				'Las imágenes que dan información tienen un texto que las describe para quien usa un lector de pantalla.',
			] ],

			[ 'h2', 'Hasta qué punto cumplimos las normas' ],
			[ 'p', 'Esta web se ha hecho para cumplir el nivel AA de las Pautas de Accesibilidad para el Contenido Web (WCAG) 2.2, que es la referencia europea (norma UNE-EN 301 549). Hoy es <strong>parcialmente conforme</strong>: casi todo lo cumple, salvo los contenidos que se indican a continuación.' ],

			[ 'h2', 'Contenidos que todavía no son accesibles' ],
			[ 'ul', [
				'<strong>Guías en PDF</strong> de la página «Familias y cuidadores»: algunas las han hecho otras entidades, como la Federación Española de Párkinson, y no hemos podido revisar si se leen bien con un lector de pantalla. Si necesitas alguna en otro formato, pídenosla y te la daremos.',
				'<strong>Vídeos de ejercicios</strong>: puede que algunos no tengan subtítulos o tengan solo los automáticos de YouTube. Estamos revisándolos.',
				'<strong>Mapa</strong> de la sección de contacto: el mapa de OpenStreetMap no se puede usar bien con un lector de pantalla. La dirección está siempre escrita al lado y el enlace «Cómo llegar en el mapa» abre la ruta.',
			] ],

			[ 'h2', 'Cómo hemos preparado esta declaración' ],
			[ 'p', 'La ha preparado la propia asociación a partir de una revisión de la web con herramientas automáticas de accesibilidad (axe-core) en todas las plantillas, con la pantalla a 320 píxeles de ancho y en modo de alto contraste, y de comprobaciones a mano de los formularios, el buscador de guías y los vídeos.' ],
			[ 'p', $pending( 'una revisión a mano completa (solo con teclado, con la pantalla ampliada al 200 % y con un lector de pantalla) antes de publicar la web, y la fecha de esa revisión.' ) ],

			[ 'h2', 'Si encuentras un problema' ],
			[ 'p', 'Si algo de esta web no te funciona o te cuesta usarlo, cuéntanoslo. Nos ayuda a mejorarla y buscaremos la manera de darte la información que necesitas:' ],
			[ 'ul', [
				'Por teléfono: <a href="tel:+34956591928">956 59 19 28</a>, de lunes a viernes de 9:00 a 17:00.',
				'Por correo electrónico: <a href="mailto:unidad@parkinsonbahiadecadiz.org">unidad@parkinsonbahiadecadiz.org</a>.',
				'En persona: C/ Milongas s/n, 11100 San Fernando (Cádiz), mejor con cita previa.',
			] ],
			[ 'p', 'Dinos en qué página estabas y qué querías hacer. Te responderemos lo antes posible.' ],
			[ 'p', $pending( 'el plazo máximo de respuesta que se compromete a cumplir la asociación (el modelo oficial usa 20 días hábiles).' ) ],
		];
	}

	/**
	 * Texto del aviso legal (datos que pide la Ley 34/2002, LSSI).
	 *
	 * @return array<int, array{0:string, 1:string|string[]}>
	 */
	private function legal_notice_sections(): array {
		$privacy = get_privacy_policy_url();
		$privacy = '' !== $privacy ? '<a href="' . esc_url( $privacy ) . '">política de privacidad</a>' : 'política de privacidad';

		return [
			[ 'p', 'Aquí tienes los datos de quién está detrás de esta web y las condiciones para usarla.' ],

			[ 'h2', 'Quién es el titular de la web' ],
			[ 'ul', [
				'<strong>Entidad:</strong> Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz (Párkinson Bahía de Cádiz), entidad sin ánimo de lucro.',
				'<strong>CIF:</strong> G-11591302.',
				'<strong>Domicilio:</strong> C/ Milongas s/n, 11100 San Fernando (Cádiz).',
				'<strong>Teléfono:</strong> <a href="tel:+34956591928">956 59 19 28</a>.',
				'<strong>Correo electrónico:</strong> <a href="mailto:unidad@parkinsonbahiadecadiz.org">unidad@parkinsonbahiadecadiz.org</a>.',
				'<strong>Registro de Asociaciones de Andalucía:</strong> nº 6.809, inscrita el 23 de junio de 2003.',
				'<strong>Registro de Entidades de Voluntariado de Andalucía:</strong> nº 1198.',
			] ],

			[ 'h2', 'Para qué sirve esta web' ],
			[ 'p', 'Para dar a conocer la asociación, su centro de día y sus terapias, ofrecer información y recursos a las personas con párkinson y a sus familias, y facilitar que cualquiera pueda hacerse socio, colaborar o ponerse en contacto con nosotros.' ],

			[ 'h2', 'La información de la web no sustituye al médico' ],
			[ 'p', 'Los textos, guías y vídeos de esta web son orientativos y generales. No sustituyen el diagnóstico ni el consejo de tu neurólogo, de tu médico de familia o de los profesionales que te atienden. Antes de cambiar tu tratamiento o de empezar un ejercicio nuevo, consúltalo con ellos.' ],

			[ 'h2', 'Cómo usar la web' ],
			[ 'ul', [
				'Puedes usar la web libremente y sin registrarte.',
				'Si envías un formulario, los datos que escribas deben ser ciertos y tuyos, o de una persona que te haya dado permiso.',
				'No está permitido usar la web para fines ilegales, para dañarla o para enviar mensajes en masa.',
			] ],

			[ 'h2', 'Textos, fotos y logotipos' ],
			[ 'p', 'Los textos y las fotos de esta web son de la asociación o se publican con permiso de sus autores. Puedes compartir los textos citando a Párkinson Bahía de Cádiz y enlazando a esta web. Las fotos de personas no se pueden usar fuera de esta web sin nuestro permiso, para proteger su intimidad.' ],
			[ 'p', 'Los logotipos de las administraciones, fundaciones y empresas que nos apoyan son de sus titulares y aparecen aquí para agradecer su colaboración. Las guías de otras entidades son de sus autores.' ],

			[ 'h2', 'Enlaces a otras webs' ],
			[ 'p', 'Algunos enlaces llevan a webs de otras entidades, como medios de comunicación, federaciones o YouTube. No somos responsables de su contenido ni de cómo tratan tus datos: te recomendamos leer sus condiciones.' ],

			[ 'h2', 'Tus datos personales' ],
			[ 'p', 'Cómo tratamos los datos que nos das en esta web se explica en la ' . $privacy . '.' ],

			[ 'h2', 'Ley aplicable' ],
			[ 'p', 'Esta web y estas condiciones se rigen por la legislación española.' ],
		];
	}

	/**
	 * Crea y publica la página «Política de cookies», la enlaza en los ajustes
	 * del plugin «Cookies accesibles» y rehace el menú legal del pie con
	 * «Cookies» y «Configurar cookies».
	 *
	 * La lista de cookies no se escribe a mano: la pinta el atajo
	 * [pbc_cookies_tabla] a partir de los servicios que declaran el tema y los
	 * plugins, así que siempre coincide con lo que se carga.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el texto y el menú aunque se hayan cambiado a mano.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function cookies( array $args, array $assoc_args ): void {
		$this->start();

		if ( ! shortcode_exists( 'pbc_cookies_tabla' ) ) {
			WP_CLI::warning( 'El plugin «Cookies accesibles» (parkinson-cookies) no está activo: la página se crea, pero la lista de cookies no se verá hasta activarlo.' );
		}

		$page_id = $this->prose_page(
			'politica-de-cookies',
			'Política de cookies',
			'Qué guarda esta web en tu navegador, para qué, quién lo pone y cómo aceptarlo, rechazarlo o cambiar de opinión.',
			$this->blocks( $this->cookie_sections() ),
			$assoc_args
		);

		$settings                = (array) get_option( 'pck_settings', [] );
		$settings['policy_page'] = $page_id;
		update_option( 'pck_settings', $settings, true );

		$this->footer_menu( 'Legal', 'footer-legal', $this->legal_menu_items(), $assoc_args );

		WP_CLI::success( 'Política de cookies lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Elementos del menú legal del pie, solo con las páginas que existen.
	 *
	 * @return array<int, array{page?:int, url?:string, title?:string}>
	 */
	private function legal_menu_items(): array {
		$items = [];

		foreach ( [ 'declaracion-de-accesibilidad', 'aviso-legal' ] as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$items[] = [ 'page' => (int) $page->ID ];
			}
		}

		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy > 0 && 'publish' === get_post_status( $privacy ) ) {
			$items[] = [
				'page'  => $privacy,
				'title' => 'Privacidad',
			];
		}

		$cookies = get_page_by_path( 'politica-de-cookies' );
		if ( $cookies && 'publish' === $cookies->post_status ) {
			$items[] = [
				'page'  => (int) $cookies->ID,
				'title' => 'Cookies',
			];
			$items[] = [
				'url'   => get_permalink( $cookies ) . '#configurar-cookies',
				'title' => 'Configurar cookies',
			];
		}

		return $items;
	}

	/**
	 * Texto de la política de cookies.
	 *
	 * @return array<int, array{0:string, 1:string|string[]}>
	 */
	private function cookie_sections(): array {
		return [
			[ 'p', 'Última revisión: ' . wp_date( 'j \d\e F \d\e Y' ) . '.' ],

			[ 'h2', 'Qué son las cookies' ],
			[ 'p', 'Las cookies son pequeños archivos que una web guarda en tu navegador (en el ordenador, la tableta o el móvil) para recordar algo, por ejemplo una preferencia. Hay otras formas parecidas de guardar datos, como el almacenamiento local del navegador. En esta página las llamamos a todas «cookies».' ],

			[ 'h2', 'Qué cookies usa esta web' ],
			[ 'p', 'La web de Párkinson Bahía de Cádiz (Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz, CIF G‑11591302, C/ Milongas s/n, 11100 San Fernando, Cádiz) usa muy pocas:' ],
			[ 'ul', [
				'<strong>Necesarias.</strong> Hacen que la web funcione y recuerdan lo que tú eliges, como el tamaño del texto o tu decisión sobre las cookies. No necesitan tu permiso porque sin ellas la web no podría darte lo que pides.',
				'<strong>Contenido de otras webs.</strong> Los vídeos de ejercicios (YouTube) y el mapa del centro (OpenStreetMap). Solo se cargan si das permiso. Hasta entonces la web no se conecta con esos servicios.',
			] ],
			[ 'p', 'No usamos cookies de publicidad ni para hacer un perfil de ti. Ahora mismo tampoco medimos las visitas. Si un día lo hacemos, se añadirá aquí y te volveremos a preguntar.' ],
			[ 'p', 'Esta es la lista completa, con quién pone cada una, para qué sirve y cuánto dura:' ],
			[ 'shortcode', '[pbc_cookies_tabla nivel="3"]' ],

			[ 'h2', 'Cómo aceptar, rechazar o cambiar de opinión' ],
			[ 'p', 'La primera vez que entras te preguntamos en un aviso abajo de la pantalla. Puedes pulsar «Aceptar», «Rechazar» o «Elegir» para decidir por tipos. Rechazar es igual de fácil que aceptar, y puedes seguir usando la web aunque no aceptes.' ],
			[ 'p', 'Si no has dado permiso y pulsas un vídeo o el mapa, te preguntaremos antes de cargarlo.' ],
			[ 'p', 'Puedes cambiar tu elección cuando quieras con el enlace «Configurar cookies», al final de cada página, o con este botón:' ],
			[ 'shortcode', '[pbc_cookies_ajustes]' ],
			[ 'p', 'Guardamos tu elección durante 12 meses. Después, o si cambiamos las cookies que usamos, te volveremos a preguntar.' ],

			[ 'h2', 'Cómo borrar las cookies de tu navegador' ],
			[ 'p', 'También puedes ver y borrar las cookies desde tu navegador. Así se hace en los más usados:' ],
			[ 'ul', [
				'<a href="https://support.google.com/chrome/answer/95647?hl=es">Google Chrome</a>',
				'<a href="https://support.mozilla.org/es/kb/borrar-cookies-y-datos-del-sitio-en-firefox">Mozilla Firefox</a>',
				'<a href="https://support.apple.com/es-es/105082">Safari (iPhone y iPad)</a>',
				'<a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-las-cookies-en-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09">Microsoft Edge</a>',
			] ],
			[ 'p', 'Si borras todas las cookies, la web volverá a preguntarte y olvidará el tamaño de texto que hubieras elegido.' ],

			[ 'h2', 'Datos fuera de la Unión Europea' ],
			[ 'p', 'Google (YouTube) y Cloudflare (que protege los formularios contra envíos automáticos) pueden tratar datos en Estados Unidos. Lo hacen bajo el Marco de Privacidad de Datos entre la Unión Europea y Estados Unidos, que la Comisión Europea considera una protección adecuada.' ],

			[ 'h2', 'Más información' ],
			[ 'p', 'Para saber cómo tratamos tus datos personales y cómo ejercer tus derechos, lee la <a href="' . esc_url( (string) get_privacy_policy_url() ) . '">política de privacidad</a>. Si tienes cualquier duda, escríbenos a <a href="mailto:unidad@parkinsonbahiadecadiz.org">unidad@parkinsonbahiadecadiz.org</a> o llámanos al <a href="tel:+34956591928">956 59 19 28</a>.' ],
		];
	}

	/**
	 * Crea y publica la página «Política de privacidad» y el menú legal del pie.
	 *
	 * El texto parte de lo que se sabe de la asociación y de cómo trata los
	 * datos esta web. Lo que falta por confirmar va marcado como «Pendiente de
	 * confirmar» y debe revisarse antes de publicar la web.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Reescribe el texto aunque la página ya tenga contenido propio.
	 *
	 * @param string[]             $args       Argumentos posicionales.
	 * @param array<string, mixed> $assoc_args Opciones.
	 */
	public function privacidad( array $args, array $assoc_args ): void {
		$page_id = (int) get_option( 'wp_page_for_privacy_policy' );
		$page    = $page_id > 0 ? get_post( $page_id ) : null;

		if ( ! $page || 'trash' === $page->post_status ) {
			$existing = get_page_by_path( 'politica-de-privacidad' );
			$page_id  = $existing ? (int) $existing->ID : (int) wp_insert_post(
				[
					'post_type'   => 'page',
					'post_status' => 'draft',
					'post_title'  => 'Política de privacidad',
					'post_name'   => 'politica-de-privacidad',
				]
			);
			$page     = get_post( $page_id );
		}

		// Solo se escribe encima del borrador de ejemplo de WordPress o de un texto generado antes por este comando.
		$is_ours    = (bool) get_post_meta( $page_id, '_pbc_seeded', true );
		$is_default = 'draft' === $page->post_status || '' === trim( $page->post_content );

		if ( ! $is_ours && ! $is_default && empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'La página ya tiene un texto propio; no se toca (usa --force para reescribirla).' );
		} else {
			wp_update_post(
				[
					'ID'           => $page_id,
					'post_status'  => 'publish',
					'post_title'   => 'Política de privacidad',
					'post_name'    => 'politica-de-privacidad',
					'post_excerpt' => 'Qué datos personales recoge Párkinson Bahía de Cádiz en esta web, para qué los usa, cuánto tiempo los guarda y cómo ejercer tus derechos.',
					'post_content' => $this->privacy_content(),
				]
			);
			update_post_meta( $page_id, '_pbc_seeded', 1 );
			WP_CLI::log( 'Política de privacidad escrita y publicada.' );
		}

		update_option( 'wp_page_for_privacy_policy', $page_id );

		// Menú legal del pie.
		$menu    = wp_get_nav_menu_object( 'Legal' );
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( 'Legal' );
		$items   = (array) wp_get_nav_menu_items( $menu_id );

		if ( ! in_array( $page_id, array_map( static fn ( $item ): int => (int) $item->object_id, $items ), true ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-object-id' => $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				]
			);
		}

		$locations                 = (array) get_theme_mod( 'nav_menu_locations', [] );
		$locations['footer-legal'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		WP_CLI::success( 'Política de privacidad lista: ' . get_permalink( $page_id ) );
	}

	/**
	 * Texto de la política de privacidad, en bloques del editor.
	 */
	private function privacy_content(): string {
		$pending = static fn ( string $text ): string => '<strong>Pendiente de confirmar:</strong> ' . $text;

		$sections = [
			[ 'p', 'En Párkinson Bahía de Cádiz cuidamos tus datos igual que cuidamos a las personas que atendemos. Aquí te explicamos, con palabras sencillas, qué datos recogemos en esta web, para qué los usamos y qué puedes hacer si quieres consultarlos, cambiarlos o borrarlos.' ],
			[ 'p', 'Última actualización: ' . wp_date( 'j \d\e F \d\e Y' ) . '.' ],

			[ 'h2', 'Quién es responsable de tus datos' ],
			[ 'ul', [
				'<strong>Entidad:</strong> Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz (Párkinson Bahía de Cádiz).',
				'<strong>CIF:</strong> G-11591302.',
				'<strong>Registro:</strong> Registro de Asociaciones de Andalucía, nº 6.809.',
				'<strong>Dirección:</strong> C/ Milongas s/n, 11100 San Fernando (Cádiz).',
				'<strong>Teléfono:</strong> <a href="tel:+34956591928">956 59 19 28</a>.',
				'<strong>Correo electrónico:</strong> <a href="mailto:unidad@parkinsonbahiadecadiz.org">unidad@parkinsonbahiadecadiz.org</a>.',
			] ],
			[ 'p', $pending( 'si la asociación tiene una persona delegada de protección de datos y, en ese caso, su nombre y su forma de contacto.' ) ],

			[ 'h2', 'Qué datos recogemos y para qué' ],
			[ 'p', 'Solo te pedimos los datos que hacen falta para lo que tú nos pides. En los formularios de esta web no te pedimos datos de salud.' ],
			[ 'h3', 'Si te haces socio o socia' ],
			[ 'ul', [
				'<strong>Datos:</strong> nombre y apellidos, DNI o NIE, teléfono, correo electrónico (si lo indicas), número de cuenta (IBAN) y la cuota que eliges.',
				'<strong>Para qué:</strong> darte de alta como socio, cobrar tu cuota por domiciliación bancaria, informarte de la actividad de la asociación y cumplir nuestras obligaciones legales y contables.',
			] ],
			[ 'h3', 'Si te ofreces como voluntario o voluntaria' ],
			[ 'ul', [
				'<strong>Datos:</strong> nombre, teléfono, correo electrónico (si lo indicas) y las áreas en las que te gustaría ayudar.',
				'<strong>Para qué:</strong> llamarte, conocerte y organizar tu colaboración.',
			] ],
			[ 'h3', 'Si nos escribes desde el formulario de contacto' ],
			[ 'ul', [
				'<strong>Datos:</strong> nombre, teléfono, correo electrónico (si lo indicas), el tema de tu consulta y el mensaje que escribas (si escribes uno).',
				'<strong>Para qué:</strong> llamarte y responder a tu consulta. En el mensaje no hace falta que nos cuentes datos de salud: preferimos hablarlo contigo por teléfono.',
			] ],
			[ 'h3', 'Si nos llamas o nos escribes' ],
			[ 'ul', [
				'<strong>Datos:</strong> los que tú nos des por teléfono o por correo electrónico.',
				'<strong>Para qué:</strong> responder a tu consulta o tramitar lo que nos pidas, por ejemplo una plaza en el centro de día.',
			] ],
			[ 'p', 'Los datos de las personas usuarias del centro de día y de las terapias no se recogen a través de esta web: se tratan en el propio centro, con su documentación específica.' ],

			[ 'h2', 'Por qué podemos tratar tus datos' ],
			[ 'ul', [
				'<strong>Porque tú nos lo pides y lo aceptas:</strong> al enviar un formulario marcas la casilla de aceptación de esta política. Guardamos la fecha y la hora en que lo aceptaste para poder demostrarlo si hace falta.',
				'<strong>Porque es necesario para tu relación con la asociación:</strong> por ejemplo, para gestionar tu alta como socio y cobrar la cuota.',
				'<strong>Porque la ley nos obliga:</strong> por ejemplo, a conservar la documentación contable y fiscal.',
			] ],
			[ 'p', 'Puedes retirar tu consentimiento cuando quieras. Eso no afecta a lo que hayamos hecho con tus datos hasta ese momento.' ],

			[ 'h2', 'Cuánto tiempo guardamos tus datos' ],
			[ 'ul', [
				'<strong>Socios:</strong> mientras sigas siendo socio. Después, solo el tiempo que la ley obliga a conservar la documentación.',
				'<strong>Voluntariado y consultas (también las del formulario de contacto):</strong> el tiempo necesario para atender tu solicitud.',
			] ],
			[ 'p', 'Los formularios que recibimos por la web se borran para siempre, sin que nadie tenga que acordarse, cuando pasa su plazo:' ],
			[ 'shortcode', '[pbc_rgpd_plazos]' ],
			[ 'p', $pending( 'los plazos de conservación definitivos de cada formulario (los de arriba son una propuesta).' ) ],

			[ 'h2', 'A quién comunicamos tus datos' ],
			[ 'p', 'No vendemos ni cedemos tus datos. Solo los comunicamos cuando es imprescindible:' ],
			[ 'ul', [
				'A <strong>nuestro banco</strong>, para cobrar las cuotas de los socios por domiciliación.',
				'A las <strong>administraciones públicas</strong>, cuando una ley nos obliga a ello.',
				'A las <strong>empresas que nos prestan servicios</strong> necesarios para que la web funcione, como el alojamiento. Trabajan siguiendo nuestras instrucciones y con un contrato que les obliga a proteger tus datos.',
			] ],
			[ 'p', $pending( 'el nombre de la empresa de alojamiento de la web y si guarda los datos dentro de la Unión Europea.' ) ],
			[ 'p', 'Para evitar envíos automáticos de robots, los formularios pueden usar el servicio Turnstile de Cloudflare, que comprueba que quien envía es una persona. Para ello Cloudflare recibe datos técnicos de tu navegador, como la dirección IP. Puedes consultar su <a href="https://www.cloudflare.com/es-es/privacypolicy/">política de privacidad</a>.' ],

			[ 'h2', 'Cómo protegemos tus datos' ],
			[ 'ul', [
				'El DNI y el número de cuenta se guardan cifrados.',
				'Solo las personas autorizadas de la asociación pueden ver los formularios recibidos.',
				'Los avisos que recibimos por correo cuando alguien envía un formulario no incluyen el DNI ni el número de cuenta.',
				'La web funciona con conexión segura (https).',
			] ],

			[ 'h2', 'Tus derechos' ],
			[ 'p', 'Puedes pedirnos en cualquier momento:' ],
			[ 'ul', [
				'<strong>Saber</strong> qué datos tuyos tenemos y recibir una copia.',
				'<strong>Corregir</strong> los datos que estén mal o incompletos.',
				'<strong>Borrar</strong> tus datos cuando ya no sean necesarios.',
				'<strong>Oponerte</strong> a que los usemos o pedir que <strong>limitemos</strong> su uso.',
				'<strong>Llevarte</strong> tus datos a otra entidad (portabilidad).',
				'<strong>Retirar</strong> el consentimiento que nos diste.',
			] ],
			[ 'p', 'Para hacerlo, escríbenos a <a href="mailto:unidad@parkinsonbahiadecadiz.org">unidad@parkinsonbahiadecadiz.org</a>, llámanos al <a href="tel:+34956591928">956 59 19 28</a> o envíanos una carta a C/ Milongas s/n, 11100 San Fernando (Cádiz). Es posible que te pidamos algún dato para comprobar que eres tú. Te responderemos en el plazo máximo de un mes.' ],
			[ 'p', 'Si crees que no hemos atendido bien tu solicitud, puedes presentar una reclamación ante la Agencia Española de Protección de Datos, en <a href="https://www.aepd.es/">www.aepd.es</a>.' ],

			[ 'h2', 'Cookies' ],
			[ 'p', 'Esta web no usa cookies de publicidad ni de medición de visitas. El mapa y los vídeos de ejercicios, que vienen de OpenStreetMap y de YouTube, solo se cargan si das permiso. Todo lo que guarda la web en tu navegador, y cómo cambiar tu elección, está en la <a href="' . esc_url( home_url( user_trailingslashit( '/politica-de-cookies' ) ) ) . '">política de cookies</a>.' ],

			[ 'h2', 'Cambios en esta política' ],
			[ 'p', 'Si cambiamos la forma de tratar tus datos, actualizaremos esta página e indicaremos la fecha arriba. Si el cambio es importante y tenemos tu contacto, te avisaremos.' ],
		];

		return $this->blocks( $sections );
	}

	/**
	 * Convierte una lista de secciones ([ tipo, contenido ]) en bloques del editor.
	 * Tipos: p, h2, h3, ul (contenido = lista de elementos) y shortcode.
	 *
	 * @param array<int, array{0:string, 1:string|string[]}> $sections Secciones.
	 */
	private function blocks( array $sections ): string {
		$html = '';

		foreach ( $sections as [ $type, $content ] ) {
			if ( 'h2' === $type || 'h3' === $type ) {
				$level = 'h3' === $type ? ' {"level":3}' : '';
				$html .= '<!-- wp:heading' . $level . ' --><' . $type . ' class="wp-block-heading">' . $content . '</' . $type . '><!-- /wp:heading -->' . "\n\n";
			} elseif ( 'ul' === $type ) {
				$items = '';
				foreach ( $content as $item ) {
					$items .= '<!-- wp:list-item --><li>' . $item . '</li><!-- /wp:list-item -->';
				}
				$html .= '<!-- wp:list --><ul class="wp-block-list">' . $items . '</ul><!-- /wp:list -->' . "\n\n";
			} elseif ( 'shortcode' === $type ) {
				$html .= '<!-- wp:shortcode -->' . $content . '<!-- /wp:shortcode -->' . "\n\n";
			} else {
				$html .= '<!-- wp:paragraph --><p>' . $content . '</p><!-- /wp:paragraph -->' . "\n\n";
			}
		}

		return $html;
	}

	/**
	 * Crea elementos de un tipo de contenido si todavía no existen (por título).
	 *
	 * @param array<int, array{0:string, 1:string, 2:array<string, mixed>}> $items Título, extracto y campos.
	 */
	private function seed_items( string $post_type, array $items ): void {
		foreach ( $items as $order => [ $title, $excerpt, $meta ] ) {
			$existing = get_posts(
				[
					'post_type'      => $post_type,
					'post_status'    => 'any',
					'title'          => $title,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				]
			);

			if ( $existing ) {
				continue;
			}

			$post_id = (int) wp_insert_post(
				[
					'post_type'    => $post_type,
					'post_status'  => 'publish',
					'post_title'   => $title,
					// Sin «·» en la dirección: WordPress lo dejaría como «%c2%b7».
					'post_name'    => sanitize_title( str_replace( '·', '', $title ) ),
					'post_excerpt' => $excerpt,
					'menu_order'   => $order + 1,
				]
			);

			foreach ( $meta as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		WP_CLI::log( "Contenidos comprobados: {$post_type}" );
	}

	/**
	 * Guarda una página de Elementor: un contenedor a ancho completo con un widget por sección.
	 *
	 * @param array<string, array<string, mixed>> $widgets Tipo de widget => ajustes.
	 */
	private function save_page( int $page_id, array $widgets, string $prefix ): void {
		$elements = [];
		foreach ( $widgets as $type => $settings ) {
			$elements[] = [
				'id'         => substr( md5( $prefix . '-' . $type ), 0, 7 ),
				'elType'     => 'widget',
				// «tipo#2» permite repetir un widget en la misma página.
				'widgetType' => explode( '#', $type )[0],
				'settings'   => $settings,
				'elements'   => [],
			];
		}

		$zero = [
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '0',
			'bottom'   => '0',
			'left'     => '0',
			'isLinked' => true,
		];

		// Un único contenedor a ancho completo, sin relleno ni separación: cada widget trae los suyos.
		$data = [
			[
				'id'       => substr( md5( $prefix . '-container' ), 0, 7 ),
				'elType'   => 'container',
				'settings' => [
					'content_width'  => 'full',
					'flex_direction' => 'column',
					'flex_gap'       => [
						'unit'     => 'px',
						'size'     => 0,
						'column'   => '0',
						'row'      => '0',
						'isLinked' => true,
					],
					'padding'        => $zero,
					'padding_tablet' => $zero,
					'padding_mobile' => $zero,
				],
				'elements' => $elements,
				'isInner'  => false,
			],
		];

		$document = \Elementor\Plugin::$instance->documents->get( $page_id, false );

		if ( ! $document ) {
			WP_CLI::error( 'Elementor no reconoce la página.' );
		}

		$document->set_is_built_with_elementor( true );
		$saved = $document->save(
			[
				'elements' => $data,
				'settings' => [ 'post_status' => 'publish' ],
			]
		);

		if ( ! $saved ) {
			WP_CLI::error( 'Elementor no ha guardado la página.' );
		}

		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}
