<?php

namespace Settings_Revisions;

class Plugin {
	const POST_TYPE_SLUG    = 'settings-revision';
	const READY_ACTION_NAME = 'settings_revisions_plugin_loaded';
	public $post_type;
	public $customizer_integration;
	public $cli;

	/**
	 *
	 */
	function __construct() {
		register_activation_hook( PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( PLUGIN_FILE, array( $this, 'deactivate' ) );
		$this->load_textdomain();

		$this->upgrade();

		$args = array(
			'plugin' => $this,
		);
		$this->post_type = new Post_Type( $args );
		$this->customizer_integration = new Customizer_Integration( $args );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->cli = new Command_Line_Interface( $args );
		}
		// @todo This is not currently applicable because we don't support pending or future revisions
		//$this->settings_filtering = new Settings_Filtering( array(
		//	'plugin' => $this,
		//));
		do_action( self::READY_ACTION_NAME, $this );
	}

	/**
	 * Call function once the plug-in has been set up
	 */
	function ready( $callback ) {
		if ( did_action( self::READY_ACTION_NAME ) ) {
			call_user_func( $callback, $this );
		} else {
			add_action( self::READY_ACTION_NAME, $callback );
		}
	}

	/**
	 * Not using plugin_dir_url because it is not symlink-friendly
	 */
	function get_plugin_path_url( $path = null ) {
		$plugin_dirname = basename( dirname( PLUGIN_FILE ) );
		$base_dir       = trailingslashit( plugin_dir_url( '' ) ) . $plugin_dirname;
		if ( $path ) {
			return trailingslashit( $base_dir ) . ltrim( $path, '/' );
		} else {
			return $base_dir;
		}
	}

	/**
	 *
	 */
	function load_textdomain() {
		$locale  = apply_filters( 'plugin_locale', get_locale(), 'settings-revisions' );
		$mo_file = sprintf( '%s/%s/%s-%s.mo', \WP_LANG_DIR, 'settings-revisions', 'settings-revisions', $locale );
		load_textdomain( 'settings-revisions', $mo_file );
		load_plugin_textdomain( 'settings-revisions', false, dirname( plugin_basename( PLUGIN_FILE ) ) . trailingslashit( $this->get_meta_data( 'DomainPath' ) ) );
	}

	/**
	 *
	 */
	function upgrade() {
		$is_old = version_compare( get_option( 'settings_revisions_version' ), $this->get_version(), '<' );
		if ( $is_old ) {
			// @todo upgrade
			update_option( 'settings_revisions_version', $this->get_version() );
		}
	}

	/**
	 *
	 */
	function get_meta_data( $key = null ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data(PLUGIN_FILE);
		if ( ! is_null( $key ) ) {
			return $data[$key];
		} else {
			return $data;
		}
	}

	/**
	 *
	 */
	function get_version() {
		return $this->get_meta_data( 'Version' );
	}

	/**
	 *
	 */
	function activate() {
		flush_rewrite_rules();
		add_option( 'settings_revisions_version', $this->get_version() );
	}

	/**
	 *
	 */
	function deactivate() {
		flush_rewrite_rules();
	}

}
