<?php
/**
 * Detalle de los tipos de contenido propios (por ejemplo, una terapia).
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
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="pbc-article__thumbnail">
						<?php
						the_post_thumbnail(
							'pbc-feature',
							[
								'sizes'         => '(min-width: 900px) 804px, 100vw',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
							]
						);
						?>
					</figure>
				<?php endif; ?>
				<div class="pbc-prose">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	endif;
endwhile;

get_footer();
