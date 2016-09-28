<?php

/**
 * Core_Service_Response
 *
 * @name Core_Service_Response
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
class Core_Service_Response {

    /**
     *
     * @var boolean
     */
    protected $_success;

    /**
     *
     * @var Exception
     */
    protected $_exception;

    /**
     *
     * @var string
     */
    protected $_message;

    /**
     *
     * @param string $success
     * @param Exception $exception
     */
    public function __construct($success = null, $message = null, Exception $exception = null) {
       $this->_success = $success;
       $this->_message = $message;
       $this->_exception = $exception;
    }

	/**
	 *
	 * @param boolean $success
	 * @return Core_Service_Response
	 */
    public function setSuccess($success) {
        $this->_success = (bool) $success;

        return $this;
    }

	/**
     * @return Exception
     */
    public function getException() {
        return $this->_exception;
    }

	/**
	 *
	 * @param Exception $exception
	 * @return Core_Service_Response
	 */
    public function setException($exception) {
        $this->_exception = $exception;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isSuccess() {
        return $this->_success;
    }

    /**
     *
     * @return boolean
     */
    public function hasException() {
        return null !== $this->_exception;
    }
}