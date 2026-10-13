<?php
/**
 * Bloque de contacto común a todas las páginas, justo antes del pie.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_phone      = TemplateTags::mod( 'pbc_phone' );
$pbc_phone_href = TemplateTags::phone_href();
$pbc_email      = sanitize_email( TemplateTags::mod( 'pbc_email' ) );
$pbc_address    = TemplateTags::mod( 'pbc_address' );
$pbc_hours      = TemplateTags::mod( 'pbc_hours' );
$pbc_map_url    = TemplateTags::mod( 'pbc_map_url' );
$pbc_title      = TemplateTags::mod( 'pbc_contact_title' );
$pbc_text       = TemplateTags::mod( 'pbc_contact_text' );
?>
<section class="pbc-contact" id="contacto" aria-labelledby="pbc-contact-title">
	<div class="pbc-contact__inner">
		<div class="pbc-contact__body">
			<h2 class="pbc-contact__title" id="pbc-contact-title"><?php echo esc_html( $pbc_title ); ?></h2>
			<?php if ( '' !== $pbc_text ) : ?>
				<p class="pbc-contact__text"><?php echo esc_html( $pbc_text ); ?></p>
			<?php endif; ?>

			<dl class="pbc-contact__list">
				<?php if ( '' !== $pbc_phone_href ) : ?>
					<div class="pbc-contact__item">
						<dt class="pbc-contact__term"><?php esc_html_e( 'Teléfono', 'parkinson-bahia' ); ?></dt>
						<dd class="pbc-contact__value"><a class="pbc-contact__phone" href="<?php echo esc_url( $pbc_phone_href ); ?>"><?php echo esc_html( $pbc_phone ); ?></a></dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $pbc_email ) : ?>
					<div class="pbc-contact__item">
						<dt class="pbc-contact__term"><?php esc_html_e( 'Correo electrónico', 'parkinson-bahia' ); ?></dt>
						<dd class="pbc-contact__value"><a class="pbc-contact__email" href="<?php echo esc_url( 'mailto:' . $pbc_email ); ?>"><?php echo esc_html( $pbc_email ); ?></a></dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $pbc_address ) : ?>
					<div class="pbc-contact__item">
						<dt class="pbc-contact__term"><?php esc_html_e( 'Dirección', 'parkinson-bahia' ); ?></dt>
						<dd class="pbc-contact__value"><?php echo nl2br( esc_html( $pbc_address ) ); ?></dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $pbc_hours ) : ?>
					<div class="pbc-contact__item">
						<dt class="pbc-contact__term"><?php esc_html_e( 'Horario', 'parkinson-bahia' ); ?></dt>
						<dd class="pbc-contact__value"><?php echo nl2br( esc_html( $pbc_hours ) ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>

			<?php if ( '' !== $pbc_map_url ) : ?>
				<a class="pbc-button pbc-button--outline" href="<?php echo esc_url( $pbc_map_url ); ?>"><?php esc_html_e( 'Cómo llegar en el mapa', 'parkinson-bahia' ); ?></a>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Hueco del mapa. El plugin del sitio puede pintar aquí un mapa de carga
		 * bajo demanda; mientras tanto se muestra un panel decorativo sin terceros.
		 */
		if ( has_action( 'parkinson_bahia/contact_map' ) ) :
			do_action( 'parkinson_bahia/contact_map' );
		else :
			?>
			<div class="pbc-contact__map" aria-hidden="true"></div>
		<?php endif; ?>
	</div>
</section>
