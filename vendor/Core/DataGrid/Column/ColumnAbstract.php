<?php

/**
 * Core_DataGrid_Column_AbstractColumn
 */
abstract class Core_DataGrid_Column_ColumnAbstract {

	protected $_identity;

	protected $_name;

	protected $_label;

	protected $_options;

	protected $_decorator;

	protected $_sortable = false;

	public function __construct($name, $label = null, $options = array()) {
		$this->_name = $name;
		$this->_identity = $name;
		$this->_options = $options;

		if (isset($this->_options['decorator'])) {
			$this->_decorator = $this->_options['decorator'];
		}

		if(isset($this->_options['sortable'])) {
			$this->_sortable = (boolean) $this->_options['sortable'];
		}

		if (null === $label) {
			$this->_label = $this->_name;
		} else {
			$this->_label = $label;
		}

		$this->_init();
	}

	protected function _init() {}

	/**
	 *
	 * @param array $row
	 * @return string
	 */
	abstract public function render(array $row);

	/**
	 *
	 * @return string
	 */
	public function getIdentity() {
		return $this->_identity;
	}

	/**
	 *
	 * @param string $identity
	 */
	public function setIdentity($identity) {
		$this->_identity = $identity;
	}

	/**
	 *
	 * @return the $_name
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->_label;
	}

	/**
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->_name = $name;
	}

	/**
	 *
	 * @param string $label
	 */
	public function setLabel($label) {
		$this->_label = $label;
	}

	public function setOptions($options) {
		$this->_options = $options;
	}

	public function getOptions() {
		return $this->_options;
	}

	public function getOption($key, $default=null) {
		return array_key_exists($key, $this->_options)? $this->_options[$key]: $default;
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasDecorator() {
		return ! empty($this->_decorator);
	}

	/**
	 *
	 * @return boolean
	 */
	public function isSortable() {
		return $this->_sortable;
	}

	/**
	 * Tenta obter o valor da coluna no array $row.
	 *
	 * @param array $row
	 * @param mixed $default
	 * @return mixed
	 */
	public function getValueInRow(array $row, $default = null) {
		return array_key_exists($this->_identity, $row) ? $row[$this->_identity] : $default;
	}

	/**
	 *
	 * @param string $decorator
	 * @param array $row
	 * @return string
	 */
	protected function _applyDecorator(array $row, $decorator=null) {
		$decorator = empty($decorator)? $this->_decorator: $decorator;

		foreach($row as $key => $value) {
			$decorator = str_replace('{{' . $key . '}}', $value, $decorator);
		}

		return $decorator;
	}

}