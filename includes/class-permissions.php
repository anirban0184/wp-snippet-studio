<?php
/** Administrator-only content management. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Permissions {
	public static function can_manage(): bool {
		return ! is_multisite() && current_user_can( 'manage_options' );
	}
}
