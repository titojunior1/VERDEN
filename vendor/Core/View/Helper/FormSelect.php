<?php

class Core_View_Helper_FormSelect extends Core_View_Helper_FormElement {

	/**
	 * Generates 'select' list of options.
	 *
	 * @access public
	 *
	 * @param string|array $name If a string, the element name. If an
	 *        array, all other parameters are ignored, and the array elements
	 *        are extracted in place of added parameters.
	 *
	 * @param mixed $value The option value to mark as 'selected'; if an
	 *        array, will mark all values in the array as 'selected' (used for
	 *        multiple-select elements).
	 *
	 * @param array|string $attribs Attributes added to the 'select' tag.
	 *        the optional 'optionClasses' attribute is used to add a class to
	 *        the options within the select (associative array linking the option
	 *        value to the desired class)
	 *
	 * @param array $options An array of key-value pairs where the array
	 *        key is the radio value, and the array value is the radio text.
	 *
	 * @param string $listsep When disabled, use this list separator string
	 *        between list values.
	 *
	 * @return string The select tag and options XHTML.
	 */
	public function formSelect($name, $value = null, $attribs = null, $options = null, $listsep = "<br />\n") {
		$info = $this->_getInfo($name, $value, $attribs, $options, $listsep);
		extract($info); // name, id, value, attribs, options, listsep, disable


		// força o $value como um array, então podemos comparar multiplos
		// valores para multiplas opções; também garante que seja string
		// para proposito de comparação
		$value = array_map('strval', (array) $value);

		// verifica se o elemento tem multiplos valores
		$multiple = '';

		if (substr($name, - 2) == '[]') {
			$multiple = ' multiple="multiple"';
		}

		if (isset($attribs['multiple'])) {
			// Define o atributo
			if ($attribs['multiple']) {
				$multiple = ' multiple="multiple"';

				// Certifica-se que o nome indica que valores
				// multiplos são permitidos
				if (! empty($multiple) && (substr($name, - 2) != '[]')) {
					$name .= '[]';
				}
			} else {
				$multiple = '';
			}
			unset($attribs['multiple']);
		}

		// handle the options classes
		$optionClasses = array();
		if (isset($attribs['optionClasses'])) {
			$optionClasses = $attribs['optionClasses'];
			unset($attribs['optionClasses']);
		}

		// now start building the XHTML.
		$disabled = '';
		if (true === $disable) {
			$disabled = ' disabled="disabled"';
		}

		$emptyOption = array();

		if (isset($attribs['emptyOption'])) {
		    $emptyOption = $attribs['emptyOption'];
		    unset($attribs['emptyOption']);
		}

		// Build the surrounding select element first.
		$xhtml = '<select' . ' name="' . $this->_escape($name) . '"' . ' id="' . $this->_escape($id) . '"' . $multiple . $disabled . $this->_htmlAttribs($attribs) . ">\n    ";

		// build the list of options
		$list = array();

		if (!empty($emptyOption)) {
			$list[] = $this->_build('', $emptyOption, array(), false);
			unset($attribs['emptyOption']);
		}

		foreach((array) $options as $opt_value => $opt_label) {
			if (is_array($opt_label)) {
				$opt_disable = '';
				if (is_array($disable) && in_array($opt_value, $disable)) {
					$opt_disable = ' disabled="disabled"';
				}

				$opt_id = ' id="' . $this->_escape($id) . '-optgroup-' . $this->_escape($opt_value) . '"';
				$list[] = '<optgroup' . $opt_disable . $opt_id . ' label="' . $this->_escape($opt_value) . '">';

				foreach($opt_label as $val => $lab) {
					$list[] = $this->_build($val, $lab, $value, $disable, $optionClasses);
				}

				$list[] = '</optgroup>';
			} else {
				$list[] = $this->_build($opt_value, $opt_label, $value, $disable, $optionClasses);
			}
		}

		// adiciona os options no html e fecha o select
		$xhtml .= implode("\n    ", $list) . "\n</select>";

		return $xhtml;
	}

	/**
	 * Constrói a tag <option>
	 *
	 * @param string $value Options Value
	 * @param string $label Options Label
	 * @param array $selected The option value(s) to mark as 'selected'
	 * @param array|bool $disable Whether the select is disabled, or individual options are
	 * @param array $optionClasses The classes to associate with each option value
	 * @return string Option Tag XHTML
	 */
	protected function _build($value, $label, $selected, $disable, $optionClasses = array()) {
		if (is_bool($disable)) {
			$disable = array();
		}

		$class = null;
		if (array_key_exists($value, $optionClasses)) {
			$class = $optionClasses[$value];
		}

		$opt = '<option' . ' value="' . $this->_escape($value) . '"';

		if ($class) {
			$opt .= ' class="' . $class . '"';
		}
		// selected?
		if (in_array((string) $value, $selected)) {
			$opt .= ' selected="selected"';
		}

		// disabled?
		if (in_array($value, $disable)) {
			$opt .= ' disabled="disabled"';
		}

		$opt .= '>' . $this->_escape($label) . "</option>";

		return $opt;
	}

}