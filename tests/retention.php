<?php
/** Package replacement/uninstall retention smoke checks. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Repository;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Disposable test site required.' );
}
$repository = new Repository();
$action = getenv( 'WPSSTUDIO_RETENTION_ACTION' );
if ( 'seed' === $action ) {
	$id = $repository->save( array( 'title' => 'Package retention check', 'description' => 'Disposable fixture', 'type' => 'css', 'scope' => 'frontend', 'priority' => -12, 'code' => 'body { --wpsstudio-retention: 1; }' ) );
	if ( is_wp_error( $id ) || true !== $repository->status( $id, 1, true ) ) {
		WP_CLI::error( 'Could not seed the retention record.' );
	}
	update_option( 'wpsstudio_test_retention', array( 'id' => $id, 'hash' => hash( 'sha256', wp_json_encode( $repository->get( $id ) ) ) ), false );
} else {
	$expected = get_option( 'wpsstudio_test_retention' );
	$row = $repository->get( (int) $expected['id'] );
	if ( ! $row || ! hash_equals( $expected['hash'], hash( 'sha256', wp_json_encode( $row ) ) ) || ! $repository->verified( $row ) || 'active' !== $row['status'] ) {
		WP_CLI::error( 'Snippet/source/settings/signature changed across package lifecycle.' );
	}
	if ( 'remove' === $action ) {
		$repository->delete( (int) $row['id'], (int) $row['revision'] );
		delete_option( 'wpsstudio_test_retention' );
	}
}
WP_CLI::success( 'Package data-retention check passed: ' . $action );
