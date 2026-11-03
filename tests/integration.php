<?php
/** Real-WordPress security, rendering, concurrency and schema checks. Disposable sites only. */

use GlowingCoders\WPSnippetStudio\{Installer, Permissions, Renderer, Repository, Validator};

require __DIR__ . '/bootstrap.php';
global $wpdb;
$repo = new Repository();
$renderer = new Renderer( $repo );
$admin = get_current_user_id();
$created = array();
$checks = 0;
$table = $wpdb->prefix . 'wpsstudio_snippets';
$check = static function ( $condition, $label ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( $label ); }
	++$checks;
};
$input = static fn( $type, $code ) => array( 'title' => 'Content QA — বাংলা "quotes"', 'description' => "A description\nsecond line", 'type' => $type, 'code' => $code );
$save = static function ( $data ) use ( $repo, &$created ) {
	$id = $repo->save( $data );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$created[] = $id;
	return $id;
};
$activate = static function ( $id ) use ( $repo ) {
	$row = $repo->get( $id );
	if ( true !== $repo->status( $id, (int) $row['revision'], true ) ) { throw new RuntimeException( 'Activation failed.' ); }
};
try {
	$check( Installer::ready() && Permissions::can_manage(), 'Authorized installed content edition' );
	$check( '1.0.0' === WPSSTUDIO_VERSION && ! class_exists( 'GlowingCoders\\WPSnippetStudio\\Runner' ), 'Only content renderer loaded' );
	$check( ! is_file( WPSSTUDIO_PATH . 'includes/class-runner.php' ), 'Executable runner absent from installed package' );
	$check( array( 'html', 'text' ) === Validator::TYPES, 'Only content types accepted' );
	foreach ( array( 'php', 'js', 'css', 'PHP', 'unknown' ) as $type ) {
		$check( is_wp_error( $repo->save( $input( $type, 'content' ) ) ), 'Unsupported type rejected: ' . $type );
	}
	foreach ( array( 'title', 'description', 'code', 'type' ) as $field ) {
		$bad = $input( 'text', 'content' ); $bad[ $field ] = array( 'forged' );
		$check( is_wp_error( $repo->save( $bad ) ), 'Array field rejected: ' . $field );
	}
	$check( is_wp_error( $repo->save( $input( 'text', '' ) ) ), 'Empty content rejected' );
	$check( is_wp_error( $repo->save( $input( 'text', str_repeat( 'a', 1048577 ) ) ) ), 'Content size bounded' );
	$bad = $input( 'text', 'content' ); $bad['title'] = str_repeat( 'x', 151 );
	$check( is_wp_error( $repo->save( $bad ) ), 'Long title rejected' );
	foreach ( array( '', '0', '-1', '1foo', '1.5', '1e3', '+1', '01', array( 1 ), '99999999999999999999999999999' ) as $id ) {
		$check( 0 === Validator::id( $id ), 'Invalid ID rejected' );
	}
	$html = '<p id="content-qa" class="cta" onclick="alert(1)" style="color:red">Hello <strong>বাংলা</strong> <a href="javascript:alert(1)">bad</a><a href="https://example.com/">safe</a><img src="https://example.com/a.png" onerror="alert(1)" alt="test"></p><script>privateMarker()</script><iframe src="https://example.com/"></iframe><svg onload="alert(1)"></svg>';
	$id = $save( $input( 'html', $html ) );
	$row = $repo->get( $id );
	$check( 'inactive' === $row['status'] && $repo->verified( $row ), 'New HTML saved inactive with approval' );
	$check( '' === do_shortcode( '[wpsstudio id="' . $id . '"]' ), 'Inactive HTML is empty' );
	foreach ( array( '<script', '<iframe', '<svg', 'onclick=', 'onerror=', 'style=', 'javascript:' ) as $unsafe ) {
		$check( ! str_contains( strtolower( $row['code'] ), $unsafe ), 'Unsafe HTML removed: ' . $unsafe );
	}
	$check( str_contains( $row['code'], '<strong>বাংলা</strong>' ) && str_contains( $row['code'], 'https://example.com/' ), 'Useful safe HTML preserved' );
	$activate( $id );
	$check( str_contains( do_shortcode( '[wpsstudio id="' . $id . '"]' ), '<strong>বাংলা</strong>' ), 'Active HTML renders' );
	$row = $repo->get( $id );
	$check( $id === $repo->save( $input( 'html', '<p>Edited</p>' ), $id, (int) $row['revision'] ), 'Active edit saved' );
	$check( '' === do_shortcode( '[wpsstudio id="' . $id . '"]' ), 'Editing deactivates existing output' );
	$check( is_wp_error( $repo->save( $input( 'html', '<p>stale</p>' ), $id, (int) $row['revision'] ) ), 'Stale edit blocked' );
	$check( is_wp_error( $repo->status( $id, (int) $row['revision'], true ) ), 'Stale activation blocked' );
	$check( is_wp_error( $repo->delete( $id, (int) $row['revision'] ) ), 'Stale deletion blocked' );
	$text = "<strong>literal & text</strong>\nবাংলা [wpsstudio_fixture] <?php update_option('wpsstudio_executed', 'bad'); ?>";
	$GLOBALS['wpsstudio_nested_calls'] = 0;
	add_shortcode( 'wpsstudio_fixture', static function () { ++$GLOBALS['wpsstudio_nested_calls']; return 'executed'; } );
	$text_id = $save( $input( 'text', $text ) ); $activate( $text_id );
	$output = do_shortcode( '[wpsstudio id="' . $text_id . '"]' );
	$check( str_contains( $output, '&lt;strong&gt;literal &amp; text&lt;/strong&gt;<br>' ), 'Text escaped with a line break' );
	$check( str_contains( $output, '[wpsstudio_fixture]' ) && 0 === $GLOBALS['wpsstudio_nested_calls'], 'Nested shortcodes are not evaluated' );
	$check( str_contains( $output, '&lt;?php' ) && false === get_option( 'wpsstudio_executed' ), 'PHP-looking text remains literal' );
	$check( '' === $renderer->shortcode( array( 'id' => array( $text_id ) ) ) && '' === $renderer->shortcode( 'bad' ), 'Malformed shortcode attributes fail closed' );
	$check( '' === do_shortcode( '[wpsstudio id="999999999"]' ), 'Missing shortcode is empty' );
	foreach ( array( 0, wp_insert_user( array( 'user_login' => 'content_author_' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password( 32, false ), 'role' => 'author' ) ) ) as $user_id ) {
		if ( is_wp_error( $user_id ) ) { throw new RuntimeException( 'Could not create test user.' ); }
		wp_set_current_user( $user_id );
		$check( ! Permissions::can_manage(), 'Content authors/anonymous cannot manage' );
		$check( is_wp_error( $repo->save( $input( 'text', 'forged' ) ) ), 'Unauthorized save denied' );
		$row = $repo->get( $text_id );
		$check( is_wp_error( $repo->status( $text_id, (int) $row['revision'], false ) ), 'Unauthorized status denied' );
		$check( is_wp_error( $repo->delete( $text_id, (int) $row['revision'] ) ), 'Unauthorized delete denied' );
		wp_set_current_user( $admin );
		if ( $user_id ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user_id ); }
	}
	update_option( 'wpsstudio_safe_mode', '1', false );
	$check( '' === do_shortcode( '[wpsstudio id="' . $text_id . '"]' ) && 'active' === $repo->get( $text_id )['status'], 'Pause preserves active state and hides content' );
	update_option( 'wpsstudio_safe_mode', '0', false );
	$check( $output === do_shortcode( '[wpsstudio id="' . $text_id . '"]' ), 'Resume restores exact text output' );
	$wpdb->update( $table, array( 'code' => '<script>forged()</script>' ), array( 'id' => $text_id ) );
	$check( '' === do_shortcode( '[wpsstudio id="' . $text_id . '"]' ), 'Tampered content fails closed' );
	$row = $repo->get( $text_id );
	$check( is_wp_error( $repo->status( $text_id, (int) $row['revision'], true ) ), 'Tampered content cannot activate' );
	$check( true === $repo->status( $text_id, (int) $row['revision'], false ), 'Tampered record can be disabled' );
	$check( ! $repo->verified( $repo->get( $text_id ) ), 'Deactivation does not approve tampered source' );
	$row = $repo->get( $text_id );
	$repo->save( $input( 'text', $text ), $text_id, (int) $row['revision'] ); $activate( $text_id );
	$check( $output === do_shortcode( '[wpsstudio id="' . $text_id . '"]' ), 'Explicit review restores content' );
	for ( $i = 0; $i < 27; ++$i ) { $save( $input( 'text', 'Pagination ' . $i ) ); }
	$first = $repo->page( 'text', 1 ); $second = $repo->page( 'text', 2 );
	$check( 25 === count( $first ) && count( $second ) > 0, 'Content list paginates at 25' );
	$check( ! array_intersect( array_column( $first, 'id' ), array_column( $second, 'id' ) ), 'Pagination does not repeat IDs' );
	$check( ! isset( $first[0]['code'] ) && ! isset( $first[0]['description'] ), 'List query omits content bodies' );
	update_option( 'wpsstudio_schema_version', '99', false );
	$check( '' === do_shortcode( '[wpsstudio id="' . $text_id . '"]' ) && ! Installer::install(), 'Unknown future schema is not downgraded' );
	update_option( 'wpsstudio_schema_version', Installer::SCHEMA_VERSION, false );
	$backup = $table . '_content_qa_missing';
	$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $table, $backup ) );
	try {
		$wpdb->show_errors(); ob_start();
		$missing = $renderer->shortcode( array( 'id' => (string) $text_id ) );
		$failed = $repo->save( $input( 'text', 'Private database failure marker' ) );
		$visible = ob_get_clean();
		$check( '' === $missing && '' === $visible && is_wp_error( $failed ), 'Database loss has no public content or SQL diagnostics' );
	} finally {
		$wpdb->hide_errors();
		$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $backup, $table ) );
	}
	$row = $repo->get( $id );
	$check( true === $repo->delete( $id, (int) $row['revision'] ) && null === $repo->get( $id ), 'Valid permanent deletion works in disposable fixture' );
	WP_CLI::success( $checks . ' content integration checks passed.' );
} finally {
	wp_set_current_user( $admin );
	update_option( 'wpsstudio_schema_version', Installer::SCHEMA_VERSION, false );
	update_option( 'wpsstudio_safe_mode', '0', false );
	remove_shortcode( 'wpsstudio_fixture' );
	foreach ( $created as $id ) { $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); }
}
