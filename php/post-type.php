<?php

class Settings_Revisions_Post_Type {
	const SLUG        = 'settings-revision';
	const META_BOX_ID = 'settings-revision-options';
	public $plugin    = null;
	public $l10n      = array();

	//const DEFAULT_PUBLISH_CAPABILITY = 'manage_network_options'; // @todo pending
	/**
	 * @todo Show UI but prevent editing or creating new posts; only use admin as way to view posts, and trash or maybe set active settings
	 */
	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, get_object_vars( $this ) );
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'admin_menu', array( $this, 'remove_add_new_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		$this->l10n = array(
			'sole_authorship_label' => __( 'by %1$s', 'settings-revisions' ),
			'dual_authorship_label' => __( 'by %1$s w/ %2$s', 'settings-revisions' ),
			'revision_option_text'  => __( '{date} {time} {author}: {comment}', 'settings-revisions' ),
			// @todo Pending and Future option texts
		);
	}

	/**
	 *
	 */
	function register() {
		register_post_type(
			self::SLUG,
			array(
				'public'               => false,
				'show_ui'              => true,
				'show_in_menu'         => true,
				'show_in_admin_bar'    => false,
				'label'                => __( 'Settings Revisions', 'settings-revisions' ),
				'description'          => __( 'Snapshot of settings at a point in time.', 'settings-revisions' ),
				'labels'               => array(),
				'supports'             => array(
					'title',
					'author',
				),
				'register_meta_box_cb' => array( $this, 'add_meta_box' ),
			)
		);
	}


	/**
	 * Remove "Add New" menu under Settings Revisions parent menu
	 */
	function remove_add_new_menu() {
		remove_submenu_page(
			'edit.php?post_type=settings-revision',
			'post-new.php?post_type=settings-revision'
		);
	}

	/**
	 * @todo pending
	 */
	//function get_publish_capability() {
	//	return apply_filters( 'settings_revisions_publish_capability', self::DEFAULT_PUBLISH_CAPABILITY );
	//}

	/**
	 * Create postmeta key out of a $type and $id
	 *
	 * @param $type
	 * @param $id
	 * @return string
	 */
	static function format_post_meta_key( $type, $id ) {
		assert( in_array( $type, array( 'theme_mod', 'option' ) ) );
		return sprintf( '%s_%s', $type, $id );
	}

	/**
	 * Parse a postmeta key into its id and type
	 *
	 * @param $key
	 * @return bool|array
	 */
	static function parse_post_meta_key( $key ) {
		if ( ! preg_match( '/^(option|theme_mod)_(.+)/', $key, $matches ) ) {
			return false;
		}
		$type = $matches[1];
		$id   = $matches[2];
		return compact( 'id', 'type' );
	}

	/**
	 * Get the settings revision post that is active
	 * @return array|null
	 */
	function get_active_post() {
		$query_vars = array(
			'post_type'      => self::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$posts = get_posts( $query_vars );
		return array_shift( $posts );
	}

	/**
	 * @private
	 */
	protected function _assoc_settings_array( array $settings ) {
		$assoc_settings = array();
		foreach ( $settings as $setting ) {
			$setting = (array) $setting;
			$assoc_settings[$setting['type'] . '_' . $setting['id']] = $setting;
		}
		return $assoc_settings;
	}

	/**
	 * @param int|object|WP_Post [$post]
	 * @return array|null
	 */
	function get_revision_settings( $post = null ) {
		$post = get_post( $post );
		if ( empty( $post ) || self::SLUG !== $post->post_type ) {
			return null;
		}
		$settings = array();
		foreach ( get_post_custom_keys( $post->ID ) as $key ) {
			$key_info = self::parse_post_meta_key( $key );
			if ( $key_info ) {
				$value = get_post_meta( $post->ID, $key, true );
				$value = maybe_unserialize( $value );
				$settings[] = array_merge( $key_info, compact( 'value' ) );
			}
		}
		return $settings;
	}

	/**
	 * @return array|null
	 */
	function get_active_settings() {
		$post = $this->get_active_post();
		if ( empty( $post ) ) {
			return null;
		}
		return $this->get_revision_settings( $post );
	}

	/**
	 * @param array [$args]
	 * @throws Exception
	 * @return int
	 */
	function save_revision_settings( $args = array() ) {
		$defaults = array(
			//'post_id' => null, // @todo pending
			'comment' => '',
			//'is_pending' => false, // @todo pending
			//'scheduled_date' => null, // @todo future
			'settings' => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		// Force pending status if they don't have the permissions to publish
		//$can_publish_settings = current_user_can( $this->get_publish_capability() );
		//if ( ! $args['is_pending'] && ! $can_publish_settings ) {
		//	$args['is_pending'] = true;
		//}
		// @todo If there were no changes made in the UI (non-dirty state), and if the selected revision was pending, update that post

		$post_data = array(
			'post_type'   => self::SLUG,
			'post_status' => 'publish', // @todo pending: $args['is_pending'] ? 'pending' : 'publish',
			'post_title'  => $args['comment'],
			//'post_date' => $args['scheduled_date'], // @todo future
		);
		// @todo pending
		//if ( $args['post_id'] ) {
		//	$post_data['ID'] = $args['post_id'];
		//}
		$r = wp_insert_post( $post_data, true );
		if ( is_wp_error( $r ) ) {
			throw new Exception( $r->get_error_message() );
		}
		$post_id = $r;
		// @todo merge $args['settings'] with existing published settings
		foreach ( $args['settings'] as $setting ) {
			$setting = (array) $setting;
			if ( in_array( $setting['type'], array( 'theme_mod', 'option' ) ) ) {
				add_post_meta(
					$post_id,
					self::format_post_meta_key( $setting['type'], $setting['id'] ),
					wp_slash( serialize( $setting['value'] ) )
				);
			}
		}
		return $post_id;
	}

	/**
	 *
	 */
	function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( empty( $screen ) || 'post' !== $screen->base || self::SLUG !== $screen->post_type ) {
			return;
		}
		$plugin = $this->plugin;
		wp_enqueue_style(
			'settings-revisions-metabox',
			$plugin->get_plugin_path_url( 'css/metabox.css' ),
			array(),
			$plugin->get_version()
		);
	}

	/**
	 *
	 */
	function add_meta_box() {
		add_meta_box(
			self::META_BOX_ID,
			__( 'Settings Snapshot', 'settings-revisions' ),
			array( $this, '_meta_box_callback' ),
			self::SLUG,
			'normal',
			'default'
		);
	}

	/**
	 *
	 */
	function _meta_box_callback( $post ) {
		$settings = $this->get_revision_settings( $post );
		?>
		<?php if ( empty( $settings ) ) : ?>
			<p><em><?php esc_html_e( 'No settings were stored with this revision.', 'settings-revisions' ) ?></em></p>
			<?php return; ?>
		<?php endif; ?>

		<table>
			<thead>
				<th class="id" scope="col"><?php esc_html_e( 'ID', 'settings-revisions' ) ?></th>
				<th class="type" scope="col"><?php esc_html_e( 'Type', 'settings-revisions' ) ?></th>
				<th class="value" scope="col"><?php esc_html_e( 'Value', 'settings-revisions' ) ?></th>
			</thead>
			<tbody>
				<?php foreach ( $settings as $setting ) : ?>
					<tr>
						<th class="id" scope="row"><?php echo esc_html( $setting['id'] ) ?></th>
						<td class="type"><?php echo esc_html( $setting['type'] ) ?></td>
						<td class="value"><pre><?php echo esc_html( $setting['value'] ) ?></pre></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public function _filter_posts_where( $where, $query ) {
		global $wpdb;
		if ( $query->get( 'after_post_id' ) ) {
			$where .= $wpdb->prepare( " AND $wpdb->posts.ID > %d", $query->get( 'after_post_id' ) );
		}
		if ( $query->get( 'before_post_id' ) ) {
			$where .= $wpdb->prepare( " AND $wpdb->posts.ID < %d", $query->get( 'before_post_id' ) );
		}
		return $where;
	}

	/**
	 * Get a list of <option> elements containing the settings-revision posts
	 */
	public function get_dropdown_contents( $query_vars = array() ) {
		$defaults = array(
			'post_type'      => Settings_Revisions_Post_Type::SLUG,
			'post_status'    => array( 'publish', ), // @todo pending and future
			'after_post_id'  => null,
			'before_post_id' => null,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$query_vars = wp_parse_args( $query_vars, $defaults );

		$where_filter = array( $this, '_filter_posts_where' );
		add_filter( 'posts_where', $where_filter, 10, 2 );
		$query = new WP_Query( $query_vars );
		remove_filter( 'posts_where', $where_filter, 10, 2 );

		if ( ! $query->have_posts() ) {
			return sprintf( '<option value="">%s</option>', esc_html__( 'Default Settings', 'settings-revisions' ) );
		}

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			echo $this->_get_the_revision_select_option_html(); // xss ok
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Render the revision setting $post in an <option>
	 */
	protected function _get_the_revision_select_option_html( $default_selected = false ) {
		ob_start();
		$settings = array();
		foreach ( $this->get_revision_settings( get_the_ID() ) as $setting ) {
			$id = $setting['id'];
			$type = $setting['type'];
			$value = $setting['value'];
			$mock_customize_setting = (object) compact( 'id', 'type' ); // because customizer is not loaded, we can't $wp_customize->get_setting( $id )
			$value = apply_filters( "customize_sanitize_js_{$id}", $value, $mock_customize_setting );
			/**
			 * What happens in the case that a setting was not registered? How can we sanitize the JS value?
			 * Widget Customizer, for example, needs to convert PHP arrays into a serialized representation
			 * that can be manipulated via JS in the customizer. To do this, we register a sanitize_js_callback
			 * when adding the setting. But what if an old settings revision contains a widget which
			 * has since been deleted? What if we wanted to restore that widget? We can re-create the setting
			 * on the front-end with JS, but we wouldn't have the JS-sanitized setting to populate into the
			 * setting. So instead of dumping out all settings up-front, we may need to grab them later
			 * via Ajax once an old setting is selected. In the mean time, we can get around this
			 * via a temp_customize_sanitize_js filter.
			 */
			// @todo if _get_the_revision_select_option_html() is invoked over Ajax, then the temp_customize_sanitize_js filter is not yet added!
			$value = apply_filters( 'temp_customize_sanitize_js', $value, $mock_customize_setting );
			$key = sprintf( '%s_%s', $type, $id );
			$settings[$key] = $value;
		}
		$author             = get_the_author();
		$modified_author    = get_the_modified_author();
		$is_dual_authorship = ( $modified_author && $author !== $modified_author );
		?>
		<option
			title="<?php echo esc_attr( get_the_time( 'c' ) ) ?> <?php the_title_attribute() ?>"
			value="<?php the_ID() ?>"
			data-settings="<?php echo esc_attr( json_encode( $settings ) ) ?>"
			data-comment="<?php the_title_attribute() ?>"
			data-post_id="<?php the_ID() ?>"
			<?php
			/* @todo pending and future
			data-is_pending="<?php echo esc_attr( 'pending' === (string) get_post_status() ) ?>"
			data-scheduled_date="<?php echo esc_attr( get_the_time( 'Y-m-d\TH:i:s' ) ) ?>"
			*/
			?>
			<?php selected( $default_selected ) ?>
			>
			<?php
			$author_tpl = ( $is_dual_authorship ? $this->l10n['dual_authorship_label'] : $this->l10n['sole_authorship_label'] );
			$tpl_vars = array(
				'{date}'    => get_the_time( get_option( 'date_format' ) ),
				'{time}'    => get_the_time( get_option( 'time_format' ) ),
				'{author}'  => sprintf( $author_tpl, $author, $modified_author ),
				'{comment}' => get_the_title(),
			);
			$option_text = str_replace(
				array_keys( $tpl_vars ),
				array_values( $tpl_vars ),
				$this->l10n['revision_option_text']
			);
			echo esc_html( $option_text );
		?>
		</option>
		<?php
		return trim( ob_get_clean() );
	}

}
