<?php
/** Protected content-management header. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wpsstudio">
	<div class="wpsstudio-heading">
		<h1><span class="dashicons dashicons-editor-code" aria-hidden="true"></span> WP Snippet Studio <span class="wpsstudio-version"><?php echo esc_html( WPSSTUDIO_VERSION ); ?></span></h1>
		<form method="post" action="<?php echo esc_url( $this->url() ); ?>">
			<?php $this->fields( Renderer::paused() ? 'resume' : 'pause' ); ?>
			<button class="button"
			<?php
			if ( Renderer::paused() ) :
				?>
				data-confirm="<?php esc_attr_e( 'Resume output from all active content snippets?', 'wp-snippet-studio' ); ?>"<?php endif; ?>><?php echo Renderer::paused() ? esc_html__( 'Resume output', 'wp-snippet-studio' ) : esc_html__( 'Pause all output', 'wp-snippet-studio' ); ?></button>
		</form>
	</div>
	<p class="wpsstudio-subtitle"><?php esc_html_e( 'Reusable HTML and plain text. Save inactive, review, then activate where needed.', 'wp-snippet-studio' ); ?></p>
	<?php if ( Renderer::paused() ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'All snippet output is paused. Active settings are preserved and snippets remain editable.', 'wp-snippet-studio' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $legacy ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Records from an earlier development build are preserved in the database. Unsupported types are unavailable in this content-only edition. Existing HTML/text content requires review before activation.', 'wp-snippet-studio' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $this->error ) : ?>
		<div class="notice notice-error inline" role="alert"><p><?php echo esc_html( $this->error->get_error_message() ); ?></p></div>
	<?php endif; ?>
	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fixed read-only notice allow-list.
	$notice   = isset( $_GET['notice'] ) && is_string( $_GET['notice'] ) ? $_GET['notice'] : '';
	$messages = array(
		'saved'      => __( 'Snippet saved inactive. Review the filtered content, then activate it from All Snippets.', 'wp-snippet-studio' ),
		'activate'   => __( 'Snippet activated.', 'wp-snippet-studio' ),
		'deactivate' => __( 'Snippet deactivated.', 'wp-snippet-studio' ),
		'delete'     => __( 'Snippet deleted.', 'wp-snippet-studio' ),
		'pause'      => __( 'All snippet output paused.', 'wp-snippet-studio' ),
		'resume'     => __( 'Snippet output resumed.', 'wp-snippet-studio' ),
	);
	if ( isset( $messages[ $notice ] ) ) :
		?>
		<div class="notice notice-success inline" role="status"><p><?php echo esc_html( $messages[ $notice ] ); ?></p></div>
	<?php endif; ?>
