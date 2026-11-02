<?php
/**
 * Plugin Name: WP Snippet Studio
 * Description: Manage trusted PHP, CSS, JavaScript, HTML and reusable text snippets independently of your theme.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author: Glowing Coders
 * Author URI: https://glowingcoders.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-snippet-studio
 * Update URI: https://glowingcoders.com/wp-snippet-studio/
 *
 * @package WPSnippetStudio
 */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

define( 'WPSSTUDIO_VERSION', '0.1.0' );
define( 'WPSSTUDIO_FILE', __FILE__ );
define( 'WPSSTUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSSTUDIO_URL', plugin_dir_url( __FILE__ ) );

require_once WPSSTUDIO_PATH . 'includes/class-permissions.php';
require_once WPSSTUDIO_PATH . 'includes/class-installer.php';
require_once WPSSTUDIO_PATH . 'includes/class-validator.php';
require_once WPSSTUDIO_PATH . 'includes/class-repository.php';
require_once WPSSTUDIO_PATH . 'includes/class-runner.php';
require_once WPSSTUDIO_PATH . 'admin/class-admin.php';

register_activation_hook( __FILE__, array( Installer::class, 'activate' ) );

// Multisite needs its own permission, migration and recovery model. Defer it.
if ( is_multisite() ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WP Snippet Studio 0.1.0 supports single-site WordPress only. Snippet execution is disabled on multisite.', 'wp-snippet-studio' ) . '</p></div>';
		}
	);
	return;
}

$wpsstudio_repository = new Repository();
$wpsstudio_runner     = new Runner( $wpsstudio_repository );
$wpsstudio_admin      = new Admin( $wpsstudio_repository, $wpsstudio_runner );
$wpsstudio_runner->register();
$wpsstudio_admin->register();
unset( $wpsstudio_repository, $wpsstudio_runner, $wpsstudio_admin );
