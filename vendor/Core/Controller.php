<?php

/**
 * Core_ControllerAbstract
 *
 * Implementação simples de controller
 *
 */
class Core_Controller {

	/**
	 *
	 * @var Core_View
	 */
	private $_view;

	/**
	 *
	 * @var string
	 */
	private $_currentAction;

	/**
	 *
	 * @var Core_Controller_Request
	 */
	private $_request;

	public function __construct() {
		$this->_init();
	}

	/**
	 * Método para ser utilizado para inicialização.
	 * Desta forma não é necessário sobreescrever o método mágico __construct
	 */
	protected function _init() {}

	public function execute($action) {
		if(stripos($action, 'action') === false) {
			$action = "{$action}Action";
		}

		if(!method_exists($this, $action)) {
			throw new RuntimeException("Method '{$action}' not exists");
		}

		$this->_currentAction = $action;

		call_user_func(array($this, $action));
	}

	/**
	 *
	 * @param string $action
	 * @param string $currentAction
	 * @return boolean
	 */
	public function checkAction($action) {
		$a = str_ireplace('action', '', $action);
		$b = str_ireplace('action', '', $this->_currentAction);

		return strcasecmp($a, $b) == 0;
	}

	/**
	 *
	 * @return Core_View
	 */
	public function getView() {
		if(null === $this->_view) {
			$this->_view = new Core_View();
		}

		return $this->_view;
	}

	/**
	 *
	 * @return Core_Controller_Request
	 */
	public function getRequest() {
		if(null === $this->_request) {
			$this->_request = new Core_Controller_Request();
		}

		return $this->_request;
	}

}