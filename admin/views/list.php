<?php
/** Compact, horizontally scrollable snippet list. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;
$tabs = array( '' => __( 'All Snippets', 'wp-snippet-studio' ), 'php' => __( 'PHP Functions', 'wp-snippet-studio' ), 'css' => __( 'CSS Styles', 'wp-snippet-studio' ), 'js' => __( 'JS Scripts', 'wp-snippet-studio' ), 'html' => __( 'HTML Content', 'wp-snippet-studio' ), 'text' => __( 'Text', 'wp-snippet-studio' ) );
?>
<nav class="wpsstudio-tabs" aria-label="<?php esc_attr_e( 'Snippet types', 'wp-snippet-studio' ); ?>">
	<?php foreach ( $tabs as $key => $label ) : ?>
		<a href="<?php echo esc_url( $this->url( $key ? array( 'type' => $key ) : array() ) ); ?>" <?php if ( $key === $type ) : ?>aria-current="page"<?php endif; ?>><?php echo esc_html( $label ); ?> <span><?php echo esc_html( (string) $counts[ $key ?: 'all' ] ); ?></span></a>
	<?php endforeach; ?>
</nav>
<div class="wpsstudio-toolbar">
	<h2><?php echo esc_html( $tabs[ $type ] ); ?></h2>
	<a class="button button-primary" href="<?php echo esc_url( $this->url( array( 'page' => 'wpsstudio-new' ) ) ); ?>"><?php esc_html_e( 'Add Snippet', 'wp-snippet-studio' ); ?></a>
</div>
<p class="wpsstudio-hint"><?php esc_html_e( 'Lower priorities load first. New snippets and edits are saved inactive.', 'wp-snippet-studio' ); ?></p>
<div class="wpsstudio-table-scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Snippets table. Scroll horizontally on smaller screens.', 'wp-snippet-studio' ); ?>">
<table class="widefat striped wpsstudio-table">
	<caption class="screen-reader-text"><?php esc_html_e( 'Manage snippet type, run location, priority, status and actions on one line.', 'wp-snippet-studio' ); ?></caption>
	<colgroup><col class="wpsstudio-col-name"><col class="wpsstudio-col-type"><col class="wpsstudio-col-scope"><col class="wpsstudio-col-priority"><col class="wpsstudio-col-status"><col class="wpsstudio-col-actions"></colgroup>
	<thead><tr>
		<th scope="col"><?php esc_html_e( 'Name', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Type', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Run location', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Priority', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'wp-snippet-studio' ); ?></th><th scope="col"><?php esc_html_e( 'Actions', 'wp-snippet-studio' ); ?></th>
	</tr></thead>
	<tbody>
	<?php if ( ! $snippets ) : ?>
		<tr><td colspan="6" class="wpsstudio-empty"><?php esc_html_e( 'No snippets yet. Add your first snippet to get started.', 'wp-snippet-studio' ); ?></td></tr>
	<?php endif; ?>
	<?php foreach ( $snippets as $row ) :
		$id = (int) $row['id'];
		$revision = (int) $row['revision'];
		$manual = 'php' === $row['type'] && 'manual' === $row['scope'];
		$active = 'active' === $row['status'];
		?>
		<tr>
			<td class="wpsstudio-name"><a href="<?php echo esc_url( $this->url( array( 'id' => $id ) ) ); ?>" title="<?php echo esc_attr( $row['title'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a><?php if ( $row['last_error'] ) : ?><span class="wpsstudio-review" title="<?php esc_attr_e( 'Review the source and save inactive before running again.', 'wp-snippet-studio' ); ?>"><?php esc_html_e( 'Review required', 'wp-snippet-studio' ); ?></span><?php endif; ?></td>
			<td><span class="wpsstudio-type wpsstudio-type-<?php echo esc_attr( $row['type'] ); ?>"><?php echo esc_html( strtoupper( $row['type'] ) ); ?></span></td>
			<td><?php echo esc_html( $this->scope_label( $row ) ); ?></td>
			<td>
				<?php if ( ! $manual && in_array( $row['type'], array( 'php', 'css', 'js' ), true ) ) : ?>
				<form method="post" action="<?php echo esc_url( $this->url( array( 'type' => $type, 'paged' => $current_page ) ) ); ?>" class="wpsstudio-priority-form">
					<?php $this->fields( 'priority', $id, $revision ); ?>
					<label class="screen-reader-text" for="wpsstudio-priority-<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_html( sprintf( /* translators: %s: snippet title. */ __( 'Priority for %s', 'wp-snippet-studio' ), $row['title'] ) ); ?></label>
					<input id="wpsstudio-priority-<?php echo esc_attr( (string) $id ); ?>" name="priority" type="number" min="-9999" max="9999" step="1" value="<?php echo esc_attr( $row['priority'] ); ?>" required>
					<button class="button button-small wpsstudio-priority-save" aria-label="<?php esc_attr_e( 'Save priority', 'wp-snippet-studio' ); ?>"><span class="dashicons dashicons-saved" aria-hidden="true"></span></button>
				</form>
				<?php else : ?><span aria-label="<?php esc_attr_e( 'Priority does not apply', 'wp-snippet-studio' ); ?>">—</span><?php endif; ?>
			</td>
			<td>
				<?php if ( $manual ) : ?>
					<span class="wpsstudio-status-text"><?php esc_html_e( 'Manual', 'wp-snippet-studio' ); ?></span>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( $this->url( array( 'type' => $type, 'paged' => $current_page ) ) ); ?>">
						<?php $this->fields( $active ? 'deactivate' : 'activate', $id, $revision ); ?>
						<button class="wpsstudio-switch" type="submit" role="switch" aria-checked="<?php echo $active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: snippet title. */ __( 'Active status for %s', 'wp-snippet-studio' ), $row['title'] ) ); ?>" <?php if ( ! $active && in_array( $row['type'], array( 'php', 'js' ), true ) ) : ?>data-confirm="<?php esc_attr_e( 'Activate this trusted code? PHP runs with your website’s permissions; JavaScript runs in visitors’ browsers.', 'wp-snippet-studio' ); ?>"<?php endif; ?>><span aria-hidden="true"></span><span class="wpsstudio-switch-label"><?php echo $active ? esc_html__( 'Active', 'wp-snippet-studio' ) : esc_html__( 'Inactive', 'wp-snippet-studio' ); ?></span></button>
					</form>
				<?php endif; ?>
			</td>
			<td><div class="wpsstudio-actions">
				<a class="button button-small" href="<?php echo esc_url( $this->url( array( 'id' => $id ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-snippet-studio' ); ?></a>
				<?php if ( $manual ) : ?><form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( 'run', $id, $revision ); ?><button class="button button-small" <?php disabled( Runner::safe_mode() ); ?> data-confirm="<?php esc_attr_e( 'Run this code once now? Changes made by PHP cannot be automatically undone. A failed attempt is also recorded.', 'wp-snippet-studio' ); ?>"><?php esc_html_e( 'Run once', 'wp-snippet-studio' ); ?></button></form><?php endif; ?>
				<form method="post" action="<?php echo esc_url( $this->url() ); ?>"><?php $this->fields( 'delete', $id, $revision ); ?><button class="button button-small wpsstudio-delete" data-confirm="<?php esc_attr_e( 'Permanently delete this snippet? This cannot be undone.', 'wp-snippet-studio' ); ?>"><?php esc_html_e( 'Delete', 'wp-snippet-studio' ); ?></button></form>
			</div></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
<div class="wpsstudio-pagination">
	<span><?php echo esc_html( sprintf( /* translators: 1: current page, 2: page count, 3: item count. */ __( 'Page %1$d of %2$d · %3$d snippets', 'wp-snippet-studio' ), $current_page, $pages, $total ) ); ?></span>
	<?php if ( $current_page > 1 ) : ?><a class="button" href="<?php echo esc_url( $this->url( array( 'type' => $type, 'paged' => $current_page - 1 ) ) ); ?>"><?php esc_html_e( 'Previous', 'wp-snippet-studio' ); ?></a><?php endif; ?>
	<?php if ( $current_page < $pages ) : ?><a class="button" href="<?php echo esc_url( $this->url( array( 'type' => $type, 'paged' => $current_page + 1 ) ) ); ?>"><?php esc_html_e( 'Next', 'wp-snippet-studio' ); ?></a><?php endif; ?>
</div>
