<?php
/**
 * Base Fields Class
 *
 * Verison: 0.2
 *
 * @since 0.2 Added checkboxes field.
 *
 * Author: Cloud Stone <cloudinstone@gmail.com>
 * License: GPLv2 or late
 */

namespace SC_Reward;

class BaseFields {
	protected $wrap_type = 'empty';
	protected $default_field_args = array();
	protected $desc_tag = 'p';

	public function __construct() {

	}

	public function renderField( $field ) {
		$field = array_merge( $this->getWrapArgs(), $this->default_field_args, $field );

		$field_id = $this->getId( $field );

		$wrap_id = isset( $field['wrap_id'] ) ? $field['wrap_id'] : $field_id . '-wrap';

		echo sprintf( $field['before_wrap'], $this->buildWrapClass( $field ), $wrap_id );

		// Render label.
		if ( ! $field['label'] === false ) {
			echo sprintf( $field['before_label'], 'field-label' );
			if ( isset( $field['label'] ) ) {
				$label = $field['label'];

				if ( ! isset( $field['no_label_tag_wrap'] ) ) {
					$label = '<label for="' . $field_id . '">' . $label . '</label>';
				}

				echo $label;

			}
			echo $field['after_label'];
		}

		echo sprintf( $field['before_control'], 'field-control' );

		$this->control( $field );

		// Render description.
		if ( isset( $field['desc'] ) ) {
			$desc_tag = isset( $field['desc_tag'] ) ? $field['desc_tag'] : $this->desc_tag;
			echo '<' . $desc_tag . ' class="description">' . $field['desc'] . '</' . $desc_tag . '>';
		}

		echo $field['after_control'];

		echo $field['after_wrap'];
	}

	public function formatField( $field ) {
		if ( $field['type'] == 'radio' || $field['type'] == 'checkboxes' ) {
			$field['no_label_tag_wrap'] = true;
		}

		return $field;
	}

	public function control( $field ) {
		if ( isset( $field['callback'] ) ) {
			$func = $field['callback'];
		} elseif ( isset( $field['type'] ) && method_exists( $this, $field['type'] ) ) {
			$func = array( $this, $field['type'] );
		} else {
			$func = array( $this, 'input' );
		}

		return call_user_func( $func, $field );
	}

	public function input( $field ) {
		$atts          = $this->buildCommonAtts( $field, true );
		$atts['type']  = $field['type'];
		$atts['value'] = $this->getValue( $field );

		if ( $field['type'] == 'number' ) {
			$atts['min'] = 0;
		}

		$attr = $this->array2attr( $atts );

		echo "<input{$attr}>";
	}

	public function checkbox( $field ) {
		$atts         = $this->buildCommonAtts( $field, true );
		$atts['type'] = $field['type'];

		if ( ! isset( $atts['value'] ) ) {
			$atts['value'] = true;
		}

		if ( ! empty( $this->getValue( $field ) ) ) {
			$atts['checked'] = 'checked';
		}

		$attr = $this->array2attr( $atts );

		$label = isset( $field['append_label'] ) ? '<label for="' . $atts['id'] . '">' . $field['append_label'] . '</label>' : '';

		echo "<input{$attr}> {$label}";
	}

	public function checkboxes( $field ) {
		$value  = $this->getValue( $field, array() );
		$value = is_array($value) ? $value : array();
		$choices = $this->getChoices( $field );

		foreach ( $choices as $key => $label ) {
			$atts = array(
				'type'  => 'checkbox',
				'name'  => $field['name'] . '[]',
				'value' => $key

			);

			if ( in_array( $key, $value ) ) {
				$atts['checked'] = 'checked';
			}
			$attr = $this->array2attr( $atts );

			$label = '<label>' . $label . '</label>';

			echo isset( $field['before_checkbox'] ) ? $field['before_checkbox'] : '';
			echo "<input{$attr}> {$label} ";
			if(isset($field['sort_name']))
				echo '<input type="hidden" name="'.$field['sort_name'].'[]" value="'.$key.'">';
			echo isset( $field['after_checkbox'] ) ? $field['after_checkbox'] : '';
		}

	}

	public function radio( $field ) {
		$value   = $this->getValue( $field );
		$choices = $this->getChoices( $field );

		foreach ( $choices as $key => $label ) {
			$field['value']   = $key;
			$field['checked'] = $key == $value ? 'checked' : '';

			$this->input( $field );
		}
	}

	public function textarea( $field ) {
		$attr  = $this->buildCommonAtts( $field );
		$value = $this->getValue( $field );

		echo "<textarea{$attr}>{$value}</textarea>";
	}

	public function select( $field ) {
		$attr    = $this->buildCommonAtts( $field );
		$value   = $this->getValue( $field );
		$choices = $this->getChoices( $field );

		echo "<select{$attr}>";
		foreach ( $choices as $key => $label ) {
			echo '<option' . selected( $key, $value, false ) . ' value="' . $key . '">' . $label . '</option>';
		}
		echo '</select>';
	}

	public function setWrapType( $wrap_type ) {
		$this->wrap_type = $wrap_type;

		return $this;
	}

	public function setDefaultFieldArgs( $args ) {
		$this->default_field_args = $args;

		return $this;
	}

	public function setDescTag( $tag ) {
		$this->desc_tag = $tag;

		return $this;
	}

	public function getWrapArgs() {
		return $this->getPresetWraps()[ $this->wrap_type ];
	}

	public function buildWrapClass( $field ) {
		$wrap_classes = array( 'field-wrap' );

		if ( isset( $field['type'] ) ) {
			$wrap_classes[] = 'field-wrap-' . $field['type'];
		}

		if ( isset( $field['wrap_class'] ) ) {
			$wrap_classes[] = $field['wrap_class'];
		}

		return implode( ' ', $wrap_classes );
	}

	public function buildCommonAtts( $field, $return_array = false ) {
		$atts = array(
			'id' => $this->getId( $field ),
		);

		if ( isset( $field['name'] ) ) {
			$atts['name'] = $field['name'];
		}

		if ( isset( $field['class'] ) ) {
			$atts['class'] = $field['class'];
		}

		if ( isset( $field['atts'] ) ) {
			$atts = array_merge( $atts, $field['atts'] );
		}

		// Only `input` tag need `value` attribute.
		if ( isset( $atts['value'] ) ) {
			unset( $atts['value'] );
		}

		if ( $return_array ) {
			return $atts;
		} else {
			return $this->array2attr( $atts );
		}
	}

	public function getChoices( $field ) {
		$choices = array();
		if ( isset( $field['choices'] ) ) {
			$choices = $field['choices'];
		} elseif ( isset( $field['choices_callback'] ) ) {
			$callback_args = isset( $field['chocies_callback_args'] ) ? $field['chocies_callback_args'] : null;

			$choices = call_user_func( $field['choices_callback'], $callback_args );
		}

		if ( ! $this->isAssoc( $choices ) ) {
			$choices = array_combine( array_values( $choices ), array_values( $choices ) );
		}

		return $choices;
	}

	public function getPresetWraps() {
		return array(
			'table-row' => array(
				'before_wrap'    => '<tr class="%1$s" id="%2$s">',
				'before_label'   => '<th class="%s">',
				'after_label'    => '</th>',
				'before_control' => '<td class="%s">',
				'after_control'  => '</td>',
				'after_wrap'     => '</tr>',
			),
			'list-item' => array(
				'before_wrap'    => '<li class="%1$s" id="%2$s">',
				'before_label'   => '<span class="%s">',
				'after_label'    => '</span>',
				'before_control' => '<span class="%s">',
				'after_control'  => '</span>',
				'after_wrap'     => '</li>',
			),
			'flex-col'  => array(
				'before_wrap'    => '<div class="%1$s" id="%2$s">',
				'before_label'   => '<div class="%s">',
				'after_label'    => '</div>',
				'before_control' => '<div class="%s">',
				'after_control'  => '</div>',
				'after_wrap'     => '</div>',
			),
			'empty'     => array(
				'before_wrap'    => '',
				'before_label'   => '',
				'after_label'    => '',
				'before_control' => '',
				'after_control'  => '',
				'after_wrap'     => '',
			)

		);
	}

	public function getId( $field ) {
		$id = '';

		if ( isset( $field['atts']['id'] ) ) {
			$id = $field['atts']['id'];
		} elseif ( isset( $field['id'] ) ) {
			$id = $field['id'];
		} elseif ( isset( $field['name'] ) ) {
			$id = str_replace( '[', '-', $field['name'] );
		}

		if ( $id ) {
			$id = $this->sanitizeKey( $id );
		}

		return $id;
	}

	public function getValue( $field, $fallback = '' ) {
		if ( isset( $field['value'] ) ) {
			$value = $field['value'];
		} elseif ( isset( $field['default'] ) ) {
			$value = $field['default'];
		} else {
			$value = $fallback;
		}

		return $value;
	}

	public function sanitizeKey( $key ) {
		$key = strtolower( $key );
		$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );

		return $key;
	}

	public function isAssoc( array $arr ) {
		if ( array() === $arr ) {
			return false;
		}

		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	public function array2attr( $array ) {
		$attr = '';
		foreach ( $array as $key => $value ) {
			if ( in_array( $key, array( 'disabled', 'checked', 'hidden' ) ) ) {
				if ( $value ) {
					$attr .= ' ' . $key;
				}
			} else {
				$attr .= ' ' . $key . '="' . $value . '"';
			}
		}

		return $attr;
	}
}