<?php
/** Preserve snippets unless an owner explicitly opts into permanent removal. @package WPSnippetStudio */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'WPSSTUDIO_DELETE_DATA' ) || true !== WPSSTUDIO_DELETE_DATA || is_multisite() ) {
	return;
}

global $wpdb;
$wpsstudio_table = $wpdb->prefix . 'wpsstudio_snippets';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Explicit owner opt-in permanently removes this plugin's custom table during uninstall; neither a cached read nor the Options API can perform this operation.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpsstudio_table ) );
delete_option( 'wpsstudio_schema_version' );
delete_option( 'wpsstudio_safe_mode' );
