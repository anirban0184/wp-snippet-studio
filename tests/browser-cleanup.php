<?php
/** Delete only records owned by this explicitly disposable browser fixture. */
require __DIR__ . '/bootstrap.php';
global $wpdb;
$path = getenv( 'WPSSTUDIO_QA_FIXTURE' );
if ( ! $path || ! is_file( $path ) ) { WP_CLI::success( 'No browser fixture to clean.' ); return; }
$fixture = json_decode( file_get_contents( $path ), true );
if ( ! is_array( $fixture ) || home_url() !== $fixture['baseURL'] ) { WP_CLI::error( 'Unexpected fixture; cleanup stopped.' ); }
foreach ( $fixture['snippets'] as $id ) { $wpdb->delete( $wpdb->prefix . 'wpsstudio_snippets', array( 'id' => (int) $id ), array( '%d' ) ); }
wp_delete_post( (int) $fixture['post'], true );
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( $fixture['users'] as $id ) { wp_delete_user( (int) $id ); }
update_option( 'wpsstudio_safe_mode', '0', false );
unlink( $path );
WP_CLI::success( 'Disposable content browser fixtures removed.' );
