<?php
/** Simultaneous manual POST-equivalent execution through independent DB connections. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Repository;
use GlowingCoders\WPSnippetStudio\Runner;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) || ! function_exists( 'pcntl_fork' ) ) {
	WP_CLI::error( 'Concurrency checks require a disposable local/development site and the CLI pcntl extension.' );
}
$repository = new Repository();
$wpdb = $GLOBALS['wpdb'];
$prefix = $wpdb->prefix;
$directory = sys_get_temp_dir() . '/wpsstudio-race-' . bin2hex( random_bytes( 8 ) );
if ( ! mkdir( $directory, 0700 ) ) {
	WP_CLI::error( 'Could not create a private race-test directory.' );
}
$id = 0;
$safe = get_option( 'wpsstudio_safe_mode', '0' );
$reconnect = static function () use ( $prefix ): void {
	$GLOBALS['wpdb'] = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$GLOBALS['wpdb']->set_prefix( $prefix );
};

try {
	update_option( 'wpsstudio_safe_mode', '0', false );
	add_option( 'wpsstudio_concurrent_counter', 0, '', false );
	$id = $repository->save( array(
		'title' => 'Concurrent manual maintenance',
		'description' => 'Disposable race-test fixture',
		'type' => 'php', 'scope' => 'manual', 'priority' => 10,
		'code' => 'global $wpdb; $wpdb->query($wpdb->prepare("UPDATE %i SET option_value = option_value + 1 WHERE option_name = %s", $wpdb->options, "wpsstudio_concurrent_counter"));',
	) );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	// Never share a live MySQL socket across forks. Every worker and the parent reconnect.
	$wpdb->close();
	$children = array();
	try {
		for ( $worker = 0; $worker < 2; ++$worker ) {
			$pid = pcntl_fork();
			if ( -1 === $pid ) {
				throw new RuntimeException( 'Could not start a concurrency worker.' );
			}
			if ( 0 === $pid ) {
				$reconnect();
				file_put_contents( $directory . '/ready-' . $worker, 'ready' );
				$deadline = microtime( true ) + 5;
				while ( ! is_file( $directory . '/go' ) && microtime( true ) < $deadline ) {
					usleep( 1000 );
				}
				if ( ! is_file( $directory . '/go' ) ) {
					exit( 2 );
				}
				$result = ( new Runner( new Repository() ) )->run_manual( $id, 1 );
				file_put_contents( $directory . '/result-' . $worker, wp_json_encode( array( 'accepted' => true === $result, 'error' => is_wp_error( $result ) ? $result->get_error_code() : '' ) ) );
				exit( 0 );
			}
			$children[] = $pid;
		}
		$deadline = microtime( true ) + 5;
		while ( ( ! is_file( $directory . '/ready-0' ) || ! is_file( $directory . '/ready-1' ) ) && microtime( true ) < $deadline ) {
			usleep( 1000 );
		}
		file_put_contents( $directory . '/go', 'go' );
		foreach ( $children as $pid ) {
			pcntl_waitpid( $pid, $status );
			if ( ! pcntl_wifexited( $status ) || 0 !== pcntl_wexitstatus( $status ) ) {
				throw new RuntimeException( 'A concurrency worker did not complete normally.' );
			}
		}
	} finally {
		$reconnect();
		$wpdb = $GLOBALS['wpdb'];
	}
	$results = array( json_decode( file_get_contents( $directory . '/result-0' ), true ), json_decode( file_get_contents( $directory . '/result-1' ), true ) );
	wp_cache_delete( 'wpsstudio_concurrent_counter', 'options' );
	$row = $repository->get( $id );
	if ( 1 !== count( array_filter( $results, static fn( $result ) => $result['accepted'] ) ) || 1 !== (int) get_option( 'wpsstudio_concurrent_counter' ) || ! $repository->verified( $row ) || 'inactive' !== $row['status'] || ! $row['last_run_at'] ) {
		throw new RuntimeException( 'Concurrent attempts did not produce exactly one accepted execution with a valid receipt.' );
	}
	WP_CLI::success( 'Concurrent manual-run check passed: two independent workers, one accepted execution, one blocked attempt, one stored effect.' );
} finally {
	if ( is_int( $id ) && $id > 0 ) {
		$row = $repository->get( $id );
		if ( $row ) {
			$repository->delete( $id, (int) $row['revision'] );
		}
	}
	delete_option( 'wpsstudio_concurrent_counter' );
	update_option( 'wpsstudio_safe_mode', $safe, false );
	foreach ( glob( $directory . '/*' ) as $file ) {
		unlink( $file );
	}
	rmdir( $directory );
}
