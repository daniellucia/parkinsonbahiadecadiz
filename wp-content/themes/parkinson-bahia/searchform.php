<?php
/**
 * Formulario de búsqueda.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$pbc_search_id    = wp_unique_id( 'pbc-search-' );
$pbc_search_label = isset( $args['pbc_label'] ) && '' !== $args['pbc_label'] ? (string) $args['pbc_label'] : __( 'Buscar en la web', 'parkinson-bahia' );
$pbc_search_hint  = isset( $args['pbc_hint'] ) ? (string) $args['pbc_hint'] : '';
$pbc_search_class = isset( $args['pbc_class'] ) ? ' ' . sanitize_html_class( (string) $args['pbc_class'] ) : '';
?>
<form class="pbc-search<?php echo esc_attr( $pbc_search_class ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="pbc-search__label" for="<?php echo esc_attr( $pbc_search_id ); ?>"><?php echo esc_html( $pbc_search_label ); ?></label>
	<?php if ( '' !== $pbc_search_hint ) : ?>
		<p class="pbc-search__hint" id="<?php echo esc_attr( $pbc_search_id . '-hint' ); ?>"><?php echo esc_html( $pbc_search_hint ); ?></p>
	<?php endif; ?>
	<div class="pbc-search__row">
		<input class="pbc-search__input" type="search" id="<?php echo esc_attr( $pbc_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off"<?php echo '' !== $pbc_search_hint ? ' aria-describedby="' . esc_attr( $pbc_search_id . '-hint' ) . '"' : ''; ?>>
		<button class="pbc-button pbc-button--primary" type="submit" data-pbc-loading-text="<?php esc_attr_e( 'Buscando…', 'parkinson-bahia' ); ?>"><?php esc_html_e( 'Buscar', 'parkinson-bahia' ); ?></button>
	</div>
</form>
