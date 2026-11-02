<?php
/** Source is escaped in a textarea, never interpolated into JavaScript. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
// Invalid arrays from a forged form must never reach an escaping function as strings.
foreach ( $this->defaults() as $key => $fallback ) {
	if ( isset( $snippet[ $key ] ) && ! is_scalar( $snippet[ $key ] ) ) {
		$snippet[ $key ] = $fallback;
	}
}
?>
<div class="wpsstudio-toolbar"><h2><?php echo $snippet['id'] ? esc_html__( 'Edit Snippet', 'wp-snippet-studio' ) : esc_html__( 'Add Snippet', 'wp-snippet-studio' ); ?></h2><a class="button" href="<?php echo esc_url( $this->url() ); ?>"><?php esc_html_e( 'All Snippets', 'wp-snippet-studio' ); ?></a></div>
<?php
if ( 'active' === $snippet['status'] ) :
	?>
	<div class="notice notice-info inline"><p><?php esc_html_e( 'This snippet is active. Saving your edits will deactivate it until you review and activate it again.', 'wp-snippet-studio' ); ?></p></div><?php endif; ?>
<?php
if ( $snippet['last_error'] || ( $snippet['id'] && ! $this->draft && ! $this->repository->verified( $snippet ) ) ) :
	?>
	<div class="notice notice-error inline"><p><?php esc_html_e( 'This snippet needs review. Check its source and save inactive to approve it again. Error details are not shown publicly.', 'wp-snippet-studio' ); ?></p></div><?php endif; ?>
<form method="post" action="<?php echo esc_url( $this->url( $snippet['id'] ? array( 'id' => (int) $snippet['id'] ) : array( 'page' => 'wpsstudio-new' ) ) ); ?>" class="wpsstudio-editor-form">
	<?php $this->fields( 'save', (int) $snippet['id'], (int) $snippet['revision'] ); ?>
	<div class="wpsstudio-editor-main">
		<label for="wpsstudio-title"><?php esc_html_e( 'Snippet title', 'wp-snippet-studio' ); ?></label>
		<input type="text" id="wpsstudio-title" name="title" value="<?php echo esc_attr( $snippet['title'] ); ?>" maxlength="150" placeholder="<?php esc_attr_e( 'Give your snippet a clear name', 'wp-snippet-studio' ); ?>" required>
		<label for="wpsstudio-code"><?php esc_html_e( 'Code / content', 'wp-snippet-studio' ); ?></label>
		<textarea id="wpsstudio-code" name="code" rows="20" spellcheck="false" aria-describedby="wpsstudio-code-help" data-php-help="<?php esc_attr_e( 'PHP body only, without opening or closing PHP tags. Automatic PHP loads at plugins_loaded, before the theme. Use hooks for later work.', 'wp-snippet-studio' ); ?>" data-css-help="<?php esc_attr_e( 'CSS only, without style tags. Active snippets load in the frontend head.', 'wp-snippet-studio' ); ?>" data-js-help="<?php esc_attr_e( 'JavaScript only, without script tags. Active snippets load in the frontend footer without automatic jQuery dependencies.', 'wp-snippet-studio' ); ?>" data-html-help="<?php esc_attr_e( 'WordPress post-safe HTML only. Scripts, event handlers and unsafe markup are removed when saved.', 'wp-snippet-studio' ); ?>" data-text-help="<?php esc_attr_e( 'Reusable plain text. HTML is escaped and line breaks are preserved. Shortcodes inside snippets are not evaluated.', 'wp-snippet-studio' ); ?>" required><?php echo esc_textarea( $snippet['code'] ); ?></textarea>
		<p id="wpsstudio-code-help" class="description"></p>
		<noscript><p class="description"><?php esc_html_e( 'PHP: omit PHP tags. CSS/JavaScript: omit style/script tags. HTML is filtered to WordPress post markup. Text is escaped on output.', 'wp-snippet-studio' ); ?></p></noscript>
		<label for="wpsstudio-description"><?php esc_html_e( 'Description', 'wp-snippet-studio' ); ?></label>
		<textarea id="wpsstudio-description" name="description" rows="3"><?php echo esc_textarea( $snippet['description'] ); ?></textarea>
	</div>
	<aside class="wpsstudio-editor-settings" aria-label="<?php esc_attr_e( 'Snippet settings', 'wp-snippet-studio' ); ?>">
		<h3><?php esc_html_e( 'Snippet settings', 'wp-snippet-studio' ); ?></h3>
		<label for="wpsstudio-type"><?php esc_html_e( 'Type', 'wp-snippet-studio' ); ?></label>
		<select id="wpsstudio-type" name="type">
		<?php
		foreach ( array(
			'php'  => 'PHP',
			'css'  => 'CSS',
			'js'   => 'JavaScript',
			'html' => 'HTML',
			'text' => __( 'Text / reusable content', 'wp-snippet-studio' ),
		) as $key => $label ) :
			?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $snippet['type'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<div class="wpsstudio-php-settings">
			<label for="wpsstudio-scope"><?php esc_html_e( 'PHP run location', 'wp-snippet-studio' ); ?></label>
			<select id="wpsstudio-scope" name="scope">
			<?php
			foreach ( array(
				'everywhere' => __( 'Everywhere', 'wp-snippet-studio' ),
				'frontend'   => __( 'Frontend only', 'wp-snippet-studio' ),
				'admin'      => __( 'WordPress admin only', 'wp-snippet-studio' ),
				'manual'     => __( 'Run once manually', 'wp-snippet-studio' ),
			) as $key => $label ) :
				?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $snippet['scope'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		</div>
		<div class="wpsstudio-priority-setting"><label for="wpsstudio-editor-priority"><?php esc_html_e( 'Priority', 'wp-snippet-studio' ); ?></label><input id="wpsstudio-editor-priority" name="priority" type="number" min="-9999" max="9999" step="1" value="<?php echo esc_attr( (string) $snippet['priority'] ); ?>" required><p class="description"><?php esc_html_e( 'Default 10. Lower numbers load first.', 'wp-snippet-studio' ); ?></p></div>
		<div class="wpsstudio-shortcode-info" hidden><label for="wpsstudio-shortcode"><?php esc_html_e( 'Shortcode', 'wp-snippet-studio' ); ?></label><input id="wpsstudio-shortcode" type="text" readonly value="<?php echo esc_attr( $snippet['id'] ? '[wpsstudio id="' . (int) $snippet['id'] . '"]' : __( 'Available after saving', 'wp-snippet-studio' ) ); ?>"><p class="description"><?php esc_html_e( 'Activate the snippet, then paste this into your content.', 'wp-snippet-studio' ); ?></p></div>
		<p class="wpsstudio-trust-note"><?php esc_html_e( 'Only use code you trust. PHP has your website’s permissions and cannot be sandboxed by this plugin. Save inactive, review, then activate from the list.', 'wp-snippet-studio' ); ?></p>
		<?php
		if ( $snippet['last_run_at'] ) :
			?>
			<p class="description"><?php echo esc_html( sprintf( /* translators: %s: UTC date/time. */ __( 'Last manual attempt: %s UTC', 'wp-snippet-studio' ), $snippet['last_run_at'] ) ); ?></p><?php endif; ?>
		<button class="button button-primary button-large"><?php esc_html_e( 'Save inactive', 'wp-snippet-studio' ); ?></button>
	</aside>
</form>
