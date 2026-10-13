<?php
/**
 * Página no encontrada.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

get_header();

$pbc_phone      = TemplateTags::mod( 'pbc_phone' );
$pbc_phone_href = TemplateTags::phone_href();
?>
<div class="pbc-article">
	<header class="pbc-page-header pbc-page-header--narrow">
		<h1 class="pbc-page-header__title"><?php esc_html_e( 'No encontramos esta página', 'parkinson-bahia' ); ?></h1>
	</header>
	<div class="pbc-article__body">
		<div class="pbc-prose">
			<p><?php esc_html_e( 'Puede que la dirección haya cambiado o que el enlace esté mal escrito. Puedes volver al inicio, buscar lo que necesitas o llamarnos.', 'parkinson-bahia' ); ?></p>
		</div>
		<?php get_search_form(); ?>
		<p class="pbc-actions">
			<a class="pbc-button pbc-button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Ir al inicio', 'parkinson-bahia' ); ?></a>
			<?php if ( '' !== $pbc_phone_href ) : ?>
				<a class="pbc-button pbc-button--outline" href="<?php echo esc_url( $pbc_phone_href ); ?>">
					<?php
					/* translators: %s: número de teléfono. */
					printf( esc_html__( 'Llamar al %s', 'parkinson-bahia' ), esc_html( $pbc_phone ) );
					?>
				</a>
			<?php endif; ?>
		</p>
	</div>
</div>
<?php
get_footer();
