<?php

if (!defined('ABSPATH')) die;

/**
 * Gravity Forms functionality.
 *
 * Provides Gravity Forms functionality.
 *
 * @since      1.0.0
 * @package    LogicHop
 */

class LogicHop_GravityForms {

	/**
	 * Logic Hop conditions
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $logichop    Logic Hop class
	 */
	 private $logichop;

	/**
	 * Logic Hop conditions
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $public    Logic Hop Public class
	 */
	private $public = null;

	/**
	 * Plugin version
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    Plugin version
	 */
	private $version = '1.0';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    	1.0.0
	 * @param       object    $logic	LogicHop_Core functionality & logic.
	 */
	public function __construct () {
		$this->add_hooks_filters();
	}

	/**
	 * Add actions
	 *
	 * @since    	1.0.0
	 */
	public function add_hooks_filters () {
		add_action( 'gform_loaded', array( $this, 'gform_loaded' ), 5 );
		add_action( 'gform_after_submission', array( $this, 'form_submitted' ), 10, 2 );
		add_action( 'logichop_after_plugin_init', array( $this, 'logichop_plugin_init' ) );
		add_action( 'logichop_integration_init', array( $this, 'logichop_integration_init' ) );
	}

	/**
	 * Logic Hop plugin init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_plugin_init ( $logichop ) {
		$this->logichop = $logichop;
	}

	/**
	 * Add actions during LH integration init
	 *
	 * @since    	1.0.0
	 */
	public function logichop_integration_init () {
		add_filter( 'logichop_data_object_create', array( $this, 'data_object_create' ) );

		add_action( 'logichop_data_retrieve', array( $this, 'data_retrieve' ) , 10, 1);
		add_filter( 'logichop_editor_shortcode_variables', array( $this, 'shortcode_vars' ) );
		add_filter( 'logichop_gutenberg_variables', array( $this, 'gutenberg_vars' ) );
		add_filter( 'logichop_client_meta_integrations', array( $this, 'client_meta' ) );
		add_filter( 'logichop_admin_post_lookup', array( $this, 'ajax_form_lookup' ), 10, 4 );
		add_filter( 'logichop_admin_post_title_lookup', array( $this, 'ajax_form_title_lookup' ), 10, 4 );
	}

	/**
	 * Create default data factory object
	 *
	 * @since    1.0.0
	 */
	public function data_object_create ( $data ) {
		$data->GravityForms = array();
		$data->GravityFormsData = new stdclass;
		return $data;
	}

	/**
 	 * Form editor loaded - Register Addon functionality
 	 *
 	 * @since    1.0.0
 	 */
	public function gform_loaded () {
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
	  	return;
	  }
	  if ( ! class_exists( 'LogicHop_GravityFormsAddon' ) ) {
			require_once( 'gravity_forms_addon.php' );
		}
	  LogicHop_GravityFormsAddon::register( 'LogicHop_GravityFormsAddon' );
	}

	/**
	 * Condition builder AJAX lookup – Returns form names
	 *
	 * To-do: Implement search function
	 *
	 * @since    1.0.0
	 * @param    boolean		$data			Filter switch
	 * @param    string		$lookup			String to lookup
	 * @param    array		$post_type			Post type – Looking for 'gravity-forms'
	 * @param    array		$query_type			Type of query
	 * @return    array		Array of form objects
	 */
	public function ajax_form_lookup ( $data, $lookup, $post_type, $query_type ) {
		if ( ! in_array( 'gravity-forms', $post_type ) ) {
			return false;
		}

		if ( class_exists( 'GFForms') ) {
			$forms = GFAPI::get_forms(  );
			if ( $forms ) {
				$form_data = array();
				foreach ( $forms as $f ) {
					$tmp = new stdclass();
					$tmp->id = $f['id'];
					$tmp->title = $f['title'];
					$form_data[] = $tmp;
				}
				return $form_data;
			}
		}

		return false;
	}

	/**
	 * Condition builder AJAX lookup – Returns form title
	 *
	 * @since    1.0.0
	 * @param    boolean		$data			Filter switch
	 * @param    string		$lookup			String to lookup
	 * @param    array		$post_type			Post type – Looking for 'gravity-forms'
	 * @param    array		$query_type			Type of query
	 * @return    object		Object containing form title
	 */
	public function ajax_form_title_lookup ( $data, $lookup, $post_type, $query_type ) {

		if ( ! in_array( 'gravity-forms', $post_type ) ) {
			return false;
		}

		if ( class_exists( 'GFForms') ) {
			$form = GFAPI::get_form( $lookup );
			if ( $form ) {
				$form_data = new stdclass();
				$form_data->title = $form['title'];
				return $form_data;
			}
		}

		return false;
	}

	/**
	 * Parse data returned from SPF lookup
	 *
	 * @since    1.0.0
	 * @param    array		$data	Store data
	 * @return   boolean   	Data retrieved
	 */
	public function data_retrieve ( $data ) {

		if ( is_array( $data ) ) {
			$data = array_change_key_case( $data, CASE_LOWER );
		}
		
		if ( isset( $data['gf_form'] ) ) {
			foreach ( $data['gf_form'] as $key => $value ) {
				$gf = explode( ':', $key );
				if ( isset( $gf[0] ) && isset( $gf[1] ) ) {
					$form_id = $gf[0];
					$entry_id = $gf[1];
					$this->logichop->logic->data_factory->set_array_value( 'GravityForms', $form_id, $entry_id, false );
					$this->get_form_data( $form_id, $entry_id );
				}
			}
			$this->logichop->logic->data_factory->transient_save();
		}

		return false;
	}

	/**
	 * Store form event in Logic Hop
	 *
	 * @since    1.0.0
	 * @param    integer		$form_id			Gravity Form form id
	 * @param    integer		$entry_id			Gravity Form entry id
	 * @param    boolean		$spf_store			Switch to send to Logic Hop SPF
	 */
	public function store_form_event ( $form_id, $entry_id, $spf_store = false ) {
		$fid = (int) $form_id;
		$eid = (int) $entry_id;

		if ( $fid > 0 && $eid > 0 ) {
			$this->logichop->logic->data_factory->set_array_value( 'GravityForms', $fid, $eid );
			if ( $spf_store ) {
				$spf_value = sprintf( '%s:%s', $fid, $eid );
				$this->logichop->logic->data_remote_put( 'gf_form', $spf_value );
			}
		}
	}

	/**
	 * Store field data in Logic Hop
	 *
	 * @since    1.0.0
	 * @param    object		$field			Gravity Forms field object
	 * @param    string		$key			Field key
	 * @param    string		$value			Field value
	 */
	public function store_field_data ( $field, $key, $value ) {
		if ( $value == '' ) {
			return false;
		}
		if ( $field['type'] == 'checkbox' ) {
			$value = "checked";
		} else if ( $field['type'] == 'multiselect' ) {
			$value = json_decode( $value );
		}
		if ( $this->field_data_access( $field ) ) {
			$this->logichop->logic->data_factory->set_object_value( 'GravityFormsData', $key, $value );
		}
	}

	/**
	 * Get Gravity Form data
	 *
	 * @since    1.0.0
	 * @param    integer		$form_id			Gravity Form form id
	 * @param    integer		$entry_id			Gravity Form entry id
	 * @param    object		$form_object			Optional Gravity Form form object
	 * @param    object		$entry_object			Optional Gravity Form entry object
	 * @return    boolean		Is Logic Hop data access active
	 */
	public function get_form_data ( $form_id, $entry_id, $form_object = false, $entry_object = false ) {
		if ( class_exists( 'GFForms') ) {
			$form = ( $form_object ) ? $form_object : GFAPI::get_form( $form_id );
			if ( $form ) {
				if ( $this->form_data_access( $form ) ) {
					$entry = ( $entry_object ) ? $entry_object : GFAPI::get_entry( $entry_id );
					if ( $entry ) {
						foreach ( $form['fields'] as $field ) {
							$inputs = $field->get_entry_inputs();
							if ( is_array( $inputs ) ) {
								foreach ( $inputs as $input ) {
									$id = (string) $input['id'];
									$label = isset( $input['label'] ) ? $input['label'] : '';
									$admin_label = isset( $input['adminLabel'] ) ? $input['adminLabel'] : false;
									$key = $this->get_field_name( $label, $admin_label );
									$value = rgar( $entry, $id );
									$this->store_field_data( $field, $key, $value );
								}
							} else {
								$id = (string) $field->id;
								$label = isset( $field->label ) ? $field->label : '';
								$admin_label = isset( $field->adminLabel ) ? $field->adminLabel : false;
								$key = $this->get_field_name( $label, $admin_label );
								$value = rgar( $entry, $id );
								$this->store_field_data( $field, $key, $value );
							}
						}
					}
				}
			}
		}
	}

	/**
 	 * Form submitted
 	 *
 	 * @since    1.0.0
	 * @param    string		$entry			Gravity Forms entry data
	 * @param    string		$form			Gravity Forms form data
 	 */
	public function form_submitted ( $entry, $form ) {
		if ( $this->form_data_access( $form ) ) {
			if ( isset( $form['id'] ) && isset( $entry['id'] ) ) {
				$this->get_form_data ( $form['id'], $entry['id'], $form, $entry );
				$this->store_form_event( $form['id'], $entry['id'], true );
			}
		}
	}

	/**
	 * Is Logic Hop data access active
	 *
	 * @since    1.0.0
	 * @param    object		$form			Gravity Form form object
	 * @return    boolean		Is Logic Hop data access active
	 */
	public function form_data_access ( $form ) {
		if ( isset( $form['logic_hop_addon'] ) && isset( $form['logic_hop_addon']['logichop_get_form'] ) ) {
			if ( $form['logic_hop_addon']['logichop_get_form'] == '1' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is Logic Hop field access active
	 *
	 * @since    1.0.0
	 * @param    object		$field			Gravity Form field object
	 * @return    boolean		Is Logic Hop field access active
	 */
	public function field_data_access ( $field ) {
		if ( isset( $field['logichopDisableData'] ) ) {
			if ( $field['logichopDisableData'] !== true ) {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns field name formatted for use as property name
	 *
	 * @since    1.0.0
	 * @param    string		$label			Gravity Forms field label
	 * @param    string		$admin_label			Gravity Forms field admin label
	 * @return    string		Field name
	 */
	public function get_field_name ( $label, $admin_label = false ) {
		$key = ( ! $admin_label ) ? $label : $admin_label;
		return str_replace( ' ', '_', $key );
	}

	/**
 	 * Add variables to editor
 	 *
 	 * @since    1.0.0
 	 * @return   string    	Variables as datalist options
 	 */
	public function shortcode_vars ($datalist) {
		$datalist .= '<option value="GravityFormsData.#field#">Gravity Forms Data</option>';
		return $datalist;
	}

	/**
	 * Add variables to Gutenberg plugin
	 *
	 * @since    1.0.0
	 * @return   string    	Variables as array
	 */
	public function gutenberg_vars ( $options ) {
		$options[] = [ 'value' => 'GravityFormsData.#field#',
										'label' => 'Gravity Forms Data'
								];
		return $options;
	}

	/**
	 * Generate client meta data
	 *
	 * @since    1.0.0
	 * @param    array		$integrations	Integration names
	 * @return   array    	$integrations	Integration names
	 */
	public function client_meta ( $integrations ) {
		$integrations[] = 'gravity-forms';
		return $integrations;
	}
}
