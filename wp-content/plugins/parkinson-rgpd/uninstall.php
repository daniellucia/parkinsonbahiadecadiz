<?php
/**
 * Al borrar el plugin se borran sus ajustes y la tarea programada.
 * No se borra ningún dato de las personas: eso lo deciden los plazos.
 *
 * @package ParkinsonRgpd
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'prgpd_settings' );
delete_option( 'prgpd_last_run' );
wp_clear_scheduled_hook( 'prgpd_purge' );
