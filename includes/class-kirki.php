<?php

/**
 * The API class.
 */
class Kirki {

	public static $config   = array();
	public static $fields   = array();
	public static $panels   = array();
	public static $sections = array();

	/**
	 * the class constructor
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'add_to_customizer' ), 1 );
	}

	/**
	 * Helper function that adds the fields, sections and panels to the customizer.
	 * @return void
	 */
	public function add_to_customizer() {
		$this->fields_from_filters();
		add_action( 'customize_register', array( $this, 'add_panels' ), 97 );
		add_action( 'customize_register', array( $this, 'add_sections' ), 98 );
		add_action( 'customize_register', array( $this, 'add_fields' ), 99 );
	}

	/**
	 * Process fields added using the 'kirki/fields' and 'kirki/controls' filter.
	 * These filters are no longer used, this is simply for backwards-compatibility
	 */
	public function fields_from_filters() {

		$fields = apply_filters( 'kirki/controls', array() );
		$fields = apply_filters( 'kirki/fields', $fields );

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				self::add_field( 'global', $field );
			}
		}

	}

	/**
	 * Get the value of an option from the db.
	 *
	 * @var 	string	the ID of the configuration corresponding to this field
	 * @var		string	the field_id (defined as 'settings' in the field arguments)
	 *
	 * @return 	mixed 	the saved value of the field.
	 *
	 */
	public static function get_option( $config_id = '', $field_id = '' ) {

		$value = '';

		if ( ( '' == $field_id ) && '' != $config_id ) {
			$field_id  = $config_id;
			$config_id = 'global';
		}

		$config_id = ( '' == $config_id ) ? 'global' : $config_id;

		// Are we using options or theme_mods?
		$mode = self::$config[ $config_id ]['option_type'];
		// Is there an option name set?
		$option_name = false;
		if ( 'option' == $mode && isset( self::$config[ $config_id ]['option'] ) ) {
			$option_name = self::$config[ $config_id ]['option'];
		}

		if ( 'theme_mod' == $mode ) {

			// We're using theme_mods
			$value = get_theme_mod( $field_id, Kirki_Field::sanitize_default( self::$fields[ $field_id ]['default'] ) );

		} elseif ( 'option' == $mode ) {

			// We're using options
			if ( $option_name ) {

				// Options are serialized as a single option in the db
				$options = get_option( $option_name );
				$value   = ( isset( $options[ $field_id ] ) ) ? $options[ $field_id ] : self::$fields[ $field_id ]['default'];
				$value   = maybe_unserialize( $value );

			} else {

				// Each option separately saved in the db
				$value = get_option( $field_id, self::$fields[ $field_id ]['default'] );

			}

		}

		if ( defined( 'KIRKI_REDUX_COMPATIBILITY' ) && KIRKI_REDUX_COMPATIBILITY ) {

			switch ( self::$fields[ $field_id ]['type'] ) {

				case 'image' :
					$value = Kirki_Helper::get_image_from_url( $value );
					break;

			}

		}

		return $value;

	}

	/**
	 * Sets the configuration options.
	 *
	 * @var		string		the configuration ID.
	 * @var		array		the configuration options.
	 */
	public static function add_config( $config_id, $args = array() ) {

		$default_args = array(
			'capability'  => 'edit_theme_options',
			'option_type' => 'theme_mod',
			'option'      => '',
			'compiler'    => array(),
		);
		$args = array_merge( $default_args, $args );

		// Allow empty value as the config ID by setting the id to global.
		$config_id = ( '' == $config_id ) ? 'global' : $config_id;

		// Set the config
		self::$config[ $config_id ] = $args;

	}

	/**
	 * register our panels to the WordPress Customizer
	 * @var	object	The WordPress Customizer object
	 */
	public function add_panels( $wp_customize ) {

		if ( ! empty( self::$panels ) ) {

			foreach ( self::$panels as $panel ) {
				$wp_customize->add_panel( sanitize_key( $panel['id'] ), array(
					'title'           => esc_textarea( $panel['title'] ),
					'priority'        => esc_attr( $panel['priority'] ),
					'description'     => esc_textarea( $panel['description'] ),
					'active_callback' => $panel['active_callback'],
				) );
			}

		}
	}

	/**
	 * register our sections to the WordPress Customizer
	 * @var	object	The WordPress Customizer object
	 */
	public function add_sections( $wp_customize ) {

		if ( ! empty( self::$sections ) ) {

			foreach ( self::$sections as $section ) {
				$wp_customize->add_section( sanitize_key( $section['id'] ), array(
					'title'           => esc_textarea( $section['title'] ),
					'priority'        => esc_attr( $section['priority'] ),
					'panel'           => esc_attr( $section['panel'] ),
					'description'     => esc_textarea( $section['description'] ),
					'active_callback' => $section['active_callback'],
				) );
			}

		}

	}

	/**
	 * Create the settings and controls from the $fields array and register them.
	 * @var	object	The WordPress Customizer object
	 */
	public function add_fields( $wp_customize ) {

		$control_types = apply_filters( 'kirki/control_types', array(
			'color'           	=> 'WP_Customize_Color_Control',
			'color-alpha'     	=> 'Kirki_Controls_Color_Alpha_Control',
			'image'           	=> 'WP_Customize_Image_Control',
			'upload'          	=> 'WP_Customize_Upload_Control',
			'switch'          	=> 'Kirki_Controls_Switch_Control',
			'toggle'          	=> 'Kirki_Controls_Toggle_Control',
			'radio-buttonset' 	=> 'Kirki_Controls_Radio_ButtonSet_Control',
			'radio-image'     	=> 'Kirki_Controls_Radio_Image_Control',
			'sortable'        	=> 'Kirki_Controls_Sortable_Control',
			'slider'          	=> 'Kirki_Controls_Slider_Control',
			'number'          	=> 'Kirki_Controls_Number_Control',
			'multicheck'      	=> 'Kirki_Controls_MultiCheck_Control',
			'palette'         	=> 'Kirki_Controls_Palette_Control',
			'custom'          	=> 'Kirki_Controls_Custom_Control',
			'editor'          	=> 'Kirki_Controls_Editor_Control',
			'select2'         	=> 'Kirki_Controls_Select2_Control',
			'select2-multiple'	=> 'Kirki_Controls_Select2_Multiple_Control'
		) );

		foreach ( self::$fields as $field ) {

			if ( 'background' != $field['type'] ) {

				$wp_customize->add_setting( Kirki_Field::sanitize_settings( $field ), array(
					'default'           => Kirki_Field::sanitize_default( $field ),
					'type'              => Kirki_Field::sanitize_type( $field ),
					'capability'        => Kirki_Field::sanitize_capability( $field ),
					'transport'         => Kirki_Field::sanitize_transport( $field ),
					'sanitize_callback' => Kirki_Field::sanitize_callback( $field ),
				) );

				if ( array_key_exists( $field['type'], $control_types ) ) {

					$class_name = $control_types[ $field['type'] ];
					$wp_customize->add_control( new $class_name(
						$wp_customize,
						Kirki_Field::sanitize_id( $field ),
						Kirki_Field::sanitize_field( $field )
					) );

				} else {

					$wp_customize->add_control( new WP_Customize_Control(
						$wp_customize,
						Kirki_Field::sanitize_id( $field ),
						Kirki_Field::sanitize_field( $field )
					) );

				}

			}

		}

	}

	/**
	 * Create a new panel
	 *
	 * @var		string		the ID for this panel
	 * @var		array		the panel arguments
	 */
	public static function add_panel( $id = '', $args = array() ) {

		if ( is_array( $id ) && empty( $args ) ) {
			$args = $id;
			$id   = 'global';
		}

		// Add the section to the $fields variable
		$args['id']          = esc_attr( $id );
		$args['description'] = ( isset( $args['description'] ) ) ? esc_textarea( $args['description'] ) : '';
		$args['priority']    = ( isset( $args['priority'] ) ) ? esc_attr( $args['priority'] ) : 10;
		if ( ! isset( $args['active_callback'] ) ) {
			$args['active_callback'] = ( isset( $args['required'] ) ) ? 'kirki_active_callback' : '__return_true';
		}

		self::$panels[$args['id']] = $args;

	}

	/**
	 * Create a new section
	 *
	 * @var		string		the ID for this section
	 * @var		array		the section arguments
	 */
	public static function add_section( $id, $args ) {

		if ( is_array( $id ) && empty( $args ) ) {
			$args = $id;
			$id   = 'global';
		}

		// Add the section to the $fields variable
		$args['id']          = esc_attr( $id );
		$args['panel']       = ( isset( $args['panel'] ) ) ? esc_attr( $args['panel'] ) : '';
		$args['description'] = ( isset( $args['description'] ) ) ? esc_textarea( $args['description'] ) : '';
		$args['priority']    = ( isset( $args['priority'] ) ) ? esc_attr( $args['priority'] ) : 10;
		if ( ! isset( $args['active_callback'] ) ) {
			$args['active_callback'] = ( isset( $args['required'] ) ) ? 'kirki_active_callback' : '__return_true';
		}

		self::$sections[ $args['id'] ] = $args;

	}

	/**
	 * Create a new field
	 *
	 * @var		string		the configuration ID for this field
	 * @var		array		the field arguments
	 */
	public static function add_field( $config_id, $args ) {

		if ( is_array( $config_id ) && empty( $args ) ) {
			$args      = $config_id;
			$config_id = 'global';
		}

		$config_id = ( '' == $config_id ) ? 'global' : $config_id;

		// Get the configuration options
		$config = self::$config[ $config_id ];

		/**
		 * If we've set an option in the configuration
		 * then make sure we're using options and not theme_mods
		 */
		if ( '' != $config['option'] ) {
			$config['option_type'] = 'option';
		}

		/**
		 * If no option name has been set for the field,
		 * use the one from the configuration
		 */
		if ( ! isset( $args['option'] ) ) {
			$args['option'] = $config['option'];
		}

		/**
		 * If no capability has been set for the field,
		 * use the one from the configuration
		 */
		if ( ! isset( $args['capability'] ) ) {
			$args['capability'] = $config['capability'];
		}

		/**
		 * Check if [settings] is set.
		 * If not set, check for [setting]
		 */
		if ( ! isset( $args['settings'] ) && isset( $args['setting'] ) ) {
			$args['settings'] = $args['setting'];
		}

		/**
		 * If no option-type has been set for the field,
		 * use the one from the configuration
		 */
		if ( ! isset( $args['option_type'] ) ) {
			$args['option_type'] = $config['option_type'];
		}

		// Add the field to the static $fields variable properly indexed
		self::$fields[Kirki_Field::sanitize_settings( $args )] = $args;

		if ( 'background' == $args['type'] ) {
			// Build the background fields
			self::$fields = Kirki_Field::build_background_fields( self::$fields );
		}

	}

	/**
	 * Build the variables.
	 *
	 * @return array 	('variable-name' => value)
	 */
	public function get_variables() {

		$variables = array();

		foreach ( self::$fields as $field ) {

			if ( isset( $field['variables'] ) && false != $field['variables'] ) {

				foreach ( $field['variables'] as $field_variable ) {

					if ( isset( $field_variable['name'] ) ) {
						$variable_name     = esc_attr( $field_variable['name'] );
						$variable_callback = ( isset( $field_variable['callback'] ) && is_callable( $field_variable['callback'] ) ) ? $field_variable['callback'] : false;

						if ( $variable_callback ) {
							$variables[ $variable_name ] = call_user_func( $field_variable['callback'], self::get_option( Kirki_Field::sanitize_settings( $field ) ) );
						} else {
							$variables[ $variable_name ] = self::get_option( $field['settings'] );
						}

					}

				}

			}

		}

		return apply_filters( 'kirki/variable', $variables );

	}

}
