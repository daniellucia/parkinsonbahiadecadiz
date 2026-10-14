<?php
/**
 * Un resultado de búsqueda: qué es (página, terapia, noticia), título y resumen.
 * Se usa dentro del bucle.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$pbc_type = get_post_type_object( (string) get_post_type() );
$pbc_kind = match ( get_post_type() ) {
	'post'  => __( 'Noticia', 'parkinson-bahia' ),
	'page'  => __( 'Página', 'parkinson-bahia' ),
	default => $pbc_type ? (string) $pbc_type->labels->singular_name : '',
};
$pbc_excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', get_the_ID() ) ) ), 30 );
?>
<article class="pbc-result">
	<p class="pbc-result__kind">
		<?php echo esc_html( $pbc_kind ); ?>
		<?php if ( 'post' === get_post_type() ) : ?>
			· <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		<?php endif; ?>
	</p>
	<h2 class="pbc-result__title"><a class="pbc-result__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<?php if ( '' !== trim( $pbc_excerpt ) ) : ?>
		<p class="pbc-result__excerpt"><?php echo esc_html( $pbc_excerpt ); ?></p>
	<?php endif; ?>
</article>
