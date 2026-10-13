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
							'link_page'  => $page( 'familias-y-cuidadores' ),
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

		$elements = [];
		foreach ( $widgets as $type => $settings ) {
			$elements[] = [
				'id'         => substr( md5( 'home-' . $type ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => $type,
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
				'id'       => substr( md5( 'home-container' ), 0, 7 ),
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

		$document = \Elementor\Plugin::$instance->documents->get( $home_id, false );

		if ( ! $document ) {
			WP_CLI::error( 'Elementor no reconoce la página de inicio.' );
		}

		$document->set_is_built_with_elementor( true );
		$saved = $document->save(
			[
				'elements' => $data,
				'settings' => [ 'post_status' => 'publish' ],
			]
		);

		if ( ! $saved ) {
			WP_CLI::error( 'Elementor no ha guardado la portada.' );
		}

		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}
