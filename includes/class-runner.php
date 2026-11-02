<?php
/** Trusted snippet execution and recovery. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Runner {
	private ?array $executing = null;
	private string $reserve;
	private bool $php_loaded    = false;
	private int $buffer_level   = 0;
	private ?array $fatal_error = null;

	public function __construct( private Repository $repository ) {
		$this->reserve = str_repeat( 'r', 65536 );
	}

	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'automatic_php' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_shortcode( 'wpsstudio', array( $this, 'shortcode' ) );
		register_shutdown_function( array( $this, 'shutdown' ) );
	}

	public static function safe_mode(): bool {
		return ( defined( 'WPSSTUDIO_SAFE_MODE' ) && WPSSTUDIO_SAFE_MODE ) || '1' === get_option( 'wpsstudio_safe_mode', '0' );
	}

	/** Route recognition is never authorization. */
	public static function management_request(): bool {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Route recognition is read-only, never authorization.
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? $_GET['page'] : '';
		return is_admin() && 'admin.php' === $pagenow && in_array( $page, array( 'wpsstudio', 'wpsstudio-new' ), true );
	}

	private function available(): bool {
		return Installer::ready() && ! is_multisite() && ! self::safe_mode();
	}

	public function automatic_php(): void {
		global $pagenow;
		// Login and management are recovery routes; custom PHP cannot disable them.
		if ( $this->php_loaded || ! $this->available() || self::management_request() || 'wp-login.php' === $pagenow ) {
			return;
		}
		$this->php_loaded = true;
		foreach ( $this->repository->active( 'php' ) as $row ) {
			if ( 'manual' === $row['scope'] || ( 'admin' === $row['scope'] && ! is_admin() ) || ( 'frontend' === $row['scope'] && ( is_admin() || $this->background_request() ) ) ) {
				continue;
			}
			if ( ! $this->execute( $row ) ) {
				break; // Stop the request's remaining PHP after a failure.
			}
		}
	}

	/** REST is not always defined at plugins_loaded; recognize its routes too. */
	private function background_request(): bool {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only execution scope detection.
		if ( isset( $_GET['rest_route'] ) ) {
			return true;
		}
		$path   = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$prefix = wp_parse_url( rest_url(), PHP_URL_PATH );
		return is_string( $path ) && is_string( $prefix ) && str_starts_with( trailingslashit( $path ), trailingslashit( $prefix ) );
	}

	public function run_manual( int $id, int $revision ): true|\WP_Error {
		if ( ! Permissions::can_manage() ) {
			return new \WP_Error( 'forbidden', __( 'You do not have permission to run snippets.', 'wp-snippet-studio' ) );
		}
		if ( ! $this->available() ) {
			return new \WP_Error( 'safe_mode', __( 'Manual execution is disabled while Safe Mode is on or the database is unavailable.', 'wp-snippet-studio' ) );
		}
		$row = $this->repository->claim_manual( $id, $revision );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		// Claim updates the signature; reload the exact claimed revision before running.
		$claimed = $this->repository->get( $id );
		if ( ! $claimed || (int) $claimed['revision'] !== (int) $row['revision'] ) {
			return new \WP_Error( 'conflict', __( 'The snippet changed before it could run. The attempt was recorded.', 'wp-snippet-studio' ) );
		}
		return $this->execute( $claimed, true ) ? true : new \WP_Error( 'runtime', __( 'The manual attempt failed. Safe Mode was enabled. Review the code before trying again.', 'wp-snippet-studio' ) );
	}

	private function execute( array $row, bool $manual = false ): bool {
		$valid = $this->repository->valid_for_execution( $row );
		if ( is_wp_error( $valid ) ) {
			$this->repository->quarantine( $row, 'integrity' === $valid->get_error_code() ? 'integrity' : 'validation' );
			return false;
		}
		$this->executing    = $row;
		$level              = ob_get_level();
		$this->buffer_level = $level;
		// WordPress flushes buffers early during shutdown. Filter a fatal buffer at
		// flush time, before its normal shutdown hook can expose partial PHP output.
		ob_start(
			function ( string $output ): string {
				$error = error_get_last();
				if ( $this->executing && $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
						$this->fatal_error = $error;
						return '';
				}
				return $output;
			}
		);
		try {
			// Locally entered code is privileged, NOT sandboxed. Validation never uses eval.
			// Isolate the eval scope so snippets cannot accidentally inherit this object.
			( static function ( string $wpsstudio_source ): void {
				// phpcs:ignore Squiz.PHP.Eval.Discouraged -- Explicit purpose: execute locally approved trusted PHP.
				eval( $wpsstudio_source );
			} )( $row['code'] );
			while ( ob_get_level() > $level + 1 ) {
				ob_end_flush();
			}
			$output          = ob_get_level() > $level ? ob_get_clean() : '';
			$this->executing = null;
			if ( ! $manual ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted PHP output, like a plugin's own output.
				echo $output;
			}
			return true;
		} catch ( \Throwable $error ) {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			$this->repository->quarantine( $row, 'runtime' );
			update_option( 'wpsstudio_safe_mode', '1', false );
			$this->executing = null;
			return false;
		}
	}

	public function shutdown(): void {
		$error = $this->fatal_error ?? error_get_last();
		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			return;
		}
		$this->reserve = ''; // Leave a small chance of recovery after memory exhaustion.
		if ( $this->executing ) {
			while ( ob_get_level() > $this->buffer_level ) {
				ob_end_clean();
			}
			$this->repository->quarantine( $this->executing, 'fatal' );
			update_option( 'wpsstudio_safe_mode', '1', false );
		} elseif ( str_contains( $error['file'], __FILE__ ) && str_contains( $error['file'], "eval()'d code" ) ) {
			// A callback declared in a snippet may fail after initial loading.
			update_option( 'wpsstudio_safe_mode', '1', false );
		}
	}

	public function assets(): void {
		if ( ! $this->available() || is_admin() ) {
			return;
		}
		foreach ( array( 'css', 'js' ) as $type ) {
			foreach ( $this->repository->active( $type ) as $row ) {
				if ( is_wp_error( $this->repository->valid_for_execution( $row ) ) ) {
					$this->repository->quarantine( $row, 'validation' );
					continue;
				}
				$handle = 'wpsstudio-' . $row['id'];
				if ( 'css' === $type ) {
					wp_register_style( $handle, false, array(), WPSSTUDIO_VERSION );
					wp_enqueue_style( $handle );
					wp_add_inline_style( $handle, $row['code'] );
				} else {
					wp_register_script( $handle, false, array(), WPSSTUDIO_VERSION, true );
					wp_enqueue_script( $handle );
					wp_add_inline_script( $handle, $row['code'] );
				}
			}
		}
	}

	public function shortcode( mixed $attributes ): string {
		if ( ! $this->available() ) {
			return '';
		}
		$attributes = shortcode_atts( array( 'id' => '' ), is_array( $attributes ) ? $attributes : array(), 'wpsstudio' );
		$id         = Validator::id( $attributes['id'] );
		$row        = $id ? $this->repository->get( $id ) : null;
		if ( ! $row || 'active' !== $row['status'] || ! in_array( $row['type'], array( 'html', 'text' ), true ) ) {
			return '';
		}
		if ( is_wp_error( $this->repository->valid_for_execution( $row ) ) ) {
			$this->repository->quarantine( $row, 'integrity' );
			return '';
		}
		return 'html' === $row['type'] ? wp_kses_post( $row['code'] ) : nl2br( esc_html( $row['code'] ), false );
	}
}
