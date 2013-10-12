<?php

class Settings_Revisions_Preview_Filtering {

	public $plugin;

	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, get_object_vars( $this ) );
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

		$this->plugin->ready( array( $this, 'add_filters_to_use_active_settings_outside_customizer' ) );
	}

	public function add_filters_to_use_active_settings_outside_customizer() {
		$self = $this;
		$is_customizer = $this->plugin->customizer_integration->is_customizer();
		if ( $is_customizer ) {
			return;
		}

		$active_settings = $this->plugin->post_type->get_active_settings();
		if ( empty( $active_settings ) ) {
			return;
		}

		foreach ( $active_settings as $setting ) {
			$setting = (object)$setting;

			// Parse the ID for array keys.
			$id_keys = preg_split( '/\[/', str_replace( ']', '', $setting->id ) );
			$id_base = array_shift( $id_keys );

			// Rebuild the ID.
			$id = $id_base;
			if ( ! empty( $id_keys ) ) {
				$id .= '[' . implode( '][', $id_keys ) . ']';
			}

			$filter = array( $this, '_setting_filter' );
			$this->_filtering_id_keys = $id_keys;
			$this->_filtering_setting = $setting;
			switch ( $setting->type ) {
				case 'theme_mod' :
					add_filter( 'theme_mod_' . $id_base, $filter );
					break;
				case 'option' :
					if ( empty( $id_keys ) )
						add_filter( 'pre_option_' . $id_base, $filter );
					else {
						add_filter( 'option_' . $id_base, $filter );
						add_filter( 'default_option_' . $id_base, $filter );
					}
					break;
			}
		}
	}

	/**
	 * Callback function to filter the theme mods and options.
	 * In PHP 5.3 we wouldn't need this
	 */
	protected function _setting_filter( $original ) {
		return $this->multidimensional_replace( $original, $this->_filtering_id_keys, $this->_filtering_setting );
	}

	protected $_filtering_id_keys;
	protected $_filtering_setting;

	/******************************************************************************
	 * Begin functions copied from WP_Customize_Setting class
	 * Protected keywords have been removed so they can be invoked from closure
	 * @todo Should we just extend WP_Customize_Setting?
	 *****************************************************************************/

	/**
	 * Multidimensional helper function.
	 *
	 * @since 3.4.0
	 *
	 * @param $root
	 * @param $keys
	 * @param bool $create Default is false.
	 * @return null|array Keys are 'root', 'node', and 'key'.
	 */
	final function multidimensional( &$root, $keys, $create = false ) {
		if ( $create && empty( $root ) )
			$root = array();

		if ( ! isset( $root ) || empty( $keys ) )
			return;

		$last = array_pop( $keys );
		$node = &$root;

		foreach ( $keys as $key ) {
			if ( $create && ! isset( $node[ $key ] ) )
				$node[ $key ] = array();

			if ( ! is_array( $node ) || ! isset( $node[ $key ] ) )
				return;

			$node = &$node[ $key ];
		}

		if ( $create && ! isset( $node[ $last ] ) )
			$node[ $last ] = array();

		if ( ! isset( $node[ $last ] ) )
			return;

		return array(
			'root' => &$root,
			'node' => &$node,
			'key'  => $last,
		);
	}

	/**
	 * Will attempt to replace a specific value in a multidimensional array.
	 *
	 * @since 3.4.0
	 *
	 * @param $root
	 * @param $keys
	 * @param mixed $value The value to update.
	 * @return mixed
	 */
	final function multidimensional_replace( $root, $keys, $value ) {
		if ( ! isset( $value ) )
			return $root;
		elseif ( empty( $keys ) ) // If there are no keys, we're replacing the root.
			return $value;

		$result = $this->multidimensional( $root, $keys, true );

		if ( isset( $result ) )
			$result['node'][ $result['key'] ] = $value;

		return $root;
	}

	/**
	 * Will attempt to fetch a specific value from a multidimensional array.
	 *
	 * @since 3.4.0
	 *
	 * @param $root
	 * @param $keys
	 * @param mixed $default A default value which is used as a fallback. Default is null.
	 * @return mixed The requested value or the default value.
	 */
	final function multidimensional_get( $root, $keys, $default = null ) {
		if ( empty( $keys ) ) // If there are no keys, test the root.
			return isset( $root ) ? $root : $default;

		$result = $this->multidimensional( $root, $keys );
		return isset( $result ) ? $result['node'][ $result['key'] ] : $default;
	}

	/**
	 * Will attempt to check if a specific value in a multidimensional array is set.
	 *
	 * @since 3.4.0
	 *
	 * @param $root
	 * @param $keys
	 * @return bool True if value is set, false if not.
	 */
	final function multidimensional_isset( $root, $keys ) {
		$result = $this->multidimensional_get( $root, $keys );
		return isset( $result );
	}

}
