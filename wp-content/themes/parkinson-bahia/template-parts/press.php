<?php
/**
 * «En los medios»: lo que publica la prensa sobre la asociación (Actualidad).
 *
 * Los datos llegan por el filtro «parkinson_bahia/press_items» (los aporta el
 * plugin del sitio). Sin datos no se pinta nada.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Apariciones en prensa: listas con media, date, datetime (AAAA-MM-DD), title y url.
 *
 * @param array<int, array<string, string>> $items Apariciones.
 */
$pbc_press = (array) apply_filters( 'parkinson_bahia/press_items', [] );

if ( ! $pbc_press ) {
	return;
}
?>
<section class="pbc-press" aria-labelledby="pbc-press-title">
	<div class="pbc-press__inner">
		<h2 class="pbc-press__title" id="pbc-press-title"><?php esc_html_e( 'En los medios', 'parkinson-bahia' ); ?></h2>
		<ul class="pbc-press__list">
			<?php foreach ( $pbc_press as $pbc_item ) : ?>
				<?php
				if ( ! is_array( $pbc_item ) || empty( $pbc_item['url'] ) || empty( $pbc_item['title'] ) ) {
					continue;
				}
				?>
				<li class="pbc-press__item">
					<a class="pbc-press__link" href="<?php echo esc_url( $pbc_item['url'] ); ?>" target="_blank" rel="noopener">
						<span class="pbc-press__source">
							<?php if ( ! empty( $pbc_item['media'] ) ) : ?>
								<strong class="pbc-press__media"><?php echo esc_html( $pbc_item['media'] ); ?></strong>
							<?php endif; ?>
							<?php if ( ! empty( $pbc_item['date'] ) ) : ?>
								<time class="pbc-press__date" datetime="<?php echo esc_attr( (string) ( $pbc_item['datetime'] ?? '' ) ); ?>"><?php echo esc_html( $pbc_item['date'] ); ?></time>
							<?php endif; ?>
						</span>
						<span class="pbc-press__headline">
							<?php echo esc_html( $pbc_item['title'] ); ?>
							<span class="pbc-press__new-tab"><?php esc_html_e( '(se abre en una pestaña nueva)', 'parkinson-bahia' ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
