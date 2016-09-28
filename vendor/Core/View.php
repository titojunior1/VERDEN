<?php

/**
 * Total_ViewHelper
 *
 * Responsável por carregar as classes ViewHelper definidas.
 */
class Core_View {

	/**
	 *
	 * @var string
	 */
	private static $_classPrefix = 'Core_View_Helper_';

	/**
	 *
	 * @var array
	 */
	private static $_cachedHelpers = array();

	/**
	 *
	 * @var string
	 */
	private static $_escape = 'htmlspecialchars';

	/**
	 *
	 * @var string
	 */
	private static $_encoding = 'UTF-8';

	/**
	 *
	 * @var array
	 */
	private $_vars = array();

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->_vars[$name] = $value;
	}

	/**
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return array_key_exists($name, $this->_vars)? $this->_vars[$name]: null;
	}

	/**
	 * Permite o teste com as funçoes empty() e isset().
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key) {
		return array_key_exists($key, $this->_vars);
	}

	/**
	 *
	 * @param string $key
	 */
	public function __unset($key) {
		if(array_key_exists($key, $this->_vars)) {
			unset($this->_vars[$key]);
		}
	}

	/**
	 * Executa o view helper.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args) {
		$helper = self::getHelper($name);

		return call_user_func_array(array($helper, $name), $args);
	}


	/**
	 *
	 * @param string $escape
	 */
	public static function setDefaultEscape($escape) {
		self::$_escape = $escape;
	}

	/**
	 *
	 * @param string $encoding
	 */
	public static function setDefaultEncoding($encoding) {
		self::$_encoding = $encoding;
	}

	/**
	 *
	 * @param string $helperName
	 * @param mixed $args
	 * @param boolean $new
	 * @throws Exception
	 * @return Total_View_Helper_HelperAbstract
	 */
	public static function getHelper($helperName) {

		$className = self::$_classPrefix . ucfirst($helperName);

		if (! class_exists($className)) {
			throw new Exception("Class $className not found.");
		}

		if (! isset(self::$_cachedHelpers[$className])) {
			self::$_cachedHelpers[$className] = new $className(new self());
		}

		return self::$_cachedHelpers[$className];
	}

	public function escape($value) {
		if (in_array(self::$_escape, array(
			'htmlspecialchars', 'htmlentities'
		))) {
			return call_user_func(self::$_escape, $value, ENT_COMPAT, self::$_encoding);
		}

		if (1 == func_num_args()) {
			return call_user_func(self::$_escape, $value);
		}
		$args = func_get_args();
		return call_user_func_array(self::$_escape, $args);
	}
}