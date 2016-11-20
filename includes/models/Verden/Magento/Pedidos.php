<?php
/**
 *
 * Classe para processar o cadastro de Pedidos via webservice no ERP da Magento
 *
 * @author Tito Junior
 *
 */
class Model_Verden_Magento_Pedidos extends Model_Verden_Magento_MagentoWebService{
	
	/*
	 * Instancia Webservice Magento
	*/
	private $_magento;
	
	/*
	 * Instancia Webservice KPL
	*/
	private $_kpl;
	
	/**
	 *
	 * construtor.
	 */
	function __construct () {
	
		if (empty ( $this->_magento )) {
			$this->_magento = new Model_Verden_Magento_MagentoWebService();
		}
		
	}
	
	/**
	 *
	 * Processar cadastro de clientes via webservice.
	 * @param array $request
	 */
	function ProcessaPedidosWebservice ( $request ) {
		

		// erros
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		foreach ( $request as $i => $d ) {
		
			$arrayClientes [$i] ['Email'] = $d ['email'];
			$arrayClientes [$i] ['CPFouCNPJ'] = $d ['taxvat'];
			$arrayClientes [$i] ['TipoPessoa'] = $d ['CodigoProdutoAbacos'];
			$arrayClientes [$i] ['Nome'] = $d ['firstname'];
			$arrayClientes [$i] ['Sexo'] = $d ['PrecoTabela'];
			
			
		}
		
		var_dump($arrayClientes);
	}
	
}