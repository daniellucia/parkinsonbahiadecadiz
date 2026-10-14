<?php
/**
 * Página no encontrada.
 *
 * Pensada para que nadie se quede atascado: dice qué ha pasado sin culpar a
 * nadie, sugiere páginas parecidas a partir de las palabras de la dirección
 * (la web es nueva y muchas direcciones antiguas han cambiado), ofrece el
 * buscador ya relleno, los accesos principales y el teléfono.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

get_header();

$pbc_phone      = TemplateTags::mod( 'pbc_phone' );
$pbc_phone_href = TemplateTags::phone_href();
$pbc_hours      = TemplateTags::mod( 'pbc_hours_short' );
$pbc_words      = TemplateTags::words_from_request();
$pbc_found      = [];

// Páginas parecidas: primero con todas las palabras; si no hay, con cada una por separado.
if ( $pbc_words ) {
	$pbc_queries = array_merge( [ implode( ' ', $pbc_words ) ], count( $pbc_words ) > 1 ? $pbc_words : [] );

	foreach ( $pbc_queries as $pbc_query ) {
		$pbc_found = get_posts(
			[
				's'                => $pbc_query,
				'post_type'        => array_values( get_post_types( [ 'exclude_from_search' => false ] ) ),
				'post_status'      => 'publish',
				'has_password'     => false,
				'posts_per_page'   => 4,
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);
		if ( $pbc_found ) {
			break;
		}
	}
}
?>
<div class="pbc-notfound">
	<header class="pbc-notfound__header">
		<p class="pbc-notfound__eyebrow"><?php esc_html_e( 'Error 404', 'parkinson-bahia' ); ?></p>
		<h1 class="pbc-notfound__title"><?php esc_html_e( 'No encontramos esta página', 'parkinson-bahia' ); ?></h1>
		<p class="pbc-notfound__text"><?php esc_html_e( 'Hemos renovado la web y algunas direcciones han cambiado, o puede que el enlace tenga un error. No te preocupes: aquí tienes cómo seguir.', 'parkinson-bahia' ); ?></p>
	</header>

	<?php if ( $pbc_found ) : ?>
		<section class="pbc-notfound__section" aria-labelledby="pbc-notfound-suggest">
			<h2 class="pbc-notfound__subtitle" id="pbc-notfound-suggest"><?php esc_html_e( '¿Buscabas alguna de estas?', 'parkinson-bahia' ); ?></h2>
			<ul class="pbc-notfound__suggestions">
				<?php foreach ( $pbc_found as $pbc_post ) : ?>
					<li><a class="pbc-notfound__suggestion" href="<?php echo esc_url( get_permalink( $pbc_post ) ); ?>"><?php echo esc_html( get_the_title( $pbc_post ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<section class="pbc-notfound__section" aria-label="<?php esc_attr_e( 'Buscar', 'parkinson-bahia' ); ?>">
		<?php
		// El buscador llega relleno con las palabras de la dirección para no tener que escribirlas.
		set_query_var( 's', implode( ' ', $pbc_words ) );
		get_search_form(
			[
				'pbc_label' => __( 'Busca lo que necesitas', 'parkinson-bahia' ),
				'pbc_hint'  => __( 'Escribe una o dos palabras, por ejemplo: logopedia, transporte o socio.', 'parkinson-bahia' ),
				'pbc_class' => 'pbc-search--page',
			]
		);
		set_query_var( 's', '' );
		?>
	</section>

	<div class="pbc-notfound__section">
		<?php get_template_part( 'template-parts/helpful-links', null, [ 'title' => __( 'O ve directamente a', 'parkinson-bahia' ) ] ); ?>
	</div>

	<?php if ( '' !== $pbc_phone_href ) : ?>
		<aside class="pbc-notfound__help" aria-labelledby="pbc-notfound-help">
			<h2 class="pbc-notfound__subtitle" id="pbc-notfound-help"><?php esc_html_e( '¿Prefieres que te ayudemos?', 'parkinson-bahia' ); ?></h2>
			<p class="pbc-notfound__help-text">
				<?php esc_html_e( 'Llámanos y te decimos dónde está lo que buscas.', 'parkinson-bahia' ); ?>
				<?php if ( '' !== $pbc_hours ) : ?>
					<span class="pbc-notfound__hours"><?php echo esc_html( $pbc_hours ); ?></span>
				<?php endif; ?>
			</p>
			<p class="pbc-actions pbc-notfound__actions">
				<a class="pbc-button pbc-button--primary" href="<?php echo esc_url( $pbc_phone_href ); ?>">
					<?php
					/* translators: %s: número de teléfono. */
					printf( esc_html__( 'Llamar al %s', 'parkinson-bahia' ), esc_html( $pbc_phone ) );
					?>
				</a>
				<a class="pbc-button pbc-button--outline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Ir al inicio', 'parkinson-bahia' ); ?></a>
			</p>
		</aside>
	<?php endif; ?>
</div>
<?php
get_footer();
