<?php
/**
 * Plugin Name: Settings Revisions
 * Plugin URI:  http://github.com/x-team/wp-settings-revisions
 * Description: Keep revisions of changes to your settings, including Theme Customizer changes. Allow rollbacks to previous revisions, and editorial flow publishing changes.
 * Version:     0.1.3
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
	_settings_revisions_boot( array(
		'activation'      => true,
		'activation_args' => func_get_args(),
	));
}
register_activation_hook( __FILE__, '_settings_revisions_activate_wrapper' );


/**
 * Function to assert the proper version of PHP is in use
 */
function _settings_revisions_check_php_version( $die = false ) {
	$required_version = '5.3';
	$is_old_version   = version_compare( PHP_VERSION, $required_version, '<' );
	if ( $is_old_version ) {
		$error_message = sprintf(
			__( 'Sorry, the Settings Revisions plugin requires PHP %2s+, but your server is currently running PHP %3s, Please bug your host to upgrade to a recent version of PHP which is less bug-prone.', 'settings-revisions' ),
			$required_version,
			PHP_VERSION
		);
		if ( $die ) {
			// This will show message in activation notification on the plugins page
			die( $error_message ); // @todo why doesn't wp_die() work?
		}
	}
	return $is_old_version;
}


/**
 * Initialize plugin and activate it if we're activating
 */
function _settings_revisions_boot( $options = array() ) {
	global $settings_revisions_plugin;

	extract( wp_parse_args( $options, array(
		'activation'      => false,
		'activation_args' => array(),
	)));

	// Just in case database with plugin already activated is loaded onto a server with an old PHP version
	if ( _settings_revisions_check_php_version( $activation ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
		return;
	}

	// Define plugin constants in the namespace
	$ns = 'Settings_Revisions';
	define( $ns . '\NS_PREFIX', '\\' . $ns . '\\' );
	$file = WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ );
	define( $ns . '\PLUGIN_FILE', $file );
	define( $ns . '\PLUGIN_DIR', dirname( $file ) );
	define( $ns . '\SLUG', basename( __FILE__, '.php' ) );

	// Register autoload handler for this plugin's classes and initialize plugin class
	spl_autoload_register( '_settings_revisions_autoload' );
	$class_name                = $ns . '\Plugin'; // to prevent fatal syntax error
	$settings_revisions_plugin = new $class_name();

	// Because the constructor's register_activate_hook was called too late
	if ( $activation ) {
		call_user_func_array( array( $settings_revisions_plugin, 'activate' ), $activation_args );
		remove_action( 'plugins_loaded', '_settings_revisions_boot' );
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
add_action( 'plugins_loaded', '_settings_revisions_boot' );


/**
 * spl_autoload_register handler
 */
function _settings_revisions_autoload( $class_name ) {
	if ( preg_match( '/^Settings_Revisions\\\\(.+)/', $class_name, $matches ) ) {
		$class_name = $matches[1];
		$file_basename = strtolower( str_ireplace( '_', '-', $class_name ) );
		require_once __DIR__ . '/php/' . $file_basename . '.php';
	}
}
