<?php

namespace SettingsRevisions;

require_once ABSPATH . WPINC . '/class-wp-customize-control.php';

/**
 * Revision Selection Control Class
 *
 * @package WordPress
 * @subpackage Customize
 */
class MetaControl extends \WP_Customize_Control {
	public $plugin;

	/**
	 * @var string
	 */
	public $type = 'SettingsRevisionsMetaControl';

	/**
	 * @var array
	 */
	public $l10n;

	/**
	 * Constructor.
	 *
	 * If $args['settings'] is not defined, use the $id as the setting ID.
	 *
	 * @since 3.4.0
	 * @uses WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager
	 * @param string $id
	 * @param array $args
	 */
	public function __construct( Plugin $plugin, \WP_Customize_Manager $manager, $id, $args = array() ) {
		$this->plugin = $plugin;
		parent::__construct( $manager, $id, $args );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		$this->l10n = array(
			'confirm_dialog' => __( 'You have unsaved changed. Do you want to override them with the restored snapshot?', TEXT_DOMAIN ),
			'new_option_text_format' => __( '(Modified) %s', TEXT_DOMAIN ),
		);
	}

	/**
	 *
	 */
	function enqueue() {
		$screen = get_current_screen();
		if ($screen->base !== 'customize') {
			return;
		}

		wp_enqueue_script(
			'settings-revisions-meta-control',
			$this->plugin->get_plugin_path_url( 'js/meta-control.js' ),
			array( 'jquery', 'customize-controls' ),
			$this->plugin->get_version()
		);
		wp_enqueue_style(
			'settings-revisions-meta-control',
			$this->plugin->get_plugin_path_url( 'css/meta-control.css' ),
			array(),
			$this->plugin->get_version()
		);
		wp_localize_script('settings-revisions-meta-control', 'SettingsRevisionsMetaControl_exported', array(
			'l10n' => $this->l10n,
			'latest_revisions_dropdown_options_action' => $this->plugin->customizer_integration->ajax_latest_dropdown_options_action,
			'latest_revisions_dropdown_options_nonce' => wp_create_nonce( $this->plugin->customizer_integration->ajax_latest_dropdown_options_action ),
		));
	}

	/**
	 * Render the control's content.
	 */
	public function render_content() {
		
		$query_vars = array(
			'post_status' => array( 'publish', ), // @todo pending and future
			'posts_per_page' => apply_filters( 'settings_revisions_meta_control_revisions_list_count', 50 ),
		);

		$active_post_id = null;
		$active_post = $this->plugin->post_type->get_active_post();
		if ($active_post) {
			$active_post_id = $active_post->ID;
		}
		?>

		<label>
			<span class="customize-control-title"><?php echo esc_html_e( 'Active Revision:', TEXT_DOMAIN ); ?></span>
			<select class="active">
				<?php $options = $this->plugin->post_type->get_dropdown_contents( $query_vars ); ?>
				<?php if ( $options ): ?>
					<?php echo $options; ?>
				<?php else: ?>
					<option value=""><?php esc_html_e( 'Default Settings', TEXT_DOMAIN ) ?></option>
				<?php endif; ?>
			</select>
		</label>

		<div class="new-settings-meta" >
			<?php
			/* @todo pending and future
			$can_publish_settings = current_user_can( $this->plugin->post_type->get_publish_capability() );
			<p class="field is_pending">
				<label>
					<input type="checkbox" class="value" <?php checked( ! $can_publish_settings ) ?> <?php disabled( ! $can_publish_settings ) ?>>
					<span><?php echo esc_html_e( 'Save settings revision as pending review', TEXT_DOMAIN ); ?></span>
				</label>
			</p>
			<p class="field scheduled_date">
				<label>
					<input type="checkbox">
					<span class="if-unchecked"><?php echo esc_html_e( 'Schedule settings for activation', TEXT_DOMAIN ); ?></span>
					<span class="if-checked" hidden><?php echo esc_html_e( 'Date scheduled for activation:', TEXT_DOMAIN ); ?></span>
				</label>
				<span class="customize-control-content if-checked">
					<input
						type="datetime-local"
						class="value"
						min="<?php // Not behaving properly in Chrome: echo esc_attr( str_replace( ' ', 'T', current_time( 'mysql' ) ) ) ?>"
						title="<?php esc_attr_e( 'Enter the future date for when the settings should be applied.', TEXT_DOMAIN ) ?>">
				</span>
			</p>
			*/
			?>

			<p class="field comment">
				<label>
					<span class="customize-control-title"><?php echo esc_html_e( 'Comment:', TEXT_DOMAIN ); ?></span>
					<span class="customize-control-content"><input type="text" class="value" value="<?php echo esc_attr($active_post ? get_the_title($active_post) : '') ?>" title="<?php esc_attr_e( 'Provide a descriptive note about this revision', TEXT_DOMAIN ) ?>" maxlength="65535"></span>
				</label>
			</p>
		</div>
		<?php
	}

}
