<?php
/** Preserve every official finding and fail CI on unreviewed findings. */

$root = dirname( __DIR__ );
$raw = file_get_contents( $root . '/build/content/plugin-check.raw.txt' );
$issues = array();
$file = null;
$recognized = false;
foreach ( preg_split( '/\\R/', $raw ) as $line ) {
	$line = trim( $line );
	if ( '' === $line ) {
		continue;
	}
	if ( str_starts_with( $line, 'FILE: ' ) ) {
		$file = substr( $line, 6 );
		continue;
	}
	if ( str_starts_with( $line, '[' ) ) {
		$records = json_decode( $line, true, 512, JSON_THROW_ON_ERROR );
		if ( ! is_array( $records ) || ! $file ) {
			throw new RuntimeException( 'Unexpected Plugin Check report structure.' );
		}
		$recognized = true;
		foreach ( $records as $issue ) {
			if ( ! is_array( $issue ) || ! isset( $issue['type'], $issue['code'], $issue['message'] ) ) {
				throw new RuntimeException( 'Invalid Plugin Check finding.' );
			}
			$issues[] = array_merge( array( 'file' => $file ), $issue );
		}
		continue;
	}
	if ( 'Success: No errors found.' === $line ) {
		$recognized = true;
		continue;
	}
	throw new RuntimeException( 'Unrecognized Plugin Check output; inspect the raw report.' );
}
if ( ! $recognized ) {
	throw new RuntimeException( 'Plugin Check did not produce a recognized report.' );
}
$unreviewed = $issues;
$report = array(
	'errors' => count( array_filter( $issues, static fn( array $issue ): bool => 'ERROR' === $issue['type'] ) ),
	'warnings' => count( array_filter( $issues, static fn( array $issue ): bool => 'WARNING' === $issue['type'] ) ),
	'unreviewed_findings' => count( $unreviewed ),
	'review_required' => 'All findings must be resolved before submission.',
	'issues' => $issues,
);
file_put_contents( $root . '/build/content/plugin-check.json', json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL );
echo count( $issues ) . ' official findings retained; ' . count( $unreviewed ) . ' unreviewed findings. See plugin-check.json.' . PHP_EOL;
if ( $unreviewed ) {
	exit( 1 );
}
