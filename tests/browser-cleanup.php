<?php
/** Remove disposable fixture data only. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Repository;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Cleanup requires an explicitly authorized disposable test site.' );
}
$path = getenv( 'WPSSTUDIO_QA_FIXTURE' );
if ( ! $path || ! is_file( $path ) ) {
	WP_CLI::success( 'No fixtures to clean.' );
	return;
}
$fixture = json_decode( file_get_contents( $path ), true );
$repository = new Repository();
foreach ( $fixture['snippets'] as $id ) {
	$row = $repository->get( (int) $id );
	if ( $row ) {
		$repository->delete( (int) $id, (int) $row['revision'] );
	}
}
wp_delete_post( (int) $fixture['post'], true );
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( array( 'wpsstudio_browser_admin', 'wpsstudio_browser_editor' ) as $login ) {
	$user = get_user_by( 'login', $login );
	if ( $user ) {
		wp_delete_user( $user->ID );
	}
}
delete_option( 'wpsstudio_browser_manual' );
update_option( 'wpsstudio_safe_mode', '0', false );
unlink( $path );
WP_CLI::success( 'Disposable fixtures removed.' );
