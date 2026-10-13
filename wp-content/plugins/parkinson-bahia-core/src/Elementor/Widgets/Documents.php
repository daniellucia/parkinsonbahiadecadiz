<?php
/**
 * Widget «Transparencia»: texto, datos registrales y documentos descargables (tipo de contenido Documento).
 *
 * @package ParkinsonBahiaCore
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Core\Elementor\Widgets;

use DanielLucia\ParkinsonBahia\Core\PostTypes\Documento;
use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

final class Documents extends Widget {

	protected function slug(): string {
		return 'documents';
	}

	public function get_title(): string {
		return esc_html__( 'Transparencia', 'parkinson-bahia-core' );
	}

	public function get_icon(): string {
		return 'eicon-document-file';
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => esc_html__( 'Contenido', 'parkinson-bahia-core' ) ] );
		$this->source_note( __( 'Los documentos se añaden en «La asociación → Documentos de transparencia» del escritorio. Aquí solo se muestran.', 'parkinson-bahia-core' ) );
		$this->text_control( 'title', esc_html__( 'Título', 'parkinson-bahia-core' ), esc_html__( 'Transparencia', 'parkinson-bahia-core' ) );
		$this->heading_tag_control();
		$this->text_control( 'text', esc_html__( 'Texto', 'parkinson-bahia-core' ), '', true );

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			[
				'label'       => esc_html__( 'Dato', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'value',
			[
				'label'       => esc_html__( 'Valor', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$this->add_control(
			'facts',
			[
				'label'       => esc_html__( 'Datos registrales', 'parkinson-bahia-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{ label }}',
				'default'     => [],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$title_id = $this->title_id();
		$facts    = is_array( $settings['facts'] ?? null ) ? $settings['facts'] : [];
		$items    = $this->items( Documento::POST_TYPE );
		?>
		<section class="pbc-documents" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="pbc-documents__inner">
				<div class="pbc-documents__body">
					<?php $this->render_heading( $this->heading_tag( $settings ), (string) $settings['title'], 'pbc-documents__title', $title_id ); ?>
					<?php $this->render_text( (string) $settings['text'], 'pbc-documents__text' ); ?>
					<?php if ( $facts ) : ?>
						<dl class="pbc-documents__facts">
							<?php foreach ( $facts as $fact ) : ?>
								<?php
								if ( '' === trim( (string) ( $fact['label'] ?? '' ) ) || '' === trim( (string) ( $fact['value'] ?? '' ) ) ) {
									continue;
								}
								?>
								<div class="pbc-documents__fact">
									<dt class="pbc-documents__fact-label"><?php echo esc_html( $fact['label'] ); ?></dt>
									<dd class="pbc-documents__fact-value"><?php echo esc_html( $fact['value'] ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</div>
				<?php if ( $items ) : ?>
					<ul class="pbc-documents__list">
						<?php foreach ( $items as $item ) : ?>
							<?php
							$file_id = absint( get_post_meta( $item->ID, Documento::META_FILE, true ) );
							$url     = $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : '';
							$path    = $file_id > 0 ? (string) get_attached_file( $file_id ) : '';
							$type    = '' !== $path ? strtoupper( (string) pathinfo( $path, PATHINFO_EXTENSION ) ) : '';
							$size    = '' !== $path && is_readable( $path ) ? size_format( (int) filesize( $path ), 1 ) : '';
							?>
							<li class="pbc-documents__item">
								<?php if ( '' !== $url ) : ?>
									<a class="pbc-documents__link" href="<?php echo esc_url( $url ); ?>">
										<span class="pbc-documents__name"><?php echo esc_html( get_the_title( $item ) ); ?></span>
										<span class="pbc-documents__meta">
											<?php
											/* translators: 1: tipo de archivo (PDF), 2: tamaño. */
											echo esc_html( trim( sprintf( __( 'Descargar %1$s %2$s', 'parkinson-bahia-core' ), $type, $size ? '(' . $size . ')' : '' ) ) );
											?>
										</span>
									</a>
								<?php else : ?>
									<span class="pbc-documents__link pbc-documents__link--pending">
										<span class="pbc-documents__name"><?php echo esc_html( get_the_title( $item ) ); ?></span>
										<span class="pbc-documents__meta"><?php esc_html_e( 'Próximamente', 'parkinson-bahia-core' ); ?></span>
									</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<?php $this->render_empty_notice( __( 'Todavía no hay documentos. Añádelos en «La asociación → Documentos de transparencia».', 'parkinson-bahia-core' ) ); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
