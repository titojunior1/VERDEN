<?php

/**
 * Total_View_Helper_HelperAbstract
 *
 * Utilizada para a implementação de view helpers.
 *
 */
abstract class Core_View_Helper_HelperAbstract {

	/**
	 *
	 * @var Core_View
	 */
	protected $_view;

	/**
	 *
	 * @param Core_View $view
	 */
	public function __construct(Core_View $view = null) {
		if(null === $view) {
			$view = new Core_View();
		}

		$this->_view = $view;
	}

	/**
	 *
	 * @param string $var
	 * @return string
	 */
	protected function _escape($var) {
		return $this->_view->escape($var);
	}

}