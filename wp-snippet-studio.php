<?php
/**
 * Plugin Name: WP Snippet Studio
 * Description: Reuse reviewed HTML and plain text with inactive-first saves, individual controls and shortcodes.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author: Glowing Coders
 * Author URI: https://glowingcoders.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-snippet-studio
 *
 * @package WPSnippetStudio
 */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

define( 'WPSSTUDIO_VERSION', '1.0.0' );
define( 'WPSSTUDIO_FILE', __FILE__ );
define( 'WPSSTUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSSTUDIO_URL', plugin_dir_url( __FILE__ ) );

require_once WPSSTUDIO_PATH . 'includes/class-permissions.php';
require_once WPSSTUDIO_PATH . 'includes/class-installer.php';
require_once WPSSTUDIO_PATH . 'includes/class-validator.php';
require_once WPSSTUDIO_PATH . 'includes/class-repository.php';
require_once WPSSTUDIO_PATH . 'includes/class-renderer.php';
require_once WPSSTUDIO_PATH . 'admin/class-admin.php';

register_activation_hook( __FILE__, array( Installer::class, 'activate' ) );

if ( is_multisite() ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WP Snippet Studio supports single-site WordPress. Content output is disabled on multisite.', 'wp-snippet-studio' ) . '</p></div>';
		}
	);
	return;
}

$wpsstudio_repository = new Repository();
$wpsstudio_renderer   = new Renderer( $wpsstudio_repository );
$wpsstudio_admin      = new Admin( $wpsstudio_repository );
$wpsstudio_renderer->register();
$wpsstudio_admin->register();
unset( $wpsstudio_repository, $wpsstudio_renderer, $wpsstudio_admin );
