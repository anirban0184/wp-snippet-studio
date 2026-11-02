<?php
/** Protected administration, with POST/redirect/GET for successful writes. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Admin {
	private ?\WP_Error $error = null;
	private ?array $draft = null;

	public function __construct( private Repository $repository, private Runner $runner ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_notices', array( $this, 'recovery_notice' ) );
	}

	public function menu(): void {
		if ( ! Permissions::can_manage() ) {
			return;
		}
		add_menu_page( __( 'WP Snippet Studio', 'wp-snippet-studio' ), __( 'Snippet Studio', 'wp-snippet-studio' ), 'manage_options', 'wpsstudio', array( $this, 'render' ), 'dashicons-editor-code', 81 );
		add_submenu_page( 'wpsstudio', __( 'All Snippets', 'wp-snippet-studio' ), __( 'All Snippets', 'wp-snippet-studio' ), 'manage_options', 'wpsstudio', array( $this, 'render' ) );
		add_submenu_page( 'wpsstudio', __( 'Add Snippet', 'wp-snippet-studio' ), __( 'Add New', 'wp-snippet-studio' ), 'manage_options', 'wpsstudio-new', array( $this, 'render' ) );
	}

	public function handle(): void {
		if ( ! Runner::management_request() ) {
			return;
		}
		if ( ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage snippets.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		if ( ! Installer::ready() && ! Installer::install() ) {
			$this->error = new \WP_Error( 'database', __( 'The snippet database is unavailable. No snippets will run.', 'wp-snippet-studio' ) );
			return;
		}
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}
		// All source is handled only after authorization. WordPress slashes request data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Explicit verification immediately below.
		$post   = wp_unslash( $_POST );
		$action = isset( $post['wpsstudio_action'] ) && is_string( $post['wpsstudio_action'] ) ? $post['wpsstudio_action'] : '';
		$id     = Validator::id( $post['id'] ?? '' );
		$nonce  = isset( $post['_wpnonce'] ) && is_string( $post['_wpnonce'] ) ? $post['_wpnonce'] : '';
		if ( ! in_array( $action, array( 'save', 'activate', 'deactivate', 'priority', 'delete', 'run', 'safe_on', 'safe_off' ), true ) || ! wp_verify_nonce( $nonce, 'wpsstudio_' . $action . '_' . $id ) ) {
			wp_die( esc_html__( 'The security check failed. Reload this page and try again.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		$revision = Validator::id( $post['revision'] ?? '' );
		if ( 'save' === $action ) {
			$input = array_intersect_key( $post, array_flip( array( 'title', 'description', 'code', 'type', 'scope', 'priority' ) ) );
			$result = $this->repository->save( $input, $id, $revision );
			if ( is_wp_error( $result ) ) {
				$this->draft = array_merge( $this->defaults(), $input, array( 'id' => $id, 'revision' => $revision ) );
			} else {
				wp_safe_redirect( $this->url( array( 'id' => $result, 'notice' => 'saved' ) ) );
				exit;
			}
		} elseif ( 'safe_on' === $action || 'safe_off' === $action ) {
			if ( 'safe_off' === $action && defined( 'WPSSTUDIO_SAFE_MODE' ) && WPSSTUDIO_SAFE_MODE ) {
				$result = new \WP_Error( 'constant', __( 'Remove WPSSTUDIO_SAFE_MODE from wp-config.php before turning Safe Mode off here.', 'wp-snippet-studio' ) );
			} else {
				update_option( 'wpsstudio_safe_mode', 'safe_on' === $action ? '1' : '0', false );
				$result = true;
			}
		} elseif ( ! $id || ! $revision ) {
			$result = new \WP_Error( 'invalid', __( 'A valid snippet and revision are required.', 'wp-snippet-studio' ) );
		} elseif ( 'activate' === $action || 'deactivate' === $action ) {
			$result = $this->repository->status( $id, $revision, 'activate' === $action );
		} elseif ( 'priority' === $action ) {
			$result = $this->repository->priority( $id, $revision, $post['priority'] ?? '' );
		} elseif ( 'delete' === $action ) {
			$result = $this->repository->delete( $id, $revision );
		} else {
			$result = $this->runner->run_manual( $id, $revision );
		}
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
			return;
		}
		wp_safe_redirect( $this->url( array( 'notice' => $action ) ) );
		exit;
	}

	public function recovery_notice(): void {
		if ( Runner::safe_mode() && Permissions::can_manage() && ! Runner::management_request() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'WP Snippet Studio Safe Mode is on. Snippets are paused.', 'wp-snippet-studio' ) . ' <a href="' . esc_url( $this->url() ) . '">' . esc_html__( 'Review snippets', 'wp-snippet-studio' ) . '</a></p></div>';
		}
	}

	public function assets(): void {
		if ( ! Runner::management_request() || ! Permissions::can_manage() ) {
			return;
		}
		wp_enqueue_style( 'wpsstudio-admin', WPSSTUDIO_URL . 'assets/admin.css', array(), WPSSTUDIO_VERSION );
		wp_enqueue_script( 'wpsstudio-admin', WPSSTUDIO_URL . 'assets/admin.js', array(), WPSSTUDIO_VERSION, true );
		if ( $this->editing() ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php', 'codemirror' => array( 'lineNumbers' => true, 'indentUnit' => 4, 'indentWithTabs' => true, 'lint' => false ) ) );
			if ( $settings ) {
				wp_add_inline_script( 'wpsstudio-admin', 'window.wpsstudioEditorSettings = ' . wp_json_encode( $settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
			}
		}
	}

	private function editing(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view selection.
		return null !== $this->draft || 'wpsstudio-new' === ( $_GET['page'] ?? '' ) || isset( $_GET['id'] );
	}

	private function defaults(): array {
		return array( 'id' => 0, 'revision' => 0, 'title' => '', 'description' => '', 'code' => '', 'type' => 'php', 'scope' => 'everywhere', 'priority' => 10, 'status' => 'inactive', 'last_run_at' => null, 'last_error' => '' );
	}

	public function render(): void {
		if ( ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to view snippet source.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		$editing = $this->editing();
		$counts  = Installer::ready() ? $this->repository->counts() : array_fill_keys( array_merge( Validator::TYPES, array( 'all' ) ), 0 );
		if ( $editing ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only record lookup behind capability gate.
			$id      = Validator::id( $_GET['id'] ?? '' );
			$snippet = $this->draft ?? ( $id ? $this->repository->get( $id ) : $this->defaults() );
			if ( ! $snippet ) {
				wp_die( esc_html__( 'Snippet not found.', 'wp-snippet-studio' ), '', array( 'response' => 404 ) );
			}
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Allow-listed read-only filtering.
			$type = isset( $_GET['type'] ) && is_string( $_GET['type'] ) && in_array( $_GET['type'], Validator::TYPES, true ) ? $_GET['type'] : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
			$current_page = max( 1, Validator::id( $_GET['paged'] ?? '' ) );
			$total        = $counts[ $type ?: 'all' ];
			$pages        = max( 1, (int) ceil( $total / 25 ) );
			$current_page = min( $current_page, $pages );
			$snippets     = Installer::ready() ? $this->repository->page( $type, $current_page ) : array();
		}
		require WPSSTUDIO_PATH . 'admin/views/header.php';
		require WPSSTUDIO_PATH . ( $editing ? 'admin/views/editor.php' : 'admin/views/list.php' );
		echo '</div>';
	}

	private function url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => 'wpsstudio' ), $args ), admin_url( 'admin.php' ) );
	}

	private function fields( string $action, int $id = 0, int $revision = 0 ): void {
		wp_nonce_field( 'wpsstudio_' . $action . '_' . $id, '_wpnonce', false );
		echo '<input type="hidden" name="wpsstudio_action" value="' . esc_attr( $action ) . '"><input type="hidden" name="id" value="' . esc_attr( (string) $id ) . '"><input type="hidden" name="revision" value="' . esc_attr( (string) $revision ) . '">';
	}

	private function scope_label( array $row ): string {
		if ( 'php' !== $row['type'] ) {
			return in_array( $row['type'], array( 'html', 'text' ), true ) ? __( 'Shortcode', 'wp-snippet-studio' ) : ( 'css' === $row['type'] ? __( 'Frontend · Head', 'wp-snippet-studio' ) : __( 'Frontend · Footer', 'wp-snippet-studio' ) );
		}
		return array( 'everywhere' => __( 'Everywhere', 'wp-snippet-studio' ), 'frontend' => __( 'Frontend only', 'wp-snippet-studio' ), 'admin' => __( 'Admin only', 'wp-snippet-studio' ), 'manual' => __( 'Run once manually', 'wp-snippet-studio' ) )[ $row['scope'] ] ?? __( 'Review required', 'wp-snippet-studio' );
	}
}
