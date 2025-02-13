<?php
/**
 * Plugin Name: WooDynamicX
 * Description: Adds dynamic custom fields for WooCommerce products (text:value format).
 * Version: 1.7
 * Author: Md Ikram Hossain Joy
 * Author URI: http://mdikramhossainjoy.com
 * Plugin URI: https://github.com/mdikramhossainjoy/woocommerce-dynamic-custom-fields
 * Documentation: https://mdikramhossainjoy.com/docs/woocommerce-dynamic-custom-fields
 * GitHub: https://github.com/mdikramhossainjoy/woocommerce-dynamic-custom-fields
 */

if (!defined('ABSPATH')) exit;

class WooDynamicX {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_custom_fields_metabox']);
        add_action('save_post', [$this, 'save_custom_fields']);
        add_action('woocommerce_single_product_summary', [$this, 'display_custom_fields'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
        add_action('admin_footer', [$this, 'custom_admin_script']);
    }

    public function add_custom_fields_metabox() {
        add_meta_box('woodynamicx_custom_fields', 'Custom Fields', [$this, 'custom_fields_callback'], 'product', 'normal', 'high');
    }

    public function custom_fields_callback($post) {
        $custom_fields = get_post_meta($post->ID, '_woodynamicx_custom_fields', true);
        echo '<div id="custom-fields-container">';
        if ($custom_fields) {
            foreach ($custom_fields as $field) {
                list($text, $value) = explode(':', $field, 2) + ['', ''];
                echo '<div class="custom-field-row">
                        <input type="text" class="custom-text" name="custom_fields_text[]" placeholder="Text" value="' . esc_attr($text) . '">
                        <input type="text" class="custom-value" name="custom_fields_value[]" placeholder="Value" value="' . esc_attr($value) . '">
                        <button type="button" class="button remove-field">Remove</button>
                      </div>';
            }
        }
        echo '</div>';
        echo '<button type="button" id="add-field" class="button button-primary">Add Field</button>';
        wp_nonce_field('save_custom_fields', 'custom_fields_nonce');
    }

    public function save_custom_fields($post_id) {
        if (!isset($_POST['custom_fields_nonce']) || !wp_verify_nonce($_POST['custom_fields_nonce'], 'save_custom_fields')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $texts = isset($_POST['custom_fields_text']) ? array_map('sanitize_text_field', $_POST['custom_fields_text']) : [];
        $values = isset($_POST['custom_fields_value']) ? array_map('sanitize_text_field', $_POST['custom_fields_value']) : [];
        $fields = [];

        foreach ($texts as $index => $text) {
            if (!empty($text) || !empty($values[$index])) {
                $fields[] = $text . ':' . $values[$index];
            }
        }

        update_post_meta($post_id, '_woodynamicx_custom_fields', $fields);
    }

    public function display_custom_fields() {
        global $post;
        $custom_fields = get_post_meta($post->ID, '_woodynamicx_custom_fields', true);
        if ($custom_fields) {
            echo '<div class="custom-fields-container">';
            $total_fields = count($custom_fields);
            foreach ($custom_fields as $index => $field) {
                list($text, $value) = explode(':', $field, 2) + ['', ''];
                echo '<span class="custom-field-inline"><strong>' . esc_html($text) . ':</strong> ' . esc_html($value) . '</span>';
                if ($index < $total_fields - 1) {
                    echo '<br>';
                }
            }
            echo '</div>';
        }
    }

    public function enqueue_admin_styles() {
        echo '<style>
        #custom-fields-container { margin-bottom: 10px; }
        .custom-field-row { display: flex; align-items: center; margin-bottom: 5px; }
        .custom-text, .custom-value { width: 30%; margin-right: 10px; }
        .remove-field { background: #ff5f5f; color: #fff; border: none; padding: 5px 10px; cursor: pointer; }
        .remove-field:hover { background: #e04e4e; }
        </style>';
    }

    public function enqueue_frontend_styles() {
        echo '<style>
        .custom-fields-container { margin-top: 10px; font-size: 16px; }
        .custom-field-inline { padding: 5px 10px; background: #f5f5f5; border-radius: 5px; display: inline-block; margin-bottom: 5px; }
        </style>';
    }

    public function custom_admin_script() {
        echo '<script>
        jQuery(document).ready(function ($) {
            $("#add-field").click(function () {
                $("#custom-fields-container").append(`<div class="custom-field-row">
                    <input type="text" class="custom-text" name="custom_fields_text[]" placeholder="Text">
                    <input type="text" class="custom-value" name="custom_fields_value[]" placeholder="Value">
                    <button type="button" class="button remove-field">Remove</button>
                </div>`);
            });

            $(document).on("click", ".remove-field", function () {
                $(this).closest(".custom-field-row").remove();
            });
        });
        </script>';
    }
}

new WooDynamicX();
