# CMB2 Field Calendar
Allow calendar for a post

## Installation

#### Composer
`composer require anewholm/cmb2-field-calendar`

#### Manual
1. [Download](https://github.com/anewholm/cmb2-field-calendar/archive/master.zip) the plugin
2. Place the plugin folder in your `/wp-content/plugins/` directory
3. Activate the plugin in the plugins dashboard

# Usage
```php
array(
  'id' => $prefix . 'location_metabox_calendar',
  'title' => __( 'Calendar', $plugin_slug ),
  'object_types' => array( $post_type, ), // Post type
  'context' => 'side',
  'priority' => 'high',
  'show_names' => false,
  'fields' => array(
    array(
      'name' => __( 'Calendar', $plugin_slug ),
      'id' => $plugin_slug . '_location_calendar',
      'type' => 'calendar',
      'desc' => 'Used in Maps.',
    ),
  ),
),
```
