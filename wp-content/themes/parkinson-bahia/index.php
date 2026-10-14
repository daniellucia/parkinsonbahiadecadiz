<?php
/**
 * Listados: Actualidad, categorías, archivos y resultados de búsqueda.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

get_header();

$pbc_is_news = is_home() || is_category();

if ( is_search() ) {
	/* translators: %s: texto buscado. */
	$pbc_title = sprintf( __( 'Resultados para «%s»', 'parkinson-bahia' ), get_search_query() );
	$pbc_intro = '';
} elseif ( is_home() ) {
	$pbc_title = TemplateTags::news_title();
	$pbc_intro = TemplateTags::mod( 'pbc_blog_intro' );
} else {
	$pbc_title = wp_strip_all_tags( get_the_archive_title() );
	$pbc_intro = wp_strip_all_tags( get_the_archive_description() );
}

if ( is_category() ) {
	$pbc_title = single_cat_title( '', false );
}
?>

<header class="pbc-page-header pbc-page-header--split">
	<?php get_template_part( 'template-parts/breadcrumb' ); ?>
	<div class="pbc-page-header__row">
		<h1 class="pbc-page-header__title"><?php echo esc_html( $pbc_title ); ?></h1>
		<?php if ( '' !== $pbc_intro ) : ?>
			<p class="pbc-page-header__intro"><?php echo esc_html( $pbc_intro ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	if ( $pbc_is_news ) :
		$pbc_categories = get_categories(
			[
				'hide_empty' => true,
				'exclude'    => [ (int) get_option( 'default_category' ) ],
			]
		);

		if ( $pbc_categories ) :
			?>
			<nav class="pbc-chips" aria-label="<?php esc_attr_e( 'Filtrar noticias', 'parkinson-bahia' ); ?>">
				<ul class="pbc-chips__list">
					<li class="pbc-chips__item">
						<a class="pbc-chips__link" href="<?php echo esc_url( TemplateTags::news_url() ); ?>" <?php echo is_home() ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'Todas', 'parkinson-bahia' ); ?></a>
					</li>
					<?php foreach ( $pbc_categories as $pbc_cat ) : ?>
						<li class="pbc-chips__item">
							<a class="pbc-chips__link" href="<?php echo esc_url( get_category_link( $pbc_cat ) ); ?>" <?php echo is_category( $pbc_cat->term_id ) ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $pbc_cat->name ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<?php
		endif;
	endif;
	?>
</header>

<?php $pbc_has_posts = have_posts(); ?>
<div class="pbc-listing<?php echo is_search() ? ' pbc-listing--search' : ''; ?>">
	<?php if ( is_search() ) : ?>
		<?php get_search_form( [ 'pbc_label' => __( 'Buscar otra cosa', 'parkinson-bahia' ), 'pbc_class' => 'pbc-search--page' ] ); ?>
		<?php if ( $pbc_has_posts ) : ?>
			<p class="pbc-listing__count">
				<?php
				$pbc_total = (int) $GLOBALS['wp_query']->found_posts;
				/* translators: %d: número de resultados. */
				echo esc_html( sprintf( _n( 'Hemos encontrado %d resultado.', 'Hemos encontrado %d resultados.', $pbc_total, 'parkinson-bahia' ), $pbc_total ) );
				?>
			</p>
			<div class="pbc-results">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/search-result' );
				endwhile;
				?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $pbc_has_posts && ! is_search() ) : ?>
		<?php
		// La primera noticia de la primera página va destacada.
		if ( $pbc_is_news && ! is_paged() ) :
			the_post();
			get_template_part(
				'template-parts/card',
				null,
				[
					'featured' => true,
					'heading'  => 'h2',
				]
			);
		endif;
		?>

		<?php if ( have_posts() ) : ?>
			<div class="pbc-listing__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card', null, [ 'heading' => 'h2' ] );
				endwhile;
				?>
			</div>
		<?php endif; ?>

		<?php
		the_posts_pagination(
			[
				'mid_size'           => 1,
				'prev_text'          => __( 'Anterior', 'parkinson-bahia' ),
				'next_text'          => __( 'Siguiente', 'parkinson-bahia' ),
				'screen_reader_text' => __( 'Más páginas', 'parkinson-bahia' ),
				'aria_label'         => __( 'Más páginas', 'parkinson-bahia' ),
				'before_page_number' => '<span class="screen-reader-text">' . __( 'Página', 'parkinson-bahia' ) . ' </span>',
				'class'              => 'pbc-pagination',
			]
		);
		?>
	<?php elseif ( is_search() && $pbc_has_posts ) : ?>
		<?php
		the_posts_pagination(
			[
				'mid_size'           => 1,
				'prev_text'          => __( 'Anterior', 'parkinson-bahia' ),
				'next_text'          => __( 'Siguiente', 'parkinson-bahia' ),
				'screen_reader_text' => __( 'Más resultados', 'parkinson-bahia' ),
				'aria_label'         => __( 'Más resultados', 'parkinson-bahia' ),
				'before_page_number' => '<span class="screen-reader-text">' . __( 'Página', 'parkinson-bahia' ) . ' </span>',
				'class'              => 'pbc-pagination',
			]
		);
		?>
	<?php elseif ( is_search() ) : ?>
		<p class="pbc-listing__empty">
			<?php
			$pbc_phone = TemplateTags::mod( 'pbc_phone' );
			/* translators: %s: teléfono. */
			echo esc_html( sprintf( __( 'No hemos encontrado nada con esas palabras. Prueba con otras más sencillas, mira estos accesos o llámanos al %s y te ayudamos.', 'parkinson-bahia' ), $pbc_phone ) );
			?>
		</p>
		<?php get_template_part( 'template-parts/helpful-links', null, [ 'title' => __( 'Lo que más se consulta', 'parkinson-bahia' ) ] ); ?>
	<?php else : ?>
		<p class="pbc-listing__empty"><?php esc_html_e( 'Todavía no hay noticias publicadas.', 'parkinson-bahia' ); ?></p>
	<?php endif; ?>
</div>

<?php
// «En los medios», solo en la primera página de Actualidad.
if ( is_home() && ! is_paged() ) {
	get_template_part( 'template-parts/press' );
}

get_footer();
