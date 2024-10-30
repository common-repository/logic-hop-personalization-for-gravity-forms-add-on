<?php

	GFForms::include_addon_framework();

	class LogicHop_GravityFormsAddon extends GFAddOn {

		protected $_version = '1.0.1';
		protected $_min_gravityforms_version = '1.9';
		protected $_slug = 'logic_hop_addon';
		protected $_full_path = __FILE__;
		protected $_title = 'Logic Hop Add-On';
		protected $_short_title = 'Logic Hop';
		private static $_instance = null;

		/**
		 * Get an instance of this class.
		 *
		 * @return GFSimpleAddOn
		 */
		public static function get_instance() {
			if ( self::$_instance == null ) {
				self::$_instance = new LogicHop_GravityFormsAddon();
			}
			return self::$_instance;
		}

		/**
		 * Handles initialize hooks
		 */
		public function init() {
			parent::init();

			add_action( 'gform_field_advanced_settings', array( $this, 'field_advanced_settings' ), 10, 2 );
			add_action( 'gform_editor_js', array( $this, 'field_settings_script' ) );
		}

		/**
		 * Render field settings
		 *
		 * @return array
		 */
		public function field_advanced_settings ( $position, $form_id ) {
			if ( $position == 550 ) {
				$form = GFAPI::get_form( $form_id );
				if ( $form && isset( $form['logic_hop_addon'] ) && isset( $form['logic_hop_addon']['logichop_get_form'] ) ) {
					if ( $form['logic_hop_addon']['logichop_get_form'] == '1' ) {
						printf('<li class="encrypt_setting field_setting">
											<label for="field_admin_label" class="section_label">%s</label>
											<input type="checkbox" id="logichop_disable_data" onclick="SetFieldProperty(\'logichopDisableData\', this.checked);" /> %s
										</li>',
										__( 'Logic Hop' , 'logic-hop' ),
										__( 'Disable this field\'s data in Logic Hop', 'logic-hop' )
									);
					}
				}
			}
		}

		/**
		 * Render field setting Javascript
		 *
		 * @return array
		 */
		public function field_settings_script () {
		    print("<script type='text/javascript'>
						for( i in fieldSettings ) {
		 					fieldSettings[i] += ', .encrypt_setting';
		 				}
			    	jQuery(document).on('gform_load_field_settings', function(event, field, form){
			      	jQuery('#logichop_disable_data').attr('checked', field['logichopDisableData'] == true);
			      });
			    </script>"
				);
		}

		/**
		 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
		 *
		 * @return array
		 */
		 public function form_settings_fields( $form ) {
	 	 	return array(
	 			array(
	 				'title'  => esc_html__( 'Logic Hop Settings', 'logic-hop' ),
	 				'fields' => array(
	 					array(
	 						'label'   => esc_html__( 'Form Data', 'logic-hop' ),
	 						'type'    => 'select',
	 						'name'    => 'logichop_get_form',
	 						'choices' => array(
	 							array(
	 								'label' => esc_html__( 'Disabled for Logic Hop', 'logic-hop' ),
	 								'value' => false,
	 							),
	 							array(
	 								'label' => esc_html__( 'Available in Logic Hop', 'logic-hop' ),
	 								'value' => true,
	 							),
	 						),
	 					),
	 				),
	 			),
	 		);
	 	}
	}
