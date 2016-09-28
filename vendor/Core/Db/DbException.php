<?php

/**
 * Core_Db_DbException
 *
 * @name Core_Db_DbException
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
class Core_Db_DbException extends Exception {

    public function __construct($message, $code, $previous) {
        if(is_string($previous)) {
            $previous = new Exception($previous);
        }

        parent::__construct($message, $code, $previous);
    }

}