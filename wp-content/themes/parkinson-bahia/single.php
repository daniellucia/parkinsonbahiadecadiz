<?php
/**
 * Detalle de una noticia.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$pbc_category = TemplateTags::primary_category( get_the_ID() );
	?>
	<article <?php post_class( 'pbc-article' ); ?>>
		<header class="pbc-page-header pbc-page-header--narrow">
			<?php get_template_part( 'template-parts/breadcrumb' ); ?>
			<p class="pbc-article__meta">
				<?php if ( $pbc_category ) : ?>
					<a class="pbc-article__category" href="<?php echo esc_url( get_category_link( $pbc_category ) ); ?>"><?php echo esc_html( $pbc_category->name ); ?></a>
				<?php endif; ?>
				<time class="pbc-article__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			</p>
			<h1 class="pbc-page-header__title pbc-page-header__title--article"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="pbc-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="pbc-article__figure">
				<?php
				the_post_thumbnail(
					'pbc-wide',
					[
						'class'         => 'pbc-article__image',
						'sizes'         => '(min-width: 1140px) 1044px, 100vw',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
					]
				);
				?>
			</figure>
		<?php endif; ?>

		<div class="pbc-article__body">
			<div class="pbc-prose">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					[
						'before' => '<nav class="pbc-article__pages" aria-label="' . esc_attr__( 'Páginas de la noticia', 'parkinson-bahia' ) . '">',
						'after'  => '</nav>',
					]
				);
				?>
			</div>

			<div class="pbc-article__footer">
				<div class="pbc-share" role="group" aria-label="<?php esc_attr_e( 'Compartir esta noticia', 'parkinson-bahia' ); ?>">
					<span class="pbc-share__label"><?php esc_html_e( 'Compartir', 'parkinson-bahia' ); ?></span>
					<?php foreach ( TemplateTags::share_links( get_the_ID() ) as $pbc_network => $pbc_url ) : ?>
						<a class="pbc-button pbc-button--outline pbc-button--small" href="<?php echo esc_url( $pbc_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $pbc_network ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(abre en otra ventana)', 'parkinson-bahia' ); ?></span>
						</a>
					<?php endforeach; ?>
					<button class="pbc-button pbc-button--outline pbc-button--small" type="button" data-pbc-copy="<?php echo esc_url( get_permalink() ); ?>" hidden><?php esc_html_e( 'Copiar enlace', 'parkinson-bahia' ); ?></button>
					<span class="screen-reader-text" role="status" data-pbc-copy-status></span>
				</div>
				<a class="pbc-article__back" href="<?php echo esc_url( TemplateTags::news_url() ); ?>">
					<?php
					/* translators: %s: nombre de la sección de noticias. */
					printf( esc_html__( 'Volver a %s', 'parkinson-bahia' ), esc_html( TemplateTags::news_title() ) );
					?>
				</a>
			</div>
		</div>
	</article>

	<?php
	$pbc_related = new WP_Query(
		[
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			'post__not_in'           => [ get_the_ID() ],
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'has_password'           => false,
			'update_post_term_cache' => true,
		]
	);

	if ( $pbc_related->have_posts() ) :
		?>
		<section class="pbc-related" aria-labelledby="pbc-related-title">
			<div class="pbc-related__inner">
				<h2 class="pbc-related__title" id="pbc-related-title"><?php esc_html_e( 'Otras noticias', 'parkinson-bahia' ); ?></h2>
				<div class="pbc-listing__grid">
					<?php
					while ( $pbc_related->have_posts() ) :
						$pbc_related->the_post();
						get_template_part(
							'template-parts/card',
							null,
							[
								'heading' => 'h3',
								'excerpt' => false,
							]
						);
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
