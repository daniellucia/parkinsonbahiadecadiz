<?php
/**
 * Al borrar el plugin se borran sus tablas (redirecciones y registro de 404)
 * y sus ajustes.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Borrado de las tablas propias al desinstalar.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}prd_redirects, {$wpdb->prefix}prd_404" );

delete_option( 'prd_settings' );
delete_option( 'prd_db_version' );
