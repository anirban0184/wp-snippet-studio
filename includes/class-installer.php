<?php
/** Schema and upgrades. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Installer {
	public const SCHEMA_VERSION = '1';

	public static function activate( bool $network_wide = false ): void {
		if ( $network_wide || is_multisite() ) {
			wp_die( esc_html__( 'WP Snippet Studio currently supports single-site installations only.', 'wp-snippet-studio' ) );
		}
		if ( ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage executable snippets.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		if ( ! self::install() ) {
			wp_die( esc_html__( 'The snippet table could not be created. Check database permissions and retry activation.', 'wp-snippet-studio' ) );
		}
	}

	public static function ready(): bool {
		return self::SCHEMA_VERSION === get_option( 'wpsstudio_schema_version' );
	}

	public static function install(): bool {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = $wpdb->prefix . 'wpsstudio_snippets';
		$charset = $wpdb->get_charset_collate();
		// dbDelta is additive; existing rows and user data are preserved.
		dbDelta( "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(150) NOT NULL,
			description text NOT NULL,
			code longtext NOT NULL,
			type varchar(10) NOT NULL,
			scope varchar(12) NOT NULL,
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
			KEY execution (status,type,priority,id)
		) $charset;" );
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( $table !== $exists || $wpdb->last_error ) {
			return false;
		}
		update_option( 'wpsstudio_schema_version', self::SCHEMA_VERSION, false );
		add_option( 'wpsstudio_safe_mode', '0', '', false );
		return true;
	}
}
