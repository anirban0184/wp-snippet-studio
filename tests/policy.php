<?php
/** Isolated process policy checks; see docs/development.md. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Permissions;
use GlowingCoders\WPSnippetStudio\Repository;
use GlowingCoders\WPSnippetStudio\Runner;
use GlowingCoders\WPSnippetStudio\Validator;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Disposable test site required.' );
}
$policy = getenv( 'WPSSTUDIO_TEST_POLICY' );
if ( ! Runner::safe_mode() ) {
	WP_CLI::error( 'wp-config Safe Mode constant did not take effect.' );
}
if ( in_array( $policy, array( 'file_edit', 'file_mods' ), true ) && Permissions::can_manage() ) {
	WP_CLI::error( 'WordPress code-modification policy was bypassed.' );
}
if ( 'tokenizer' === $policy && ( function_exists( 'token_get_all' ) || ! is_wp_error( Validator::php( 'echo 1;' ) ) ) ) {
	WP_CLI::error( 'Unavailable PHP validator did not fail closed.' );
}
if ( ! is_wp_error( ( new Runner( new Repository() ) )->run_manual( 1, 1 ) ) ) {
	WP_CLI::error( 'Safe Mode allowed manual execution.' );
}
WP_CLI::success( 'Isolated policy check passed: ' . $policy );
