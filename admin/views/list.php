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
	) as $key => $label ) :
		?>
		<a href="<?php echo esc_url( $this->url( $key ? array( 'type' => $key ) : array() ) ); ?>" class="<?php echo $type === $key ? 'is-current' : ''; ?>"
		<?php
		if ( $type === $key ) :
			?>
			aria-current="page"<?php endif; ?>><?php echo esc_html( $label ); ?> <span><?php echo esc_html( (string) $counts[ $key ? $key : 'all' ] ); ?></span></a>
	<?php endforeach; ?>
</nav>
<div class="wpsstudio-toolbar"><h2><?php esc_html_e( 'All Snippets', 'wp-snippet-studio' ); ?></h2><a class="button button-primary" href="<?php echo esc_url( $this->url( array( 'page' => 'wpsstudio-new' ) ) ); ?>"><?php esc_html_e( 'Add Snippet', 'wp-snippet-studio' ); ?></a></div>
<div class="wpsstudio-table-scroll" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Snippets table. Scroll horizontally on smaller screens.', 'wp-snippet-studio' ); ?>">
<table class="widefat striped wpsstudio-table">
	<caption class="screen-reader-text"><?php esc_html_e( 'Manage content type, shortcode, status and actions on one line.', 'wp-snippet-studio' ); ?></caption>
	<thead><tr><th scope="col"><?php esc_html_e( 'Name', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Type', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Shortcode', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Actions', 'wp-snippet-studio' ); ?></th></tr></thead>
	<tbody>
	<?php if ( ! $snippets ) : ?>
		<tr><td colspan="5"><?php esc_html_e( 'No snippets yet. Add reusable content to get started.', 'wp-snippet-studio' ); ?></td></tr>
	<?php endif; ?>
	<?php
	foreach ( $snippets as $row ) :
		$snippet_id = (int) $row['id'];
		$revision   = (int) $row['revision'];
		$active     = 'active' === $row['status'];
		$shortcode  = '[wpsstudio id="' . $snippet_id . '"]';
		?>
		<tr>
			<th scope="row"><a href="<?php echo esc_url( $this->url( array( 'id' => $snippet_id ) ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
			<?php
			if ( 64 !== strlen( $row['signature'] ) ) :
				?>
				<span class="wpsstudio-review"><?php esc_html_e( 'Review required', 'wp-snippet-studio' ); ?></span><?php endif; ?></th>
			<td><span class="wpsstudio-type wpsstudio-type-<?php echo esc_attr( $row['type'] ); ?>"><?php echo 'html' === $row['type'] ? 'HTML' : esc_html__( 'Text', 'wp-snippet-studio' ); ?></span></td>
			<td><div class="wpsstudio-shortcode-control"><label class="screen-reader-text" for="wpsstudio-shortcode-<?php echo esc_attr( (string) $snippet_id ); ?>"><?php echo esc_html( sprintf( /* translators: %s: snippet title. */ __( 'Shortcode for %s', 'wp-snippet-studio' ), $row['title'] ) ); ?></label><input id="wpsstudio-shortcode-<?php echo esc_attr( (string) $snippet_id ); ?>" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>"><button type="button" class="button button-small wpsstudio-copy" data-target="wpsstudio-shortcode-<?php echo esc_attr( (string) $snippet_id ); ?>" data-copied="<?php esc_attr_e( 'Shortcode copied.', 'wp-snippet-studio' ); ?>" data-manual="<?php esc_attr_e( 'Shortcode selected. Copy it using your keyboard.', 'wp-snippet-studio' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: snippet title. */ __( 'Copy shortcode for %s', 'wp-snippet-studio' ), $row['title'] ) ); ?>"><?php esc_html_e( 'Copy', 'wp-snippet-studio' ); ?></button></div></td>
			<td><form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( $active ? 'deactivate' : 'activate', $snippet_id, $revision ); ?><button type="submit" class="wpsstudio-switch" role="switch" aria-checked="<?php echo $active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: snippet title. */ __( 'Active status for %s', 'wp-snippet-studio' ), $row['title'] ) ); ?>"
			<?php
			if ( ! $active ) :
				?>
				data-confirm="<?php esc_attr_e( 'Activate this reviewed content wherever its shortcode is used?', 'wp-snippet-studio' ); ?>"<?php endif; ?>><span class="wpsstudio-switch-track" aria-hidden="true"></span><span><?php echo $active ? esc_html__( 'Active', 'wp-snippet-studio' ) : esc_html__( 'Inactive', 'wp-snippet-studio' ); ?></span></button></form></td>
			<td><div class="wpsstudio-actions"><a class="button button-small" href="<?php echo esc_url( $this->url( array( 'id' => $snippet_id ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-snippet-studio' ); ?></a><form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( 'delete', $snippet_id, $revision ); ?><button class="button button-small wpsstudio-delete" data-confirm="<?php esc_attr_e( 'Permanently delete this snippet? This cannot be undone.', 'wp-snippet-studio' ); ?>"><?php esc_html_e( 'Delete', 'wp-snippet-studio' ); ?></button></form></div></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
<p class="wpsstudio-copy-feedback screen-reader-text" role="status" aria-live="polite"></p>
<div class="wpsstudio-pagination">
	<?php
	if ( $current_page > 1 ) :
		?>
		<a class="button" href="
		<?php
		echo esc_url(
			$this->url(
				array(
					'type'  => $type,
					'paged' => $current_page - 1,
				)
			)
		);
		?>
		"><?php esc_html_e( 'Previous', 'wp-snippet-studio' ); ?></a><?php endif; ?>
	<span><?php echo esc_html( sprintf( /* translators: 1: current page, 2: total pages, 3: total snippets. */ __( 'Page %1$d of %2$d · %3$d snippets', 'wp-snippet-studio' ), $current_page, $pages, $total ) ); ?></span>
	<?php
	if ( $current_page < $pages ) :
		?>
		<a class="button" href="
		<?php
		echo esc_url(
			$this->url(
				array(
					'type'  => $type,
					'paged' => $current_page + 1,
				)
			)
		);
		?>
		"><?php esc_html_e( 'Next', 'wp-snippet-studio' ); ?></a><?php endif; ?>
</div>
