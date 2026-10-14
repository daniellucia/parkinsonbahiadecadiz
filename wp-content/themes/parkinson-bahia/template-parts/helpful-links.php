<?php
/**
 * Accesos rápidos a las páginas principales (404 y búsqueda sin resultados).
 *
 * Salen del menú principal; el filtro «parkinson_bahia/helpful_links» permite
 * añadir o cambiar enlaces (el plugin pone primero «Me acaban de diagnosticar»).
 * Argumento: 'title' (texto del título h2).
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_links = TemplateTags::helpful_links();

if ( ! $pbc_links ) {
	return;
}

$pbc_title_id = wp_unique_id( 'pbc-helpful-' );
?>
<section class="pbc-helpful" aria-labelledby="<?php echo esc_attr( $pbc_title_id ); ?>">
	<h2 class="pbc-helpful__title" id="<?php echo esc_attr( $pbc_title_id ); ?>"><?php echo esc_html( (string) ( $args['title'] ?? __( 'Lo que más se consulta', 'parkinson-bahia' ) ) ); ?></h2>
	<ul class="pbc-helpful__list">
		<?php foreach ( $pbc_links as $pbc_link ) : ?>
			<li class="pbc-helpful__item">
				<a class="pbc-helpful__link" href="<?php echo esc_url( $pbc_link['url'] ); ?>">
					<span class="pbc-helpful__name"><?php echo esc_html( $pbc_link['title'] ); ?></span>
					<?php if ( '' !== $pbc_link['text'] ) : ?>
						<span class="pbc-helpful__text"><?php echo esc_html( $pbc_link['text'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
