<?php
/**
 * Repeater Customizer Control.
 *
 * @package     Kirki
 * @subpackage  Controls
 * @copyright   Copyright (c) 2015, Aristeides Stathopoulos
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Early exit if the class already exists
if ( class_exists( 'Kirki_Controls_Repeater_Control' ) ) {
	return;
}

class Kirki_Controls_Repeater_Control extends WP_Customize_Control {
	public $type = 'repeater';
	public $fields = array();
	public $button_label = "";

	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );

		if ( empty( $this->button_label ) ) {
			$this->button_label = __( 'Add new row', 'Kirki' );
		}

		if ( empty( $args['fields'] ) || ! is_array( $args['fields'] ) ) {
			$args['fields'] = array();
		}

		foreach ( $args['fields'] as $key => $value ) {
			if ( ! isset( $value['default'] ) ) {
				$args['fields'][ $key ]['default'] = '';
			}

			if ( ! isset( $value['label'] ) ) {
				$args['fields'][ $key ]['label'] = '';
			}
			$args['fields'][ $key ]['id'] = $key;
		}

		$this->fields = $args['fields'];
	}

	public function to_json() {
		parent::to_json();

		$fields = $this->fields;
		$value = $this->value();
		$can_upload = current_user_can( 'upload_files' );

		$default_image_button_labels = array(
			'select'       => __( 'Select File' ),
			'change'       => __( 'Change File' ),
			'default'      => __( 'Default' ),
			'remove'       => __( 'Remove' ),
			'placeholder'  => __( 'No file selected' ),
			'frame_title'  => __( 'Select File' ),
			'frame_button' => __( 'Choose File' ),
		);

		foreach ( $value as $key => $row ) {
			foreach ( $row as $field_key => $field ) {
				$field_type = isset( $fields[ $field_key ]['type'] ) ? $fields[ $field_key ]['type'] : false;
				if ( $field_type == 'image' ) {
					$value[ $key ][ $field_key ]['canUpload'] = $can_upload;
					$value[ $key ][ $field_key ]['buttonLabels'] = $default_image_button_labels;
				}
			}
		}

		// Add attachment attributes if there's any image type
		foreach ( $fields as $key => $field ) {
			if ( $field['type'] != 'image' )
				continue;

			if ( $field['default'] ) {
				$default_attachment = array(
					'id' => 1,
					'url' => $field['default'],
					'type' => 'image',
					'icon' => wp_mime_type_icon( 'image' ),
					'title' => basename( $field['default'] ),
					'sizes' => array(
						'full' => array( 'url' => $field['default'] )
					)
				);

				// Set the default as the attachment.
				$fields[ $key ]['attachment'] = $default_attachment;

			}
			else {
				$fields[ $key ]['attachment'] = false;
			}

			$fields[ $key ]['buttonLabels'] = $default_image_button_labels;
			$fields[ $key ]['canUpload'] = $can_upload;


		}

		$this->json['fields'] = $fields;
		$this->json['value'] = $value;
	}



	public function enqueue() {
		wp_enqueue_script( 'kirki-repeater', trailingslashit( kirki_url() ) . 'includes/controls/repeater/script.js', array( 'jquery', 'customize-base' ), '', true );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	}


	public function render_content() {
		$value = json_encode( $this->value() );
		$id = $this->id;
		?>
		<label>
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
			<?php endif; ?>
			<input type="hidden" <?php $this->input_attrs(); ?> value="" <?php echo $this->get_link(); ?> />
		</label>

		<ul class="repeater-fields"></ul>

		<button class="button-secondary repeater-add"><?php echo esc_html( $this->button_label ); ?></button>
		<?php

		$this->repeater_js_template();

	}

	public function repeater_js_template() {
		?>
		<script type="text/html" class="customize-control-repeater-content">
			<# var field; var index = data['index']; #>


			<li class="repeater-row" data-row="{{{ index }}}">

				<div class="repeater-row-header">
					<span class="repeater-row-number"></span>
					<span class="repeater-row-remove"><i class="dashicons dashicons-no-alt repeater-remove"></i></span>
					<span class="repeater-row-minimize"><i class="dashicons dashicons-arrow-up repeater-minimize"></i></span>
					<span class="repeater-row-move"><i class="dashicons dashicons-sort repeater-move"></i></span>
				</div>

				<# for ( i in data ) { #>
					<# if ( ! data.hasOwnProperty( i ) ) continue; #>
					<# field = data[i]; #>
					<# if ( ! field.type ) continue; #>

					<div class="repeater-field repeater-field-{{{ field.type }}}">

						<# if ( field.type === 'text' ) { #>

							<label>
								<# if ( field.label ) { #>
									<span class="customize-control-title">{{ field.label }}</span>
								<# } #>
								<# if ( field.description ) { #>
									<span class="description customize-control-description">{{ field.description }}</span>
								<# } #>
								<input type="text" name="" value="{{{ field.default }}}" data-field="{{{ field.id }}}">
							</label>

						<# } else if ( field.type === 'checkbox' ) { #>

							<label>
								<input type="checkbox" value="true" data-field="{{{ field.id }}}" <# if ( field.default ) { #> checked="checked" <# } #> />
								<# if ( field.description ) { #>
									{{ field.description }}
								<# } #>
							</label>

						<# } else if ( field.type === 'select' ) { #>

							<label>
								<# if ( field.label ) { #>
									<span class="customize-control-title">{{ field.label }}</span>
								<# } #>
								<# if ( field.description ) { #>
									<span class="description customize-control-description">{{ field.description }}</span>
								<# } #>
								<select data-field="{{{ field.id }}}">
									<# for ( i in field.choices ) { #>
										<# if ( field.choices.hasOwnProperty( i ) ) { #>
											<option value="{{{ i }}}" <# if ( field.default == i ) { #> selected="selected" <# } #>>{{ field.choices[i] }}</option>
										<# } #>
									<# } #>
								</select>
							</label>

						<# } else if ( field.type === 'radio' ) { #>

							<label>
								<# if ( field.label ) { #>
									<span class="customize-control-title">{{ field.label }}</span>
								<# } #>
								<# if ( field.description ) { #>
									<span class="description customize-control-description">{{ field.description }}</span>
								<# } #>

								<# for ( i in field.choices ) { #>
									<# if ( field.choices.hasOwnProperty( i ) ) { #>
										<label>
											<input type="radio" data-field="{{{ field.id }}}" value="{{{ i }}}" <# if ( field.default == i ) { #> checked="checked" <# } #>> {{ field.choices[i] }} <br/>
										</label>
									<# } #>
								<# } #>
							</label>

						<# } else if ( field.type == 'textarea' ) { #>

							<# if ( field.label ) { #>
								<span class="customize-control-title">{{ field.label }}</span>
							<# } #>
							<# if ( field.description ) { #>
								<span class="description customize-control-description">{{ field.description }}</span>
							<# } #>
							<textarea rows="5" data-field="{{{ field.id }}}">{{ field.default }}</textarea>

						<# } else if ( field.type == 'image' ) { console.log(field); #>

							<label for="{{ field.id }}-{{ index }}-button">
								<# if ( field.label ) { #>
									<span class="customize-control-title">{{ field.label }}</span>
								<# } #>
								<# if ( field.description ) { #>
									<span class="description customize-control-description">{{{ field.description }}}</span>
								<# } #>
							</label>

							<div class="current">
								<div class="container">
									<div class="placeholder">
										<div class="inner">
											<span>
												{{{ field.buttonLabels.placeholder }}}
											</span>
										</div>
									</div>
								</div>
							</div>
							<div class="actions">
								<# if ( field.attachment ) { #>
									<button type="button" class="button default-button">{{{ field.buttonLabels.default }}}</button>
								<# } #>
								<# if ( field.canUpload ) { #>
									<button type="button" class="button upload-button" id="{{ field.id }}-{{ index }}-button">{{{ field.buttonLabels.select }}}</button>
								<# } #>
								<div style="clear:both"></div>
							</div>
						<# } #>
					</div>
				<# } #>
			</li>
		</script>
		<?php
	}

}
