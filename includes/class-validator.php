<?php
/** Allow-lists and non-executing syntax validation. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Validator {
	public const TYPES  = array( 'php', 'css', 'js', 'html', 'text' );
	public const SCOPES = array( 'everywhere', 'frontend', 'admin', 'manual' );

	/** Return a normalized record or a safe error without source code. */
	public static function snippet( array $input ): array|\WP_Error {
		foreach ( array( 'title', 'description', 'code', 'type', 'scope', 'priority' ) as $field ) {
			if ( ! isset( $input[ $field ] ) || ! is_scalar( $input[ $field ] ) ) {
				return new \WP_Error( 'invalid', __( 'Complete all snippet fields using valid values.', 'wp-snippet-studio' ) );
			}
		}
		$title = sanitize_text_field( (string) $input['title'] );
		$type  = (string) $input['type'];
		$scope = (string) $input['scope'];
		$code  = (string) $input['code'];
		if ( '' === trim( $title ) || mb_strlen( $title ) > 150 ) {
			return new \WP_Error( 'title', __( 'Add a title of 1–150 characters.', 'wp-snippet-studio' ) );
		}
		if ( ! in_array( $type, self::TYPES, true ) || ! in_array( $scope, self::SCOPES, true ) ) {
			return new \WP_Error( 'type', __( 'Choose a supported snippet type and run location.', 'wp-snippet-studio' ) );
		}
		$priority = self::priority( $input['priority'] );
		if ( is_wp_error( $priority ) ) {
			return $priority;
		}
		if ( '' === trim( $code ) ) {
			return new \WP_Error( 'empty', __( 'Add snippet content before saving.', 'wp-snippet-studio' ) );
		}
		if ( 'php' === $type ) {
			$valid = self::php( $code );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		} elseif ( 'js' === $type && preg_match( '~<\s*/?\s*script\b~i', $code ) ) {
			return new \WP_Error( 'tags', __( 'Enter JavaScript without script tags, including closing tags in strings or comments.', 'wp-snippet-studio' ) );
		} elseif ( 'css' === $type && preg_match( '~<\s*/?\s*style\b~i', $code ) ) {
			return new \WP_Error( 'tags', __( 'Enter CSS without style tags, including closing tags in strings or comments.', 'wp-snippet-studio' ) );
		} elseif ( 'html' === $type ) {
			$code = wp_kses_post( $code );
		}
		return array(
			'title'       => $title,
			'description' => sanitize_textarea_field( (string) $input['description'] ),
			'code'        => $code,
			'type'        => $type,
			'scope'       => 'php' === $type ? $scope : ( in_array( $type, array( 'css', 'js' ), true ) ? 'frontend' : 'shortcode' ),
			'priority'    => $priority,
		);
	}

	public static function priority( mixed $value ): int|\WP_Error {
		if ( ! is_scalar( $value ) || ! preg_match( '/^-?\d{1,4}$/D', (string) $value ) ) {
			return new \WP_Error( 'priority', __( 'Priority must be a whole number from -9999 to 9999.', 'wp-snippet-studio' ) );
		}
		return (int) $value;
	}

	public static function id( mixed $value ): int {
		return is_scalar( $value ) && preg_match( '/^[1-9]\d*$/D', (string) $value ) && (float) $value <= PHP_INT_MAX ? (int) $value : 0;
	}

	public static function php( string $code ): true|\WP_Error {
		if ( ! function_exists( 'token_get_all' ) || ! defined( 'TOKEN_PARSE' ) ) {
			return new \WP_Error( 'validator', __( 'PHP validation is unavailable. Enable PHP tokenizer before using PHP snippets.', 'wp-snippet-studio' ) );
		}
		try {
			$tokens = token_get_all( "<?php\n" . $code, TOKEN_PARSE );
			foreach ( $tokens as $index => $token ) {
				if ( is_array( $token ) && ( ( 0 !== $index && in_array( $token[0], array( T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO ), true ) ) || in_array( $token[0], array( T_CLOSE_TAG, T_INLINE_HTML, T_HALT_COMPILER, T_NAMESPACE ), true ) ) ) {
					return new \WP_Error( 'php_tags', __( 'Use a PHP code body without PHP tags, namespaces or __halt_compiler.', 'wp-snippet-studio' ) );
				}
			}
		} catch ( \Throwable $error ) {
			return new \WP_Error( 'syntax', sprintf( /* translators: %d: snippet line number. */ __( 'PHP syntax is invalid near line %d. Review the code before saving.', 'wp-snippet-studio' ), max( 1, $error->getLine() - 1 ) ) );
		}
		return true;
	}
}
