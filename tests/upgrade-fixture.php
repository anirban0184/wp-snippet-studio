<?php
/** Seed the installed 0.1.1 schema before replacing its package. */

require __DIR__ . '/bootstrap.php';
global $wpdb;
if ( '1' !== get_option( 'wpsstudio_schema_version' ) ) { WP_CLI::error( 'Install the actual legacy package first.' ); }
delete_option( 'wpsstudio_upgrade_executed' );
$table = $wpdb->prefix . 'wpsstudio_snippets';
$records = array();
foreach ( array( 'php' => "update_option('wpsstudio_upgrade_executed', 'bad');", 'css' => 'body { color: red; }', 'js' => 'window.wpsstudioUpgradeExecuted = true;', 'html' => '<p id="upgrade-html">Preserved HTML</p>', 'text' => 'Preserved plain text' ) as $type => $code ) {
	$now = current_time( 'mysql', true );
	$row = array( 'title' => 'Upgrade fixture ' . $type, 'description' => 'Preserve me', 'code' => $code, 'type' => $type, 'scope' => in_array( $type, array( 'html', 'text' ), true ) ? 'shortcode' : 'everywhere', 'status' => 'active', 'priority' => -7, 'revision' => 4, 'signature' => str_repeat( 'a', 64 ), 'last_run_hash' => str_repeat( 'b', 64 ), 'last_run_at' => $now, 'last_error' => '', 'created_at' => $now, 'updated_at' => $now );
	if ( false === $wpdb->insert( $table, $row ) ) { WP_CLI::error( 'Legacy fixture creation failed.' ); }
	$row['id'] = (int) $wpdb->insert_id;
	$signed = array();
	foreach ( array( 'id', 'title', 'description', 'code', 'type', 'scope', 'status', 'priority', 'revision', 'last_run_hash', 'last_run_at' ) as $field ) { $signed[ $field ] = (string) $row[ $field ]; }
	$row['signature'] = hash_hmac( 'sha256', wp_json_encode( $signed ), wp_salt( 'auth' ) . '|wpsstudio-v1' );
	$wpdb->update( $table, array( 'signature' => $row['signature'] ), array( 'id' => $row['id'] ) );
	$records[] = $row;
}
update_option( 'wpsstudio_upgrade_fixture', $records, false );
WP_CLI::success( 'Seeded five legacy records without loading or running the legacy plugin.' );
