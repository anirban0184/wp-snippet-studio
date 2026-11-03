<?php
/** Content-only field validation and explicit HTML allow-list. @package WPSnippetStudio */

namespace GlowingCoders\WPSnippetStudio;

defined( 'ABSPATH' ) || exit;

final class Validator {
	public const TYPES = array( 'html', 'text' );

	public static function snippet( array $input ): array|\WP_Error {
		foreach ( array( 'title', 'description', 'code', 'type' ) as $field ) {
			if ( ! isset( $input[ $field ] ) || ! is_string( $input[ $field ] ) ) {
				return new \WP_Error( 'invalid', __( 'Complete all snippet fields using valid values.', 'wp-snippet-studio' ) );
			}
		}
		$title = sanitize_text_field( $input['title'] );
		if ( '' === trim( $title ) || mb_strlen( $title ) > 150 ) {
			return new \WP_Error( 'title', __( 'Add a title of 1–150 characters.', 'wp-snippet-studio' ) );
		}
		if ( ! in_array( $input['type'], self::TYPES, true ) ) {
			return new \WP_Error( 'type', __( 'Choose HTML or plain text.', 'wp-snippet-studio' ) );
		}
		if ( strlen( $input['code'] ) > 1048576 ) {
			return new \WP_Error( 'size', __( 'Each snippet can contain up to 1 MB of content.', 'wp-snippet-studio' ) );
		}
		$code = 'html' === $input['type'] ? self::html( $input['code'] ) : wp_check_invalid_utf8( $input['code'] );
		if ( '' === trim( $code ) ) {
			return new \WP_Error( 'empty', __( 'Add supported content before saving.', 'wp-snippet-studio' ) );
		}
		return array(
			'title'       => $title,
			'description' => sanitize_textarea_field( $input['description'] ),
			'code'        => $code,
			'type'        => $input['type'],
			'scope'       => 'shortcode',
		);
	}

	/** No scripts, style attributes, forms, SVG, embeds or event handlers. */
	public static function html( string $content ): string {
		$basic = array(
			'class' => true,
			'id'    => true,
			'title' => true,
			'lang'  => true,
			'dir'   => true,
		);
		$tags  = array();
		foreach ( array( 'p', 'div', 'span', 'strong', 'em', 'b', 'i', 'u', 's', 'small', 'sub', 'sup', 'mark', 'blockquote', 'pre', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'hr', 'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr' ) as $tag ) {
			$tags[ $tag ] = $basic;
		}
		$tags['a']   = array_merge(
			$basic,
			array(
				'href' => true,
				'rel'  => true,
			)
		);
		$tags['img'] = array_merge(
			$basic,
			array(
				'src'     => true,
				'alt'     => true,
				'width'   => true,
				'height'  => true,
				'loading' => true,
			)
		);
		$tags['td']  = array_merge(
			$basic,
			array(
				'colspan' => true,
				'rowspan' => true,
			)
		);
		$tags['th']  = array_merge( $tags['td'], array( 'scope' => true ) );
		return wp_kses( $content, $tags, array( 'http', 'https', 'mailto', 'tel' ) );
	}

	public static function id( mixed $value ): int {
		if ( ! is_scalar( $value ) || ! preg_match( '/^[1-9]\d*$/D', (string) $value ) ) {
			return 0;
		}
		$validated = filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		return false === $validated ? 0 : $validated;
	}
}
