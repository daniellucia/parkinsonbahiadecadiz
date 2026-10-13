<?php
/**
 * Limpieza al desinstalar: borra los ajustes y los campos por contenido.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pllms_settings' );
delete_option( 'pllms_cache_version' );
delete_post_meta_by_key( '_pllms_exclude' );
delete_post_meta_by_key( '_pllms_description' );
