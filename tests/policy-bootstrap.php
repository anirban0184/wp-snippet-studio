<?php
/** Loaded before WordPress by WP-CLI, never by the shipped plugin. @package WPSnippetStudio */

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) ) {
	throw new RuntimeException( 'Explicit test environment required.' );
}
define( 'WPSSTUDIO_SAFE_MODE', true );
if ( 'file_edit' === getenv( 'WPSSTUDIO_TEST_POLICY' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
if ( 'file_mods' === getenv( 'WPSSTUDIO_TEST_POLICY' ) ) {
	define( 'DISALLOW_FILE_MODS', true );
}
