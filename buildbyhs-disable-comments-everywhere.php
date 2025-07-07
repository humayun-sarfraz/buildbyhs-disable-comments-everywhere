<?php
/**
 * Plugin Name:       Disable Comments Everywhere
 * Plugin URI:        https://github.com/humayun-sarfraz/buildbyhs-disable-comments-everywhere
 * Description:       Completely disables comments site-wide (front-end, admin, widgets, feeds, REST API).
 * Version:           1.0
 * Author:            Humayun Sarfraz
 * Author URI:        https://github.com/humayun-sarfraz
 * Text Domain:       disable-comments-everywhere
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Load plugin textdomain for translations
 */
if ( ! function_exists( 'dcee_load_textdomain' ) ) {
    function dcee_load_textdomain() {
        load_plugin_textdomain( 'disable-comments-everywhere', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
}
add_action( 'plugins_loaded', 'dcee_load_textdomain' );

/**
 * Remove comments and trackbacks support for all post types
 */
if ( ! function_exists( 'dcee_disable_post_type_support' ) ) {
    function dcee_disable_post_type_support() {
        $post_types = get_post_types( [], 'names' );
        foreach ( $post_types as $pt ) {
            if ( post_type_supports( $pt, 'comments' ) ) {
                remove_post_type_support( $pt, 'comments' );
                remove_post_type_support( $pt, 'trackbacks' );
            }
        }
    }
}
add_action( 'init', 'dcee_disable_post_type_support', 100 );

/**
 * Redirect attempts to comments or discussion settings pages and remove recent comments widget
 */
if ( ! function_exists( 'dcee_admin_redirect_and_remove_widget' ) ) {
    function dcee_admin_redirect_and_remove_widget() {
        global $pagenow;
        if ( in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ), true ) ) {
            wp_safe_redirect( esc_url_raw( admin_url() ) );
            exit;
        }
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    }
}
add_action( 'admin_init', 'dcee_admin_redirect_and_remove_widget' );

/**
 * Remove comments menu
 */
if ( ! function_exists( 'dcee_remove_comments_menu' ) ) {
    function dcee_remove_comments_menu() {
        remove_menu_page( 'edit-comments.php' );
    }
}
add_action( 'admin_menu', 'dcee_remove_comments_menu' );

/**
 * Remove comments link from admin bar
 */
if ( ! function_exists( 'dcee_remove_comments_admin_bar' ) ) {
    function dcee_remove_comments_admin_bar() {
        if ( is_admin_bar_showing() ) {
            remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
        }
    }
}
add_action( 'wp_before_admin_bar_render', 'dcee_remove_comments_admin_bar' );

/**
 * Unregister recent comments widget
 */
if ( ! function_exists( 'dcee_unregister_recent_comments_widget' ) ) {
    function dcee_unregister_recent_comments_widget() {
        unregister_widget( 'WP_Widget_Recent_Comments' );
    }
}
add_action( 'widgets_init', 'dcee_unregister_recent_comments_widget' );

/**
 * Disable comment feeds and block direct access
 */
if ( ! function_exists( 'dcee_disable_comment_feeds' ) ) {
    function dcee_disable_comment_feeds() {
        add_filter( 'feed_links_show_comments_feed', '__return_false' );
        add_action( 'parse_query', function( $query ) {
            if ( isset( $query->is_comment_feed ) && $query->is_comment_feed ) {
                wp_die( esc_html__( 'Comments are closed.', 'disable-comments-everywhere' ), '', array( 'response' => 403 ) );
            }
        });
    }
}
add_action( 'init', 'dcee_disable_comment_feeds' );

/**
 * Always return closed/empty for comments
 */
if ( ! function_exists( 'dcee_disable_comments_filters' ) ) {
    function dcee_disable_comments_filters() {
        add_filter( 'comments_open',       '__return_false', 20, 2 );
        add_filter( 'pings_open',          '__return_false', 20, 2 );
        add_filter( 'comments_array',      '__return_empty_array', 10, 2 );
        add_filter( 'get_comments_number', '__return_zero', 20, 2 );
        add_filter( 'option_default_comment_status', '__return_closed' );
        add_filter( 'option_default_ping_status',    '__return_closed' );
    }
}
add_action( 'init', 'dcee_disable_comments_filters' );

/**
 * Remove REST API endpoints for comments
 */
if ( ! function_exists( 'dcee_disable_rest_comments' ) ) {
    function dcee_disable_rest_comments( $endpoints ) {
        if ( isset( $endpoints['/comments'] ) ) {
            unset( $endpoints['/comments'] );
        }
        if ( isset( $endpoints['/comments/(?P<id>[\\d]+)'] ) ) {
            unset( $endpoints['/comments/(?P<id>[\\d]+)'] );
        }
        return $endpoints;
    }
}
add_filter( 'rest_endpoints', 'dcee_disable_rest_comments' );
