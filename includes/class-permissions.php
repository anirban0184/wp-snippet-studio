<?php
/** Trusted code author permissions. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Permissions {
	public static function can_manage(): bool {
		return ! is_multisite()
			&& current_user_can( 'manage_options' )
			&& current_user_can( 'edit_plugins' )
			&& wp_is_file_mod_allowed( 'wpsstudio_manage_snippets' )
			&& ! ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT );
	}
}
