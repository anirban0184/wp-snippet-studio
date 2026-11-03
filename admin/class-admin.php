<?php
/** Capability-gated content screens and authenticated POST actions. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Admin {
	private ?\WP_Error $error    = null;
	private ?array $draft        = null;
	private bool $editor_enabled = false;

	public function __construct( private Repository $repository ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public static function management_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route allow-list.
		return is_admin() && isset( $_GET['page'] ) && is_string( $_GET['page'] ) && in_array( $_GET['page'], array( 'wpsstudio', 'wpsstudio-new' ), true );
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
		if ( ! self::management_request() ) {
			return;
		}
		if ( ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage snippets.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		if ( ! Installer::ready() && ! Installer::install() ) {
			$this->error = new \WP_Error( 'database', __( 'The snippet database is unavailable. Content output is paused.', 'wp-snippet-studio' ) );
			return;
		}
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Explicit action/record nonce verification below.
		$post   = wp_unslash( $_POST );
		$action = isset( $post['wpsstudio_action'] ) && is_string( $post['wpsstudio_action'] ) ? $post['wpsstudio_action'] : '';
		$raw_id = $post['id'] ?? null;
		$id     = Validator::id( $raw_id );
		$nonce  = isset( $post['_wpnonce'] ) && is_string( $post['_wpnonce'] ) ? $post['_wpnonce'] : '';
		if ( ! is_string( $raw_id ) || ( '0' !== $raw_id && ! $id ) || ! in_array( $action, array( 'save', 'activate', 'deactivate', 'delete', 'pause', 'resume' ), true ) || ! wp_verify_nonce( $nonce, 'wpsstudio_' . $action . '_' . $id ) ) {
			wp_die( esc_html__( 'The security check failed. Reload this page and try again.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		$revision = Validator::id( $post['revision'] ?? '' );
		if ( 'save' === $action && ( ( $id && $revision ) || ( 0 === $id && '0' === ( $post['revision'] ?? null ) ) ) ) {
			$input  = array_intersect_key( $post, array_flip( array( 'title', 'description', 'code', 'type' ) ) );
			$result = $this->repository->save( $input, $id, $revision );
			if ( is_wp_error( $result ) ) {
				$this->draft = array_merge(
					$this->defaults(),
					$input,
					array(
						'id'       => $id,
						'revision' => $revision,
					)
				);
			} else {
				wp_safe_redirect(
					$this->url(
						array(
							'id'     => $result,
							'notice' => 'saved',
						)
					)
				);
				exit;
			}
		} elseif ( in_array( $action, array( 'pause', 'resume' ), true ) && 0 === $id ) {
			if ( 'resume' === $action && defined( 'WPSSTUDIO_SAFE_MODE' ) && WPSSTUDIO_SAFE_MODE ) {
				$result = new \WP_Error( 'constant', __( 'Remove WPSSTUDIO_SAFE_MODE from wp-config.php before resuming output here.', 'wp-snippet-studio' ) );
			} else {
				$value = 'pause' === $action ? '1' : '0';
				update_option( 'wpsstudio_safe_mode', $value, false );
				$result = get_option( 'wpsstudio_safe_mode' ) === $value ? true : new \WP_Error( 'database', __( 'The output setting could not be saved.', 'wp-snippet-studio' ) );
			}
		} elseif ( ! $id || ! $revision || 'save' === $action ) {
			$result = new \WP_Error( 'invalid', __( 'A valid snippet and revision are required.', 'wp-snippet-studio' ) );
		} elseif ( 'activate' === $action || 'deactivate' === $action ) {
			$result = $this->repository->status( $id, $revision, 'activate' === $action );
		} elseif ( 'delete' === $action ) {
			$result = $this->repository->delete( $id, $revision );
		} else {
			$result = new \WP_Error( 'invalid', __( 'The requested action is invalid.', 'wp-snippet-studio' ) );
		}
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
			return;
		}
		wp_safe_redirect( $this->url( array( 'notice' => $action ) ) );
		exit;
	}

	public function assets(): void {
		if ( ! self::management_request() || ! Permissions::can_manage() ) {
			return;
		}
		$dependencies = array( 'wp-a11y' );
		if ( $this->editing() ) {
			$settings             = wp_enqueue_code_editor(
				array(
					'type'       => 'text/html',
					'codemirror' => array(
						'lineNumbers'    => true,
						'indentUnit'     => 4,
						'indentWithTabs' => true,
						'lint'           => false,
					),
				)
			);
			$this->editor_enabled = false !== $settings;
			if ( $this->editor_enabled ) {
				$dependencies[] = 'code-editor';
			}
		}
		wp_enqueue_style( 'wpsstudio-admin', WPSSTUDIO_URL . 'assets/admin.css', array(), WPSSTUDIO_VERSION );
		wp_enqueue_script( 'wpsstudio-admin', WPSSTUDIO_URL . 'assets/admin.js', $dependencies, WPSSTUDIO_VERSION, true );
	}

	private function editing(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view selection.
		return null !== $this->draft || 'wpsstudio-new' === ( $_GET['page'] ?? '' ) || isset( $_GET['id'] );
	}

	private function defaults(): array {
		return array(
			'id'          => 0,
			'revision'    => 0,
			'title'       => '',
			'description' => '',
			'code'        => '',
			'type'        => 'html',
			'status'      => 'inactive',
			'signature'   => '',
		);
	}

	public function render(): void {
		if ( ! Permissions::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to view snippet content.', 'wp-snippet-studio' ), '', array( 'response' => 403 ) );
		}
		$editing = $this->editing();
		$counts  = Installer::ready() ? $this->repository->counts() : array(
			'html' => 0,
			'text' => 0,
			'all'  => 0,
		);
		$legacy  = Installer::ready() ? $this->repository->legacy_count() : 0;
		if ( $editing ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Capability-gated read-only lookup.
			$id = Validator::id( $_GET['id'] ?? '' );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Invalid read-only IDs must not become new records.
			if ( isset( $_GET['id'] ) && ! $id ) {
				wp_die( esc_html__( 'Snippet not found.', 'wp-snippet-studio' ), '', array( 'response' => 404 ) );
			}
			$snippet = $this->draft ?? ( $id ? $this->repository->get( $id ) : $this->defaults() );
			if ( ! $snippet ) {
				wp_die( esc_html__( 'Snippet not found.', 'wp-snippet-studio' ), '', array( 'response' => 404 ) );
			}
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Allow-listed read-only filtering.
			$type = isset( $_GET['type'] ) && is_string( $_GET['type'] ) && in_array( $_GET['type'], Validator::TYPES, true ) ? $_GET['type'] : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict read-only pagination.
			$current_page = max( 1, Validator::id( $_GET['paged'] ?? '' ) );
			$total        = $counts[ $type ? $type : 'all' ];
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
}
