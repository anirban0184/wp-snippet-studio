<?php
/** Verify actual ZIP replacement preserves content and disables all legacy approvals. */

use GlowingCoders\WPSnippetStudio\{Installer, Repository};
require __DIR__ . '/bootstrap.php';
global $wpdb;
$records = get_option( 'wpsstudio_upgrade_fixture' );
if ( ! is_array( $records ) || ! Installer::ready() ) { WP_CLI::error( 'Upgrade setup or schema is missing.' ); }
$repo = new Repository(); $table = $wpdb->prefix . 'wpsstudio_snippets';
foreach ( $records as $original ) {
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $original['id'] ), ARRAY_A );
	foreach ( array( 'title', 'description', 'code', 'type', 'priority', 'last_run_hash', 'last_run_at', 'created_at' ) as $field ) {
		if ( (string) $original[ $field ] !== (string) $row[ $field ] ) { WP_CLI::error( 'Upgrade altered preserved legacy data: ' . $field ); }
	}
	if ( 'inactive' !== $row['status'] || '' !== $row['signature'] || 5 !== (int) $row['revision'] || '' !== do_shortcode( '[wpsstudio id="' . $row['id'] . '"]' ) ) { WP_CLI::error( 'Legacy approval was not safely disabled.' ); }
	if ( ! in_array( $row['type'], array( 'html', 'text' ), true ) && ( null !== $repo->get( (int) $row['id'] ) || ! is_wp_error( $repo->status( (int) $row['id'], 5, true ) ) ) ) { WP_CLI::error( 'Unsupported legacy record is accessible.' ); }
}
if ( false !== get_option( 'wpsstudio_upgrade_executed' ) || is_file( WPSSTUDIO_PATH . 'includes/class-runner.php' ) ) { WP_CLI::error( 'Legacy execution marker: ' . ( false !== get_option( 'wpsstudio_upgrade_executed' ) ? 'present' : 'absent' ) . '; obsolete runner file: ' . ( is_file( WPSSTUDIO_PATH . 'includes/class-runner.php' ) ? 'present' : 'absent' ) . '; installed version: ' . WPSSTUDIO_VERSION ); }
foreach ( $records as $row ) { $wpdb->delete( $table, array( 'id' => $row['id'] ), array( '%d' ) ); }
delete_option( 'wpsstudio_upgrade_fixture' );
WP_CLI::success( 'Actual legacy ZIP upgrade preserved all five types and disabled every approval.' );
