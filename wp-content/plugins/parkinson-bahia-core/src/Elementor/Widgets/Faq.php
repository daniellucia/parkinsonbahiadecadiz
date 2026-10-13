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

	/**
	 * Preguntas pintadas en esta página, para los datos estructurados.
	 *
	 * @var array<int, array{question:string, answer:string}>
	 */
	private static array $schema_items = [];

	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Datos estructurados FAQPage (schema.org) para los buscadores.
	 *
	 * Se imprime un único bloque por página, en el pie, con las preguntas de
	 * todos los widgets de preguntas que haya: Google solo admite un FAQPage
	 * por página y exige que su texto coincida con el visible.
	 */
	public static function print_schema(): void {
		if ( ! self::$schema_items ) {
			return;
		}

		$entities = [];
		foreach ( self::$schema_items as $item ) {
			$entities[] = [
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $item['answer'],
				],
			];
		}

		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];

		// JSON_HEX_TAG evita que un «</script>» dentro de una respuesta cierre el bloque.
		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON codificado con JSON_HEX_TAG.
		);

		self::$schema_items = [];
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

		// HTML permitido por Google en el texto de una respuesta.
		$allowed = [
			'p'      => [],
			'br'     => [],
			'strong' => [],
			'em'     => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'a'      => [ 'href' => [] ],
		];

		$answers = [];

		foreach ( $items as $item ) {
			// El editor de bloques guarda marcas propias: hay que convertirlas en HTML antes de mostrarlas.
			$answers[ $item->ID ] = has_blocks( $item->post_content ) ? do_blocks( $item->post_content ) : wpautop( $item->post_content );

			// La misma pregunta no se repite aunque esté en dos widgets.
			self::$schema_items[ $item->ID ] = [
				'question' => wp_strip_all_tags( get_the_title( $item ) ),
				'answer'   => trim( (string) preg_replace( '/\s+/', ' ', wp_kses( $answers[ $item->ID ], $allowed ) ) ),
			];
		}

		if ( ! has_action( 'wp_footer', [ self::class, 'print_schema' ] ) ) {
			add_action( 'wp_footer', [ self::class, 'print_schema' ] );
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
								<?php echo wp_kses_post( $answers[ $item->ID ] ); ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
