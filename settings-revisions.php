<?php
/**
 * Plugin Name: Settings Revisions
 * Plugin URI:  http://github.com/x-team/wp-settings-revisions
 * Description: Keep revisions of changes to your settings, including Theme Customizer changes. Allow rollbacks to previous revisions, and editorial flow publishing changes.
 * Version:     0.2
 * Author:      X-Team
 * Author URI:  http://x-team.com/
 * License:     GPLv2+
 * Text Domain: settings-revisions
 * Domain Path: /locale
 */

/**
 * Copyright (c) 2013 X-Team (http://x-team.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

global $settings_revisions_plugin;

/**
 * Activation handler wrapper
 */
function _settings_revisions_activate_wrapper() {
	$options = array(
		'activation'      => true,
		'activation_args' => func_get_args(),
	);
	_settings_revisions_boot( $options );
}
register_activation_hook( __FILE__, '_settings_revisions_activate_wrapper' );

/**
 * Initialize plugin and activate it if we're activating
 */
function _settings_revisions_boot( $options = array() ) {
	global $settings_revisions_plugin;

	$defaults = array(
		'activation'      => false,
		'activation_args' => array(),
	);
	$options = wp_parse_args( $options, $defaults );

	$file = WP_PLUGIN_DIR . '/' . basename( __FILE__, '.php' ) . '/' . basename( __FILE__ );
	define( 'SETTINGS_REVISIONS_PLUGIN_FILE', $file );
	define( 'SETTINGS_REVISIONS_PLUGIN_DIR', dirname( $file ) );

	// Register autoload handler for this plugin's classes and initialize plugin class
	spl_autoload_register( '_settings_revisions_autoload' );
	$settings_revisions_plugin = new Settings_Revisions_Plugin();

	// Because the constructor's register_activate_hook was called too late
	if ( $options['activation'] ) {
		call_user_func_array( array( $settings_revisions_plugin, 'activate' ), $options['activation_args'] );
		remove_action( 'plugins_loaded', '_settings_revisions_boot' );
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php'; // for get_plugin_data()
}
add_action( 'plugins_loaded', '_settings_revisions_boot' );


/**
 * spl_autoload_register handler
 */
function _settings_revisions_autoload( $class_name ) {
	if ( preg_match( '/^Settings_Revisions_(.+)/', $class_name, $matches ) ) {
		$class_name    = $matches[1];
		$file_basename = strtolower( str_ireplace( '_', '-', $class_name ) );
		require_once dirname( __FILE__ ) . '/php/' . $file_basename . '.php';
	}
}
