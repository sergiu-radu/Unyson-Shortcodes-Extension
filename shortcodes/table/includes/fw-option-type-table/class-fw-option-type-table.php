<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Option_Type_Table extends FW_Option_Type {

	private $internal_options;

	protected function _init() {
		$button = fw()->extensions->get( 'shortcodes' )->get_shortcode( 'button' );
		if ( $button ) {
			$this->internal_options['button-options'] = array(
				'type'          => 'popup',
				'popup-title'   => __( 'Button', 'fw' ),
				'button'        => __( 'Add', 'fw' ),
				'popup-options' => $button->get_options()
			);
		}

		$this->internal_options['switch-options'] = array(
			'label'        => false,
			'type'         => 'switch',
			'right-choice' => array(
				'value' => 'yes',
				'label' => __( 'Yes', 'fw' )
			),
			'left-choice'  => array(
				'value' => 'no',
				'label' => __( 'No', 'fw' )
			),
			'value'        => 'no',
			'desc'         => false,
		);

		$this->internal_options['pricing-options'] = array(
			'amount' => array(
				'type'  => 'text',
				'label' => false,
				'desc'  => false,
				'value' => '',
				'attr' => array(
					'class' => 'fw-col-sm-6',
				)
			),
			'description' => array(
				'type'  => 'text',
				'label' => false,
				'desc'  => false,
				'value' => '',
				'attr' => array(
					'class' => 'fw-col-sm-6',
				)
			),
		);

	}

	public function get_type() {
		return 'table';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$table_shortcode = fw()->extensions->get( 'shortcodes' )->get_shortcode( 'table' );

		$static_uri = $table_shortcode->get_declared_uri() . '/includes/fw-option-type-table/static/';

		wp_enqueue_style('fw-font-awesome');

		wp_enqueue_style(
			'fw-option-' . $this->get_type() . '-default',
			$static_uri . 'css/default-styles.css',
			array(),
			fw()->theme->manifest->get_version()
		);
		wp_enqueue_style(
			'fw-option-' . $this->get_type() . '-extended',
			$static_uri . 'css/extended-styles.css',
			array(),
			fw()->theme->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$static_uri . 'js/scripts.js',
			array( 'jquery', 'fw-events',  'jquery-ui-sortable' ),
			fw()->theme->manifest->get_version(),
			true
		);

		wp_localize_script( 'fw-option-' . $this->get_type(), 'localizeTableBuilder', array( 'msgEdit' => __( 'Edit', 'fw' ) ) );
		fw()->backend->option_type( 'popup' )->enqueue_static();
		fw()->backend->option_type( 'textarea-cell' )->enqueue_static();
	}


	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$table_shortcode = fw()->extensions->get( 'shortcodes' )->get_shortcode( 'table' );

		if ( ! $table_shortcode ) {
			trigger_error(
				__( 'table-builder option type must be inside the table shortcode', 'fw' ),
				E_USER_ERROR
			);
		}

		if ( ! isset( $data['value'] ) || empty( $data['value'] ) ) {
			$data['value'] = $option['value'];
		}

		$this->replace_with_defaults( $option );
		$view_path = $table_shortcode->get_declared_path() . '/includes/fw-option-type-table/views/view.php';

		return fw_render_view( $view_path, array(
			'id'               => $option['attr']['id'],
			'option'           => $option,
			'data'             => $data,
			'internal_options' => $this->internal_options
		) );
	}

	protected function replace_with_defaults( &$option ) {
		$option['row_options']['name']['attr']['class']     = 'fw-table-builder-row-style';
		$option['columns_options']['name']['attr']['class'] = 'fw-table-builder-col-style';
		$defaults                                   = $this->_get_defaults();
		$option['row_options']['name']['choices']           = $defaults['row_options']['name']['choices'];
		$option['columns_options']['name']['choices']       = $defaults['columns_options']['name']['choices'];
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {

		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		if ( ! isset( $input_value['content'] ) || empty( $input_value['content'] ) ) {
			$input_value['content'] = $option['value']['content'];
		}

		if ( ! isset( $input_value['rows'] ) || empty( $input_value['rows'] ) ) {
			$input_value['rows'] = $option['value']['rows'];
		}

		if ( ! isset( $input_value['cols'] ) || empty( $input_value['cols'] ) ) {
			$input_value['cols'] = $option['value']['cols'];
		}

		if ( isset( $input_value['content']['_template_key_row_'] ) ) {
			unset( $input_value['content']['_template_key_row_'] );
		}

		if ( isset( $input_value['rows']['_template_key_row_'] ) ) {
			unset( $input_value['rows']['_template_key_row_'] );
		}

		$value = array();
		if ( is_array( $input_value ) ) {
			if (isset($input_value['rows'])) {
				$value['rows'] = array_values($input_value['rows']);
			}

			if (isset($input_value['cols'])) {
				$value['cols'] = array_values($input_value['cols']);
			}

			if (isset($input_value['content']) && is_array($input_value['content']) ) {
				$i = 0;
				foreach($input_value['content'] as $row => $input_value_rows_data) {
					$cols = array();
					$j = 0;
					foreach($input_value_rows_data as $column => $input_value_cols_data) {
						$row_name = $value['rows'][$i]['name'];

						foreach($option['content_options'][$row_name] as $id => $options) {
							$cols[$j][$id] = fw()->backend->option_type($options['type'])->get_value_from_input($options, $input_value_cols_data[$row_name][$id . '-' . $row . '-' . $column ]);
						}

						$j++;
					}
					$value['content'][$i] = $cols;
					$i++;
				}
			}


		}

		return $value;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'row_options'     => array(
				'name' => array(
					'type'  => 'select',
					'label' => false,
					'desc'  => false,
					'choices' => array(
						'default-row' => __( 'Default row', 'fw' ),
						'heading-row' => __( 'Heading row', 'fw' ),
						'pricing-row' => __( 'Pricing row', 'fw' ),
						'button-row'  => __( 'Button row', 'fw' ),
						'switch-row' => __('Row switch', 'fw')
						)
					)
			),
			'columns_options' => array(
				'name' => array(
					'type'  => 'select',
					'label' => false,
					'desc'  => false,
					'choices' => array(
						''              => __( 'Default column', 'fw' ),
						'highlight-col' => __( 'Highlight column', 'fw' ),
						'desc-col'      => __( 'Description column', 'fw' ),
						'center-col'    => __( 'Center text column', 'fw' )
					)
				)
			),
			'content_options' => array(
				'default-row' => array(
					'textarea' => array(
						'type' => 'textarea-cell',
						'label' => false,
						'desc' => false,
						'value' => ''
					)
				),
				'heading-row' => array(
					'textarea' => array(
						'type' => 'textarea-cell',
						'label' => false,
						'desc' => false,
						'value' => ''
					)
				),
				'pricing-row' => array(
					'amount' => array(
						'type'  => 'text',
						'label' => false,
						'desc'  => false,
						'value' => '',
						'attr' => array(
							'class' => 'fw-col-sm-6',
						)
					),
					'description' => array(
						'type'  => 'text',
						'label' => false,
						'desc'  => false,
						'value' => '',
						'attr' => array(
							'class' => 'fw-col-sm-6',
						)
					),
				),
				'button-row' => array(
					'button' => $this->internal_options['button-options']
				),
				'switch-row' => array(
					'switch' => $this->internal_options['switch-options']
				)

			),
			'value'           => array(
				'cols'  => array( array('name'=> ''), array('name'=> ''), array('name'=> '') ),
				'rows'  => array( array('name'=> 'default-row'), array('name'=> 'default-row'), array('name'=> 'default-row') ),
				'content' => $this->_fw_generate_default_values()
			)
		);
	}

	private function _fw_generate_default_values( $cols = 3, $rows = 3 ) {
		$result = array();
		for ( $i = 0; $i < $rows; $i ++ ) {
			for ( $j = 0; $j < $cols; $j ++ ) {
				$result[ $i ][ $j ]['button']      = '';
				$result[ $i ][ $j ]['textarea']    = '';
				$result[ $i ][ $j ]['switch']      = array();
				$result[ $i ][ $j ]['amount']      = '';
				$result[ $i ][ $j ]['description'] = '';
			}
		}

		return $result;
	}


	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

}

FW_Option_Type::register( 'FW_Option_Type_Table' );