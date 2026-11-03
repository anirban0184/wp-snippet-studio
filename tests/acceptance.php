<?php
/** Additional real-WordPress acceptance cases. Disposable empty test sites only. @package WPSnippetStudio */

use GlowingCoders\WPSnippetStudio\Installer;
use GlowingCoders\WPSnippetStudio\Repository;
use GlowingCoders\WPSnippetStudio\Runner;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Acceptance checks require an explicitly authorized disposable site.' );
}
$repository = new Repository();
if ( 0 !== $repository->counts()['all'] ) {
	WP_CLI::error( 'Use an empty disposable snippet table for acceptance checks.' );
}
$ids = array();
$checks = 0;
$schema = get_option( 'wpsstudio_schema_version' );
$safe = get_option( 'wpsstudio_safe_mode', '0' );
$assert = static function ( bool $condition, string $label ) use ( &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
};
$make = static function ( array $input ) use ( $repository, &$ids ): int {
	$id = $repository->save( $input );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	$ids[] = $id;
	return $id;
};

try {
	update_option( 'wpsstudio_safe_mode', '0', false );
	$sources = array(
		'php' => '$GLOBALS["wpsstudio_acceptance_marker"] = true;',
		'css' => '.acceptance::before { content: "Client\\27s Café 🧪"; }',
		'js' => 'window.acceptanceMessage = "Client\'s Café 🧪";',
		'html' => '<p title="Client\'s offer">Café 🧪 <strong>Approved</strong></p>',
		'text' => "Café 🧪\nClient's reusable <b>text</b>",
	);
	$records = array();
	foreach ( $sources as $type => $source ) {
		$input = array( 'title' => 'Acceptance ' . $type . ' — বাংলা 🧪', 'description' => 'Client\'s "quoted" description', 'code' => $source, 'type' => $type, 'scope' => 'everywhere', 'priority' => 10 );
		$id = $make( $input );
		$records[ $type ] = $id;
		$row = $repository->get( $id );
		$stored_source = $row['code'];
		$round_trip = 'html' === $type ? html_entity_decode( $stored_source, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $stored_source;
		$assert( $source === $round_trip && $input['title'] === $row['title'] && $input['description'] === $row['description'], 'Unicode, apostrophes and source round-trip for ' . $type );
		$assert( true === $repository->status( $id, (int) $row['revision'], true ), 'Activate ' . $type );
		$row = $repository->get( $id );
		$input['description'] = 'Reviewed again — বাংলা';
		$assert( $id === $repository->save( $input, $id, (int) $row['revision'] ), 'Edit existing ' . $type );
		$row = $repository->get( $id );
		$assert( 'inactive' === $row['status'] && $stored_source === $row['code'] && $repository->verified( $row ), 'Edit deactivates and preserves signed source for ' . $type );
	}

	$manual_input = array( 'title' => 'Reviewed manual maintenance', 'description' => '', 'code' => 'update_option("wpsstudio_acceptance_counter", (int) get_option("wpsstudio_acceptance_counter", 0) + 1, false);', 'type' => 'php', 'scope' => 'manual', 'priority' => 10 );
	$manual = $make( $manual_input );
	$runner = new Runner( $repository );
	$assert( true === $runner->run_manual( $manual, 1 ) && 1 === (int) get_option( 'wpsstudio_acceptance_counter' ), 'First manual source executes once' );
	$row = $repository->get( $manual );
	$manual_input['description'] = 'Metadata changed only';
	$repository->save( $manual_input, $manual, (int) $row['revision'] );
	$row = $repository->get( $manual );
	$assert( is_wp_error( $runner->run_manual( $manual, (int) $row['revision'] ) ) && 1 === (int) get_option( 'wpsstudio_acceptance_counter' ), 'A metadata edit cannot reset a manual receipt' );
	$manual_input['code'] .= "\n// Reviewed replacement source.";
	$repository->save( $manual_input, $manual, (int) $row['revision'] );
	$row = $repository->get( $manual );
	$assert( true === $runner->run_manual( $manual, (int) $row['revision'] ) && 2 === (int) get_option( 'wpsstudio_acceptance_counter' ), 'Changed, reviewed source permits one new manual attempt' );

	$wpdb = $GLOBALS['wpdb'];
	$table = $wpdb->prefix . 'wpsstudio_snippets';
	$php = $repository->get( $records['php'] );
	$wpdb->update( $table, array( 'status' => 'active' ), array( 'id' => $records['php'] ) );
	unset( $GLOBALS['wpsstudio_acceptance_marker'] );
	( new Runner( $repository ) )->automatic_php();
	$assert( ! isset( $GLOBALS['wpsstudio_acceptance_marker'] ) && 'inactive' === $repository->get( $records['php'] )['status'], 'A database-only active flag cannot approve PHP execution' );
	$php = $repository->get( $records['php'] );
	$input = array_merge( $php, array( 'scope' => 'everywhere' ) );
	$repository->save( $input, (int) $php['id'], (int) $php['revision'] );
	$php = $repository->get( $records['php'] );
	$repository->status( (int) $php['id'], (int) $php['revision'], true );
	( new Runner( $repository ) )->automatic_php();
	$assert( true === ( $GLOBALS['wpsstudio_acceptance_marker'] ?? null ), 'Explicit review/save and activation restore approved PHP execution' );

	$expected = array( (string) $records['text'] );
	for ( $index = 1; $index <= 53; ++$index ) {
		$expected[] = (string) $make( array( 'title' => sprintf( 'Pagination %03d', $index ), 'description' => '', 'code' => 'Reusable content ' . $index, 'type' => 'text', 'scope' => 'everywhere', 'priority' => 10 ) );
	}
	$pages = array( $repository->page( 'text', 1 ), $repository->page( 'text', 2 ), $repository->page( 'text', 3 ) );
	$assert( array( 25, 25, 4 ) === array_map( 'count', $pages ), '54 text snippets paginate without an artificial count cap' );
	$actual = array_map( 'strval', array_column( array_merge( ...$pages ), 'id' ) );
	$assert( $expected === $actual && 54 === count( array_unique( $actual ) ), 'Pagination preserves all IDs in deterministic order without duplication' );
	$assert( 54 === $repository->counts()['text'] && 59 === $repository->counts()['all'], 'Type and total counts include every persisted record' );

	update_option( 'wpsstudio_schema_version', 'acceptance-pending', false );
	unset( $GLOBALS['wpsstudio_acceptance_marker'] );
	( new Runner( $repository ) )->automatic_php();
	$assert( ! isset( $GLOBALS['wpsstudio_acceptance_marker'] ), 'Pending schema migration pauses executable PHP' );
	update_option( 'wpsstudio_schema_version', $schema, false );
	$assert( Installer::install() && 59 === $repository->counts()['all'], 'Migration leaves all acceptance records intact' );
	WP_CLI::success( $checks . ' additional acceptance checks passed.' );
} finally {
	update_option( 'wpsstudio_schema_version', $schema, false );
	foreach ( $ids as $id ) {
		$row = $repository->get( $id );
		if ( $row ) {
			$repository->delete( $id, (int) $row['revision'] );
		}
	}
	delete_option( 'wpsstudio_acceptance_counter' );
	update_option( 'wpsstudio_safe_mode', $safe, false );
}
