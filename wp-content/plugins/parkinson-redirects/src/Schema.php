<?php
/**
 * Tablas propias: las redirecciones (se buscan por ruta en cada visita, así
 * que necesitan índice) y el registro de direcciones no encontradas.
 *
 * @package ParkinsonRedirects
 */

declare(strict_types=1);

namespace DanielLucia\Redirects;

defined( 'ABSPATH' ) || exit;

final class Schema {

	private const VERSION = '1';
	private const OPTION  = 'prd_db_version';

	public static function maybe_upgrade(): void {
		if ( self::VERSION !== get_option( self::OPTION ) ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$rules   = $wpdb->prefix . Repository::TABLE;
		$log     = $wpdb->prefix . NotFoundLog::TABLE;

		dbDelta(
			"CREATE TABLE {$rules} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source varchar(255) NOT NULL,
				match_type varchar(10) NOT NULL DEFAULT 'exact',
				target_post bigint(20) unsigned NOT NULL DEFAULT 0,
				target_url text NOT NULL,
				anchor varchar(100) NOT NULL DEFAULT '',
				code smallint(3) unsigned NOT NULL DEFAULT 301,
				note varchar(255) NOT NULL DEFAULT '',
				hits bigint(20) unsigned NOT NULL DEFAULT 0,
				last_hit datetime DEFAULT NULL,
				created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY source (source(191)),
				KEY match_type (match_type)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$log} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				path varchar(255) NOT NULL,
				hits bigint(20) unsigned NOT NULL DEFAULT 1,
				first_seen datetime NOT NULL,
				last_seen datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY path (path(191)),
				KEY last_seen (last_seen)
			) {$charset};"
		);

		update_option( self::OPTION, self::VERSION, true );
	}
}
