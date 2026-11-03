<?php
/** Content storage, installation-bound approvals and revision checks. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The custom table has no core storage API; uncached reads and conditional writes keep revocation, approval signatures and revision conflicts current across requests.

final class Repository {
	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'wpsstudio_snippets';
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $this->query( fn() => $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d AND type IN ('html','text')", $this->table, $id ), ARRAY_A ) );
		return $row ? $row : null;
	}

	/** List screens never fetch content bodies. */
	public function page( string $type = '', int $page = 1 ): array {
		global $wpdb;
		$offset = ( max( 1, $page ) - 1 ) * 25;
		if ( in_array( $type, Validator::TYPES, true ) ) {
			return $this->query( fn() => $wpdb->get_results( $wpdb->prepare( 'SELECT id,title,type,status,revision,signature FROM %i WHERE type = %s ORDER BY id ASC LIMIT 25 OFFSET %d', $this->table, $type, $offset ), ARRAY_A ) ) ?? array();
		}
		return $this->query( fn() => $wpdb->get_results( $wpdb->prepare( "SELECT id,title,type,status,revision,signature FROM %i WHERE type IN ('html','text') ORDER BY id ASC LIMIT 25 OFFSET %d", $this->table, $offset ), ARRAY_A ) ) ?? array();
	}

	public function counts(): array {
		global $wpdb;
		$counts = array_fill_keys( Validator::TYPES, 0 );
		foreach ( $this->query( fn() => $wpdb->get_results( $wpdb->prepare( "SELECT type, COUNT(*) AS total FROM %i WHERE type IN ('html','text') GROUP BY type", $this->table ), ARRAY_A ) ) ?? array() as $row ) {
			$counts[ $row['type'] ] = (int) $row['total'];
		}
		$counts['all'] = array_sum( $counts );
		return $counts;
	}

	public function legacy_count(): int {
		global $wpdb;
		return (int) $this->query( fn() => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE type NOT IN ('html','text')", $this->table ) ) );
	}

	private function signature( array $row ): string {
		$data = array();
		foreach ( array( 'id', 'title', 'description', 'code', 'type', 'scope', 'status', 'revision' ) as $field ) {
			$data[ $field ] = (string) ( $row[ $field ] ?? '' );
		}
		return hash_hmac( 'sha256', wp_json_encode( $data ), wp_salt( 'auth' ) . '|wpsstudio-content-v2' );
	}

	public function verified( array $row ): bool {
		return isset( $row['signature'] ) && is_string( $row['signature'] ) && 64 === strlen( $row['signature'] ) && hash_equals( $this->signature( $row ), $row['signature'] );
	}

	public function valid_content( array $row ): bool {
		if ( ! $this->verified( $row ) || 'shortcode' !== $row['scope'] ) {
			return false;
		}
		$validated = Validator::snippet( $row );
		return ! is_wp_error( $validated ) && $validated['code'] === $row['code'] && $validated['title'] === $row['title'];
	}

	/** A reviewed save is always inactive, including edits of active content. */
	public function save( array $input, int $id = 0, int $revision = 0 ): int|\WP_Error {
		global $wpdb;
		if ( ! Permissions::can_manage() || ! Installer::ready() ) {
			return $this->unavailable();
		}
		$data = Validator::snippet( $input );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$now = current_time( 'mysql', true );
		if ( 0 === $id ) {
			$data = array_merge(
				$data,
				array(
					'status'     => 'inactive',
					'revision'   => 1,
					'signature'  => '',
					'created_at' => $now,
					'updated_at' => $now,
				)
			);
			if ( false === $this->query( fn() => $wpdb->insert( $this->table, $data ) ) ) {
				return $this->unavailable();
			}
			$id         = (int) $wpdb->insert_id;
			$data['id'] = $id;
			if ( 1 !== $wpdb->update(
				$this->table,
				array( 'signature' => $this->signature( $data ) ),
				array(
					'id'       => $id,
					'revision' => 1,
				)
			) ) {
				return $this->unavailable();
			}
			return $id;
		}
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		$data = array_merge(
			$row,
			$data,
			array(
				'status'   => 'inactive',
				'revision' => $revision + 1,
			)
		);
		return $this->replace( $row, $data ) ? $id : $this->conflict();
	}

	public function status( int $id, int $revision, bool $active ): true|\WP_Error {
		if ( ! Permissions::can_manage() || ! Installer::ready() ) {
			return $this->unavailable();
		}
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		$approved = $this->valid_content( $row );
		if ( $active && ! $approved ) {
			return new \WP_Error( 'review', __( 'Review the content and save it inactive before activation.', 'wp-snippet-studio' ) );
		}
		$new             = $row;
		$new['status']   = $active ? 'active' : 'inactive';
		$new['revision'] = $revision + 1;
		return $this->replace( $row, $new, $approved ) ? true : $this->conflict();
	}

	public function delete( int $id, int $revision ): true|\WP_Error {
		global $wpdb;
		if ( ! Permissions::can_manage() || ! Installer::ready() ) {
			return $this->unavailable();
		}
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		return 1 === $wpdb->delete(
			$this->table,
			array(
				'id'        => $id,
				'revision'  => $revision,
				'signature' => $row['signature'],
			),
			array( '%d', '%d', '%s' )
		) ? true : $this->conflict();
	}

	private function replace( array $old, array $replacement, bool $approve = true ): bool {
		global $wpdb;
		$replacement['updated_at'] = current_time( 'mysql', true );
		$replacement['signature']  = $approve ? $this->signature( $replacement ) : '';
		$replacement               = array_intersect_key( $replacement, array_flip( array( 'title', 'description', 'code', 'type', 'scope', 'status', 'revision', 'signature', 'updated_at' ) ) );
		return 1 === $this->query(
			fn() => $wpdb->update(
				$this->table,
				$replacement,
				array(
					'id'        => (int) $old['id'],
					'revision'  => (int) $old['revision'],
					'signature' => $old['signature'],
				)
			)
		);
	}

	/** Keep database failures private, including content-bearing SQL. */
	private function query( callable $query ): mixed {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			return $query();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	private function conflict(): \WP_Error {
		return new \WP_Error( 'conflict', __( 'This snippet changed or is unavailable. Reload it before trying again.', 'wp-snippet-studio' ) );
	}

	private function unavailable(): \WP_Error {
		return new \WP_Error( 'unavailable', __( 'Snippet management is unavailable. Check your permissions and database availability.', 'wp-snippet-studio' ) );
	}
}
