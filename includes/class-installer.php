<?php
/** Additive schema and conservative migration from the development build. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema verification and one-time migration target this plugin's custom table directly and must inspect current database state.

final class Installer {
	public const SCHEMA_VERSION = '2';

	public static function activate( bool $network_wide = false ): void {
		if ( $network_wide || ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'WP Snippet Studio requires an administrator on a single-site installation.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		if ( ! self::install() ) {
			wp_die( esc_html__( 'The snippet table is unavailable. Check database permissions and retry activation.', 'wp-snippet-studio' ) );
		}
	}

	public static function ready(): bool {
		return self::SCHEMA_VERSION === get_option( 'wpsstudio_schema_version' );
	}

	public static function install(): bool {
		global $wpdb;
		if ( ! Permissions::can_manage() ) {
			return false;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = $wpdb->prefix . 'wpsstudio_snippets';
		$charset = $wpdb->get_charset_collate();
		$version = get_option( 'wpsstudio_schema_version' );
		if ( false !== $version && ! in_array( $version, array( '1', self::SCHEMA_VERSION ), true ) ) {
			return false;
		}
		// Compatibility columns retain development-build data. They do not enable execution.
		dbDelta(
			"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(150) NOT NULL,
			description text NOT NULL,
			code longtext NOT NULL,
			type varchar(10) NOT NULL,
			scope varchar(12) NOT NULL DEFAULT 'shortcode',
			status varchar(8) NOT NULL DEFAULT 'inactive',
			priority int(11) NOT NULL DEFAULT 10,
			revision bigint(20) unsigned NOT NULL DEFAULT 1,
			signature varchar(64) NOT NULL DEFAULT '',
			last_run_hash varchar(64) NOT NULL DEFAULT '',
			last_run_at datetime DEFAULT NULL,
			last_error varchar(32) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY content_status (type,status,id)
		) $charset;"
		);
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( $table !== $exists || $wpdb->last_error ) {
			return false;
		}
		$columns = $wpdb->get_col( $wpdb->prepare( 'SHOW COLUMNS FROM %i', $table ) );
		if ( array_diff( array( 'id', 'title', 'description', 'code', 'type', 'scope', 'status', 'revision', 'signature', 'created_at', 'updated_at' ), $columns ) ) {
			return false;
		}
		if ( self::SCHEMA_VERSION !== $version ) {
			// Preserve every old row, but require fresh review under the content-only rules.
			$changed = $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'inactive', signature = '', revision = revision + 1", $table ) );
			if ( false === $changed ) {
				return false;
			}
		}
		update_option( 'wpsstudio_schema_version', self::SCHEMA_VERSION, false );
		add_option( 'wpsstudio_safe_mode', '0', '', false );
		return self::ready();
	}
}
