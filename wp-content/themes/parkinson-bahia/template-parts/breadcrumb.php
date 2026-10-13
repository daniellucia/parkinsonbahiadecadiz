<?php
/**
 * Migas de pan.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

use DanielLucia\ParkinsonBahia\Theme\TemplateTags;

defined( 'ABSPATH' ) || exit;

$pbc_items = TemplateTags::breadcrumb_items();

if ( count( $pbc_items ) < 2 ) {
	return;
}

$pbc_last = count( $pbc_items ) - 1;
?>
<nav class="pbc-breadcrumb" aria-label="<?php esc_attr_e( 'Estás en', 'parkinson-bahia' ); ?>">
	<ol class="pbc-breadcrumb__list">
		<?php foreach ( $pbc_items as $pbc_index => $pbc_item ) : ?>
			<?php if ( $pbc_index === $pbc_last ) : ?>
				<li class="pbc-breadcrumb__item" aria-current="page"><?php echo esc_html( $pbc_item['label'] ); ?></li>
			<?php else : ?>
				<li class="pbc-breadcrumb__item"><a class="pbc-breadcrumb__link" href="<?php echo esc_url( $pbc_item['url'] ); ?>"><?php echo esc_html( $pbc_item['label'] ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
