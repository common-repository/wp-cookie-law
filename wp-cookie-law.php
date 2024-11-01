<?php
/*
Plugin Name: Cookie Law Info
Plugin URI: http://tony-masterapps.com/
Description: Allow you to show how your website complies with the EU Cookie Law. Implied Consent. Style it to match your own website.
Author: Cris Rogers
Author URI: http://tony-masterapps.com/
Version: 1.5.3
License: GPL2
*/


define ( 'CLI_PLUGIN_DEVELOPMENT_MODE', false );

define ( 'CLI_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define ( 'CLI_PLUGIN_URL', plugins_url() . '/wp-cookie-law/');
define ( 'CLI_DB_KEY_PREFIX', 'CookieLawInfo-' );
define ( 'CLI_LATEST_VERSION_NUMBER', '0.9' );
define ( 'CLI_SETTINGS_FIELD', CLI_DB_KEY_PREFIX . CLI_LATEST_VERSION_NUMBER );
define ( 'CLI_MIGRATED_VERSION', CLI_DB_KEY_PREFIX . 'MigratedVersion' );


define ( 'CLI_ADMIN_OPTIONS_NAME', 'CookieLawInfo-0.8.3' );


require_once CLI_PLUGIN_PATH . 'php/functions.php';
require_once CLI_PLUGIN_PATH . 'admin/cli-admin.php';
require_once CLI_PLUGIN_PATH . 'admin/cli-admin-page.php';
require_once CLI_PLUGIN_PATH . 'php/shortcodes.php';
require_once CLI_PLUGIN_PATH . 'php/custom-post-types.php';


register_activation_hook( __FILE__, 'wpcookielaw_activate' );	
add_action( 'admin_menu', 'wpcookielaw_register_custom_menu_page' );
add_action( 'wp_enqueue_scripts', 'wpcookielaw_enqueue_frontend_scripts' );
add_action( 'wp_footer', 'wpcookielaw_inject_cli_script' );

// Shortcodes:
add_shortcode( 'delete_cookies', 'wpcookielaw_delete_cookies_shortcode' );	// a shortcode [delete_cookies (text="Delete Cookies")]
add_shortcode( 'cookie_audit', 'wpcookielaw_table_shortcode' );				// a shortcode [cookie_audit style="winter"]
add_shortcode( 'cookie_accept', 'wpcookielaw_shortcode_accept_button' );		// a shortcode [cookie_accept (colour="red")]
add_shortcode( 'cookie_link', 'wpcookielaw_shortcode_more_link' );			// a shortcode [cookie_link]
add_shortcode( 'cookie_button', 'wpcookielaw_shortcode_main_button' );		// a shortcode [cookie_button]

// Dashboard styles:
add_action( 'admin_enqueue_scripts', 'wpcookielaw_custom_dashboard_styles' );
add_action( 'admin_enqueue_scripts', 'wpcookielaw_enqueue_color_picker' );
function wpcookielaw_enqueue_color_picker( $hook ) {
    if ( 'wpcookielaw_page_wp-cookie-law' != $hook )
        return;
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wpcookielaw_admin_page_script', plugins_url('admin/cli-admin.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
}


// Cookie Audit custom post type functions:
add_action( 'admin_init', 'wpcookielaw_custom_posts_admin_init' );
add_action( 'init', 'wpcookielaw_register_custom_post_type' );
add_action( 'save_post', 'wpcookielaw_save_custom_metaboxes' );
add_filter( 'manage_edit-wpcookielaw_columns', 'wpcookielaw_edit_columns' );
add_action( 'manage_posts_custom_column',  'wpcookielaw_custom_columns' );


// Add plugin settings link:
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wpcookielaw_plugin_action_links' );
function wpcookielaw_plugin_action_links( $links ) {
   $links[] = '<a href="'. get_admin_url(null, 'edit.php?post_type=wpcookielaw&page=wp-cookie-law') .'">Settings</a>';
   $links[] = '<a href="http://wpcookielaw.com/wp-cookie-law-2-0/" target="_blank">Beta 2.0</a>';
   return $links;
}


/** Register the uninstall function */
function wpcookielaw_activate() {
	register_uninstall_hook( __FILE__, 'wpcookielaw_uninstall_plugin' );
}


/** Uninstalls the plugin (removes settings and custom meta) */
function wpcookielaw_uninstall_plugin() {
	// Bye bye settings:
	delete_option( CLI_ADMIN_OPTIONS_NAME );
	delete_option( CLI_MIGRATED_VERSION );
	delete_option( CLI_SETTINGS_FIELD );
	
	// Bye bye custom meta:
	global $post;
	$args = array('post_type' => 'wpcookielaw');
	$cookies = new WP_Query( $args );
	
	if ( !$cookies->have_posts() ) {
		return;
	}
	
	while ( $cookies->have_posts() ) : $cookies->the_post();
		// Get custom fields:
		$custom = get_post_custom( $post->ID );
		// Look for old values. If they exist, move them to new values then delete old values:
		if ( isset ( $custom["cookie_type"][0] ) ) {
			delete_post_meta( $post->ID, "cookie_type", $custom["cookie_type"][0] );
		}
		if ( isset ( $custom["cookie_duration"][0] ) ) {
			delete_post_meta( $post->ID, "cookie_duration", $custom["cookie_duration"][0] );
		}
		if ( isset ( $custom["_cli_cookie_type"][0] ) ) {
			delete_post_meta( $post->ID, "_cli_cookie_type", $custom["_cli_cookie_type"][0] );
		}
		if ( isset ( $custom["_cli_cookie_duration"][0] ) ) {
			delete_post_meta( $post->ID, "_cli_cookie_duration", $custom["_cli_cookie_duration"][0] );
		}
	endwhile;
}


?>