<?php
/**
 * Widget «Lista de terapias»: muestra las terapias publicadas (tipo de contenido Terapia).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Terapia;
use Elementor\Controls_Manager;
use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Therapies extends Widget {

	private const MAX_ITEMS = 24;

	protected function slug(): string {
		return 'therapies';
	}

	public function get_title(): string {
		return esc_html__( 'Lista de terapias', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-bullet-list';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->add_control(
			'source_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Las terapias se añaden y ordenan en el menú «Terapias» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Terapias', 'parkinson-bahia-core' ), true );
		$this->heading_tag_control();
		$this->text_control( 'intro', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );
		$this->add_control(
			'count',
			[
				'label'       => esc_html__( 'Cuántas mostrar', 'parkinson-bahia-core' ),
				'description' => esc_html__( 'Déjalo en 0 para mostrarlas todas.', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => self::MAX_ITEMS,
				'default'     => 0,
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$count    = absint( $settings['count'] ?? 0 );
		$title_id = $this->title_id();

		$query = new WP_Query(
			[
				'post_type'              => Terapia::POST_TYPE,
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => $count > 0 ? min( $count, self::MAX_ITEMS ) : self::MAX_ITEMS,
				'orderby'                => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( ! $query->have_posts() && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}
		?>
		<section class="pbc-therapies" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-therapies__inner">
				<div class="pbc-therapies__header">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-therapies__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['intro'], 'pbc-therapies__intro' ); ?>
				</div>
				<?php if ( $query->have_posts() ) : ?>
					<ul class="pbc-therapies__list">
						<?php foreach ( $query->posts as $therapy ) : ?>
							<li class="pbc-therapies__item">
								<a class="pbc-therapies__link" href="<?php echo esc_url( get_permalink( $therapy ) ); ?>">
									<span class="pbc-therapies__name"><?php echo esc_html( get_the_title( $therapy ) ); ?></span>
									<?php if ( has_excerpt( $therapy ) ) : ?>
										<span class="pbc-therapies__text"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $therapy ) ) ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="pbc-therapies__intro"><?php esc_html_e( 'Todavía no hay terapias publicadas. Añádelas en el menú «Terapias».', 'parkinson-bahia-core' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
