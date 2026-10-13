<?php
/**
 * Widget «Preguntas frecuentes»: preguntas desplegables (tipo de contenido Pregunta), opcionalmente de un solo tema.
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Pregunta;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

final class Faq extends Widget {

	protected function slug(): string {
		return 'faq';
	}

	public function get_title(): string {
		return esc_html__( 'Preguntas frecuentes', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-help-o';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Las preguntas se añaden en «Centro de día → Preguntas frecuentes» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Preguntas frecuentes', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->add_control(
			'topic',
			[
				'label'       => esc_html__( 'Tema', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->topic_options(),
				'default'     => '',
				'label_block' => true,
			]
		);
		$this->end_controls_section();
	}

	/**
	 * @return array<string, string>
	 */
	private function topic_options(): array {
		// En la web pública Elementor descarta las opciones: no hace falta consultar los temas.
		if ( \Elementor\Core\Frontend\Performance::should_optimize_controls() ) {
			return [];
		}

		$options = [ '' => esc_html__( 'Todos los temas', 'parkinson-bahia-core' ) ];
		$terms   = get_terms(
			[
				'taxonomy'   => Pregunta::TAXONOMY,
				'hide_empty' => false,
			]
		);

		foreach ( is_array( $terms ) ? $terms : [] as $term ) {
			$options[ 'term-' . $term->term_id ] = $term->name;
		}

		return $options;
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$topic    = (string) ( $settings['topic'] ?? '' );
		$args     = [
			'post_type'      => Pregunta::POST_TYPE,
			'post_status'    => 'publish',
			'has_password'   => false,
			'posts_per_page' => 40,
			'orderby'        => [
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			],
			'no_found_rows'  => true,
		];

		if ( str_starts_with( $topic, 'term-' ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtro por un único término.
				[
					'taxonomy' => Pregunta::TAXONOMY,
					'terms'    => [ absint( substr( $topic, 5 ) ) ],
				],
			];
		}

		$items = ( new \WP_Query( $args ) )->posts;

		if ( ! $items ) {
			$this->render_empty_notice( __( 'Todavía no hay preguntas para este tema. Añádelas en «Centro de día → Preguntas frecuentes».', 'parkinson-bahia-core' ) );
			return;
		}
		?>
		<section class="pbc-faq" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-faq__inner">
				<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-faq__title', $title_id ); ?>
				<div class="pbc-faq__list">
					<?php foreach ( $items as $item ) : ?>
						<details class="pbc-faq__item">
							<summary class="pbc-faq__question">
								<span class="pbc-faq__question-text"><?php echo esc_html( get_the_title( $item ) ); ?></span>
								<span class="pbc-faq__icon" aria-hidden="true"></span>
							</summary>
							<div class="pbc-faq__answer">
								<?php echo wp_kses_post( wpautop( $item->post_content ) ); ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
