<?php
/** Accessible, horizontally scrollable content list. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
?>
<nav class="wpsstudio-tabs" aria-label="<?php esc_attr_e( 'Snippet types', 'wp-snippet-studio' ); ?>">
	<?php
	foreach ( array(
		''     => __( 'All Snippets', 'wp-snippet-studio' ),
		'html' => 'HTML',
		'text' => __( 'Plain Text', 'wp-snippet-studio' ),
	) as $wpsstudio_key => $wpsstudio_label ) :
		?>
		<a href="<?php echo esc_url( $this->url( $wpsstudio_key ? array( 'type' => $wpsstudio_key ) : array() ) ); ?>" class="<?php echo $wpsstudio_type === $wpsstudio_key ? 'is-current' : ''; ?>"
		<?php
		if ( $wpsstudio_type === $wpsstudio_key ) :
			?>
			aria-current="page"<?php endif; ?>><?php echo esc_html( $wpsstudio_label ); ?> <span><?php echo esc_html( (string) $wpsstudio_counts[ $wpsstudio_key ? $wpsstudio_key : 'all' ] ); ?></span></a>
	<?php endforeach; ?>
</nav>
<div class="wpsstudio-toolbar"><h2><?php esc_html_e( 'All Snippets', 'wp-snippet-studio' ); ?></h2><a class="button button-primary" href="<?php echo esc_url( $this->url( array( 'page' => 'wpsstudio-new' ) ) ); ?>"><?php esc_html_e( 'Add Snippet', 'wp-snippet-studio' ); ?></a></div>
<div class="wpsstudio-table-scroll" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Snippets table. Scroll horizontally on smaller screens.', 'wp-snippet-studio' ); ?>">
<table class="widefat striped wpsstudio-table">
	<caption class="screen-reader-text"><?php esc_html_e( 'Manage content type, shortcode, status and actions on one line.', 'wp-snippet-studio' ); ?></caption>
	<thead><tr><th scope="col"><?php esc_html_e( 'Name', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Type', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Shortcode', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Actions', 'wp-snippet-studio' ); ?></th></tr></thead>
	<tbody>
	<?php if ( ! $wpsstudio_snippets ) : ?>
		<tr><td colspan="5"><?php esc_html_e( 'No snippets yet. Add reusable content to get started.', 'wp-snippet-studio' ); ?></td></tr>
	<?php endif; ?>
	<?php
	foreach ( $wpsstudio_snippets as $wpsstudio_row ) :
		$wpsstudio_snippet_id = (int) $wpsstudio_row['id'];
		$wpsstudio_revision   = (int) $wpsstudio_row['revision'];
		$wpsstudio_active     = 'active' === $wpsstudio_row['status'];
		$wpsstudio_shortcode  = '[wpsstudio id="' . $wpsstudio_snippet_id . '"]';
		?>
		<tr>
			<th scope="row"><a href="<?php echo esc_url( $this->url( array( 'id' => $wpsstudio_snippet_id ) ) ); ?>"><?php echo esc_html( $wpsstudio_row['title'] ); ?></a>
			<?php
			if ( 64 !== strlen( $wpsstudio_row['signature'] ) ) :
				?>
				<span class="wpsstudio-review"><?php esc_html_e( 'Review required', 'wp-snippet-studio' ); ?></span><?php endif; ?></th>
			<td><span class="wpsstudio-type wpsstudio-type-<?php echo esc_attr( $wpsstudio_row['type'] ); ?>"><?php echo 'html' === $wpsstudio_row['type'] ? 'HTML' : esc_html__( 'Text', 'wp-snippet-studio' ); ?></span></td>
			<td><div class="wpsstudio-shortcode-control"><label class="screen-reader-text" for="wpsstudio-shortcode-<?php echo esc_attr( (string) $wpsstudio_snippet_id ); ?>"><?php echo esc_html( sprintf( /* translators: %s: snippet title. */ __( 'Shortcode for %s', 'wp-snippet-studio' ), $wpsstudio_row['title'] ) ); ?></label><input id="wpsstudio-shortcode-<?php echo esc_attr( (string) $wpsstudio_snippet_id ); ?>" type="text" readonly value="<?php echo esc_attr( $wpsstudio_shortcode ); ?>"><button type="button" class="button button-small wpsstudio-copy" data-target="wpsstudio-shortcode-<?php echo esc_attr( (string) $wpsstudio_snippet_id ); ?>" data-copied="<?php esc_attr_e( 'Shortcode copied.', 'wp-snippet-studio' ); ?>" data-manual="<?php esc_attr_e( 'Shortcode selected. Copy it using your keyboard.', 'wp-snippet-studio' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: snippet title. */ __( 'Copy shortcode for %s', 'wp-snippet-studio' ), $wpsstudio_row['title'] ) ); ?>"><?php esc_html_e( 'Copy', 'wp-snippet-studio' ); ?></button></div></td>
			<td><form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( $wpsstudio_active ? 'deactivate' : 'activate', $wpsstudio_snippet_id, $wpsstudio_revision ); ?><button type="submit" class="wpsstudio-switch" role="switch" aria-checked="<?php echo $wpsstudio_active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: snippet title. */ __( 'Active status for %s', 'wp-snippet-studio' ), $wpsstudio_row['title'] ) ); ?>"
			<?php
			if ( ! $wpsstudio_active ) :
				?>
				data-confirm="<?php esc_attr_e( 'Activate this reviewed content wherever its shortcode is used?', 'wp-snippet-studio' ); ?>"<?php endif; ?>><span class="wpsstudio-switch-track" aria-hidden="true"></span><span><?php echo $wpsstudio_active ? esc_html__( 'Active', 'wp-snippet-studio' ) : esc_html__( 'Inactive', 'wp-snippet-studio' ); ?></span></button></form></td>
			<td><div class="wpsstudio-actions"><a class="button button-small" href="<?php echo esc_url( $this->url( array( 'id' => $wpsstudio_snippet_id ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-snippet-studio' ); ?></a><form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( 'delete', $wpsstudio_snippet_id, $wpsstudio_revision ); ?><button class="button button-small wpsstudio-delete" data-confirm="<?php esc_attr_e( 'Permanently delete this snippet? This cannot be undone.', 'wp-snippet-studio' ); ?>"><?php esc_html_e( 'Delete', 'wp-snippet-studio' ); ?></button></form></div></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
<p class="wpsstudio-copy-feedback screen-reader-text" role="status" aria-live="polite"></p>
<div class="wpsstudio-pagination">
	<?php
	if ( $wpsstudio_current_page > 1 ) :
		?>
		<a class="button" href="
		<?php
		echo esc_url(
			$this->url(
				array(
					'type'  => $wpsstudio_type,
					'paged' => $wpsstudio_current_page - 1,
				)
			)
		);
		?>
		"><?php esc_html_e( 'Previous', 'wp-snippet-studio' ); ?></a><?php endif; ?>
	<span><?php echo esc_html( sprintf( /* translators: 1: current page, 2: total pages, 3: total snippets. */ __( 'Page %1$d of %2$d · %3$d snippets', 'wp-snippet-studio' ), $wpsstudio_current_page, $wpsstudio_pages, $wpsstudio_total ) ); ?></span>
	<?php
	if ( $wpsstudio_current_page < $wpsstudio_pages ) :
		?>
		<a class="button" href="
		<?php
		echo esc_url(
			$this->url(
				array(
					'type'  => $wpsstudio_type,
					'paged' => $wpsstudio_current_page + 1,
				)
			)
		);
		?>
		"><?php esc_html_e( 'Next', 'wp-snippet-studio' ); ?></a><?php endif; ?>
</div>
