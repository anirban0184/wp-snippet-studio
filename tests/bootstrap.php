<?php
/** Refuse every destructive fixture outside an explicitly disposable localhost site. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
	throw new RuntimeException( 'Explicitly authorized disposable localhost WordPress required.' );
}
