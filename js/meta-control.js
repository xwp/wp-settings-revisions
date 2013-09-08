/*global jQuery, wp, SettingsRevisionsMetaControl_exported, confirm */

var SettingsRevisionsMetaControl = (function ($) {
	'use strict';

	var customize = wp.customize;
	var self = {
		l10n: {}, // populated by wp_localize_script
		latest_revisions_dropdown_options_action: null, // populated by wp_localize_script
		latest_revisions_dropdown_options_nonce: null, // populated by wp_localize_script
		control: null
	};

	self.constuctor = customize.Control.extend({
		ready: function() {
			$.extend(self, SettingsRevisionsMetaControl_exported);
			var control = this;

			var state = {
				post_id: null,
				//is_pending: null, // @todo pending
				//scheduled_date: null, // @todo future
				comment: null,
				base_post_id: null
			};
			// @todo need to implement value linking between state and field value

			var update_state = function (new_state, suppress_change_event) {
				suppress_change_event = suppress_change_event || false;
				$.extend(state, new_state);
				control.setting.set(suppress_change_event ? state : $.extend({}, state));
			};

			var has_mutated_since_snapshot_restore = false;
			var is_restoring_snapshot = false;

			var field_inputs = {
				post_id: control.container.find('select:first'),
				// is_pending: control.container.find('.is_pending .value'), // @todo pending
				// scheduled_date: control.container.find('.scheduled_date .value'), // @todo future
				comment: control.container.find('.comment .value')
			};

			/**
			 * Set up revision select
			 */
			var revision_select = field_inputs.post_id;
			revision_select.data('stableIndex', revision_select.prop('selectedIndex'));
			revision_select.on('change', function (e) {
				if (!customize.state('saved').get() && has_mutated_since_snapshot_restore && !confirm(self.l10n.confirm_dialog)) {
					e.preventDefault();
					$(this).prop('selectedIndex', $(this).data('stableIndex'));
					return false;
				}

				is_restoring_snapshot = true;
				$(this).data('stableIndex', this.selectedIndex);
				var option = $(this.options[this.selectedIndex]);
				field_inputs.comment.val(option.data('comment'));
				update_state({
					post_id: +option.data('post_id'),
					comment: option.data('comment')
				});

				var settings = option.data('settings');
				has_mutated_since_snapshot_restore = false;
				$.each(settings, function (key, value) {
					var id = key.replace(/^(theme_mod|option)_/, '');
					if (wp.customize.has(id)) {
						wp.customize.value(id)(value);
					}
				});
				is_restoring_snapshot = false;
				return true;
			});
			update_state({post_id: +revision_select.val()});

			/**
			 * @todo Scheduled Date
			 */
			//var scheduled_date_container = control.container.find('.scheduled_date');
			//
			//scheduled_date_container.find('input[type=checkbox]').on('click', function (e) {
			//	scheduled_date_container.find('.if-checked').toggle(this.checked);
			//	scheduled_date_container.find('.if-unchecked').toggle(!this.checked);
			//}).triggerHandler('click');
			//
			//field_inputs.scheduled_date.on('change', function (e) {
			//	update_state({scheduled_date: this.value});
			//}).triggerHandler('change');

			/**
			 * @todo Pending setup
			 */
			//field_inputs.is_pending.on('click', function () {
			//	update_state({is_pending: this.checked});
			//}).triggerHandler('click');

			/**
			 * Comment setup
			 */
			field_inputs.comment.on( 'input', function () {
				update_state({comment: this.value});
			}).triggerHandler('input');

			// set: control.setting.set('food');
			// get: control.setting();

			/**
			 * Listen for changes to settings
			 */
			customize.bind( 'change', function () {
				var selected_index = revision_select.prop('selectedIndex');
				var selected_option = $(revision_select[0].options[selected_index]);
				
				if ( ! is_restoring_snapshot ) {
					if ( state.post_id !== null ) {
						var new_revision_option = new Option();
						new_revision_option.text = self.l10n.new_option_text_format.replace(/%s/, selected_option.prop('text'));
						new_revision_option.value = '';
						revision_select.prepend(new_revision_option);
						revision_select.prop('selectedIndex', 0);
						revision_select.data('stableIndex', 0);
						has_mutated_since_snapshot_restore = true;
						update_state({post_id: null}, true);
					}
					// else we've already set up the state for a new revision
				}
				else {
					has_mutated_since_snapshot_restore = false;

					var is_default_selected = ( selected_option.prop('defaultSelected') === true );
					if ( is_default_selected ) {
						setTimeout(function () { // @todo hack to work around: api.bind( 'change', function() { state('saved').set( false ); });
							customize.state('saved').set(true);
						});
					}
					update_state({base_post_id: selected_option.data('post_id')}, true);

					// Remove the option for the new state
					revision_select.find('option[value=""]').remove();
				}
			});

			customize.bind( 'save', function () {
				revision_select.prop( 'disabled', true );
			});

			customize.bind( 'saved', function () {
				var post_ids = revision_select.find('option[value!=""]')
					.map(function () {
						return +$(this).data('post_id');
					})
					.get();
				var max_post_id = Math.max.apply(null, post_ids);
				var request = $.ajax( customize.settings.url.ajax, {
					type: 'GET',
					data: {
						nonce: self.latest_revisions_dropdown_options_nonce,
						action: self.latest_revisions_dropdown_options_action,
						after_post_id: max_post_id
					},
					xhrFields: {
						withCredentials: true
					}
				} );
				request.done(function ( response ) {
					revision_select.prop( 'disabled', false );

					is_restoring_snapshot = true;
					var new_options = $( response );
					new_options.first().prop('defaultSelected', true);
					revision_select.find('option').prop('defaultSelected', false);
					revision_select.prepend( new_options );
					revision_select.prop( 'selectedIndex', 0 );
					update_state({post_id: +revision_select.val()}, true);
					is_restoring_snapshot = false;

					// Remove the first New Revision
					revision_select.find('option[value=""]').remove();
				});
			});

		}
	});

	// Note that 'settings_revisions_meta' must match the PostType::$type
	customize.controlConstructor.settings_revisions_meta = self.constuctor;

	return self;
}(jQuery));
