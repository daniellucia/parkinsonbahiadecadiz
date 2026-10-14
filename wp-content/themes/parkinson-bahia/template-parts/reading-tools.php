<?php
/**
 * Botones de tamaño del texto y alto contraste.
 *
 * Se pintan dos veces: en la barra superior (pantallas anchas) y dentro del
 * menú plegable (móvil). Solo uno de los dos se ve a la vez; main.js los
 * mantiene sincronizados. Sin JavaScript no funcionan, así que nacen ocultos.
 *
 * Argumento: 'block' => clase BEM del bloque que los contiene (pbc-utility o pbc-nav).
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$pbc_block = 'pbc-nav' === ( $args['block'] ?? '' ) ? 'pbc-nav' : 'pbc-utility';
?>
<div class="pbc-reading <?php echo esc_attr( $pbc_block . '__tools' ); ?>" role="group" aria-label="<?php esc_attr_e( 'Opciones de lectura', 'parkinson-bahia' ); ?>" data-pbc-reading hidden>
	<span class="pbc-reading__label"><?php esc_html_e( 'Tamaño del texto', 'parkinson-bahia' ); ?></span>
	<span class="pbc-reading__sizes">
		<button class="pbc-reading__button pbc-reading__button--size-0" type="button" data-pbc-fs="0" aria-pressed="true" aria-label="<?php esc_attr_e( 'A: texto normal', 'parkinson-bahia' ); ?>">A</button>
		<button class="pbc-reading__button pbc-reading__button--size-1" type="button" data-pbc-fs="1" aria-pressed="false" aria-label="<?php esc_attr_e( 'A: texto grande', 'parkinson-bahia' ); ?>">A</button>
		<button class="pbc-reading__button pbc-reading__button--size-2" type="button" data-pbc-fs="2" aria-pressed="false" aria-label="<?php esc_attr_e( 'A: texto muy grande', 'parkinson-bahia' ); ?>">A</button>
	</span>
	<button class="pbc-reading__button pbc-reading__button--contrast" type="button" data-pbc-contrast aria-pressed="false">
		<span class="pbc-reading__contrast-icon" aria-hidden="true"></span>
		<?php esc_html_e( 'Alto contraste', 'parkinson-bahia' ); ?>
	</button>
</div>
