<?php
/**
 * Widget «Guías para descargar»: lista de guías y fichas con filtro por tema.
 *
 * Los datos salen del tipo de contenido Recurso. El filtro son botones
 * (aria-pressed) que solo aparecen con JavaScript; sin él se ve la lista entera.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Recurso;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Resources extends Widget {

	private const MAX_ITEMS = 60;

	protected function slug(): string {
		return 'resources';
	}

	public function get_title(): string {
		return esc_html__( 'Guías para descargar', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-download-button';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las guías se añaden en «Familias → Guías y fichas», con su archivo y su tema. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Guías y fichas para descargar', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$topics = get_terms(
			[
				'taxonomy'   => Recurso::TAXONOMY,
				'hide_empty' => false,
			]
		);
		$this->add_control(
			'topic',
			[
				'label'       => esc_html__( 'Qué guías mostrar', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Todas, o solo las de un tema.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [ '' => esc_html__( 'Todas', 'parkinson-bahia-core' ) ] + ( is_array( $topics ) ? wp_list_pluck( $topics, 'name', 'slug' ) : [] ),
				'default'     => '',
			]
		);
		$this->add_control(
			'show_filter',
			[
				'label'       => esc_html__( 'Mostrar el filtro por tema', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Solo aparece si hay guías de más de un tema.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			]
		);
		$this->text_control( 'all_label', esc_html__( 'Botón para ver todas', 'parkinson-bahia-core' ), esc_html__( 'Todos', 'parkinson-bahia-core' ) );
		$this->anchor_control();
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$topic    = sanitize_title( (string) ( $settings['topic'] ?? '' ) );
		$query    = new \WP_Query(
			[
				'post_type'      => Recurso::POST_TYPE,
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => self::MAX_ITEMS,
				'orderby'        => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'no_found_rows'  => true,
				'tax_query'      => '' !== $topic ? [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Una sola taxonomía, con pocas guías.
					[
						'taxonomy' => Recurso::TAXONOMY,
						'field'    => 'slug',
						'terms'    => $topic,
					],
				] : [],
			]
		);
		$items    = $query->posts;
		$topics   = [];
		$rows     = [];

		foreach ( $items as $item ) {
			$terms   = get_the_terms( $item, Recurso::TAXONOMY );
			$terms   = is_array( $terms ) ? $terms : [];
			// Con un tema elegido se muestra ese; si no, el primero de la guía.
			$chosen  = '' !== $topic ? array_values( wp_list_filter( $terms, [ 'slug' => $topic ] ) ) : [];
			$term    = $chosen ? $chosen[0] : ( $terms ? $terms[0] : null );
			$file_id = absint( get_post_meta( $item->ID, Recurso::META_FILE, true ) );
			$path    = $file_id > 0 ? (string) get_attached_file( $file_id ) : '';

			if ( $term ) {
				$topics[ $term->slug ] = $term->name;
			}

			$rows[] = [
				'title' => get_the_title( $item ),
				'topic' => $term ? $term->slug : '',
				'name'  => $term ? $term->name : '',
				'url'   => $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : '',
				'type'  => '' !== $path ? strtoupper( (string) pathinfo( $path, PATHINFO_EXTENSION ) ) : '',
				'size'  => '' !== $path && is_readable( $path ) ? size_format( (int) filesize( $path ), 1 ) : '',
			];
		}

		$filter = '' === $topic && 'yes' === ( $settings['show_filter'] ?? '' ) && count( $topics ) > 1;
		?>
		<section class="pbc-resources"<?php echo $this->anchor_attr( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en anchor_attr(). ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-resources__inner">
				<div class="pbc-resources__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-resources__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-resources__text' ); ?>
				</div>

				<?php if ( ! $rows ) : ?>
					<?php $this->render_empty_notice( __( 'Todavía no hay guías. Añádelas en «Familias → Guías y fichas».', 'parkinson-bahia-core' ) ); ?>
				<?php else : ?>
					<?php if ( $filter ) : ?>
						<?php // Sin JavaScript el filtro no funcionaría: se queda oculto y se ve toda la lista. ?>
						<div class="pbc-resources__filter" role="group" aria-label="<?php esc_attr_e( 'Filtrar por tema', 'parkinson-bahia-core' ); ?>" data-pbc-resources-filter hidden>
							<button class="pbc-resources__chip" type="button" aria-pressed="true" data-topic=""><?php echo esc_html( '' !== trim( (string) $settings['all_label'] ) ? $settings['all_label'] : __( 'Todos', 'parkinson-bahia-core' ) ); ?></button>
							<?php foreach ( $topics as $slug => $name ) : ?>
								<button class="pbc-resources__chip" type="button" aria-pressed="false" data-topic="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></button>
							<?php endforeach; ?>
						</div>
						<p class="pbc-resources__status" role="status" data-pbc-resources-status data-one="<?php esc_attr_e( 'Se muestra 1 guía.', 'parkinson-bahia-core' ); ?>" data-many="<?php /* translators: %d: número de guías. */ esc_attr_e( 'Se muestran %d guías.', 'parkinson-bahia-core' ); ?>"></p>
					<?php endif; ?>

					<ul class="pbc-resources__list" data-pbc-resources-list>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$meta = array_filter( [ $row['name'], trim( $row['type'] . ( '' !== $row['size'] ? ', ' . $row['size'] : '' ) ) ] );
							?>
							<li class="pbc-resources__item" data-topic="<?php echo esc_attr( $row['topic'] ); ?>">
								<?php if ( '' !== $row['url'] ) : ?>
									<a class="pbc-resources__link" href="<?php echo esc_url( $row['url'] ); ?>">
										<span class="pbc-resources__body">
											<span class="pbc-resources__name"><?php echo esc_html( $row['title'] ); ?></span>
											<span class="pbc-resources__meta">
												<?php
												/* translators: %s: tema, tipo de archivo y tamaño. */
												echo esc_html( sprintf( __( 'Descargar · %s', 'parkinson-bahia-core' ), implode( ' · ', $meta ) ) );
												?>
											</span>
										</span>
										<span class="pbc-resources__icon" aria-hidden="true">
											<svg width="20" height="20" viewBox="0 0 20 20" focusable="false"><path d="M10 3v10m0 0-4-4m4 4 4-4M4 17h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
										</span>
									</a>
								<?php else : ?>
									<span class="pbc-resources__link pbc-resources__link--pending">
										<span class="pbc-resources__body">
											<span class="pbc-resources__name"><?php echo esc_html( $row['title'] ); ?></span>
											<span class="pbc-resources__meta"><?php echo esc_html( implode( ' · ', array_filter( [ $row['name'], __( 'Próximamente', 'parkinson-bahia-core' ) ] ) ) ); ?></span>
										</span>
									</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
