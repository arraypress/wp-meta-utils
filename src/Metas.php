<?php
/**
 * Metas Utility Class
 *
 * Provides utility functions for working with multiple WordPress meta entries,
 * including bulk operations, relationship management, and essential pattern-based operations.
 *
 * @package ArrayPress\MetaUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\MetaUtils;

/**
 * Metas Class
 *
 * Operations for working with multiple WordPress meta entries and relationships.
 */
class Metas {

	// ========================================
	// Core Operations
	// ========================================

	/**
	 * Get multiple meta values for a single object.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param array  $meta_keys Array of meta keys to retrieve.
	 * @param bool   $single    Whether to return single values.
	 *
	 * @return array Array of meta values keyed by meta key.
	 */
	public static function get( string $meta_type, int $object_id, array $meta_keys, bool $single = true ): array {
		if ( empty( $meta_keys ) ) {
			return [];
		}

		$meta_values = [];
		foreach ( $meta_keys as $meta_key ) {
			$value = Meta::get( $meta_type, $object_id, $meta_key, $single );
			if ( $value !== null ) {
				$meta_values[ $meta_key ] = $value;
			}
		}

		return $meta_values;
	}

	/**
	 * Get all meta for an object.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 *
	 * @return array Array of all meta for the object.
	 */
	public static function get_all( string $meta_type, int $object_id ): array {
		return get_metadata( $meta_type, $object_id ) ?: [];
	}

	/**
	 * Update multiple meta values for a single object.
	 *
	 * @param string $meta_type      Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id      Object ID.
	 * @param array  $meta_values    Array of meta key-value pairs.
	 * @param bool   $skip_unchanged Whether to skip unchanged values.
	 *
	 * @return array Array of successfully updated meta keys.
	 */
	public static function update( string $meta_type, int $object_id, array $meta_values, bool $skip_unchanged = true ): array {
		if ( empty( $meta_values ) ) {
			return [];
		}

		$updated = [];
		foreach ( $meta_values as $meta_key => $meta_value ) {
			if ( $skip_unchanged ) {
				$success = Meta::update_if_changed( $meta_type, $object_id, $meta_key, $meta_value );
			} else {
				$success = Meta::update( $meta_type, $object_id, $meta_key, $meta_value );
			}

			if ( $success ) {
				$updated[] = $meta_key;
			}
		}

		return $updated;
	}

	/**
	 * Delete multiple meta keys for a single object.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param array  $meta_keys Array of meta keys to delete.
	 *
	 * @return int Number of meta keys successfully deleted.
	 */
	public static function delete( string $meta_type, int $object_id, array $meta_keys ): int {
		if ( empty( $meta_keys ) ) {
			return 0;
		}

		return array_reduce( $meta_keys, function ( $count, $meta_key ) use ( $meta_type, $object_id ) {
			return $count + ( Meta::delete( $meta_type, $object_id, $meta_key ) ? 1 : 0 );
		}, 0 );
	}

	// ========================================
	// Object Sync
	// ========================================

	/**
	 * Sync object properties with WordPress metadata.
	 *
	 * @param object $object      Object to sync.
	 * @param int    $object_id   WordPress object ID.
	 * @param array  $field_map   Property to meta key mapping.
	 * @param string $object_type Object type (post, user, term, comment).
	 * @param string $direction   Sync direction ('to_meta', 'from_meta', 'both').
	 *
	 * @return bool Success status.
	 */
	public static function sync_with_meta( object $object, int $object_id, array $field_map, string $object_type = 'post', string $direction = 'to_meta' ): bool {
		if ( empty( $object_id ) || empty( $field_map ) ) {
			return false;
		}

		$success = true;

		foreach ( $field_map as $property => $meta_key ) {
			if ( $direction === 'to_meta' || $direction === 'both' ) {
				if ( property_exists( $object, $property ) ) {
					$result = update_metadata( $object_type, $object_id, $meta_key, $object->$property );
					if ( ! $result ) {
						$success = false;
					}
				}
			}

			if ( $direction === 'from_meta' || $direction === 'both' ) {
				$meta_value = get_metadata( $object_type, $object_id, $meta_key, true );
				if ( $meta_value !== false ) {
					$object->$property = $meta_value;
				}
			}
		}

		return $success;
	}

	// ========================================
	// Backup & Restore
	// ========================================

	/**
	 * Backup multiple meta values for an object.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param array  $meta_keys Array of meta keys to backup.
	 *
	 * @return array Array of meta keys and their current values.
	 */
	public static function backup( string $meta_type, int $object_id, array $meta_keys ): array {
		return self::get( $meta_type, $object_id, $meta_keys );
	}

	/**
	 * Restore meta values from backup.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id Object ID.
	 * @param array  $backup    Array of meta keys and values to restore.
	 *
	 * @return array Array of successfully restored meta keys.
	 */
	public static function restore( string $meta_type, int $object_id, array $backup ): array {
		return self::update( $meta_type, $object_id, $backup, false );
	}

	// ========================================
	// Pattern Operations
	// ========================================

	/**
	 * Get meta by prefix.
	 *
	 * @param string $meta_type   Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id   Object ID.
	 * @param string $prefix      Prefix to search for.
	 * @param bool   $with_values Whether to include values.
	 *
	 * @return array Array of matching meta keys and values.
	 */
	public static function get_by_prefix( string $meta_type, int $object_id, string $prefix, bool $with_values = true ): array {
		$all_meta = self::get_all( $meta_type, $object_id );
		$filtered = [];

		foreach ( $all_meta as $key => $value ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$filtered[ $key ] = $with_values ? ( $value[0] ?? $value ) : $key;
			}
		}

		return $filtered;
	}

	/**
	 * Delete meta by prefix.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 * @param string $prefix    Prefix to search for.
	 *
	 * @return int Number of meta entries deleted.
	 */
	public static function delete_by_prefix( string $meta_type, string $prefix ): int {
		global $wpdb;

		$meta_table = self::get_meta_table_name( $meta_type );
		if ( ! $meta_table ) {
			return 0;
		}

		$sql_pattern = $wpdb->esc_like( $prefix ) . '%';
		$meta_keys   = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT meta_key FROM {$meta_table} WHERE meta_key LIKE %s",
			$sql_pattern
		) );

		$count = 0;
		foreach ( $meta_keys as $meta_key ) {
			$deleted = $wpdb->delete( $meta_table, [ 'meta_key' => $meta_key ], [ '%s' ] );
			if ( $deleted !== false ) {
				$count += $deleted;
			}
		}

		return $count;
	}

	// ========================================
	// Bulk Operations
	// ========================================

	/**
	 * Get meta values for multiple objects.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param array  $object_ids Array of object IDs.
	 * @param string $meta_key   Meta key to retrieve.
	 *
	 * @return array Array of meta values keyed by object ID.
	 */
	public static function bulk_get( string $meta_type, array $object_ids, string $meta_key ): array {
		if ( empty( $object_ids ) ) {
			return [];
		}

		$meta_values = [];
		foreach ( $object_ids as $object_id ) {
			$value = Meta::get( $meta_type, $object_id, $meta_key );
			if ( $value !== null ) {
				$meta_values[ $object_id ] = $value;
			}
		}

		return $meta_values;
	}

	/**
	 * Update meta for multiple objects.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param array  $object_ids Array of object IDs.
	 * @param string $meta_key   Meta key to update.
	 * @param mixed  $meta_value Meta value to set.
	 *
	 * @return array Array of results keyed by object ID.
	 */
	public static function bulk_update( string $meta_type, array $object_ids, string $meta_key, $meta_value ): array {
		if ( empty( $object_ids ) ) {
			return [];
		}

		$results = [];
		foreach ( $object_ids as $object_id ) {
			$results[ $object_id ] = Meta::update( $meta_type, $object_id, $meta_key, $meta_value );
		}

		return $results;
	}

	/**
	 * Delete meta for multiple objects.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param array  $object_ids Array of object IDs.
	 * @param string $meta_key   Meta key to delete.
	 *
	 * @return array Array of results keyed by object ID.
	 */
	public static function bulk_delete( string $meta_type, array $object_ids, string $meta_key ): array {
		if ( empty( $object_ids ) ) {
			return [];
		}

		$results = [];
		foreach ( $object_ids as $object_id ) {
			$results[ $object_id ] = Meta::delete( $meta_type, $object_id, $meta_key );
		}

		return $results;
	}

	// ========================================
	// Analysis & Search
	// ========================================

	/**
	 * Find large meta values.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param int    $object_id  Object ID.
	 * @param int    $size_limit Size limit in bytes.
	 *
	 * @return array Array of large meta keys and their sizes.
	 */
	public static function find_large( string $meta_type, int $object_id, int $size_limit = 1048576 ): array {
		$all_meta   = self::get_all( $meta_type, $object_id );
		$large_meta = [];

		foreach ( $all_meta as $key => $value ) {
			$size = strlen( maybe_serialize( $value ) );
			if ( $size > $size_limit ) {
				$large_meta[ $key ] = $size;
			}
		}

		return $large_meta;
	}

	/**
	 * Find objects by meta value.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param string $meta_key   Meta key to search.
	 * @param mixed  $meta_value Meta value to match.
	 * @param string $compare    Comparison operator ('=', '!=', '>', '<', 'LIKE').
	 *
	 * @return array Array of object IDs.
	 */
	public static function find_objects_by_value( string $meta_type, string $meta_key, $meta_value, string $compare = '=' ): array {
		global $wpdb;

		$meta_table = self::get_meta_table_name( $meta_type );
		if ( ! $meta_table ) {
			return [];
		}

		$id_column = $meta_type . '_id';

		switch ( strtoupper( $compare ) ) {
			case 'LIKE':
				$query = $wpdb->prepare(
					"SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = %s AND meta_value LIKE %s",
					$meta_key, '%' . $wpdb->esc_like( $meta_value ) . '%'
				);
				break;
			case '!=':
			case '<>':
				$query = $wpdb->prepare(
					"SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = %s AND meta_value != %s",
					$meta_key, $meta_value
				);
				break;
			case '>':
			case '<':
			case '>=':
			case '<=':
				$query = $wpdb->prepare(
					"SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = %s AND meta_value {$compare} %s",
					$meta_key, $meta_value
				);
				break;
			default:
				$query = $wpdb->prepare(
					"SELECT DISTINCT {$id_column} FROM {$meta_table} WHERE meta_key = %s AND meta_value = %s",
					$meta_key, $meta_value
				);
		}

		return $wpdb->get_col( $query );
	}

	/**
	 * Compare meta values across multiple objects.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param array  $object_ids Array of object IDs to compare.
	 * @param string $meta_key   Meta key to compare.
	 *
	 * @return array Array with comparison results and statistics.
	 */
	public static function compare_values( string $meta_type, array $object_ids, string $meta_key ): array {
		if ( empty( $object_ids ) ) {
			return [];
		}

		$values     = [];
		$comparison = [
			'value_counts'         => [],
			'objects_with_meta'    => 0,
			'objects_without_meta' => 0
		];

		foreach ( $object_ids as $object_id ) {
			$value = Meta::get( $meta_type, $object_id, $meta_key );
			if ( $value !== null ) {
				$values[ $object_id ] = $value;
				$comparison['objects_with_meta'] ++;

				// Count value occurrences
				$value_key                                = is_scalar( $value ) ? $value : serialize( $value );
				$comparison['value_counts'][ $value_key ] = ( $comparison['value_counts'][ $value_key ] ?? 0 ) + 1;
			} else {
				$comparison['objects_without_meta'] ++;
			}
		}

		$comparison['values']        = $values;
		$comparison['unique_values'] = array_unique( array_values( $values ), SORT_REGULAR );

		return $comparison;
	}

	/**
	 * Get statistical analysis of meta values across multiple objects.
	 *
	 * @param string $meta_type  Meta type ('post', 'user', 'term', 'comment').
	 * @param array  $object_ids Array of object IDs to analyze.
	 * @param string $meta_key   Meta key to analyze.
	 *
	 * @return array Array with statistical data (for numeric values).
	 */
	public static function get_stats( string $meta_type, array $object_ids, string $meta_key ): array {
		if ( empty( $object_ids ) ) {
			return [];
		}

		$values = [];
		foreach ( $object_ids as $object_id ) {
			$value = Meta::get_cast( $meta_type, $object_id, $meta_key, 'float', null );
			if ( is_numeric( $value ) ) {
				$values[] = (float) $value;
			}
		}

		if ( empty( $values ) ) {
			return [
				'count'          => 0,
				'numeric_values' => 0,
				'min'            => null,
				'max'            => null,
				'average'        => null,
				'sum'            => null
			];
		}

		return [
			'count'          => count( $object_ids ),
			'numeric_values' => count( $values ),
			'min'            => min( $values ),
			'max'            => max( $values ),
			'average'        => array_sum( $values ) / count( $values ),
			'sum'            => array_sum( $values )
		];
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Get meta table name for meta type.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 *
	 * @return string|null Meta table name or null if invalid type.
	 */
	private static function get_meta_table_name( string $meta_type ): ?string {
		global $wpdb;

		switch ( $meta_type ) {
			case 'post':
				return $wpdb->postmeta;
			case 'user':
				return $wpdb->usermeta;
			case 'term':
				return $wpdb->termmeta;
			case 'comment':
				return $wpdb->commentmeta;
			default:
				return null;
		}
	}

}