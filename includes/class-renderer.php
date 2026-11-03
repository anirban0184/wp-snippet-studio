<?php
/** Render approved content only; never interpret code or nested shortcodes. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Renderer {
	public function __construct( private Repository $repository ) {}

	public function register(): void {
		add_shortcode( 'wpsstudio', array( $this, 'shortcode' ) );
	}

	public static function paused(): bool {
		return ( defined( 'WPSSTUDIO_SAFE_MODE' ) && WPSSTUDIO_SAFE_MODE ) || '1' === get_option( 'wpsstudio_safe_mode', '0' );
	}

	public function shortcode( array|string $attributes = array() ): string {
		if ( is_multisite() || ! Installer::ready() || self::paused() || ! is_array( $attributes ) ) {
			return '';
		}
		$id = Validator::id( $attributes['id'] ?? '' );
		if ( ! $id ) {
			return '';
		}
		$row = $this->repository->get( $id );
		if ( ! $row || 'active' !== $row['status'] || ! $this->repository->valid_content( $row ) ) {
			return '';
		}
		return 'html' === $row['type'] ? Validator::html( $row['code'] ) : nl2br( esc_html( $row['code'] ), false );
	}
}
