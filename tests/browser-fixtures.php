<?php
/** Seed an isolated content workflow; credentials remain in an ignored private file. */

use GlowingCoders\WPSnippetStudio\Repository;
require __DIR__ . '/bootstrap.php';
$path = getenv( 'WPSSTUDIO_QA_FIXTURE' );
if ( ! $path || is_file( $path ) ) { WP_CLI::error( 'Set a fresh private WPSSTUDIO_QA_FIXTURE path.' ); }
$repo = new Repository();
$fixture = array( 'baseURL' => home_url(), 'snippets' => array(), 'users' => array() );
foreach ( array( 'admin' => 'administrator', 'editor' => 'editor' ) as $key => $role ) {
	$username = 'wpscontent_' . strtolower( wp_generate_password( 12, false, false ) );
	$password = wp_generate_password( 32, false );
	$id = wp_insert_user( array( 'user_login' => $username, 'user_pass' => $password, 'role' => $role ) );
	if ( is_wp_error( $id ) ) { WP_CLI::error( 'Test user creation failed.' ); }
	$fixture['users'][] = $id;
	$fixture[ $key ] = array( 'username' => $username, 'password' => $password );
}
foreach ( array( array( 'Reusable HTML', 'html', '<p id="wpsstudio-html" onclick="alert(1)">Reusable <strong>HTML</strong></p>' ), array( 'Reusable text', 'text', "<b id=\"wpsstudio-unsafe\">Plain & literal</b>\n[wpsstudio id=\"999999\"]" ) ) as $record ) {
	$id = $repo->save( array( 'title' => $record[0], 'type' => $record[1], 'code' => $record[2], 'description' => 'Disposable content fixture' ) );
	if ( is_wp_error( $id ) || true !== $repo->status( $id, 1, true ) ) { WP_CLI::error( 'Content fixture failed.' ); }
	$fixture['snippets'][] = $id;
}
for ( $i = 1; $i <= 27; ++$i ) {
	$id = $repo->save( array( 'title' => sprintf( 'Pagination %03d', $i ), 'description' => 'Disposable pagination fixture', 'type' => 'text', 'code' => 'Page content ' . $i ) );
	if ( is_wp_error( $id ) ) { WP_CLI::error( 'Pagination fixture failed.' ); }
	$fixture['snippets'][] = $id;
}
$fixture['post'] = wp_insert_post( array( 'post_title' => 'WP Snippet Studio content checks', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[wpsstudio id="' . $fixture['snippets'][0] . '"] [wpsstudio id="' . $fixture['snippets'][1] . '"]' ) );
update_option( 'wpsstudio_safe_mode', '0', false );
file_put_contents( $path, wp_json_encode( $fixture ) ); chmod( $path, 0600 );
WP_CLI::success( 'Private content browser fixtures prepared.' );
