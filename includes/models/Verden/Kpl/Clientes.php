<?php
/**
 * 
 * Classe para processar o cadastro de clientes via webservice do ERP KPL - Ábacos 
 * 
 * @author Tito Junior 
 * 
 */
class Model_Verden_Kpl_Clientes extends Model_Verden_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice KPL
	 */
	private $_kpl;
	
	/*
	 * Instancia Webservice Magento
	 */
	private $_chaveIdentificacao;
	
	/**
	 * 
	 * construtor.	 
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService (  );
		}
		$this->_chaveIdentificacao = KPL_KEY;
	
	}
	
	/**
	 * 
	 * Adicionar cliente.
	 * @param array $dadosCliente
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function adicionaCliente ( $dadosCliente ) {	 

		$retorno = $this->_kpl->cadastraCliente($this->_chaveIdentificacao, $dadosCliente);
		
		if ( $retorno ['CadastrarClienteResult'] ['Rows'] ['DadosClientesResultado'] ['Resultado'] ['Codigo'] == '200002' ){
			return true;
		}else{
			throw new RuntimeException('Erro ao cadastrar cliente ' . $retorno ['CadastrarClienteResult'] ['Rows'] ['DadosClientesResultado'] );
		}		

	}
	
	/**
	 *
	 * Cadastra Pedido
	 * @param array $dadosPedido
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function cadastraPedido ( $dadosPedido ) {
	
		$retorno = $this->_kpl->cadastraPedidoKpl($this->_chaveIdentificacao, $dadosPedido);
	
		if ( $retorno ['InserirPedidoResult'] ['Rows'] ['DadosPedidosResultado'] ['Resultado'] ['Codigo'] == '200001' ){
			return true;
		}else{
			throw new RuntimeException('Erro ao cadastrar cliente ' . $retorno ['CadastrarClienteResult'] ['Rows'] ['DadosClientesResultado'] );
		}
	
	}

}

