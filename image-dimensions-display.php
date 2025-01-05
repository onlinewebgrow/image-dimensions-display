<?php
/**
 * Plugin Name: Image Dimensions Display
 * Description: Displays image dimensions, aspect ratio, and recommended size in the media library.
 * Version: 1.0.6
 * Requires at least: 5.2
 * Tested up to: 6.7
 * Requires PHP: 7.2
 * Author: onlinewebgrow
 * Author URI: https://onlinewebgrow.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: image-dimensions-display
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AdvancedImageDimensions {

    public function __construct() {
        // Add columns to media library
        add_filter('manage_media_columns', array($this, 'add_image_columns'));
        add_action('manage_media_custom_column', array($this, 'display_image_columns'), 10, 2);

        // Add styles for the columns
        add_action('admin_head', array($this, 'add_admin_styles'));
    }

    /**
     * Add new columns to media library
     */
    public function add_image_columns($columns) {
        $columns['dimensions'] = esc_html__('Dimensions', 'image-dimensions-display');
        return $columns;
    }

    /**
     * Display column content
     */
    public function display_image_columns($column_name, $post_id) {
        if ($column_name === 'dimensions') {
            $this->show_dimensions_and_recommendation($post_id);
        }
    }

    /**
     * Show image dimensions, aspect ratio, and recommended size
     */
    private function show_dimensions_and_recommendation($post_id) {
        if (!wp_attachment_is_image($post_id)) {
            echo esc_html__('Not an image', 'image-dimensions-display');
            return;
        }

        $meta = wp_get_attachment_metadata($post_id);

        if (isset($meta['width']) && isset($meta['height'])) {
            $width = (int) $meta['width'];
            $height = (int) $meta['height'];
            $aspect_ratio = $this->calculate_aspect_ratio($width, $height);

            // Get WordPress media settings
            $media_settings = $this->get_media_settings();
            $recommended_size = $this->get_recommended_size($width, $height, $media_settings);

            // Display physical dimensions, aspect ratio, and recommended size
            printf(
                '<span class="image-dimensions">%s × %s px</span><br><small>(%s)</small><br><strong>%s:</strong> %s',
                esc_html($width),
                esc_html($height),
                esc_html($aspect_ratio),
                esc_html__('Recommended Size', 'image-dimensions-display'),
                esc_html($recommended_size)
            );
        } else {
            echo esc_html__('N/A', 'image-dimensions-display');
        }
    }

    /**
     * Get WordPress media settings
     */
    private function get_media_settings() {
        return array(
            'thumbnail' => array(
                'width' => (int) get_option('thumbnail_size_w'),
                'height' => (int) get_option('thumbnail_size_h'),
            ),
            'medium' => array(
                'width' => (int) get_option('medium_size_w'),
                'height' => (int) get_option('medium_size_h'),
            ),
            'large' => array(
                'width' => (int) get_option('large_size_w'),
                'height' => (int) get_option('large_size_h'),
            ),
        );
    }

    /**
     * Get recommended size based on media settings
     */
    private function get_recommended_size($width, $height, $media_settings) {
        foreach ($media_settings as $size => $dimensions) {
            if ($width <= $dimensions['width'] && $height <= $dimensions['height']) {
                return sprintf(
                    '%s (%s × %s px)',
                    esc_html(ucfirst($size)),
                    esc_html($dimensions['width']),
                    esc_html($dimensions['height'])
                );
            }
        }
        return sprintf(
            '%s (%s × %s px)',
            esc_html__('Full Size', 'image-dimensions-display'),
            esc_html($width),
            esc_html($height)
        );
    }

    /**
     * Calculate aspect ratio
     */
    private function calculate_aspect_ratio($width, $height) {
        if ($height == 0) {
            return esc_html__('Invalid', 'image-dimensions-display');
        }

        $gcd = function($a, $b) use (&$gcd) {
            return ($b === 0) ? $a : $gcd($b, $a % $b);
        };

        $divisor = $gcd($width, $height);
        return sprintf('%s:%s', 
            esc_html($width / $divisor), 
            esc_html($height / $divisor)
        );
    }

    /**
     * Add admin styles
     */
    public function add_admin_styles() {
        ?>
        <style type="text/css">
            .column-dimensions {
                width: 20%;
            }
            .image-dimensions {
                font-weight: bold;
            }
        </style>
        <?php
    }
}

// Initialize the plugin
if (is_admin()) {
    new AdvancedImageDimensions();
}