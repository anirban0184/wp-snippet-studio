<?php
/** Seed ONLY a disposable local/development site for the browser checks. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Repository;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Browser fixtures require an explicitly authorized disposable test site.' );
}
$path = getenv( 'WPSSTUDIO_QA_FIXTURE' );
if ( ! $path || is_file( $path ) ) {
	WP_CLI::error( 'Set WPSSTUDIO_QA_FIXTURE to a new private JSON path; clean existing fixtures before reseeding.' );
}

$repository = new Repository();
$fixture    = array( 'baseURL' => home_url(), 'snippets' => array() );
$pass       = wp_generate_password( 32, false );
$user       = get_user_by( 'login', 'wpsstudio_browser_admin' );
$admin      = wp_insert_user( array( 'ID' => $user ? $user->ID : 0, 'user_login' => 'wpsstudio_browser_admin', 'user_pass' => $pass, 'role' => 'administrator' ) );
$fixture['admin'] = array( 'username' => 'wpsstudio_browser_admin', 'password' => $pass );
$pass = wp_generate_password( 32, false );
$user = get_user_by( 'login', 'wpsstudio_browser_editor' );
wp_insert_user( array( 'ID' => $user ? $user->ID : 0, 'user_login' => 'wpsstudio_browser_editor', 'user_pass' => $pass, 'role' => 'editor' ) );
$fixture['editor'] = array( 'username' => 'wpsstudio_browser_editor', 'password' => $pass );

foreach ( array(
	array( 'PHP everywhere marker', 'php', 'everywhere', 'header("X-Wpsstudio-Everywhere: yes");' ),
	array( 'PHP frontend marker', 'php', 'frontend', 'header("X-Wpsstudio-Frontend: yes");' ),
	array( 'PHP admin marker', 'php', 'admin', 'header("X-Wpsstudio-Admin: yes");' ),
	array( 'Frontend color', 'css', 'frontend', 'html { --wpsstudio-e2e: 73; }' ),
	array( 'Frontend script', 'js', 'frontend', 'window.wpsstudioE2e = 73;' ),
	array( 'Reusable HTML', 'html', 'everywhere', '<p id="wpsstudio-e2e-html">Reusable <strong>HTML</strong></p>' ),
	array( 'Reusable text', 'text', 'everywhere', '<b id="wpsstudio-e2e-unsafe">Plain text</b>' ),
	array( 'Manual maintenance', 'php', 'manual', 'update_option("wpsstudio_browser_manual", (int) get_option("wpsstudio_browser_manual", 0) + 1, false);' ),
) as $index => $record ) {
	$id = $repository->save( array( 'title' => $record[0], 'type' => $record[1], 'scope' => $record[2], 'code' => $record[3], 'description' => 'Disposable browser-test fixture', 'priority' => 10 ) );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	if ( 'manual' !== $record[2] ) {
		$repository->status( $id, 1, true );
	}
	$fixture['snippets'][ $index ] = $id;
}
if ( '1' === getenv( 'WPSSTUDIO_QA_PAGINATION' ) ) {
	$fixture['pagination_ids'] = array();
	for ( $index = 1; $index <= 53; ++$index ) {
		$id = $repository->save( array( 'title' => sprintf( 'Pagination %03d', $index ), 'description' => 'Disposable pagination fixture', 'code' => 'Reusable content ' . $index, 'type' => 'text', 'scope' => 'everywhere', 'priority' => 10 ) );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id->get_error_message() );
		}
		$fixture['snippets'][] = $id;
		$fixture['pagination_ids'][] = $id;
	}
}
$fixture['post'] = wp_insert_post( array( 'post_title' => 'WP Snippet Studio browser checks', 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '[wpsstudio id="' . $fixture['snippets'][5] . '"] [wpsstudio id="' . $fixture['snippets'][6] . '"]' ) );
update_option( 'wpsstudio_safe_mode', '0', false );
file_put_contents( $path, wp_json_encode( $fixture ) );
chmod( $path, 0600 );
WP_CLI::success( 'Disposable browser fixtures created. Credentials were written to the private fixture file.' );
