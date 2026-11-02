<?php
/** Database access, integrity signatures and optimistic concurrency. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Repository {
	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'wpsstudio_snippets';
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table, $id ), ARRAY_A );
		return $row ? $row : null;
	}

	/** Source code is not fetched into the list screen. */
	public function page( string $type = '', int $page = 1 ): array {
		global $wpdb;
		$offset = ( max( 1, $page ) - 1 ) * 25;
		if ( in_array( $type, Validator::TYPES, true ) ) {
			return $wpdb->get_results( $wpdb->prepare( 'SELECT id,title,type,scope,priority,status,revision,last_error,last_run_at FROM %i WHERE type = %s ORDER BY priority ASC, id ASC LIMIT 25 OFFSET %d', $this->table, $type, $offset ), ARRAY_A ) ?? array();
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT id,title,type,scope,priority,status,revision,last_error,last_run_at FROM %i ORDER BY priority ASC, id ASC LIMIT 25 OFFSET %d', $this->table, $offset ), ARRAY_A ) ?? array();
	}

	public function counts(): array {
		global $wpdb;
		$counts = array_fill_keys( Validator::TYPES, 0 );
		foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT type, COUNT(*) AS total FROM %i GROUP BY type', $this->table ), ARRAY_A ) ?? array() as $row ) {
			if ( isset( $counts[ $row['type'] ] ) ) {
				$counts[ $row['type'] ] = (int) $row['total'];
			}
		}
		$counts['all'] = array_sum( $counts );
		return $counts;
	}

	public function active( string $type ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE status = 'active' AND type = %s ORDER BY priority ASC, id ASC", $this->table, $type ), ARRAY_A ) ?? array();
	}

	private function signature( array $row ): string {
		$data = array();
		foreach ( array( 'id', 'title', 'description', 'code', 'type', 'scope', 'status', 'priority', 'revision', 'last_run_hash', 'last_run_at' ) as $field ) {
			$data[ $field ] = (string) ( $row[ $field ] ?? '' );
		}
		// Bind approvals to this installation. This detects changes, not a full server compromise.
		return hash_hmac( 'sha256', wp_json_encode( $data ), wp_salt( 'auth' ) . '|wpsstudio-v1' );
	}

	public function verified( array $row ): bool {
		return isset( $row['signature'] ) && 64 === strlen( $row['signature'] ) && hash_equals( $this->signature( $row ), $row['signature'] );
	}

	/** Every write is inactive. A stale editor cannot overwrite a newer revision. */
	public function save( array $input, int $id = 0, int $revision = 0 ): int|\WP_Error {
		global $wpdb;
		$data = Validator::snippet( $input );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$now = current_time( 'mysql', true );
		if ( 0 === $id ) {
			$data = array_merge(
				$data,
				array(
					'status'        => 'inactive',
					'revision'      => 1,
					'signature'     => '',
					'last_run_hash' => '',
					'last_run_at'   => null,
					'last_error'    => '',
					'created_at'    => $now,
					'updated_at'    => $now,
				)
			);
			if ( false === $this->write( fn() => $wpdb->insert( $this->table, $data ) ) ) {
				return $this->database_error();
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
				return $this->database_error();
			}
			return $id;
		}
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		// An unverified receipt is preserved conservatively, never silently cleared.
		$data = array_merge(
			$row,
			$data,
			array(
				'status'     => 'inactive',
				'revision'   => $revision + 1,
				'last_error' => '',
				'updated_at' => $now,
			)
		);
		return $this->replace( $row, $data ) ? $id : $this->conflict();
	}

	public function status( int $id, int $revision, bool $active ): true|\WP_Error {
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		if ( ! $active && ! $this->verified( $row ) ) {
			return $this->quarantine( $row, 'integrity' ) ? true : $this->conflict();
		}
		if ( ! $this->verified( $row ) ) {
			return $this->integrity_error();
		}
		if ( $active ) {
			$valid = $this->valid_for_execution( $row );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			if ( 'php' === $row['type'] && 'manual' === $row['scope'] ) {
				return new \WP_Error( 'manual', __( 'Manual PHP snippets use Run once and cannot be activated automatically.', 'wp-snippet-studio' ) );
			}
		}
		$new             = $row;
		$new['status']   = $active ? 'active' : 'inactive';
		$new['revision'] = $revision + 1;
		return $this->replace( $row, $new ) ? true : $this->conflict();
	}

	public function priority( int $id, int $revision, mixed $value ): true|\WP_Error {
		$priority = Validator::priority( $value );
		if ( is_wp_error( $priority ) ) {
			return $priority;
		}
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		if ( ! $this->verified( $row ) ) {
			return $this->integrity_error();
		}
		$new             = $row;
		$new['priority'] = $priority;
		$new['revision'] = $revision + 1;
		return $this->replace( $row, $new ) ? true : $this->conflict();
	}

	public function delete( int $id, int $revision ): true|\WP_Error {
		global $wpdb;
		return 1 === $wpdb->delete(
			$this->table,
			array(
				'id'       => $id,
				'revision' => $revision,
			),
			array( '%d', '%d' )
		) ? true : $this->conflict();
	}

	/** Claim BEFORE execution. Duplicate or concurrent POSTs cannot reuse the claim. */
	public function claim_manual( int $id, int $revision ): array|\WP_Error {
		$row = $this->get( $id );
		if ( ! $row || (int) $row['revision'] !== $revision ) {
			return $this->conflict();
		}
		$valid = $this->valid_for_execution( $row );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( 'php' !== $row['type'] || 'manual' !== $row['scope'] ) {
			return new \WP_Error( 'manual', __( 'Only manual PHP snippets can use Run once.', 'wp-snippet-studio' ) );
		}
		$hash = hash( 'sha256', $row['code'] );
		if ( hash_equals( $row['last_run_hash'], $hash ) ) {
			return new \WP_Error( 'already_run', __( 'This code has already been attempted. Change and review the code before another manual run.', 'wp-snippet-studio' ) );
		}
		$new                  = $row;
		$new['last_run_hash'] = $hash;
		$new['last_run_at']   = current_time( 'mysql', true );
		$new['status']        = 'inactive';
		$new['revision']      = $revision + 1;
		return $this->replace( $row, $new ) ? $new : $this->conflict();
	}

	public function valid_for_execution( array $row ): true|\WP_Error {
		if ( ! $this->verified( $row ) ) {
			return $this->integrity_error();
		}
		$input          = $row;
		$input['scope'] = 'php' === $row['type'] ? $row['scope'] : 'everywhere';
		$valid          = Validator::snippet( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( $valid['scope'] !== $row['scope'] || $valid['code'] !== $row['code'] ) {
			return $this->integrity_error();
		}
		return true;
	}

	public function quarantine( array $row, string $reason ): bool {
		global $wpdb;
		$reason = in_array( $reason, array( 'integrity', 'validation', 'runtime', 'fatal' ), true ) ? $reason : 'runtime';
		return 1 === $wpdb->update(
			$this->table,
			array(
				'status'     => 'inactive',
				'signature'  => '',
				'last_error' => $reason,
				'revision'   => (int) $row['revision'] + 1,
			),
			array(
				'id'       => (int) $row['id'],
				'revision' => (int) $row['revision'],
			)
		);
	}

	private function replace( array $old, array $replacement ): bool {
		global $wpdb;
		$replacement['updated_at'] = current_time( 'mysql', true );
		$replacement['signature']  = $this->signature( $replacement );
		unset( $replacement['id'], $replacement['created_at'] );
		return 1 === $this->write(
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

	/** wpdb's error logger can include SQL containing source; suppress it for source writes. */
	private function write( callable $query ): int|bool {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			return $query();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	private function conflict(): \WP_Error {
		return new \WP_Error( 'conflict', __( 'This snippet changed in another request. Reload it before trying again.', 'wp-snippet-studio' ) );
	}

	private function database_error(): \WP_Error {
		return new \WP_Error( 'database', __( 'The snippet could not be stored. Check database availability and try again.', 'wp-snippet-studio' ) );
	}

	private function integrity_error(): \WP_Error {
		return new \WP_Error( 'integrity', __( 'Snippet integrity could not be verified. Review the source and save it inactive before activating or running it.', 'wp-snippet-studio' ) );
	}
}
