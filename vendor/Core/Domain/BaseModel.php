<?php

/**
 * Core_Domain_BaseModel
 *
 * Utilizada para definir um objeto de domínio.
 */
class Core_Domain_BaseModel {

	public function __construct($options = array()) {
		if (is_array($options)) {
			$this->fromArray($options);
		}
	}

	public function __set($name, $value) {
		$method = 'set' . $name;
		if (('mapper' == $name) || ! method_exists($this, $method)) {
			throw new Exception("Invalid property '$name'");
		}
		$this->$method($value);
	}

	public function __get($name) {
		$method = 'get' . $name;
		if (('mapper' == $name) || ! method_exists($this, $method)) {
			throw new Exception("Invalid property '$name'");
		}
		return $this->$method();
	}

	/**
	 *
	 * @param array $options
	 * @return Total_BaseDomainModel
	 */
	public function fromArray(array $options) {
		$methods = get_class_methods($this);
		foreach($options as $key => $value) {
			$method = 'set' . ucfirst($key);
			if (in_array($method, $methods)) {
				$this->$method($value);
			} else if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function toArray() {
		$vars = get_object_vars($this);
		$data = array();

		foreach($vars as $key => $value) {
			$data[str_replace('_', '', $key)] = $value;
		}

		return $data;
	}

	/**
	 * Implementar conforme as regras de negócios da entidade.
	 *
	 */
	public function validate($data) {
	}
}