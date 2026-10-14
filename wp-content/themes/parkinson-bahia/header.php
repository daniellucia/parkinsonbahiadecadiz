<?php
/**
 * Cabecera: barra de utilidades, logotipo y menú principal.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_phone      = TemplateTags::mod( 'pbc_phone' );
$pbc_phone_href = TemplateTags::phone_href();
$pbc_hours      = TemplateTags::mod( 'pbc_hours_short' );
$pbc_cta_label  = TemplateTags::mod( 'pbc_cta_label' );
$pbc_cta_url    = TemplateTags::mod( 'pbc_cta_url' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="pbc-skip" href="#contenido"><?php esc_html_e( 'Saltar al contenido', 'parkinson-bahia' ); ?></a>

<div class="pbc-utility" role="region" aria-label="<?php esc_attr_e( 'Teléfono y opciones de lectura', 'parkinson-bahia' ); ?>">
	<div class="pbc-utility__inner">
		<div class="pbc-utility__contact">
			<?php if ( '' !== $pbc_phone_href ) : ?>
				<a class="pbc-utility__phone" href="<?php echo esc_url( $pbc_phone_href ); ?>">
					<?php
					/* translators: %s: número de teléfono. */
					printf( esc_html__( 'Llámanos: %s', 'parkinson-bahia' ), esc_html( $pbc_phone ) );
					?>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $pbc_hours ) : ?>
				<span class="pbc-utility__hours"><?php echo esc_html( $pbc_hours ); ?></span>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/reading-tools', null, [ 'block' => 'pbc-utility' ] ); ?>
	</div>
</div>

<header class="pbc-header" data-pbc-header>
	<div class="pbc-header__inner">
		<div class="pbc-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="pbc-header__name" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="pbc-header__actions">
			<?php if ( '' !== $pbc_phone_href ) : ?>
				<a class="pbc-header__call" href="<?php echo esc_url( $pbc_phone_href ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: número de teléfono. */ __( 'Llamar al %s', 'parkinson-bahia' ), $pbc_phone ) ); ?>">
					<svg class="pbc-header__call-icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>
					<span class="pbc-header__call-text"><?php esc_html_e( 'Llamar', 'parkinson-bahia' ); ?></span>
				</a>
			<?php endif; ?>
			<button class="pbc-header__toggle" type="button" aria-expanded="false" aria-controls="pbc-menu" data-pbc-menu-toggle hidden>
				<span class="pbc-header__burger" aria-hidden="true"><span class="pbc-header__burger-line"></span></span>
				<span class="pbc-header__toggle-text" data-pbc-menu-label><?php esc_html_e( 'Menú', 'parkinson-bahia' ); ?></span>
			</button>
		</div>

		<nav class="pbc-nav" id="pbc-menu" aria-label="<?php esc_attr_e( 'Menú principal', 'parkinson-bahia' ); ?>">
			<div class="pbc-nav__panel">
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'pbc-nav__list',
						'depth'          => 1,
						'fallback_cb'    => false,
						'pbc_item_class' => 'pbc-nav__item',
						'pbc_link_class' => 'pbc-nav__link',
					]
				);
				?>
				<?php if ( '' !== $pbc_cta_label && '' !== $pbc_cta_url ) : ?>
					<a class="pbc-button pbc-button--accent pbc-nav__cta" href="<?php echo esc_url( $pbc_cta_url ); ?>"><?php echo esc_html( $pbc_cta_label ); ?></a>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/reading-tools', null, [ 'block' => 'pbc-nav' ] ); ?>
				<?php if ( '' !== $pbc_phone_href ) : ?>
					<p class="pbc-nav__contact">
						<span class="pbc-nav__contact-label"><?php esc_html_e( '¿Prefieres hablar con nosotros?', 'parkinson-bahia' ); ?></span>
						<a class="pbc-nav__phone" href="<?php echo esc_url( $pbc_phone_href ); ?>"><?php echo esc_html( $pbc_phone ); ?></a>
						<?php if ( '' !== $pbc_hours ) : ?>
							<span class="pbc-nav__hours"><?php echo esc_html( $pbc_hours ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		</nav>
	</div>
</header>

<main class="pbc-main" id="contenido" tabindex="-1">
