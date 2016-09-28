<?php

abstract class Core_View_Helper_FormElement extends Core_View_Helper_HtmlElement {

	/**
	 * Converts parameter arguments to an element info array.
	 *
	 * E.g, formExample($name, $value, $attribs, $options, $listsep) is
	 * the same thing as formExample(array('name' => ...)).
	 *
	 * Note that you cannot pass a 'disable' param; you need to pass
	 * it as an 'attribs' key.
	 *
	 * @access protected
	 *
	 * @return array An element info array with keys for name, value,
	 *         attribs, options, listsep, disable, and escape.
	 */
	protected function _getInfo($name, $value = null, $attribs = null, $options = null, $listsep = null) {
		// the baseline info.  note that $name serves a dual purpose;
		// if an array, it's an element info array that will override
		// these baseline values.  as such, ignore it for the 'name'
		// if it's an array.
		$info = array(
			'name'=>is_array($name) ? '' : $name,
				'id'=>is_array($name) ? '' : $name,
				'value'=>$value,
				'attribs'=>$attribs,
				'options'=>$options,
				'listsep'=>$listsep,
				'disable'=>false,
				'escape'=>true
		);

		// override with named args
		if (is_array($name)) {
			// only set keys that are already in info
			foreach($info as $key => $val) {
				if (isset($name[$key])) {
					$info[$key] = $name[$key];
				}
			}

			// If all helper options are passed as an array, attribs may have
			// been as well
			if (null === $attribs) {
				$attribs = $info['attribs'];
			}
		}

		$attribs = (array) $attribs;

		// Normalize readonly tag
		if (array_key_exists('readonly', $attribs)) {
			$attribs['readonly'] = 'readonly';
		}

		// Disable attribute
		if (array_key_exists('disable', $attribs)) {
			if (is_scalar($attribs['disable'])) {
				// disable the element
				$info['disable'] = (bool) $attribs['disable'];
			} else if (is_array($attribs['disable'])) {
				$info['disable'] = $attribs['disable'];
			}
		}

		// Set ID for element
		if (array_key_exists('id', $attribs)) {
			$info['id'] = (string) $attribs['id'];
		} else if ('' !== $info['name']) {
			$info['id'] = trim(strtr($info['name'], array(
				'['=>'-', ']'=>''
			)), '-');
		}

		// Remove NULL name attribute override
		if (array_key_exists('name', $attribs) && is_null($attribs['name'])) {
			unset($attribs['name']);
		}

		// Override name in info if specified in attribs
		if (array_key_exists('name', $attribs) && $attribs['name'] != $info['name']) {
			$info['name'] = $attribs['name'];
		}

		// Determine escaping from attributes
		if (array_key_exists('escape', $attribs)) {
			$info['escape'] = (bool) $attribs['escape'];
		}

		// Determine listsetp from attributes
		if (array_key_exists('listsep', $attribs)) {
			$info['listsep'] = (string) $attribs['listsep'];
		}

		// Remove attribs that might overwrite the other keys. We do this LAST
		// because we needed the other attribs values earlier.
		foreach($info as $key => $val) {
			if (array_key_exists($key, $attribs)) {
				unset($attribs[$key]);
			}
		}
		$info['attribs'] = $attribs;

		// done!
		return $info;
	}

	/**
	 * Creates a hidden element.
	 *
	 * We have this as a common method because other elements often
	 * need hidden elements for their operation.
	 *
	 * @access protected
	 *
	 * @param string $name The element name.
	 * @param string $value The element value.
	 * @param array $attribs Attributes for the element.
	 *
	 * @return string A hidden element.
	 */
	protected function _hidden($name, $value = null, $attribs = null) {
		return '<input type="hidden"'
					. ' name="' . $this->_escape($name) . '"'
					. ' value="' . $this->_escape($value) . '"'
					. $this->_htmlAttribs($attribs)
					. $this->getClosingBracket();
	}

}