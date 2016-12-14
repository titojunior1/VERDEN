<?php
/**
 * 
 * Cron para processar integra��o com sistema ERP Magento - Magento via webservice   
 * @author Tito Junior <titojunior1@gmail.com>
 * 
 */
require_once 'includes/verden.php';
class Model_Verden_Cron_MagentoCron {
	
	/**
	 * 
	 * Objeto Magento (inst�ncia do webservice magento)
	 * @var Model_Verden_Magento_MagentoWebService
	 */
	private $_magento;	

	/**
	 * Construtor
	 * @param 
	 */
	public function __construct () {
		
		echo "- Iniciando Cron para processar integracao com sistema ERP Magento via webservice" . PHP_EOL;
		
	}
	
	/**
	 *
	 * Cadastrar CLientes dispon�veis no ambiente Magento
	 */
	public function CadastraClientesMagento() {
	
		ini_set ( 'memory_limit', '512M' );
	
		// Solicita Pedidos Saida Dispon�veis
		if ( empty ( $this->_magento ) ) {
			$this->_magento = new Model_Verden_Magento_MagentoWebService();
		}
			
		echo "- importando clientes disponiveis do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		
		$date          = date('Y-m-d H:i:s');
		$timestamp = date('Y-m-d H:i:s', strtotime("-5 hours", strtotime($date)));
		$timestamp = '2016-08-19 19:20:00';	
		
		//Filtro para consulta em ambiente Magento com base na data atual regredindo 5 horas
		$complexFilter = array(
				'complex_filter' => array(
						array(
								'key' => 'created_at',
								'value' => array('key' => 'gt', 'value' => $timestamp)
						)
				)
		);
		
		
		try {
			
			$clientesDisponiveis = $this->_magento->buscaClientesDisponiveis($complexFilter);
			if ( ! is_array ( $clientesDisponiveis ) ) {
				throw new Exception ( 'Erro ao buscar clientes' );
			}
			if (count($clientesDisponiveis) == 0 ) {
				echo "Nao existem clientes disponiveis para integracao " . PHP_EOL;
	
			}else{
				$magento = new Model_Verden_Magento_Clientes();
				$retorno = $magento->ProcessaClientesWebservice($clientesDisponiveis);
				if(is_array($retorno)){
					// gravar logs de erro
					$this->_log->gravaLogErros($retorno);
				}
			}
	
			echo "- importacao de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
	
		} catch ( Exception $e ) {
			echo "- erros ao importar os pedidos de sa�da do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
	
		echo "- Finalizando cron para cadastrar pedidos de sa�da da Kpl do cliente Verden " . PHP_EOL;
	
	}

	/**
	 * 
	 * Cadastrar fornecedores do Kpl.
	 */
	public function CadastraFornecedoresMagento () {

		
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService ();
		}
		
		echo "- importando Fornecedores do cliente Verden -  " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		
		try {
			$chaveIdentificacao = KPL_KEY;
			
			$fornecedores = $this->_kpl->FornecedoresDisponiveis ( $chaveIdentificacao );
			if ( ! is_array ( $fornecedores ['FornecedoresDisponiveisResult'] ) ) {
				throw new Exception ( 'Erro ao buscar Fornecedores' );
			}
			if ( $fornecedores ['FornecedoresDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
				echo "N�o existem fornecedores dispon�veis para integra��o".PHP_EOL;
			
			}else{
				$kpl_fornecedores = new Model_Verden_Kpl_Fornecedor ();
					$retorno = $kpl_fornecedores->ProcessaFornecedoresWebservice ( $fornecedores ['FornecedoresDisponiveisResult'] );
				}
				echo "- importa��o de fornecedores do cliente Verden realizada com sucesso" . PHP_EOL;
			
		
		} catch ( Exception $e ) {
			echo "- erros ao importar os fornecedores do cliente Verden: " . $e->getMessage () . PHP_EOL;
		}
		unset ( $this->_kpl );
		
		
		echo "- Finalizando cron para cadastrar fornecedores do Kpl" . PHP_EOL;
	
	}
	
	

	/**
	 * 
	 * Cadastrar Pedidos Saida do Magento
	 */
	public function CadastraPedidosSaidaMagento () {
	
		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Dispon�veis
			if ( empty ( $this->_kpl ) ) {
				$this->_magento = new Model_Verden_Magento_MagentoWebService();
			}
			
			//Filtro para consulta em ambiente Magento com base na data atual regredindo 5 horas
			$complexFilter = array(
								'filter' => array(
												array(
													'key' => 'status', 
													'value' => 'processing'
													 )
												 )
									);
			
			$timestamp = '2016-08-19 19:20:00';
			
			//Filtro para consulta em ambiente Magento com base na data atual regredindo 5 horas
		
			
			$complexFilter = array(
					'complex_filter' => array(
							array(
									'key' => 'created_at',
									'value' => array('key' => 'gt', 'value' => $timestamp)
							)
					)
			);
			
			$complexFilter = array(
					'complex_filter' => array(
							array(
									'key' => 'increment_id',
									'value' => array('key' => 'eq', 'value' => 100267826)
							)
					)
			);
			
			echo "- importando pedidos de sa�da do cliente Verden - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			try {
				
				$pedidos_disponiveis = $this->_magento->buscaPedidosDisponiveis($complexFilter);
				if ( ! is_array ( $pedidos_disponiveis ) ) {
					throw new Exception ( 'Erro ao buscar notas de sa�da' );
				}
				var_dump($pedidos_disponiveis);
				if ( $pedidos_disponiveis ['PedidosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Nao existem pedidos de saida disponiveis para integracao ".PHP_EOL;

				}else{
					$magento = new Model_Verden_Magento_Pedidos();
					$retorno = $magento->ProcessaPedidosWebservice ( $pedidos_disponiveis );
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($retorno);					
						}	
					}

					echo "- importacao de pedidos do cliente Verden realizada com sucesso " . PHP_EOL;
					
			} catch ( Exception $e ) {
				echo "- erros ao importar os pedidos de sa�da do cliente Verden: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
		
		echo "- Finalizando cron para cadastrar pedidos de sa�da da Kpl do cliente Verden " . PHP_EOL;
		
	}
	
	
	
}

$obj = new Model_Verden_Cron_MagentoCron();
$obj->CadastraPedidosSaidaMagento();