<?php
/**
 * 
 * Cron para processar integra��o com sistema ERP KPL - �bacos via webservice   
 * @author Tito Junior <titojunior1@gmail.com>
 * 
 */
class Model_Verden_Cron_KplCron {
	
	/**
	 * 
	 * Objeto Kpl (inst�ncia do webservice kpl)
	 * @var Model_Verden_Kpl_KplWebService
	 */
	private $_kpl;	

	/**
	 * Construtor
	 * @param 
	 */
	public function __construct () {

		echo "- Iniciando Cron para processar integracao com sistema ERP KPL via webservice" . PHP_EOL;
		
	}

	/**
	 * 
	 * Atualiza estoque do Kpl
	 * 
	 * 
	 */
	public function atualizaEstoqueKpl () {

		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );		
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService();
		}
		echo "- importando estoques disponiveis do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

		try {
			$chaveIdentificacao = KPL_KEY;
			$estoques = $this->_kpl->EstoquesDisponiveis( $chaveIdentificacao );
			if ( ! is_array ( $estoques ['EstoquesDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Estoque - ' . $estoques );
			}
			if ( $estoques ['EstoquesDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem estoques disponiveis para integracao" . PHP_EOL;
			} else {
				
				$kpl_estoques = new Model_Verden_Kpl_EstoqueKpl();
				$retorno = $kpl_estoques->ProcessaEstoqueWebservice ( $estoques ['EstoquesDisponiveisResult'] ['Rows'] );
				if(is_array($retorno)){
					// ERRO					
				}	
			}
				
			echo "- importacao de estoque do cliente Verden realizada com sucesso" . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo "- erros ao importar estoque do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );

		echo "- Finalizando cron para atualizar estoque do Kpl" . PHP_EOL;
	}
	
	/**
	 * M�todo para atualizar status de pedido da KPL para o Magento
	 */
	public function atualizaStatusPedido(){
		
		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Dispon�veis
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService();
		}
			
		echo "- Atualizando status de pedidos de saida do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		try {
			$chaveIdentificacao = KPL_KEY;
			$status_disponiveis = $this->_kpl->statusPedidosDisponiveis($chaveIdentificacao);
			if ( ! is_array ( $status_disponiveis ['StatusPedidoDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar status dos pedidos' );
			}
			if ( $status_disponiveis ['StatusPedidoDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem status disponiveis para integracao ".PHP_EOL;
		
			}else{
				$kpl = new Model_Verden_Kpl_StatusPedido();
				$retorno = $kpl->ProcessaStatusWebservice( $status_disponiveis ['StatusPedidoDisponiveisResult'] ['Rows'] );
				if(is_array($retorno)){
					// gravar logs de erro
					$this->_log->gravaLogErros($retorno);
				}
			}
		
			echo "- importacao de status de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
				
		} catch ( Exception $e ) {
			echo "- erros ao importar os status de pedidos de sa�da do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		
		echo "- Finalizando cron para atualizar status de pedidos de sa�da da Kpl do cliente Verden " . PHP_EOL;
	}

	/**
	 * 
	 * Importa os produtos dispon�veis.
	 * @throws Exception
	 */
	public function cadastraProdutosKpl () {

		ini_set ( 'memory_limit', '512M' );
		ini_set ( 'default_socket_timeout', 120 );
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService();
		}
		echo "- importando produtos do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;

		try {
			
			echo PHP_EOL;
			echo "Consultando produtos disponiveis para integracao " . PHP_EOL;
			$chaveIdentificacao = KPL_KEY;
			$produtos = $this->_kpl->ProdutosDisponiveis ( $chaveIdentificacao );
			if ( ! is_array ( $produtos ['ProdutosDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Produtos - ' . $produtos );
			}
			if ( $produtos ['ProdutosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem produtos disponiveis para integracao" . PHP_EOL;
			} else {
				
				$kpl_produtos = new Model_Verden_Kpl_Produtos();
					$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'] );
					if(is_array($retorno))
					{
						// ERRO					
					}	
				}
				
				echo "- importacao de produtos do cliente Verden realizada com sucesso" . PHP_EOL;
		
		} catch ( Exception $e ) {
			echo "- erros ao importar os produtos do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );

		echo "- Finalizando cron para cadastrar produtos do Kpl " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	}
	
	/**
	 *
	 * Importa os pre�os dispon�veis.
	 * @throws Exception
	 */
	public function cadastraPrecosKpl () {
	
		ini_set ( 'memory_limit', '512M' );
			
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService();
		}
		echo "- importando precos do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
	
		try {
			$chaveIdentificacao = KPL_KEY;
			$precos = $this->_kpl->PrecosDisponiveis( $chaveIdentificacao );
			if ( ! is_array ( $precos ['PrecosDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Pre�os - ' . $precos );
			}
			if ( $precos ['PrecosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "Nao existem precos disponiveis para integracao" . PHP_EOL;
			} else {
					
				$kpl_pre�os = new Model_Verden_Kpl_Precos();
				$retorno = $kpl_pre�os->ProcessaPrecosWebservice( $precos ['PrecosDisponiveisResult'] ['Rows'] );
				if(is_array($retorno))
				{
					// ERRO
				}
			}
				
			echo "- importacao de precos do cliente Verden realizada com sucesso" . PHP_EOL;
				
		} catch ( Exception $e ) {
		echo "- erros ao importar os precos do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		unset ( $chaveIdentificacao );
	
		echo "- Finalizando cron para atualizar precos da Kpl" . PHP_EOL;
	}
	
}