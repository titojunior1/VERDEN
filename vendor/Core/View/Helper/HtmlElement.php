<?php

/**
 * Total_View_Helper_HtmlElement
 */
abstract class Core_View_Helper_HtmlElement extends Core_View_Helper_HelperAbstract {

	/**
	 *
	 * @return string
	 */
	public function getClosingBracket() {
// 		if (! $this->_closingBracket) {
// 			if ($this->_isXhtml()) {
// 				$this->_closingBracket = ' />';
// 			} else {
// 				$this->_closingBracket = '>';
// 			}
// 		}

		return ' />';
	}

	/**
	 * Converte um array associativo em atributos para as tags html.
	 *
	 * @param array $attribs
	 * @return string
	 */
	protected function _htmlAttribs($attribs) {
		$xhtml = '';

		foreach((array) $attribs as $key => $val) {
			$key = $this->_escape($key);

			if (('on' == substr($key, 0, 2)) || ('constraints' == $key)) {
				// Don't escape event attributes; _do_ substitute double quotes with singles
				if (! is_scalar($val)) {
					// non-scalar data should be cast to JSON first
					$val = json_encode($val);
				}

				// Escapa aspas simples dentro dos valores de atributos de eventos
				// Criará um html, onde os valores dos atributos tem
				// aspas simples ao redor, e escapa as aspas simples ou aspas duplas não escapadas.
				$val = str_replace('\'', '&#39;', $val);
			} else {
				if (is_array($val)) {
					$val = implode(' ', $val);
				}
				$val = $this->_escape($val);
			}

			if ('id' == $key) {
				$val = $this->_normalizeId($val);
			}

			if (strpos($val, '"') !== false) {
				$xhtml .= " $key='$val'";
			} else {
				$xhtml .= " $key=\"$val\"";
			}

		}

		return $xhtml;
	}

	/**
	 * Normaliza um ID.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function _normalizeId($value) {
		if (strstr($value, '[')) {
			if ('[]' == substr($value, - 2)) {
				$value = substr($value, 0, strlen($value) - 2);
			}
			$value = trim($value, ']');
			$value = str_replace('][', '-', $value);
			$value = str_replace('[', '-', $value);
		}
		return $value;
	}
}