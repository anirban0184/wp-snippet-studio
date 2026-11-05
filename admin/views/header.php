<?php
/** Protected content-management header. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wpsstudio">
	<div class="wpsstudio-heading">
		<h1><span class="dashicons dashicons-editor-code" aria-hidden="true"></span> Coder Snippet Studio <span class="wpsstudio-version"><?php echo esc_html( WPSSTUDIO_VERSION ); ?></span></h1>
		<form method="post" action="<?php echo esc_url( $this->url() ); ?>">
			<?php $this->fields( Renderer::paused() ? 'resume' : 'pause' ); ?>
			<button class="button"
			<?php
			if ( Renderer::paused() ) :
				?>
				data-confirm="<?php esc_attr_e( 'Resume output from all active content snippets?', 'coder-snippet-studio' ); ?>"<?php endif; ?>><?php echo Renderer::paused() ? esc_html__( 'Resume output', 'coder-snippet-studio' ) : esc_html__( 'Pause all output', 'coder-snippet-studio' ); ?></button>
		</form>
	</div>
	<p class="wpsstudio-subtitle"><?php esc_html_e( 'Reusable HTML and plain text. Save inactive, review, then activate where needed.', 'coder-snippet-studio' ); ?></p>
	<?php if ( Renderer::paused() ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'All snippet output is paused. Active settings are preserved and snippets remain editable.', 'coder-snippet-studio' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $wpsstudio_legacy ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Records from an earlier development build are preserved in the database. Unsupported types are unavailable in this content-only edition. Existing HTML/text content requires review before activation.', 'coder-snippet-studio' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $this->error ) : ?>
		<div class="notice notice-error inline" role="alert"><p><?php echo esc_html( $this->error->get_error_message() ); ?></p></div>
	<?php endif; ?>
	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fixed read-only notice allow-list.
	$wpsstudio_notice   = isset( $_GET['notice'] ) && is_string( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
	$wpsstudio_messages = array(
		'saved'      => __( 'Snippet saved inactive. Review the filtered content, then activate it from All Snippets.', 'coder-snippet-studio' ),
		'activate'   => __( 'Snippet activated.', 'coder-snippet-studio' ),
		'deactivate' => __( 'Snippet deactivated.', 'coder-snippet-studio' ),
		'delete'     => __( 'Snippet deleted.', 'coder-snippet-studio' ),
		'pause'      => __( 'All snippet output paused.', 'coder-snippet-studio' ),
		'resume'     => __( 'Snippet output resumed.', 'coder-snippet-studio' ),
	);
	if ( isset( $wpsstudio_messages[ $wpsstudio_notice ] ) ) :
		?>
		<div class="notice notice-success inline" role="status"><p><?php echo esc_html( $wpsstudio_messages[ $wpsstudio_notice ] ); ?></p></div>
	<?php endif; ?>
