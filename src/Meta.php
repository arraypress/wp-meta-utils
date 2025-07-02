<?php
/**
 * Meta Utility Class
 *
 * Provides utility functions for working with individual WordPress meta entries,
 * including CRUD operations, type casting, and essential value manipulation.
 *
 * @package ArrayPress\MetaUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\MetaUtils;

/**
 * Meta Class
 *
 * Core operations for working with individual WordPress meta entries.
 */
class Meta {

	// ========================================
	// Core Operations
	// ========================================

	/**
	 * Check if meta key exists for an object.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to check.
	 *
	 * @return bool True if meta key exists.
	 */
	public static function exists( string $meta_type, int $object_id, string $meta_key ): bool {
		$meta = get_metadata( $meta_type, $object_id, $meta_key, true );

		return $meta !== '';
	}

	/**
	 * Get meta value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to retrieve.
	 * @param bool   $single    Whether to return single value.
	 *
	 * @return mixed Meta value or null if not found.
	 */
	public static function get( string $meta_type, int $object_id, string $meta_key, bool $single = true ) {
		$value = get_metadata( $meta_type, $object_id, $meta_key, $single );

		// Handle empty string as null for single values
		if ( $single && $value === '' ) {
			return null;
		}

		// Handle empty array as null for multiple values
		if ( ! $single && empty( $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get meta value with default.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to retrieve.
	 * @param mixed  $default   Default value if meta doesn't exist.
	 *
	 * @return mixed Meta value or default.
	 */
	public static function get_with_default( string $meta_type, int $object_id, string $meta_key, $default ) {
		$value = get_metadata( $meta_type, $object_id, $meta_key, true );

		return $value !== '' ? $value : $default;
	}

	/**
	 * Get meta value with type casting.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to retrieve.
	 * @param string $cast_type Type to cast to ('int', 'float', 'bool', 'array', 'string').
	 * @param mixed  $default   Default value if meta doesn't exist.
	 *
	 * @return mixed Meta value cast to specified type.
	 */
	public static function get_cast( string $meta_type, int $object_id, string $meta_key, string $cast_type, $default = null ) {
		$value = get_metadata( $meta_type, $object_id, $meta_key, true );

		if ( $value === '' && $default !== null ) {
			return self::cast_value( $default, $cast_type );
		}

		return self::cast_value( $value, $cast_type );
	}

	/**
	 * Update meta value.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key to update.
	 * @param mixed  $meta_value New meta value.
	 *
	 * @return bool True on success.
	 */
	public static function update( string $meta_type, int $object_id, string $meta_key, $meta_value ): bool {
		return update_metadata( $meta_type, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Update meta only if value has changed.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key to update.
	 * @param mixed  $meta_value New meta value.
	 *
	 * @return bool True if value was changed.
	 */
	public static function update_if_changed( string $meta_type, int $object_id, string $meta_key, $meta_value ): bool {
		$current_value = get_metadata( $meta_type, $object_id, $meta_key, true );

		if ( $current_value !== $meta_value ) {
			return update_metadata( $meta_type, $object_id, $meta_key, $meta_value );
		}

		return false;
	}

	/**
	 * Delete meta.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to delete.
	 *
	 * @return bool True on success.
	 */
	public static function delete( string $meta_type, int $object_id, string $meta_key ): bool {
		if ( empty( $object_id ) ) {
			return false;
		}

		return delete_metadata( $meta_type, $object_id, $meta_key );
	}

	// ========================================
	// Numeric Operations
	// ========================================

	/**
	 * Increment numeric meta value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to increment.
	 * @param int    $amount    Amount to increment.
	 *
	 * @return int|bool New value on success, false on failure.
	 */
	public static function increment( string $meta_type, int $object_id, string $meta_key, int $amount = 1 ) {
		$current_value = (int) get_metadata( $meta_type, $object_id, $meta_key, true );
		$new_value     = $current_value + $amount;
		$success       = update_metadata( $meta_type, $object_id, $meta_key, $new_value );

		return $success ? $new_value : false;
	}

	/**
	 * Decrement numeric meta value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to decrement.
	 * @param int    $amount    Amount to decrement.
	 *
	 * @return int|bool New value on success, false on failure.
	 */
	public static function decrement( string $meta_type, int $object_id, string $meta_key, int $amount = 1 ) {
		$current_value = (int) get_metadata( $meta_type, $object_id, $meta_key, true );
		$new_value     = $current_value - abs( $amount );
		$success       = update_metadata( $meta_type, $object_id, $meta_key, $new_value );

		return $success ? $new_value : false;
	}

	// ========================================
	// Array Operations
	// ========================================

	/**
	 * Check if meta array contains value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 * @param mixed  $value     Value to check for.
	 *
	 * @return bool True if value exists in array.
	 */
	public static function array_contains( string $meta_type, int $object_id, string $meta_key, $value ): bool {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );

		return is_array( $current_array ) && in_array( $value, $current_array, true );
	}

	/**
	 * Append value to meta array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 * @param mixed  $value     Value to append.
	 *
	 * @return bool True on success.
	 */
	public static function array_append( string $meta_type, int $object_id, string $meta_key, $value ): bool {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );
		if ( ! is_array( $current_array ) ) {
			$current_array = [];
		}
		$current_array[] = $value;

		return update_metadata( $meta_type, $object_id, $meta_key, $current_array );
	}

	/**
	 * Remove first occurrence of value from meta array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 * @param mixed  $value     Value to remove.
	 *
	 * @return bool True if value was found and removed.
	 */
	public static function array_remove( string $meta_type, int $object_id, string $meta_key, $value ): bool {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );
		if ( ! is_array( $current_array ) ) {
			return false;
		}

		$key = array_search( $value, $current_array, true );
		if ( $key === false ) {
			return false;
		}

		unset( $current_array[ $key ] );

		return update_metadata( $meta_type, $object_id, $meta_key, array_values( $current_array ) );
	}

	/**
	 * Remove all occurrences of value from meta array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 * @param mixed  $value     Value to remove.
	 *
	 * @return bool True if any values were removed.
	 */
	public static function array_remove_all( string $meta_type, int $object_id, string $meta_key, $value ): bool {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );
		if ( ! is_array( $current_array ) ) {
			return false;
		}

		$original_count = count( $current_array );
		$current_array  = array_filter( $current_array, function ( $item ) use ( $value ) {
			return $item !== $value;
		} );

		if ( count( $current_array ) < $original_count ) {
			return update_metadata( $meta_type, $object_id, $meta_key, array_values( $current_array ) );
		}

		return false;
	}

	/**
	 * Remove duplicate values from meta array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 *
	 * @return bool True if duplicates were removed.
	 */
	public static function array_unique( string $meta_type, int $object_id, string $meta_key ): bool {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );
		if ( ! is_array( $current_array ) ) {
			return false;
		}

		$original_count = count( $current_array );
		$unique_array   = array_values( array_unique( $current_array, SORT_REGULAR ) );

		if ( count( $unique_array ) < $original_count ) {
			return update_metadata( $meta_type, $object_id, $meta_key, $unique_array );
		}

		return false;
	}

	/**
	 * Get count of items in meta array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key of the array.
	 *
	 * @return int Count of items in array.
	 */
	public static function array_count( string $meta_type, int $object_id, string $meta_key ): int {
		$current_array = get_metadata( $meta_type, $object_id, $meta_key, true );

		return is_array( $current_array ) ? count( $current_array ) : 0;
	}

	// ========================================
	// Nested Operations
	// ========================================

	/**
	 * Get a nested value from an array meta using "dot" notation.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key containing the array.
	 * @param string $key       Key using dot notation (e.g., 'parent.child').
	 * @param mixed  $default   Default value if key doesn't exist.
	 *
	 * @return mixed Value at the specified key or default.
	 */
	public static function get_nested( string $meta_type, int $object_id, string $meta_key, string $key, $default = null ) {
		$array = self::get( $meta_type, $object_id, $meta_key );
		if ( ! is_array( $array ) ) {
			return $default;
		}

		$keys = explode( '.', $key );
		foreach ( $keys as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return $default;
			}
			$array = $array[ $segment ];
		}

		return $array;
	}

	/**
	 * Set a nested value in an array meta using "dot" notation.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key containing the array.
	 * @param string $key       Key using dot notation (e.g., 'parent.child').
	 * @param mixed  $value     Value to set.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function set_nested( string $meta_type, int $object_id, string $meta_key, string $key, $value ): bool {
		$array = self::get( $meta_type, $object_id, $meta_key );
		if ( ! is_array( $array ) ) {
			$array = [];
		}

		$keys    = explode( '.', $key );
		$current = &$array;
		foreach ( $keys as $segment ) {
			if ( ! is_array( $current ) ) {
				$current = [];
			}
			$current = &$current[ $segment ];
		}
		$current = $value;

		return self::update( $meta_type, $object_id, $meta_key, $array );
	}

	/**
	 * Remove a nested key from an array meta using "dot" notation.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key containing the array.
	 * @param string $key       Key using dot notation (e.g., 'parent.child').
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function remove_nested( string $meta_type, int $object_id, string $meta_key, string $key ): bool {
		$array = self::get( $meta_type, $object_id, $meta_key );
		if ( ! is_array( $array ) ) {
			return false;
		}

		$keys     = explode( '.', $key );
		$last_key = array_pop( $keys );
		$current  = &$array;

		foreach ( $keys as $segment ) {
			if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
				return false;
			}
			$current = &$current[ $segment ];
		}

		if ( is_array( $current ) && array_key_exists( $last_key, $current ) ) {
			unset( $current[ $last_key ] );

			return self::update( $meta_type, $object_id, $meta_key, $array );
		}

		return false;
	}

	// ========================================
	// JSON Operations
	// ========================================

	/**
	 * Get meta value as JSON array.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to retrieve.
	 * @param array  $default   Default value if meta doesn't exist or isn't valid JSON.
	 *
	 * @return array JSON decoded array or default.
	 */
	public static function get_json( string $meta_type, int $object_id, string $meta_key, array $default = [] ): array {
		$value = get_metadata( $meta_type, $object_id, $meta_key, true );

		if ( $value === '' ) {
			return $default;
		}

		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );

			return ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) ? $decoded : $default;
		}

		return $default;
	}

	/**
	 * Set meta value as JSON.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to update.
	 * @param array  $value     Array to store as JSON.
	 *
	 * @return bool True on success.
	 */
	public static function set_json( string $meta_type, int $object_id, string $meta_key, array $value ): bool {
		return update_metadata( $meta_type, $object_id, $meta_key, $value );
	}

	// ========================================
	// Boolean Operations
	// ========================================

	/**
	 * Check if meta value is truthy.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to check.
	 * @param bool   $default   Default value if meta doesn't exist.
	 *
	 * @return bool True if meta value is truthy.
	 */
	public static function is_truthy( string $meta_type, int $object_id, string $meta_key, bool $default = false ): bool {
		$meta_value = get_metadata( $meta_type, $object_id, $meta_key, true );

		if ( $meta_value !== '' ) {
			return (bool) $meta_value;
		}

		return $default;
	}

	/**
	 * Toggle boolean meta value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to toggle.
	 *
	 * @return bool|null New value on success, null on failure.
	 */
	public static function toggle( string $meta_type, int $object_id, string $meta_key ): ?bool {
		$value = self::get_cast( $meta_type, $object_id, $meta_key, 'bool', false );

		return update_metadata( $meta_type, $object_id, $meta_key, ! $value ) ? ! $value : null;
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Get the type of a meta value.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to check.
	 *
	 * @return string|null The type of the meta value or null if not found.
	 */
	public static function get_type( string $meta_type, int $object_id, string $meta_key ): ?string {
		$value = self::get( $meta_type, $object_id, $meta_key );

		return $value !== null ? gettype( $value ) : null;
	}

	/**
	 * Get the size of a meta value in bytes.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to check.
	 *
	 * @return int Size in bytes, 0 if meta doesn't exist.
	 */
	public static function get_size( string $meta_type, int $object_id, string $meta_key ): int {
		$value = self::get( $meta_type, $object_id, $meta_key );

		return $value !== null ? strlen( maybe_serialize( $value ) ) : 0;
	}

	/**
	 * Check if a meta value is of a specific type.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key to check.
	 * @param string $type      Expected type ('string', 'array', 'integer', 'boolean', etc.).
	 *
	 * @return bool True if the meta value matches the expected type, false otherwise.
	 */
	public static function is_type( string $meta_type, int $object_id, string $meta_key, string $type ): bool {
		$actual_type = self::get_type( $meta_type, $object_id, $meta_key );

		return $actual_type !== null && $actual_type === $type;
	}

	/**
	 * Check if a meta value is large (exceeds specified size).
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key to check.
	 * @param int    $size_limit Size limit in bytes. Default 1MB (1048576 bytes).
	 *
	 * @return bool True if the meta exceeds the size limit, false otherwise.
	 */
	public static function is_large( string $meta_type, int $object_id, string $meta_key, int $size_limit = 1048576 ): bool {
		return self::get_size( $meta_type, $object_id, $meta_key ) > $size_limit;
	}

	/**
	 * Migrate meta from one key to another.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id  Object ID.
	 * @param string $old_key    Old meta key.
	 * @param string $new_key    New meta key.
	 * @param bool   $delete_old Whether to delete old key after migration.
	 *
	 * @return bool True on success.
	 */
	public static function migrate_key( string $meta_type, int $object_id, string $old_key, string $new_key, bool $delete_old = true ): bool {
		$value   = get_metadata( $meta_type, $object_id, $old_key, true );
		$updated = update_metadata( $meta_type, $object_id, $new_key, $value );

		if ( $updated && $delete_old ) {
			return delete_metadata( $meta_type, $object_id, $old_key );
		}

		return $updated;
	}

	// ========================================
	// Private Helper Methods
	// ========================================

	/**
	 * Cast value to specific type.
	 *
	 * @param mixed  $value Value to cast.
	 * @param string $type  Type to cast to.
	 *
	 * @return mixed Casted value.
	 */
	private static function cast_value( $value, string $type ) {
		switch ( strtolower( $type ) ) {
			case 'int':
			case 'integer':
				return (int) $value;
			case 'float':
			case 'double':
				return (float) $value;
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'array':
				return (array) $value;
			case 'string':
				return (string) $value;
			default:
				return $value;
		}
	}

}