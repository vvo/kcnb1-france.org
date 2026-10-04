<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeAttributeParser
{
    /**
     * Protocols allowed during attribute parsing.
     * Extends WP's default list with protocols that legitimately appear in HTML
     * attributes but would otherwise be stripped by wp_kses_hair.
     */
    private static function allowed_protocols(): array {
        return array_merge( wp_allowed_protocols(), [ 'data', 'blob' ] );
    }

    /**
     * Entry point for modifying HTML attributes.
     */
    public static function modify( $tag, $target, $swaps = [], $replacements = []): string
    {
        $pieces = wp_html_split( $tag );

        foreach ( $pieces as &$piece ) {
            // Skip empty strings, text nodes, and comments.
            if ( $piece === '' || $piece[0] !== '<' || $piece[1] === '!' ) {
                continue;
            }

            // Skip closing tags.
            if ( $piece[1] === '/' ) {
                continue;
            }

            // Extract tag name; skip if not parseable.
            if ( ! preg_match( '/^<([a-z0-9]+)/i', $piece, $tag_name_match ) ) {
                continue;
            }

            // Skip non-matching tags.
            if ( strcasecmp( $tag_name_match[1], $target ) !== 0 ) {
                continue;
            }

            // This piece matches $target — apply modifications.
            $attr_string = preg_replace( '/^<[a-z0-9]+\s*|(\s?\/?>)$/i', '', $piece );
            $attrs = wp_kses_hair( $attr_string, self::allowed_protocols() );

            // 1. Handle Deletions first to clear the deck
            foreach ( $replacements as $attr_name => $new_value ) {
                if ( is_null( $new_value ) ) {
                    unset( $attrs[$attr_name] );
                }
            }

            // 2. Handle Swaps
            foreach ( $swaps as $old => $new ) {
                if ( isset( $attrs[$old] ) ) {
                    $attrs[$new] = $attrs[$old];
                    $attrs[$new]['name'] = $new;
                    unset( $attrs[$old] );
                }
            }

            // 3. Handle Replacements & Additions
            foreach ( $replacements as $attr_name => $new_value ) {
                if ( ! is_null( $new_value ) ) {
                    $attrs[$attr_name] = [
                        'name'  => $attr_name,
                        'value' => $new_value,
                    ];
                }
            }

            // Rebuild this tag.
            $rebuilt = '<' . $tag_name_match[1];
            foreach ( $attrs as $name => $data ) {
                $value = esc_attr( $data['value'] );
                $rebuilt .= " {$name}=\"{$value}\"";
            }
            $rebuilt .= ( strpos( $piece, '/>' ) !== false ) ? ' />' : '>';

            $piece = $rebuilt;
        }
        unset( $piece );

        return implode( '', $pieces );
    }

    /**
     * Entry point for retrieving attributes.
     * Returns a single value (string|null) if a string name is provided,
     * or an associative array if a regex is provided.
     */
    public static function get( $tag, $target, $query ) // : string|array|null TODO: uncomment when PHP7.4 is no longer supported
    {
        $pieces = wp_html_split( $tag );

        foreach ( $pieces as $piece ) {
            // Skip empty strings, text nodes, and comments.
            if ( $piece === '' || $piece[0] !== '<' || $piece[1] === '!' ) {
                continue;
            }

            // Skip closing tags.
            if ( $piece[1] === '/' ) {
                continue;
            }

            // Extract tag name.
            if ( ! preg_match( '/^<([a-z0-9]+)/i', $piece, $tag_name_match ) ) {
                continue;
            }

            // Skip non-matching tags.
            if ( strcasecmp( $tag_name_match[1], $target ) !== 0 ) {
                continue;
            }

            // Found a matching tag — parse its attributes.
            $attr_string = preg_replace( '/^<[a-z0-9]+\s*|(\s?\/?>)$/i', '', $piece );
            $attrs = wp_kses_hair( $attr_string, self::allowed_protocols() );

            // Single attribute request
            if ( is_string( $query ) && strpos( $query, '/' ) !== 0 ) {
                return $attrs[$query]['value'] ?? null;
            }

            // Regex request
            $results = [];
            foreach ( $attrs as $name => $data ) {
                if ( preg_match( $query, $name ) ) {
                    $results[$name] = $data['value'];
                }
            }
            return $results;
        }

        // No matching tag found.
        return is_string( $query ) && strpos( $query, '/' ) !== 0 ? null : [];
    }

    /**
     * Helper to rename attributes.
     * Supports:
     * - rename( $tag, 'src', 'data-src' )
     * - rename( $tag, ['src' => 'data-src', 'srcset' => 'data-srcset'] )
     */
    public static function rename( $tag, $target, $old_name, $new_name = null ): string {
        if ( is_array( $old_name ) ) {
            $swaps = $old_name;
        } else {
            $swaps = [ $old_name => $new_name ];
        }

        return self::modify( $tag, $target, $swaps );
    }

    /**
     * Helper to remove one or more attributes.
     * Supports:
     * - remove( $tag, 'style' )
     * - remove( $tag, ['style', 'onclick', 'onerror'] )
     */
    public static function remove( $tag, $target, $attributes ): string {
        // array_fill_keys expects an array; (array) cast handles single strings safely.
        $replacements = array_fill_keys( (array) $attributes, null );

        return self::modify( $tag, $target, [], $replacements );
    }

    /**
     * Helper to replace or add attribute values.
     * Supports:
     * - replace( $tag, 'class', 'lazyload' )
     * - replace( $tag, ['class' => 'lazyload', 'src' => 'placeholder.png'] )
     */
    public static function replace( $tag, $target, $attribute, $value = null ): string {
        if ( is_array( $attribute ) ) {
            $replacements = $attribute;
        } else {
            $replacements = [ $attribute => $value ];
        }

        return self::modify( $tag, $target, [], $replacements );
    }

    /**
     * Helper to rebuild a HTML tag from a string
     * Can be used to fix minified HTML that is missing quotes around attributes
     * Supports:
     * - replace( $tag )
     */
    public static function rebuild( $tag, $target ): string
    {
        return self::modify( $tag, $target, [], [], true );
    }

    /**
     * Retrieves a single attribute value.
     * @param string $tag The HTML tag string.
     * @param string $name The attribute name to find.
     * @return string|null The attribute value or null if not found.
     */
    public static function get_attribute( string $tag, string $target, string $name ): ?string
    {
        $result = self::get( $tag, $target, $name );
        return is_string( $result ) ? $result : null;
    }

    /**
     * Retrieves a list of attributes matching a regex or prefix.
     * @param string $tag The HTML tag string.
     * @param string $regex The regex pattern to match keys against (e.g., '/^data-/').
     * @return array Associative array of [name => value].
     */
    public static function get_attributes( string $tag, string $target, string $regex ): array
    {
        $result = self::get( $tag, $target, $regex );
        return is_array( $result ) ? $result : [];
    }

    /**
     * Modifies a single specific attribute via callback.
     * If callback returns null, the attribute is removed.
     */
    public static function modify_attribute( string $tag, string $target, string $name, callable $callback ): string
    {
        $value = self::get_attribute( $tag, $target, $name );
        $new_value = $callback( $name, $value );

        if ( $new_value === $value ) {
            return $tag;
        }

        return self::modify( $tag, $target, [], [ $name => $new_value ] );
    }

    /**
     * Modifies multiple attributes matching a regex via callback.
     */
    public static function modify_attributes( string $tag, string $target, string $regex, callable $callback ): string
    {
        $attrs = self::get_attributes( $tag, $target, $regex );
        $replacements = [];

        foreach ( $attrs as $name => $value ) {
            $new_value = $callback( $name, $value );

            if ( $new_value !== $value ) {
                $replacements[ $name ] = $new_value;
            }
        }

        return empty( $replacements ) ? $tag : self::modify( $tag, $target, [], $replacements );
    }
}
