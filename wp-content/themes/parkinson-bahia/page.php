<?php
/**
 * Páginas. Las construidas con Elementor ocupan todo el ancho y ponen su propio
 * título dentro de sus widgets; el resto usa la plantilla de texto.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( TemplateTags::is_built_with_elementor( get_the_ID() ) ) :
		the_content();
	else :
		?>
		<article <?php post_class( 'pbc-article' ); ?>>
			<header class="pbc-page-header pbc-page-header--narrow">
				<?php get_template_part( 'template-parts/breadcrumb' ); ?>
				<h1 class="pbc-page-header__title"><?php the_title(); ?></h1>
			</header>
			<div class="pbc-article__body">
				<div class="pbc-prose">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	endif;
endwhile;

get_footer();
