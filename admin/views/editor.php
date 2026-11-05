<?php
/** Escaped HTML/text editor. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
foreach ( $this->defaults() as $wpsstudio_key => $wpsstudio_fallback ) {
	if ( isset( $wpsstudio_snippet[ $wpsstudio_key ] ) && ! is_scalar( $wpsstudio_snippet[ $wpsstudio_key ] ) ) {
		$wpsstudio_snippet[ $wpsstudio_key ] = $wpsstudio_fallback;
	}
}
?>
<div class="wpsstudio-toolbar"><h2><?php echo $wpsstudio_snippet['id'] ? esc_html__( 'Edit Snippet', 'coder-snippet-studio' ) : esc_html__( 'Add Snippet', 'coder-snippet-studio' ); ?></h2><a class="button" href="<?php echo esc_url( $this->url() ); ?>"><?php esc_html_e( 'All Snippets', 'coder-snippet-studio' ); ?></a></div>
<?php
if ( 'active' === $wpsstudio_snippet['status'] ) :
	?>
	<div class="notice notice-info inline"><p><?php esc_html_e( 'Saving this active snippet will deactivate it. Review and activate it again after editing.', 'coder-snippet-studio' ); ?></p></div><?php endif; ?>
<?php
if ( $wpsstudio_snippet['id'] && ! $this->draft && ! $this->repository->valid_content( $wpsstudio_snippet ) ) :
	?>
	<div class="notice notice-warning inline"><p><?php esc_html_e( 'This content needs review. Check it and save inactive before activation.', 'coder-snippet-studio' ); ?></p></div><?php endif; ?>
<form method="post" action="<?php echo esc_url( $this->url( $wpsstudio_snippet['id'] ? array( 'id' => (int) $wpsstudio_snippet['id'] ) : array( 'page' => 'wpsstudio-new' ) ) ); ?>" class="wpsstudio-editor-form" data-enhance="<?php echo $this->editor_enabled ? '1' : '0'; ?>">
	<?php $this->fields( 'save', (int) $wpsstudio_snippet['id'], (int) $wpsstudio_snippet['revision'] ); ?>
	<div class="wpsstudio-editor-main">
		<label for="wpsstudio-title"><?php esc_html_e( 'Snippet title', 'coder-snippet-studio' ); ?></label><input id="wpsstudio-title" name="title" type="text" maxlength="150" value="<?php echo esc_attr( $wpsstudio_snippet['title'] ); ?>" required>
		<label for="wpsstudio-code"><?php esc_html_e( 'Content', 'coder-snippet-studio' ); ?></label><textarea id="wpsstudio-code" name="code" rows="20" spellcheck="false" aria-describedby="wpsstudio-content-help" data-html-help="<?php esc_attr_e( 'Basic HTML only: text formatting, headings, lists, links, images and tables. Scripts, styles, forms, embeds and event handlers are removed. Review the filtered content after saving.', 'coder-snippet-studio' ); ?>" data-text-help="<?php esc_attr_e( 'Plain text is escaped and line breaks are preserved. HTML, PHP and nested shortcodes are displayed literally.', 'coder-snippet-studio' ); ?>" required><?php echo esc_textarea( $wpsstudio_snippet['code'] ); ?></textarea>
		<p id="wpsstudio-content-help" class="description"><?php esc_html_e( 'HTML is filtered; plain text is escaped. Each snippet can contain up to 1 MB. Nested shortcodes are not evaluated.', 'coder-snippet-studio' ); ?></p>
		<label for="wpsstudio-description"><?php esc_html_e( 'Description', 'coder-snippet-studio' ); ?></label><textarea id="wpsstudio-description" name="description" rows="3"><?php echo esc_textarea( $wpsstudio_snippet['description'] ); ?></textarea>
	</div>
	<aside class="wpsstudio-editor-settings" aria-label="<?php esc_attr_e( 'Snippet settings', 'coder-snippet-studio' ); ?>">
		<h3><?php esc_html_e( 'Snippet settings', 'coder-snippet-studio' ); ?></h3>
		<label for="wpsstudio-type"><?php esc_html_e( 'Type', 'coder-snippet-studio' ); ?></label><select id="wpsstudio-type" name="type"><option value="html" <?php selected( $wpsstudio_snippet['type'], 'html' ); ?>>HTML</option><option value="text" <?php selected( $wpsstudio_snippet['type'], 'text' ); ?>><?php esc_html_e( 'Plain text', 'coder-snippet-studio' ); ?></option></select>
		<label for="wpsstudio-shortcode"><?php esc_html_e( 'Shortcode', 'coder-snippet-studio' ); ?></label><input id="wpsstudio-shortcode" type="text" readonly value="<?php echo esc_attr( $wpsstudio_snippet['id'] ? '[wpsstudio id="' . (int) $wpsstudio_snippet['id'] . '"]' : __( 'Available after saving', 'coder-snippet-studio' ) ); ?>">
		<p class="description"><?php esc_html_e( 'Activate the snippet, then place its shortcode in content. Active content is public wherever the shortcode is used.', 'coder-snippet-studio' ); ?></p>
		<button class="button button-primary button-large"><?php esc_html_e( 'Save inactive', 'coder-snippet-studio' ); ?></button>
	</aside>
</form>
