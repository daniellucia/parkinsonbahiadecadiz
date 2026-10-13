<?php
/**
 * Formulario de búsqueda.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$pbc_search_id = wp_unique_id( 'pbc-search-' );
?>
<form class="pbc-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="pbc-search__label" for="<?php echo esc_attr( $pbc_search_id ); ?>"><?php esc_html_e( 'Buscar en la web', 'parkinson-bahia' ); ?></label>
	<div class="pbc-search__row">
		<input class="pbc-search__input" type="search" id="<?php echo esc_attr( $pbc_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off">
		<button class="pbc-button pbc-button--primary" type="submit"><?php esc_html_e( 'Buscar', 'parkinson-bahia' ); ?></button>
	</div>
</form>
