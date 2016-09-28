<?php

/**
 * Core_Domain_EntityValidator
 *
 * @name Core_Domain_EntityValidateInterface
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
abstract class Core_Domain_EntityValidateAbstract {

	const VALIDATE_TYPE_REQUIRED = 'required';
	const VALIDATE_TYPE_INVALID = 'invalid';
	const MESSAGE_REQUIRED_FIELD = 'O campo [%s] não foi informado ou não esta em um formato válido. [%s]';
	const MESSAGE_INVALID_FIELD = 'O campo [%s] não é valido. [%s]';

	/**
	 *
	 * @var array
	 */
	protected $_required = array();

	/**
	 *
	 * @var array
	 */
	protected $_messages = array();

	/**
	 * Faz a validação dos dados.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function validate($data) {
		$this->clearMessages();

		$this->_validateRequiredFields($data);

		if($this->hasErrors()) {
			return false;
		}

		$this->_doValidate($data);

		return !$this->hasErrors();
	}

	/**
	 * Verifica se existem erros na validação.
	 *
	 * @return boolean
	 */
	public function hasErrors() {
		return count($this->_messages) > 0;
	}

	/**
	 * @return array
	 */
	public function getMessages() {
		return $this->_messages;
	}

	/**
	 *
	 * @return string
	 */
	public function getStringMessages() {
	    $error = '';

	    foreach($this->_messages as $message) {
	        $error.= implode('.', $message) . ' - ';
	    }

	    return trim($error, '- ');
	}

	/**
	 * Limpa as mensagens de erro
	 */
	public function clearMessages() {
		$this->_messages = array();
	}

	/**
	 *
	 * @param string $field
	 * @param integer $type
	 * @throws InvalidArgumentException
	 */
	protected function _addMessage($field, $type, $value = null) {
		$label = isset($this->_required[$field]) ? $this->_required[$field]: $field;

		switch ($type) {
			case self::VALIDATE_TYPE_INVALID :
				$message = sprintf(self::MESSAGE_INVALID_FIELD, $label, $value);
				break;

			case self::VALIDATE_TYPE_REQUIRED :
			    $message = sprintf(self::MESSAGE_REQUIRED_FIELD, $label, $value);
				break;
		}

		$this->_messages[$field][] = $message;
	}

	/**
	 *
	 * @param string $field
	 * @param string $message
	 */
	protected function _addCustomMessage($field, $message) {
	    $this->_messages[$field][] = $message;
	}

	/**
	 *
	 * @param array $data
	 */
	protected function _validateRequiredFields($data) {
		foreach($this->_required as $field => $label) {
			if(!array_key_exists($field, $data)) {
				$this->_addMessage($field, self::VALIDATE_TYPE_REQUIRED);
			} else {
				$value = trim($data[$field]);

				if(strlen($value) == 0) {
					$this->_addMessage($field, self::VALIDATE_TYPE_REQUIRED, $value);
				}
			}
		}
	}

	/**
	 * Faz a validação dos dados informados.
	 *
	 * @param boolean $data
	 */
	abstract protected function _doValidate($data);
}