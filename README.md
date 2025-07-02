# WordPress Meta Utilities

A lightweight WordPress library for working with metadata across all WordPress meta types (post, user, term, comment). Provides clean APIs for essential meta operations, bulk management, pattern-based operations, and advanced array handling with WordPress-style simplicity.

## Features

* 🎯 **Universal Support**: Works seamlessly with all WordPress meta types (post, user, term, comment)
* 🚀 **Core Operations**: Essential CRUD with type casting and defaults
* 📊 **Bulk Management**: Process multiple meta entries and objects efficiently
* 🔍 **Pattern Matching**: Delete and retrieve by prefix patterns
* 📈 **Advanced Arrays**: Comprehensive array manipulation (append, remove, nested operations)
* 🔢 **Numeric Operations**: Increment, decrement with automatic initialization
* 🔄 **JSON Support**: Seamless JSON encoding/decoding for complex data
* 📋 **Search & Analysis**: Find objects by meta values, statistical analysis
* 🧹 **Cleanup Tools**: Pattern-based deletion and maintenance operations

## Installation

```bash
composer require arraypress/wp-meta-utils
```

## Quick Start

### Basic Meta Operations

```php
use ArrayPress\MetaUtils\Meta;

// Basic CRUD - works with any meta type
$exists = Meta::exists( 'post', 123, 'featured' );
$value  = Meta::get( 'post', 123, 'featured' );
Meta::update( 'post', 123, 'featured', true );
Meta::delete( 'post', 123, 'featured' );

// Get with defaults and type casting
$view_count  = Meta::get_cast( 'post', 123, 'view_count', 'int', 0 );
$settings    = Meta::get_cast( 'user', 456, 'preferences', 'array', [] );
$is_featured = Meta::get_cast( 'post', 123, 'featured', 'bool', false );

// Increment counters and toggle flags
$new_views  = Meta::increment( 'post', 123, 'view_count' );
$new_status = Meta::toggle( 'post', 123, 'featured' );
```

### Bulk Operations

```php
use ArrayPress\MetaUtils\Metas;

// Get multiple meta values for one object
$meta_data = Metas::get( 'post', 123, [ 'title', 'description', 'featured' ] );

// Update multiple values at once
$updates      = [
	'title'      => 'New Title',
	'featured'   => true,
	'view_count' => 100
];
$updated_keys = Metas::update( 'post', 123, $updates );

// Bulk operations across multiple objects
$post_ids    = [ 123, 456, 789 ];
$view_counts = Metas::bulk_get( 'post', $post_ids, 'view_count' );
Metas::bulk_update( 'post', $post_ids, 'featured', true );

// Find objects by meta value
$featured_posts = Metas::find_objects_by_value( 'post', 'featured', true );
```

## Comprehensive Examples

### E-commerce Product Management

```php
// Product inventory management
$product_id = 123;

// Get product data with type safety
$price      = Meta::get_cast( 'post', $product_id, 'price', 'float', 0.00 );
$stock      = Meta::get_cast( 'post', $product_id, 'stock_count', 'int', 0 );
$categories = Meta::get_cast( 'post', $product_id, 'categories', 'array', [] );

// Process sale - decrement stock
$new_stock = Meta::decrement( 'post', $product_id, 'stock_count', 1 );
if ( $new_stock <= 0 ) {
	Meta::update( 'post', $product_id, 'in_stock', false );

	// Add to out-of-stock list
	Meta::array_append( 'post', $product_id, 'notifications', 'out_of_stock' );
}

// Track product analytics
Meta::increment( 'post', $product_id, 'view_count' );
Meta::increment( 'post', $product_id, 'sales_count' );

// Update product categories
if ( ! Meta::array_contains( 'post', $product_id, 'categories', 'bestseller' ) ) {
	Meta::array_append( 'post', $product_id, 'categories', 'bestseller' );
}
```

### User Preferences and Activity

```php
$user_id = 456;

// Complex user settings with nested data
$preferences = [
	'notifications' => [
		'email' => true,
		'sms'   => false,
		'push'  => true
	],
	'display'       => [
		'theme'    => 'dark',
		'language' => 'en'
	]
];
Meta::set_json( 'user', $user_id, 'preferences', $preferences );

// Get nested preference
$email_enabled = Meta::get_nested( 'user', $user_id, 'preferences', 'notifications.email', false );

// Update specific nested value
Meta::set_nested( 'user', $user_id, 'preferences', 'display.theme', 'light' );

// Track user activity
Meta::increment( 'user', $user_id, 'login_count' );
Meta::array_append( 'user', $user_id, 'recent_posts', $post_id );

// Manage user favorites
Meta::array_append( 'user', $user_id, 'favorites', $product_id );
$favorite_count = Meta::array_count( 'user', $user_id, 'favorites' );
```

### Content Management System

```php
// SEO and content management
$post_id = 789;

// Batch update SEO data
$seo_data = [
	'seo_title'        => 'Optimized Title',
	'meta_description' => 'SEO description',
	'focus_keyword'    => 'wordpress',
	'canonical_url'    => 'https://example.com/post'
];
$updated  = Metas::update( 'post', $post_id, $seo_data );

// Track content performance
Meta::increment( 'post', $post_id, 'social_shares' );
Meta::array_append( 'post', $post_id, 'referrers', $_SERVER['HTTP_REFERER'] ?? 'direct' );

// Content flags and status
Meta::toggle( 'post', $post_id, 'needs_review' );
$review_status = Meta::is_truthy( 'post', $post_id, 'needs_review' );

// Related content management
$related_posts = Meta::get_cast( 'post', $post_id, 'related_posts', 'array', [] );
if ( count( $related_posts ) < 5 ) {
	Meta::array_append( 'post', $post_id, 'suggested_related', $related_post_id );
}
```

### Analytics and Reporting

```php
// Content analytics across multiple posts
$post_ids = [ 100, 101, 102, 103, 104 ];

// Get view statistics
$stats = Metas::get_stats( 'post', $post_ids, 'view_count' );
// Returns: count, numeric_values, min, max, average, sum

// Compare engagement across posts
$comparison = Metas::compare_values( 'post', $post_ids, 'engagement_score' );
// Returns: values, unique_values, value_counts, objects_with_meta, objects_without_meta

// Find high-performing content
$popular_posts    = Metas::find_objects_by_value( 'post', 'view_count', 1000, '>' );
$featured_content = Metas::find_objects_by_value( 'post', 'featured', true );

// Bulk operations for maintenance
$large_meta = Metas::find_large( 'post', $post_id, 500000 ); // Find meta > 500KB
Metas::delete_by_prefix( 'post', 'temp_' ); // Clean temporary data
```

### Advanced Array Operations

```php
// Managing complex arrays
$post_id = 123;

// Tags management
Meta::array_append( 'post', $post_id, 'tags', 'wordpress' );
Meta::array_append( 'post', $post_id, 'tags', 'php' );

// Remove outdated tags
Meta::array_remove( 'post', $post_id, 'tags', 'old-tag' );
Meta::array_remove_all( 'post', $post_id, 'tags', 'deprecated' );

// Clean up duplicates
Meta::array_unique( 'post', $post_id, 'tags' );

// Check content and count
$has_wp_tag = Meta::array_contains( 'post', $post_id, 'tags', 'wordpress' );
$tag_count  = Meta::array_count( 'post', $post_id, 'tags' );

// Nested array operations
$metadata = [
	'seo'    => [ 'title' => 'Page Title', 'description' => 'Page Description' ],
	'social' => [ 'twitter' => '@username', 'facebook' => 'page-id' ]
];
Meta::update( 'post', $post_id, 'page_meta', $metadata );

// Access nested data
$twitter_handle = Meta::get_nested( 'post', $post_id, 'page_meta', 'social.twitter' );
Meta::set_nested( 'post', $post_id, 'page_meta', 'seo.title', 'Updated Title' );
```

### System Maintenance and Migration

```php
// Data migration and cleanup
$post_ids = [ 1, 2, 3, 4, 5 ];

// Migrate old meta keys
foreach ( $post_ids as $post_id ) {
	Meta::migrate_key( 'post', $post_id, 'old_view_count', 'view_count' );
	Meta::migrate_key( 'post', $post_id, 'legacy_status', 'current_status' );
}

// Backup critical meta before changes
$backup = Metas::backup( 'post', 123, [ 'title', 'content', 'featured' ] );
// ... perform risky operations ...
Metas::restore( 'post', 123, $backup ); // Restore if needed

// System-wide cleanup
Metas::delete_by_prefix( 'post', 'cache_' );     // Remove all cache entries
Metas::delete_by_prefix( 'user', 'temp_' );      // Remove temporary user data

// Performance monitoring
$large_meta = Metas::find_large( 'post', 123, 1048576 ); // Find meta > 1MB
foreach ( $large_meta as $key => $size ) {
	error_log( "Large meta found: {$key} ({$size} bytes)" );
}
```

## API Reference

### Meta Class Methods (Single Entry Operations)

**Core Operations:**
- `exists( $meta_type, $object_id, $meta_key )` - Check if meta exists
- `get( $meta_type, $object_id, $meta_key, $single )` - Get meta value
- `get_with_default( $meta_type, $object_id, $meta_key, $default )` - Get with fallback
- `get_cast( $meta_type, $object_id, $meta_key, $cast_type, $default )` - Get with type casting
- `update( $meta_type, $object_id, $meta_key, $meta_value )` - Update meta
- `update_if_changed( $meta_type, $object_id, $meta_key, $meta_value )` - Conditional update
- `delete( $meta_type, $object_id, $meta_key )` - Delete meta

**Numeric Operations:**
- `increment( $meta_type, $object_id, $meta_key, $amount )` - Increment value
- `decrement( $meta_type, $object_id, $meta_key, $amount )` - Decrement value

**Array Operations:**
- `array_contains( $meta_type, $object_id, $meta_key, $value )` - Check if array contains value
- `array_append( $meta_type, $object_id, $meta_key, $value )` - Add to array
- `array_remove( $meta_type, $object_id, $meta_key, $value )` - Remove from array
- `array_remove_all( $meta_type, $object_id, $meta_key, $value )` - Remove all occurrences
- `array_unique( $meta_type, $object_id, $meta_key )` - Remove duplicates
- `array_count( $meta_type, $object_id, $meta_key )` - Count array items

**Nested Operations:**
- `get_nested( $meta_type, $object_id, $meta_key, $key, $default )` - Get nested value
- `set_nested( $meta_type, $object_id, $meta_key, $key, $value )` - Set nested value
- `remove_nested( $meta_type, $object_id, $meta_key, $key )` - Remove nested key

**JSON Operations:**
- `get_json( $meta_type, $object_id, $meta_key, $default )` - Get as JSON array
- `set_json( $meta_type, $object_id, $meta_key, $value )` - Set as JSON

**Boolean Operations:**
- `is_truthy( $meta_type, $object_id, $meta_key, $default )` - Check if truthy
- `toggle( $meta_type, $object_id, $meta_key )` - Toggle boolean value

**Utility Methods:**
- `get_type( $meta_type, $object_id, $meta_key )` - Get value type
- `get_size( $meta_type, $object_id, $meta_key )` - Get size in bytes
- `is_type( $meta_type, $object_id, $meta_key, $type )` - Check value type
- `is_large( $meta_type, $object_id, $meta_key, $size_limit )` - Check if large
- `migrate_key( $meta_type, $object_id, $old_key, $new_key, $delete_old )` - Migrate meta key

### Metas Class Methods (Bulk Operations)

**Core Operations:**
- `get( $meta_type, $object_id, $meta_keys, $single )` - Get multiple meta values
- `get_all( $meta_type, $object_id )` - Get all meta for object
- `update( $meta_type, $object_id, $meta_values, $skip_unchanged )` - Update multiple values
- `delete( $meta_type, $object_id, $meta_keys )` - Delete multiple keys

**Backup & Restore:**
- `backup( $meta_type, $object_id, $meta_keys )` - Backup meta values
- `restore( $meta_type, $object_id, $backup )` - Restore from backup

**Pattern Operations:**
- `get_by_prefix( $meta_type, $object_id, $prefix, $with_values )` - Get by prefix
- `delete_by_prefix( $meta_type, $prefix )` - Delete by prefix

**Bulk Operations:**
- `bulk_get( $meta_type, $object_ids, $meta_key )` - Get meta for multiple objects
- `bulk_update( $meta_type, $object_ids, $meta_key, $meta_value )` - Update multiple objects
- `bulk_delete( $meta_type, $object_ids, $meta_key )` - Delete from multiple objects

**Analysis & Search:**
- `find_large( $meta_type, $object_id, $size_limit )` - Find large meta entries
- `find_objects_by_value( $meta_type, $meta_key, $meta_value, $compare )` - Find objects by meta
- `compare_values( $meta_type, $object_ids, $meta_key )` - Compare across objects
- `get_stats( $meta_type, $object_ids, $meta_key )` - Statistical analysis

## Supported Type Casting

The `get_cast()` method supports these types:
- `'int'` or `'integer'` - Cast to integer
- `'float'` or `'double'` - Cast to float
- `'bool'` or `'boolean'` - Cast to boolean
- `'array'` - Cast to array
- `'string'` - Cast to string

## Supported Meta Types

All methods work with these WordPress meta types:
- `'post'` - Post meta (wp_postmeta table)
- `'user'` - User meta (wp_usermeta table)
- `'term'` - Term meta (wp_termmeta table)
- `'comment'` - Comment meta (wp_commentmeta table)

## When to Use What

### Use Meta class for:
- **Single meta operations** on individual objects
- **Type-safe retrieval** with casting and defaults
- **Array manipulation** without manual serialization
- **Numeric counters** and boolean flags
- **Nested data access** with dot notation

```php
// Type-safe operations
$count    = Meta::get_cast( 'post', 123, 'view_count', 'int', 0 );
$settings = Meta::get_cast( 'user', 456, 'preferences', 'array', [] );

// Array operations
Meta::array_append( 'post', 123, 'tags', 'new-tag' );
$has_tag = Meta::array_contains( 'post', 123, 'tags', 'wordpress' );
```

### Use Metas class for:
- **Bulk operations** across multiple objects or meta keys
- **System maintenance** and cleanup
- **Data analysis** and reporting
- **Pattern-based operations**

```php
// Bulk operations
$view_counts = Metas::bulk_get( 'post', [ 1, 2, 3 ], 'view_count' );
Metas::delete_by_prefix( 'post', 'temp_' );

// Analysis
$stats    = Metas::get_stats( 'post', $post_ids, 'engagement_score' );
$featured = Metas::find_objects_by_value( 'post', 'featured', true );
```

## Best Practices

### Performance Optimization
- Use bulk operations when working with multiple objects
- Leverage `update_if_changed()` to avoid unnecessary database writes
- Use type casting to ensure consistent data types
- Monitor large meta entries with `find_large()`

### Data Integrity
- Always use defaults with `get_with_default()` or `get_cast()`
- Use `backup()` and `restore()` for critical operations
- Validate data types with `is_type()` before processing
- Use `migrate_key()` for safe meta key transitions

### Code Organization
- Use consistent meta key naming patterns
- Group related meta operations in transactions when possible
- Use prefix patterns for easy cleanup and maintenance
- Document meta key purposes and expected data types

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-meta-utils)
- [Issue Tracker](https://github.com/arraypress/wp-meta-utils/issues)