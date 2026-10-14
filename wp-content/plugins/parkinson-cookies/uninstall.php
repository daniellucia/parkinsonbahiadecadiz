<?php
/**
 * Al borrar el plugin se borran sus ajustes. Las elecciones de cada visitante
 * viven en su navegador (cookie «pbc_cookies») y caducan solas.
 *
 * @package ParkinsonCookies
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pck_settings' );
