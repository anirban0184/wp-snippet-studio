<?php
/** Shared protected page header. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wpsstudio">
	<div class="wpsstudio-heading">
		<h1><span class="dashicons dashicons-editor-code" aria-hidden="true"></span> WP Snippet Studio <span class="wpsstudio-version"><?php echo esc_html( WPSSTUDIO_VERSION ); ?></span></h1>
		<form method="post" action="<?php echo esc_url( $this->url() ); ?>">
			<?php $this->fields( Runner::safe_mode() ? 'safe_off' : 'safe_on' ); ?>
			<button class="button"
			<?php
			if ( Runner::safe_mode() ) :
				?>
				data-confirm="<?php esc_attr_e( 'Resume all active snippets? Review problematic snippets first.', 'wp-snippet-studio' ); ?>"<?php endif; ?>><?php echo Runner::safe_mode() ? esc_html__( 'Turn Safe Mode off', 'wp-snippet-studio' ) : esc_html__( 'Enable Safe Mode', 'wp-snippet-studio' ); ?></button>
		</form>
	</div>
	<?php if ( Runner::safe_mode() ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'Safe Mode is on. All snippet execution and shortcode output are paused. You can still review and edit snippets.', 'wp-snippet-studio' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $this->error ) : ?>
		<div class="notice notice-error inline" role="alert"><p><?php echo esc_html( $this->error->get_error_message() ); ?></p></div>
	<?php endif; ?>
	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fixed notice allow-list, never source or arbitrary messages.
	$notice   = isset( $_GET['notice'] ) && is_string( $_GET['notice'] ) ? $_GET['notice'] : '';
	$messages = array(
		'saved'      => __( 'Snippet saved inactive. Review it, then activate it from All Snippets.', 'wp-snippet-studio' ),
		'activate'   => __( 'Snippet activated.', 'wp-snippet-studio' ),
		'deactivate' => __( 'Snippet deactivated.', 'wp-snippet-studio' ),
		'priority'   => __( 'Priority updated.', 'wp-snippet-studio' ),
		'delete'     => __( 'Snippet deleted.', 'wp-snippet-studio' ),
		'run'        => __( 'Manual attempt completed. The snippet remains inactive.', 'wp-snippet-studio' ),
		'safe_on'    => __( 'Safe Mode enabled.', 'wp-snippet-studio' ),
		'safe_off'   => __( 'Safe Mode disabled.', 'wp-snippet-studio' ),
	);
	if ( isset( $messages[ $notice ] ) ) :
		?>
		<div class="notice notice-success inline" role="status"><p><?php echo esc_html( $messages[ $notice ] ); ?></p></div>
	<?php endif; ?>
