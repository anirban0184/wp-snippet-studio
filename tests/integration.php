<?php
/**
 * Real WordPress/MariaDB integration checks. Never run against a production DB.
 * WPSSTUDIO_TESTS=1 wp eval-file tests/integration.php --user=<administrator>
 *
 * @package WPSnippetStudio
 */

use GlowingCoders\WPSnippetStudio\Installer;
use GlowingCoders\WPSnippetStudio\Permissions;
use GlowingCoders\WPSnippetStudio\Repository;
use GlowingCoders\WPSnippetStudio\Runner;
use GlowingCoders\WPSnippetStudio\Validator;

if ( '1' !== getenv( 'WPSSTUDIO_TESTS' ) || ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	WP_CLI::error( 'Integration checks require WPSSTUDIO_TESTS=1 and a disposable local/development WordPress site.' );
}

$repository = new Repository();
$ids        = array();
$users      = array();
$checks     = 0;
$admin_id   = get_current_user_id();
$safe       = get_option( 'wpsstudio_safe_mode', '0' );
$table      = $GLOBALS['wpdb']->prefix . 'wpsstudio_snippets';

$assert = static function ( bool $condition, string $label ) use ( &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
};
$make = static function ( array $overrides = array() ) use ( $repository, &$ids ): int {
	$id = $repository->save( array_merge( array( 'title' => 'Integration test', 'description' => '', 'code' => '$GLOBALS["wpsstudio_test_value"] = 1;', 'type' => 'php', 'scope' => 'everywhere', 'priority' => '10' ), $overrides ) );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	$ids[] = $id;
	return $id;
};
$activate = static function ( int $id ) use ( $repository, $assert ): void {
	$row = $repository->get( $id );
	$assert( true === $repository->status( $id, (int) $row['revision'], true ), 'Activation succeeds after review' );
};

try {
	$assert( Permissions::can_manage(), 'Administrator has code management capabilities' );
	$assert( Installer::ready(), 'Schema installed on activation' );
	foreach ( array( 0, '0', -1, '1 OR 1=1', '2.1', array(), '1e2', '9223372036854775808', '999999999999999999999999999999' ) as $invalid ) {
		$assert( 0 === Validator::id( $invalid ), 'Reject malformed identifier' );
	}
	foreach ( array( '-9999', '0', '9999', 10 ) as $valid ) {
		$assert( ! is_wp_error( Validator::priority( $valid ) ), 'Accept integer priority' );
	}
	foreach ( array( '10000', '-10000', '1.2', '1e2', '1 OR 1=1', array() ) as $invalid ) {
		$assert( is_wp_error( Validator::priority( $invalid ) ), 'Reject malformed priority' );
	}
	foreach ( array( 'if (', '<?php echo 1;', 'echo 1; ?>hello', 'namespace Example;', '__halt_compiler();' ) as $code ) {
		$assert( is_wp_error( Validator::php( $code ) ), 'Reject invalid or unsupported PHP' );
	}
	$assert( true === Validator::php( 'add_action("init", static function () {});' ), 'Accept a normal hooked PHP snippet' );
	$assert( true === Validator::php( '$literal = "<?php is a string";' ), 'Do not confuse literal strings with PHP tags' );
	foreach ( array( 'js' => array( '<script>alert(1)</script>', 'var x = "</SCRIPT >";', '</script\n>' ), 'css' => array( '</STYLE><script>alert(1)</script>', '<style>body{}</style>' ) ) as $type => $payloads ) {
		foreach ( $payloads as $code ) {
			$assert( is_wp_error( $repository->save( array( 'title' => 'Blocked', 'description' => '', 'code' => $code, 'type' => $type, 'scope' => 'frontend', 'priority' => 10 ) ) ), 'Reject inline asset tag breakout' );
		}
	}

	$id  = $make( array( 'title' => 'Client\'s "quoted" code <script>unsafe</script>' ) );
	$row = $repository->get( $id );
	$assert( 'inactive' === $row['status'], 'New snippets are inactive' );
	$assert( $repository->verified( $row ), 'New record has a valid installation signature' );
	$assert( ! str_contains( $row['title'], '<script>' ), 'Metadata markup removed' );
	$assert( Installer::install(), 'Schema migration can run twice' );
	$assert( $row === $repository->get( $id ), 'Migration preserves the entire snippet' );
	$activate( $id );
	$row = $repository->get( $id );
	$assert( true === $repository->priority( $id, (int) $row['revision'], -25 ), 'Inline priority update' );
	$assert( 'active' === $repository->get( $id )['status'], 'Changing priority preserves active status' );
	$assert( is_wp_error( $repository->priority( $id, (int) $row['revision'], 20 ) ), 'Stale update rejected' );
	$row = $repository->get( $id );
	$input = $row;
	$input['code'] = '$GLOBALS["wpsstudio_test_value"] = 2;';
	$assert( $id === $repository->save( $input, $id, (int) $row['revision'] ), 'Save existing snippet' );
	$assert( 'inactive' === $repository->get( $id )['status'], 'Edits deactivate an active snippet' );
	$assert( is_wp_error( $repository->delete( $id, (int) $row['revision'] ) ), 'Stale delete rejected' );

	$html = $make( array( 'type' => 'html', 'code' => '<p class="offer" onclick="alert(1)">Hello <strong>client</strong></p><script>alert(1)</script><a href="javascript:alert(1)">Bad</a>' ) );
	$text = $make( array( 'type' => 'text', 'code' => "<script>window.bad = true;</script>\n[another_shortcode]" ) );
	$runner = new Runner( $repository );
	$assert( '' === $runner->shortcode( array( 'id' => $html ) ), 'Inactive content is private' );
	$activate( $html );
	$activate( $text );
	$rendered = $runner->shortcode( array( 'id' => $html ) );
	$assert( str_contains( $rendered, '<strong>client</strong>' ), 'HTML keeps permitted markup' );
	$assert( ! str_contains( $rendered, 'onclick' ) && ! str_contains( $rendered, '<script' ) && ! str_contains( $rendered, 'javascript:' ), 'HTML strips executable markup' );
	$rendered = $runner->shortcode( array( 'id' => $text ) );
	$assert( str_contains( $rendered, '&lt;script&gt;' ) && str_contains( $rendered, '<br>' ) && str_contains( $rendered, '[another_shortcode]' ), 'Plain text is escaped without nested shortcode execution' );
	$activate( $id );
	$assert( '' === $runner->shortcode( array( 'id' => $id ) ), 'Public shortcode cannot execute PHP' );
	$assert( '' === $runner->shortcode( array( 'id' => '1 OR 1=1' ) ), 'Shortcode injection rejected' );
	$assert( '' === $runner->shortcode( array( 'id' => array( $html ) ) ), 'Array identifier rejected' );
	$GLOBALS['wpdb']->update( $table, array( 'code' => '<p>tampered</p>' ), array( 'id' => $html ) );
	$assert( '' === $runner->shortcode( array( 'id' => $html ) ), 'Unsigned database changes never execute' );
	$assert( 'inactive' === $repository->get( $html )['status'], 'Tampered active record quarantined' );
	$assert( is_wp_error( $repository->status( $html, (int) $repository->get( $html )['revision'], true ) ), 'Quarantined record requires explicit source review/save' );

	$manual = $make( array( 'scope' => 'manual', 'code' => '$GLOBALS["wpsstudio_manual_count"] = ($GLOBALS["wpsstudio_manual_count"] ?? 0) + 1; echo "private manual output";' ) );
	$assert( is_wp_error( $repository->status( $manual, 1, true ) ), 'Manual PHP cannot be activated automatically' );
	update_option( 'wpsstudio_safe_mode', '1', false );
	$assert( is_wp_error( $runner->run_manual( $manual, 1 ) ), 'Safe Mode blocks manual execution' );
	$assert( '' === $runner->shortcode( array( 'id' => $text ) ), 'Safe Mode blocks shortcodes' );
	update_option( 'wpsstudio_safe_mode', '0', false );
	ob_start();
	$result = $runner->run_manual( $manual, 1 );
	$output = ob_get_clean();
	$assert( true === $result && '' === $output, 'Manual execution succeeds and discards response output' );
	$assert( 1 === $GLOBALS['wpsstudio_manual_count'], 'Manual code executed once' );
	$assert( is_wp_error( $runner->run_manual( $manual, 1 ) ), 'Replay of original request rejected' );
	$assert( is_wp_error( $runner->run_manual( $manual, (int) $repository->get( $manual )['revision'] ) ), 'Fresh request cannot rerun identical attempted code' );
	$assert( 1 === $GLOBALS['wpsstudio_manual_count'], 'Repeated requests did not repeat side effects' );
	$assert( 'inactive' === $repository->get( $manual )['status'] && $repository->get( $manual )['last_run_at'], 'Manual receipt stored while inactive' );

	$claimed_id = $make( array( 'scope' => 'manual' ) );
	$assert( is_array( $repository->claim_manual( $claimed_id, 1 ) ), 'First atomic claim succeeds' );
	$assert( is_wp_error( $repository->claim_manual( $claimed_id, 1 ) ), 'Second claim with same revision fails' );

	$css = $make( array( 'type' => 'css', 'code' => '.wpsstudio-test { color: red; }' ) );
	$js  = $make( array( 'type' => 'js', 'code' => 'window.wpsstudioTest = true;' ) );
	$activate( $css );
	$activate( $js );
	$runner->assets();
	$assert( wp_style_is( 'wpsstudio-' . $css, 'enqueued' ), 'Frontend CSS enqueued' );
	$assert( wp_script_is( 'wpsstudio-' . $js, 'enqueued' ), 'Frontend JavaScript enqueued' );
	$assert( 1 === wp_scripts()->get_data( 'wpsstudio-' . $js, 'group' ), 'JavaScript is in footer group' );
	wp_dequeue_style( 'wpsstudio-' . $css );
	wp_dequeue_script( 'wpsstudio-' . $js );
	update_option( 'wpsstudio_safe_mode', '1', false );
	$runner->assets();
	$assert( ! wp_style_is( 'wpsstudio-' . $css, 'enqueued' ) && ! wp_script_is( 'wpsstudio-' . $js, 'enqueued' ), 'Safe Mode pauses frontend assets' );
	update_option( 'wpsstudio_safe_mode', '0', false );

	// Prove deterministic ordering using real execution, not only SQL output.
	foreach ( $repository->active( 'php' ) as $active_row ) {
		$repository->status( (int) $active_row['id'], (int) $active_row['revision'], false );
	}
	foreach ( array( array( 'b', 20 ), array( 'a', -10 ), array( 'c', 20 ) ) as $pair ) {
		$ordered = $make( array( 'priority' => $pair[1], 'code' => '$GLOBALS["wpsstudio_order"][] = "' . $pair[0] . '";' ) );
		$activate( $ordered );
	}
	( new Runner( $repository ) )->automatic_php();
	$assert( array( 'a', 'b', 'c' ) === $GLOBALS['wpsstudio_order'], 'Lower priority, then ID, controls PHP execution order' );

	$broken = $make( array( 'priority' => -9999, 'code' => 'echo "PRIVATE-CODE"; throw new \RuntimeException("SECRET-ERROR");' ) );
	$activate( $broken );
	ob_start();
	( new Runner( $repository ) )->automatic_php();
	$output = ob_get_clean();
	$assert( '' === $output, 'Runtime failure discards partial private output' );
	$assert( Runner::safe_mode(), 'Runtime exception enables Safe Mode' );
	$broken_row = $repository->get( $broken );
	$assert( 'inactive' === $broken_row['status'] && 'runtime' === $broken_row['last_error'], 'Failed snippet quarantined without sensitive error text' );
	update_option( 'wpsstudio_safe_mode', '0', false );

	// Login is not authorization. Check anonymous, content roles and an incomplete capability set.
	wp_set_current_user( 0 );
	$assert( ! Permissions::can_manage(), 'Anonymous access denied' );
	$assert( is_wp_error( $runner->run_manual( $manual, 2 ) ), 'Anonymous manual execution denied' );
	foreach ( array( 'subscriber', 'editor' ) as $role ) {
		$user = wp_insert_user( array( 'user_login' => 'wpsstudio_test_' . $role . '_' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password( 24 ), 'role' => $role ) );
		$users[] = $user;
		wp_set_current_user( $user );
		$assert( ! Permissions::can_manage(), 'Content role cannot manage executable code' );
		if ( 'editor' === $role ) {
			wp_get_current_user()->add_cap( 'manage_options' );
			$assert( ! Permissions::can_manage(), 'manage_options alone does not authorize executable code' );
		}
	}
	wp_set_current_user( $admin_id );
	$deny_modifications = static fn() => false;
	add_filter( 'file_mod_allowed', $deny_modifications );
	$assert( ! Permissions::can_manage(), 'File modification policy respected' );
	remove_filter( 'file_mod_allowed', $deny_modifications );
	$assert( Permissions::can_manage(), 'Trusted permissions restored' );

	// Render a hostile source string in the REAL editor; it must remain textarea text.
	$xss = $make( array( 'type' => 'text', 'code' => '</textarea><script id="wpsstudio-xss">window.pwned=true</script>', 'title' => 'SQL\' OR 1=1 --' ) );
	$_GET = array( 'page' => 'wpsstudio', 'id' => $xss );
	ob_start();
	( new GlowingCoders\WPSnippetStudio\Admin( $repository, $runner ) )->render();
	$editor_html = ob_get_clean();
	$assert( ! str_contains( $editor_html, '<script id="wpsstudio-xss">' ) && str_contains( $editor_html, '&lt;/textarea&gt;' ), 'Editor source cannot escape its textarea' );
	$assert( $repository->get( $xss )['title'] === 'SQL\' OR 1=1 --', 'Quoted metadata survives a prepared database write' );
	$_GET = array();
	$assert( ! array_key_exists( 'code', $repository->page()[0] ), 'List query does not expose source' );

	// Database failure must pause execution, not turn an array return type into a fatal.
	$wpdb = $GLOBALS['wpdb'];
	$temporary_table = $table . '_test_unavailable';
	$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $table, $temporary_table ) );
	try {
		$previous = $wpdb->suppress_errors( true );
		$assert( array() === $repository->active( 'php' ), 'Database read failure cannot execute PHP' );
		$assert( array() === $repository->page() && 0 === $repository->counts()['all'], 'Database failure gives an empty management result' );
		$wpdb->suppress_errors( $previous );
		$display = $wpdb->show_errors( true );
		ob_start();
		$failure = $repository->save( array( 'title' => 'Database failure', 'description' => '', 'type' => 'php', 'scope' => 'everywhere', 'priority' => 10, 'code' => '$private = "WPSSTUDIO_PRIVATE_DB_SOURCE";' ) );
		$output = ob_get_clean();
		$wpdb->show_errors( $display );
		$assert( is_wp_error( $failure ) && '' === $output && ! str_contains( $failure->get_error_message(), 'WPSSTUDIO_PRIVATE_DB_SOURCE' ), 'Source-bearing database error stays private' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'RENAME TABLE %i TO %i', $temporary_table, $table ) );
	}

	WP_CLI::success( $checks . ' integration checks passed on WordPress ' . get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION );
} finally {
	wp_set_current_user( $admin_id );
	foreach ( $ids as $id ) {
		$row = $repository->get( $id );
		if ( $row ) {
			$repository->delete( $id, (int) $row['revision'] );
		}
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $user ) {
		wp_delete_user( $user );
	}
	update_option( 'wpsstudio_safe_mode', $safe, false );
}
