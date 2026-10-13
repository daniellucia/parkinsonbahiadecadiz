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

		<?php // Los botones solo funcionan con JavaScript; sin él no se muestran. ?>
		<div class="pbc-utility__tools" role="group" aria-label="<?php esc_attr_e( 'Opciones de lectura', 'parkinson-bahia' ); ?>" data-pbc-reading hidden>
			<span class="pbc-utility__label"><?php esc_html_e( 'Tamaño del texto', 'parkinson-bahia' ); ?></span>
			<button class="pbc-utility__button pbc-utility__button--size-0" type="button" data-pbc-fs="0" aria-pressed="true" aria-label="<?php esc_attr_e( 'A: texto normal', 'parkinson-bahia' ); ?>">A</button>
			<button class="pbc-utility__button pbc-utility__button--size-1" type="button" data-pbc-fs="1" aria-pressed="false" aria-label="<?php esc_attr_e( 'A: texto grande', 'parkinson-bahia' ); ?>">A</button>
			<button class="pbc-utility__button pbc-utility__button--size-2" type="button" data-pbc-fs="2" aria-pressed="false" aria-label="<?php esc_attr_e( 'A: texto muy grande', 'parkinson-bahia' ); ?>">A</button>
			<button class="pbc-utility__button pbc-utility__button--contrast" type="button" data-pbc-contrast aria-pressed="false">
				<span class="pbc-utility__contrast-icon" aria-hidden="true"></span>
				<?php esc_html_e( 'Alto contraste', 'parkinson-bahia' ); ?>
			</button>
		</div>
	</div>
</div>

<header class="pbc-header">
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
				<a class="pbc-button pbc-button--primary pbc-header__call" href="<?php echo esc_url( $pbc_phone_href ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: número de teléfono. */ __( 'Llamar al %s', 'parkinson-bahia' ), $pbc_phone ) ); ?>">
					<?php esc_html_e( 'Llamar', 'parkinson-bahia' ); ?>
				</a>
			<?php endif; ?>
			<button class="pbc-button pbc-button--outline-ink pbc-header__toggle" type="button" aria-expanded="false" aria-controls="pbc-menu" data-pbc-menu-toggle hidden>
				<?php esc_html_e( 'Menú', 'parkinson-bahia' ); ?>
			</button>
		</div>

		<nav class="pbc-nav" id="pbc-menu" aria-label="<?php esc_attr_e( 'Menú principal', 'parkinson-bahia' ); ?>">
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
		</nav>
	</div>
</header>

<main class="pbc-main" id="contenido" tabindex="-1">
