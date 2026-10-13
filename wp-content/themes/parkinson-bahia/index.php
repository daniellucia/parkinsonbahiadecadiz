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

<div class="pbc-listing">
	<?php if ( is_search() ) : ?>
		<?php get_search_form(); ?>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
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
	<?php else : ?>
		<p class="pbc-listing__empty">
			<?php
			if ( is_search() ) {
				esc_html_e( 'No hemos encontrado nada con esas palabras. Prueba con otras o llámanos y te ayudamos.', 'parkinson-bahia' );
			} else {
				esc_html_e( 'Todavía no hay noticias publicadas.', 'parkinson-bahia' );
			}
			?>
		</p>
	<?php endif; ?>
</div>

<?php
get_footer();
