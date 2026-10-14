<?php
/**
 * Ficha de un evento de la agenda.
 *
 * Usa los bloques del tema (cabecera de página, migas de pan, .pbc-prose,
 * .pbc-button). Un tema puede sustituirla con su propio single-pbc_evento.php.
 *
 * @package ParkinsonAgenda
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Agenda\Event;
use DanielLucia\ParkinsonBahia\Agenda\Markup;
use DanielLucia\ParkinsonBahia\Agenda\Plugin;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$pag_event  = Event::from_post( get_post() );
	$pag_phone  = Plugin::phone();
	$pag_agenda = Plugin::page_url();
	$pag_open   = ! $pag_event->is_past() && ! $pag_event->cancelled;
	?>
	<article <?php post_class( 'pbc-event-page' ); ?>>
		<header class="pbc-page-header pbc-page-header--narrow">
			<?php get_template_part( 'template-parts/breadcrumb' ); ?>
			<?php if ( '' !== $pag_event->type_label() ) : ?>
				<p class="pbc-event-page__type"><?php echo esc_html( $pag_event->type_label() ); ?></p>
			<?php endif; ?>
			<h1 class="pbc-page-header__title pbc-page-header__title--article"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="pbc-event-page__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>

		<div class="pbc-event-page__body">
			<?php if ( $pag_event->cancelled ) : ?>
				<p class="pbc-event-page__notice pbc-event-page__notice--cancelled"><strong><?php esc_html_e( 'Esta actividad se ha cancelado.', 'parkinson-agenda' ); ?></strong> <?php esc_html_e( 'Si tenías plaza, te avisaremos por teléfono.', 'parkinson-agenda' ); ?></p>
			<?php elseif ( $pag_event->is_past() ) : ?>
				<p class="pbc-event-page__notice"><?php esc_html_e( 'Esta actividad ya se celebró.', 'parkinson-agenda' ); ?>
					<?php if ( '' !== $pag_agenda ) : ?>
						<a href="<?php echo esc_url( $pag_agenda ); ?>"><?php esc_html_e( 'Mira las próximas en la agenda', 'parkinson-agenda' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<section class="pbc-event-page__card" aria-labelledby="pbc-event-facts-title">
				<?php echo Markup::date_badge( $pag_event ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup escapa cada valor. ?>
				<div class="pbc-event-page__card-body">
					<h2 class="pbc-event-page__card-title" id="pbc-event-facts-title"><?php esc_html_e( 'Cuándo y dónde', 'parkinson-agenda' ); ?></h2>
					<dl class="pbc-event-page__facts">
						<div class="pbc-event-page__fact">
							<dt><?php echo Markup::icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo. ?><?php esc_html_e( 'Cuándo', 'parkinson-agenda' ); ?></dt>
							<dd>
								<time datetime="<?php echo esc_attr( $pag_event->datetime_attr() ); ?>"><?php echo esc_html( $pag_event->date_label() ); ?></time>
								<?php if ( '' !== $pag_event->time_label() ) : ?>
									<br><?php echo esc_html( $pag_event->time_label() ); ?>
								<?php endif; ?>
							</dd>
						</div>
						<?php if ( '' !== $pag_event->place ) : ?>
							<div class="pbc-event-page__fact">
								<dt><?php echo Markup::icon( 'place' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo. ?><?php esc_html_e( 'Dónde', 'parkinson-agenda' ); ?></dt>
								<dd><?php echo esc_html( $pag_event->place ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $pag_event->price ) : ?>
							<div class="pbc-event-page__fact">
								<dt><?php echo Markup::icon( 'price' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo. ?><?php esc_html_e( 'Precio', 'parkinson-agenda' ); ?></dt>
								<dd><?php echo esc_html( $pag_event->price ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $pag_open && '' !== $pag_event->signup ) : ?>
							<div class="pbc-event-page__fact">
								<dt><?php echo Markup::icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo. ?><?php esc_html_e( 'Cómo apuntarse', 'parkinson-agenda' ); ?></dt>
								<dd><?php echo esc_html( $pag_event->signup ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>

					<div class="pbc-event-page__actions">
						<?php if ( $pag_open && '' !== $pag_event->signup_url ) : ?>
							<a class="pbc-button pbc-button--primary" href="<?php echo esc_url( $pag_event->signup_url ); ?>"><?php esc_html_e( 'Apuntarme', 'parkinson-agenda' ); ?></a>
						<?php endif; ?>
						<a class="pbc-button <?php echo esc_attr( $pag_open && '' === $pag_event->signup_url ? 'pbc-button--primary' : 'pbc-button--outline' ); ?>" href="<?php echo esc_url( $pag_phone['href'] ); ?>">
							<?php
							echo Markup::icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo.
							echo esc_html(
								$pag_open
									/* translators: %s: teléfono. */
									? sprintf( __( 'Llamar y apuntarme: %s', 'parkinson-agenda' ), $pag_phone['label'] )
									/* translators: %s: teléfono. */
									: sprintf( __( '¿Dudas? Llama al %s', 'parkinson-agenda' ), $pag_phone['label'] )
							);
							?>
						</a>
					</div>
				</div>
			</section>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="pbc-event-page__figure">
					<?php
					the_post_thumbnail(
						'pbc-wide',
						[
							'class'         => 'pbc-event-page__image',
							'sizes'         => '(min-width: 948px) 900px, 100vw',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						]
					);
					?>
				</figure>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
				<div class="pbc-prose pbc-event-page__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $pag_agenda ) : ?>
				<p class="pbc-event-page__back">
					<a class="pbc-event-page__back-link" href="<?php echo esc_url( $pag_agenda ); ?>"><?php esc_html_e( 'Volver a la agenda', 'parkinson-agenda' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
