<?php

/**
 * 
 * Classe para processar o cadastro de pedidos de venda (sa�da) via webservice do ERP KPL - �bacos 
 * 
 * @author Tito Junior 
 * 
 */

final class Model_Verden_Kpl_Pedido extends Model_Verden_Kpl_KplWebService {
	
	/**
	 * Caracteres especiais
	 */
	private $_caracteres_especiais = array ( "\"", "'", "\\", "`" );
	
	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 */
	function __construct () {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService ();
		}			
	}
}
