<?php
/**
 * Tarjeta de noticia. Se usa dentro del bucle.
 *
 * Argumentos: 'featured' (bool) para la noticia destacada, 'heading' (h2|h3), 'excerpt' (bool).
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_featured = ! empty( $args['featured'] );
$pbc_heading  = in_array( $args['heading'] ?? '', [ 'h2', 'h3' ], true ) ? $args['heading'] : 'h3';
$pbc_excerpt  = $args['excerpt'] ?? true;
$pbc_category = 'post' === get_post_type() ? TemplateTags::primary_category( get_the_ID() ) : null;
?>
<article <?php post_class( $pbc_featured ? 'pbc-card pbc-card--featured' : 'pbc-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php
		the_post_thumbnail(
			$pbc_featured ? 'pbc-feature' : 'pbc-card',
			[
				'class'         => 'pbc-card__image',
				'sizes'         => $pbc_featured ? '(min-width: 960px) 580px, 100vw' : '(min-width: 1040px) 380px, (min-width: 700px) 50vw, 100vw',
				'loading'       => $pbc_featured ? 'eager' : 'lazy',
				'fetchpriority' => $pbc_featured ? 'high' : 'auto',
			]
		);
		?>
	<?php endif; ?>

	<div class="pbc-card__body">
		<p class="pbc-card__meta">
			<?php if ( $pbc_category ) : ?>
				<span class="pbc-card__category"><?php echo esc_html( $pbc_category->name ); ?></span>
			<?php endif; ?>
			<time class="pbc-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</p>

		<<?php echo esc_html( $pbc_heading ); ?> class="pbc-card__title">
			<a class="pbc-card__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</<?php echo esc_html( $pbc_heading ); ?>>

		<?php if ( $pbc_excerpt ) : ?>
			<p class="pbc-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>

		<?php if ( $pbc_featured ) : ?>
			<a class="pbc-card__more" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php esc_html_e( 'Leer la noticia', 'parkinson-bahia' ); ?></a>
		<?php endif; ?>
	</div>
</article>
