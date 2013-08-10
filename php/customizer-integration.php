<?php

namespace Settings_Revisions;

class Customizer_Integration {
	public $plugin = null;
	public $ajax_latest_dropdown_options_action = 'settings_revisions_latest_dropdown_options';

	/**
	 * @todo Show UI but prevent editing or creating new posts; only use admin as way to view posts, and trash or maybe set active settings
	 */
	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, get_object_vars( $this ) );
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

		add_action( 'customize_register', array( $this, 'add_customizer_controls' ) );
		add_action( 'customize_save', array( $this, 'on_customize_save' ) );

		// We have to do this here and not in the Meta_Control::__construct because it is not loaded on normal Ajax requests
		add_action( 'wp_ajax_' . $this->ajax_latest_dropdown_options_action, array( $this, '_respond_ajax_latest_revisions' ) );
	}

	/**
	 * @return bool
	 */
	function is_customizer() {
		return (
			(
				isset( $_REQUEST['wp_customize'] )
				&&
				'on' == $_REQUEST['wp_customize'] )
			||
			(
				is_admin()
				&&
				'customize.php' === basename( $_SERVER['PHP_SELF'] )
			)
		);
	}

	/**
	 *
	 */
	function add_customizer_controls() {
		global $wp_customize;

		$section_name = 'settings-revisions';
		$wp_customize->add_section( $section_name, array(
			'title'    => __( 'Settings Revisions', 'settings-revisions' ),
			'priority' => 1,
		) );

		$wp_customize->add_setting(
			'settings_revision_meta',
			array(
				'default'   => '',
				'transport' => 'custom', // anything other than 'postMessage' or 'refresh'
				'type'      => 'custom', // not a theme_mod or an option
			)
		);
		$wp_customize->add_control(
			new Meta_Control(
				$this->plugin,
				$wp_customize,
				'settings_revision_meta',
				array(
					'section'  => $section_name,
					'settings' => 'settings_revision_meta',
				)
			)
		);

	}

	/**
	 *
	 */
	function on_customize_save( \WP_Customize_Manager $manager ) {
		$meta = $manager->get_setting( 'settings_revision_meta' )->post_value();

		$args = array(
			// 'post_id' => $meta['post_id'], // @todo pending
			'comment'  => $meta['comment'],
			//'scheduled_date' => $meta['scheduled_date'], // @todo future
			//'is_pending' => false, // @todo pending
			'settings' => array(),
		);

		foreach ($manager->settings() as $setting) {
			if ( in_array( $setting->type, array( 'theme_mod', 'option' ) ) ) {
				$args['settings'][] = array(
					'id'    => $setting->id,
					'type'  => $setting->type,
					'value' => $setting->post_value(),
				);
			}
		}
		$this->plugin->post_type->save_revision_settings( $args );
	}

	/**
	 *
	 */
	public function _respond_ajax_latest_revisions() {
		check_ajax_referer( $this->ajax_latest_dropdown_options_action, 'nonce' );
		$args = array();
		if ( isset( $_REQUEST['after_post_id'] ) && is_numeric( $_REQUEST['after_post_id'] ) ) {
			$args['after_post_id'] = (int)$_REQUEST['after_post_id'];
		}
		if ( isset( $_REQUEST['before_post_id'] ) && is_numeric( $_REQUEST['before_post_id'] ) ) {
			$args['before_post_id'] = (int)$_REQUEST['before_post_id'];
		}
		echo $this->plugin->post_type->get_dropdown_contents( $args );
		exit;
	}
}
