<?php
/**
 * Pie: bloque de contacto común, financiadores y pie de página.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_social = array_filter(
	[
		'Facebook'  => TemplateTags::mod( 'pbc_facebook' ),
		'Instagram' => TemplateTags::mod( 'pbc_instagram' ),
		'X'         => TemplateTags::mod( 'pbc_x' ),
		'YouTube'   => TemplateTags::mod( 'pbc_youtube' ),
	]
);

$pbc_legal_name = TemplateTags::mod( 'pbc_legal_name' );
$pbc_note       = TemplateTags::mod( 'pbc_footer_note' );

/**
 * Línea de protección de datos al final del pie (la rellena el plugin de RGPD).
 *
 * @param array{text?: string, links?: array<int, array{label: string, url: string}>} $privacy Texto y enlaces.
 */
$pbc_privacy       = (array) apply_filters( 'parkinson_bahia/footer_privacy', [] );
$pbc_privacy_text  = trim( (string) ( $pbc_privacy['text'] ?? '' ) );
$pbc_privacy_links = array_filter(
	(array) ( $pbc_privacy['links'] ?? [] ),
	static fn ( $link ): bool => is_array( $link ) && ! empty( $link['label'] ) && ! empty( $link['url'] )
);

/**
 * Permite ocultar el bloque de contacto común (por ejemplo, en la página de contacto).
 */
if ( apply_filters( 'parkinson_bahia/show_contact_band', ! is_404() ) ) {
	get_template_part( 'template-parts/contact-band' );
}
?>
</main>

<footer class="pbc-footer">
	<?php
	/**
	 * Banda «Con el apoyo de». La pinta el plugin del sitio con los logotipos de
	 * los financiadores (CPT); si nadie se engancha, no se muestra.
	 */
	if ( has_action( 'parkinson_bahia/footer_funders' ) ) :
		?>
		<div class="pbc-footer__funders">
			<p class="pbc-footer__funders-title"><?php esc_html_e( 'Con el apoyo de', 'parkinson-bahia' ); ?></p>
			<?php do_action( 'parkinson_bahia/footer_funders' ); ?>
		</div>
	<?php endif; ?>

	<div class="pbc-footer__columns">
		<div class="pbc-footer__about">
			<span class="pbc-footer__name"><?php bloginfo( 'name' ); ?></span>
			<?php if ( '' !== $pbc_legal_name ) : ?>
				<span class="pbc-footer__legal"><?php echo esc_html( $pbc_legal_name ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( has_nav_menu( 'footer-asociacion' ) ) : ?>
			<nav class="pbc-footer__nav" aria-labelledby="pbc-footer-asociacion">
				<h2 class="pbc-footer__heading" id="pbc-footer-asociacion"><?php esc_html_e( 'La asociación', 'parkinson-bahia' ); ?></h2>
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'footer-asociacion',
						'container'      => false,
						'menu_class'     => 'pbc-footer__list',
						'depth'          => 1,
						'fallback_cb'    => false,
						'pbc_item_class' => 'pbc-footer__item',
						'pbc_link_class' => 'pbc-footer__link',
					]
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( $pbc_social ) : ?>
			<nav class="pbc-footer__nav" aria-labelledby="pbc-footer-redes">
				<h2 class="pbc-footer__heading" id="pbc-footer-redes"><?php esc_html_e( 'Redes sociales', 'parkinson-bahia' ); ?></h2>
				<ul class="pbc-footer__list">
					<?php foreach ( $pbc_social as $pbc_network => $pbc_url ) : ?>
						<li class="pbc-footer__item"><a class="pbc-footer__link" href="<?php echo esc_url( $pbc_url ); ?>"><?php echo esc_html( $pbc_network ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<?php if ( has_nav_menu( 'footer-legal' ) ) : ?>
			<nav class="pbc-footer__nav" aria-labelledby="pbc-footer-legal">
				<h2 class="pbc-footer__heading" id="pbc-footer-legal"><?php esc_html_e( 'Información legal', 'parkinson-bahia' ); ?></h2>
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'footer-legal',
						'container'      => false,
						'menu_class'     => 'pbc-footer__list',
						'depth'          => 1,
						'fallback_cb'    => false,
						'pbc_item_class' => 'pbc-footer__item',
						'pbc_link_class' => 'pbc-footer__link',
					]
				);
				?>
			</nav>
		<?php endif; ?>
	</div>

	<p class="pbc-footer__bottom">
		<?php
		printf(
			/* translators: 1: año, 2: nombre del sitio. */
			esc_html__( '© %1$s %2$s.', 'parkinson-bahia' ),
			esc_html( wp_date( 'Y' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
		if ( '' !== $pbc_note ) {
			echo ' ' . esc_html( $pbc_note );
		}
		?>
		<?php if ( '' !== $pbc_privacy_text || $pbc_privacy_links ) : ?>
			<span class="pbc-footer__privacy">
				<?php echo esc_html( $pbc_privacy_text ); ?>
				<?php foreach ( array_values( $pbc_privacy_links ) as $pbc_index => $pbc_link ) : ?>
					<?php echo $pbc_index > 0 ? '<span aria-hidden="true">·</span>' : ''; ?>
					<a class="pbc-footer__link pbc-footer__link--inline" href="<?php echo esc_url( (string) $pbc_link['url'], [ 'http', 'https', 'mailto' ] ); ?>"><?php echo esc_html( (string) $pbc_link['label'] ); ?></a>
				<?php endforeach; ?>
			</span>
		<?php endif; ?>
		<span class="pbc-footer__credit">
			<?php
			printf(
				/* translators: %s: enlace al autor. */
				esc_html__( 'Web: %s', 'parkinson-bahia' ),
				'<a class="pbc-footer__link pbc-footer__link--inline" href="https://daniellucia.es">Daniel Lucia</a>'
			);
			?>
		</span>
	</p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
