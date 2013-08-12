<?php

namespace Settings_Revisions;

class Command_Line_Interface {
	public $plugin = null;

	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, get_object_vars( $this ) );
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

		// @todo Add some useful commands
		//CliCommand::$plugin_instance = $this->plugin;
		//\WP_CLI::add_command( SLUG, NS_PREFIX . 'CliCommand' );
	}
}
