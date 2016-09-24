<?php
/**
 * 
 * Cron para processar integração com sistema ERP KPL - Ábacos via webservice   
 * @author    Rômulo Z. C. Cunha <romulo.cunha@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     30/08/2012
 * 
 */

class Model_Wms_Cron_KplCron extends Model_Wms_Cron_Abstract {
	
	/**
	 * 
	 * Objeto Kpl (instância do webservice kpl)
	 * @var Model_Wms_Kpl_KplWebService
	 */
	private $_kpl;
	
	/**
	 * 
	 * Objeto de Log 
	 * @var Model_Wms_Log_LogErro
	 */
	private $_log;	
	
	/**
	 * 
	 * Array com clientes encontrados
	 * @var array
	 */
	private $_clientes;

	/**
	 * 
	 * Conexão com webservice KPL.
	 * @var unknown_type
	 */
	//	private $_ws = 'http://187.120.13.174:8045/abacoswebsvc/AbacosWSWMS.asmx?wsdl'; // testes
	

	/**
	 * Construtor
	 * @param 
	 */
	public function __construct () {

		echo "- Iniciando Cron para processar integração com sistema ERP KPL via webservice" . PHP_EOL;
		
		// Carrega clientes do banco
		$this->_clientes = $this->CarregaClientesKpl ();
		$this->_log = new Model_Wms_Log_LogErro();
	}

	/**
	 * Carrega clientes do banco, utilizando clientes_erp
	 * @return array com $cli_id  
	 */
	public function CarregaClientesKpl () {

		$array_clientes = array ();
		
		$db = Db_Factory::getDbWms ();
		
		//selecionar clientes que possuem integração com a KPL
		$sql = "SELECT cli_id,cli_kpl_url FROM clientes WHERE cli_kpl_url IS NOT NULL AND cli_inventario=0 AND cli_status=1";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao consultar clientes com integração com a KPL' );
		}
		
		if ( $db->NumRows ( $res ) == 0 ) {
			echo 'Não existem clientes';
		}
		
		$row = $db->FetchAssoc ( $res );
		while ( $row ) {
			$array_clientes [] = $row ['cli_id'];
			$row = $db->FetchAssoc ( $res );
		
		}
		
		return $array_clientes;
	}

	/**
	 * 
	 * Atualiza estoque do Kpl baseado na tabela de movimentos_hist
	 */
	public function AtualizaEstoqueKpl () {

		echo "- Iniciando cron para atualizar estoque do Kpl - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
		
		$db = Db_Factory::getDbWms ();
		
		// obter a data inicial para consulta do estoque
		$sql = "SELECT cron_campo1 FROM cron_scripts WHERE cron_classe = 'KplCron' and cron_metodo='AtualizaEstoqueKpl'";
		$res2 = $db->Execute ( $sql );
		if ( ! $res2 ) {
			echo "- Erro ao consultar última data de atualização" . PHP_EOL;
			return false;
		}
		$row2 = $db->FetchAssoc ( $res2 );
		$ultima_data = $row2 ['cron_campo1'];
		if ( empty ( $ultima_data ) ) {
			$data_inicio = '0000-00-00';
			$hora_inicio = '00:00:00';
		} else {
			$ultima_data = strtotime ( $ultima_data );
			$data_inicio = date ( 'Y-m-d', $ultima_data );
			$hora_inicio = date ( 'H:i:s', $ultima_data );
		}
		foreach ( $this->_clientes as $indice => $cli_id ) {
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			$empwh_id = $this->_kpl->getArmazem ();
			try {
				echo "- atualizando estoque do cliente {$cli_id}" . PHP_EOL;
				$estoque = new Model_Wms_Kpl_EstoqueKpl ( $cli_id, $empwh_id );
				$estoque->geraAtualizacaoEstoque ( $data_inicio, $hora_inicio );
				
				// nova hora de início
				$nova_hora_inicio = date ( 'Y-m-d H:i:s' );
				
				$sql = "UPDATE cron_scripts SET cron_campo1='{$nova_hora_inicio}' WHERE cron_classe = 'KplCron' and cron_metodo='AtualizaEstoqueKpl'";
				if ( ! $db->Execute ( $sql ) ) {
					echo "- Erro ao gravar a data da última atualização no cron" . PHP_EOL;
				}
			
			} catch ( Exception $e ) {
				echo "- Erros ao atualizar estoque do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
		
		}
		$nova_hora_inicio = date ( 'Y-m-d H:i:s' );
		
		$sql = "UPDATE cron_scripts SET cron_campo1='{$nova_hora_inicio}' WHERE cron_classe = 'KplCron' and cron_metodo='AtualizaEstoqueKpl'";
		if ( ! $db->Execute ( $sql ) ) {
			echo "- Erro ao gravar a data da última atualização no cron" . PHP_EOL;
		}
		
		echo "- Finalizando cron para atualizar estoque do Kpl" . PHP_EOL;
	}

	/**
	 * 
	 * Atualiza o tracking dos pedidos feitos para o Kpl.
	 */
	public function AtualizaTrackingKpl () {

		echo "- Iniciando cron que atualiza tracking dos pedidos feitos para o Kpl" . PHP_EOL;
		
		$db = Db_Factory::getDbWms ();
		
		// obter a data inicial para consulta do estoque
		$sql = "SELECT cron_campo1 FROM cron_scripts WHERE cron_classe = 'KplCron'AND cron_metodo='AtualizaTrackingKpl'";
		$res2 = $db->Execute ( $sql );
		if ( ! $res2 ) {
			echo "- Erro ao consultar última data de atualização" . PHP_EOL;
			return false;
		}
		$row2 = $db->FetchAssoc ( $res2 );
		$ultima_data = $row2 ['cron_campo1'];
		if ( empty ( $ultima_data ) ) {
			$data_inicio = '0000-00-00';
			$hora_inicio = '00:00:00';
		} else {
			$ultima_data = strtotime ( $ultima_data );
			$data_inicio = date ( 'Y-m-d', $ultima_data );
			$hora_inicio = date ( 'H:i:s', $ultima_data );
		}
		$cron_id = $this->_log->getCronId('AtualizaTrackingKpl');
		if ( ! empty ( $this->_clientes ) ) {
			foreach ( $this->_clientes as $indice => $cli_id ) {
				if($cli_id==66){
					continue;
				}
				if ( empty ( $this->_kpl ) ) {
					$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
				}
				
				//pegar o armazem 
				$empwh_id = $this->_kpl->getArmazem ();
				try {
					echo "- atualizando tracking do cliente {$cli_id}" . PHP_EOL;
					$tracking = new Model_Wms_Kpl_TrackingKpl($cli_id) ;
					$retorno = $tracking->geraAtualizacaoTracking($data_inicio, $hora_inicio);
					if(is_array($retorno)){
						// gravar logs de erro						
						$this->_log->gravaLogErros($cron_id, $retorno);					
					}
				} catch ( Exception $e ) {
					echo "- Erros ao atualizar estoque do cliente {$cli_id}: " . PHP_EOL . $e->getMessage () . PHP_EOL;
				}
				
				unset($this->_kpl);
			}
			// nova hora de início
			$nova_hora_inicio = date ( 'Y-m-d H:i:s' );
			
			$sql = "UPDATE cron_scripts SET cron_campo1='{$nova_hora_inicio}' WHERE cron_classe = 'KplCron'AND cron_metodo='AtualizaTrackingKpl'";
			if ( ! $db->Execute ( $sql ) ) {
				echo "- Erro ao gravar a data da última atualização no cron" . PHP_EOL;
			}
		
		}
		echo "- Finalizando cron que atualiza tracking dos pedidos feitos para o Kpl" . PHP_EOL;
	}

	/**
	 * 
	 * Cadastrar fornecedores do Kpl.
	 */
	public function CadastraFornecedoresKpl () {

		foreach ( $this->_clientes as $indice => $cli_id ) {
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			echo "- importando Fornecedores do cliente {$cli_id} -  " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			$cron_id = $this->_log->getCronId('CadastraFornecedoresKpl');
			try {
				$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
				
				$fornecedores = $this->_kpl->FornecedoresDisponiveis ( $chaveIdentificacao );
				if ( ! is_array ( $fornecedores ['FornecedoresDisponiveisResult'] ) ) {
					throw new Exception ( 'Erro ao buscar Fornecedores' );
				}
				if ( $fornecedores ['FornecedoresDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Não existem fornecedores disponíveis para integração".PHP_EOL;
				
				}else{
					if($cli_id == 78)
					{
						$kpl_fornecedores = new Model_Wms_Kpl_FornecedorGWBR( $cli_id );
						$retorno = $kpl_fornecedores->ProcessaFornecedoresWebservice ( $fornecedores ['FornecedoresDisponiveisResult'] );
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}	
					}
					else 
					{
					$kpl_fornecedores = new Model_Wms_Kpl_Fornecedor ( $cli_id );
						$retorno = $kpl_fornecedores->ProcessaFornecedoresWebservice ( $fornecedores ['FornecedoresDisponiveisResult'] );
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}
					}
					echo "- importação de fornecedores do cliente {$cli_id} realizada com sucesso" . PHP_EOL;
				}
			
			} catch ( Exception $e ) {
				echo "- erros ao importar os fornecedores do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
		}
		
		echo "- Finalizando cron para cadastrar produtos do Kpl" . PHP_EOL;
	
	}

	/**
	 * 
	 * Cadastrar Notas Entrada do Kpl
	 */
	public function CadastraNotasEntradaKpl () {

		ini_set ( 'memory_limit', '512M' );
		foreach ( $this->_clientes as $indice => $cli_id ) {
			if (empty ( $this->_kpl )) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			$empwh_id = $this->_kpl->getArmazem ();
			$cron_id = $this->_log->getCronId('CadastraNotasEntradaKpl');
			echo "- importando notas de entrada do cliente {$cli_id}, warehouse {$empwh_id} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			try {
				$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
				$notas_entrada = $this->_kpl->NotasFiscaisEntradaDisponiveis ( $chaveIdentificacao );
				if ( ! is_array ( $notas_entrada ['NotasFiscaisEntradaDisponiveisResult'] ) ) {
					throw new Exception ( 'Erro ao buscar notas de entrada' );
				}
				if ( $notas_entrada ['NotasFiscaisEntradaDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Não existem pedidos de entrada disponíveis para integração".PHP_EOL;
					
				}else{
					if($cli_id == 78)
					{
						$kpl_notas_entrada = new Model_Wms_Kpl_NotasEntradaGWBR( $cli_id, $empwh_id );
						$retorno = $kpl_notas_entrada->ProcessaNotasEntradaWebservice ( $notas_entrada ['NotasFiscaisEntradaDisponiveisResult'] );
					
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);	
						}	
					}
					else 
					{
					$kpl_notas_entrada = new Model_Wms_Kpl_NotasEntrada ( $cli_id, $empwh_id );
						$retorno = $kpl_notas_entrada->ProcessaNotasEntradaWebservice ( $notas_entrada ['NotasFiscaisEntradaDisponiveisResult'] );
						
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);	
						}
					}
				}
				
			
			} catch ( Exception $e ) {
				echo "- erros ao importar as notas de entrada do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
		}
	
	}

	/**
	 * 
	 * Cadastrar Pedidos Saida do Kpl
	 */
	public function CadastraPedidosSaidaKpl () {

		//ini_set ( 'memory_limit', '-1' );
		ini_set ( 'memory_limit', '512M' );
		
		// Solicita Pedidos Saida Disponíveis
		foreach ( $this->_clientes as $indice => $cli_id ) {
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			$empwh_id = $this->_kpl->getArmazem ();
			$cron_id = $this->_log->getCronId('CadastraPedidosSaidaKpl');			
			echo "- importando pedidos de saída do cliente {$cli_id}, warehouse {$empwh_id} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			try {
				$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
				$pedidos_disponiveis = $this->_kpl->PedidosDisponiveis ( $chaveIdentificacao );
				if ( ! is_array ( $pedidos_disponiveis ['PedidosDisponiveisResult'] ) ) {
					throw new Exception ( 'Erro ao buscar notas de saída' );
				}
				if ( $pedidos_disponiveis ['PedidosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Não existem pedidos de saída disponíveis para integração".PHP_EOL;

				}else{
					if($cli_id == 78)
					{
						$kpl = new Model_Wms_Kpl_PedidoGWBR( $cli_id, $empwh_id );
						$retorno = $kpl->ProcessaArquivoSaidaWebservice ( $pedidos_disponiveis ['PedidosDisponiveisResult'] );
					
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}	
					}
					else 
					{
					$kpl = new Model_Wms_Kpl_Pedido ( $cli_id, $empwh_id );
						$retorno = $kpl->ProcessaArquivoSaidaWebservice ( $pedidos_disponiveis ['PedidosDisponiveisResult'] );
						if(is_array($retorno)){
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}	
					}
					
					
					
					echo "- importação de pedidos do cliente {$cli_id} realizada com sucesso" . PHP_EOL;
				}
			} catch ( Exception $e ) {
				echo "- erros ao importar os pedidos de saída do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
		}
		echo "- Finalizando cron para cadastrar pedidos de saída da Kpl" . PHP_EOL;
		
	}

	/**
	 * 
	 * Importa os produtos disponíveis.
	 * @throws Exception
	 */
	public function CadastraProdutosKpl () {

		ini_set ( 'memory_limit', '512M' );
		foreach 
			( $this->_clientes as $indice => $cli_id ) {
			
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			echo "- importando produtos do cliente {$cli_id} - " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			$cron_id = $this->_log->getCronId('CadastraProdutosKpl');
			try {
				$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
				$produtos = $this->_kpl->ProdutosDisponiveis ( $chaveIdentificacao );
				if ( ! is_array ( $produtos ['ProdutosDisponiveisResult'] ) ) {
					throw new Exception ( 'Erro ao buscar Produtos - ' . $produtos );
				}
				if ( $produtos ['ProdutosDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003 ) {
					echo "Não existem produtos disponíveis para integração" . PHP_EOL;
				} else {
					if($cli_id == 78)
					{
						$kpl_produtos = new Model_Wms_Kpl_ProdutosGWBR($cli_id);						
						$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'] );
						if(is_array($retorno))
						{
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}	
					}
					else 
					{
					$kpl_produtos = new Model_Wms_Kpl_Produtos ( $cli_id );
						$retorno = $kpl_produtos->ProcessaProdutosWebservice ( $produtos ['ProdutosDisponiveisResult'] ['Rows'] );
						if(is_array($retorno))
						{
							// gravar logs de erro						
							$this->_log->gravaLogErros($cron_id, $retorno);					
						}	
					}
					
					
					echo "- importação de produtos do cliente {$cli_id} realizada com sucesso" . PHP_EOL;
				
				}
			
			} catch ( Exception $e ) {
				echo "- erros ao importar os produtos do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			unset ( $this->_kpl );
			unset ( $chaveIdentificacao );
		
		//verificar chave de identificação do cliente. 
		

		}
		
		echo "- Finalizando cron para cadastrar produtos do Kpl" . PHP_EOL;
	}

	/**
	 * 
	 * Importa as notas fiscais disponíveis.
	 */
	public function NotasFiscaisDisponiveis () {

		ini_set ( 'memory_limit', '728M' );
		//ini_set ( 'memory_limit', '-1' );
		

		foreach ( $this->_clientes as $indice => $cli_id ) {
			
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
			}
			
			echo "- importando notas fiscais do cliente {$cli_id} " . date ( "d/m/Y H:i:s" ) . PHP_EOL;
			$cron_id = $this->_log->getCronId('NotasFiscaisDisponiveis');
			try {
				$empwh_id = $this->_kpl->getArmazem ();
				$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
				$notas_fiscais = $this->_kpl->NotasFiscaisSaidaDisponiveis ( $chaveIdentificacao );
				if (! is_array ( $notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] )) {
					throw new Exception ( 'Erro ao buscar Produtos' );
				}
				if ($notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
					echo "Não existem notas disponíveis para integração".PHP_EOL;
					
				}else{
					
					if($cli_id == 78)
					{
						$ns = new Model_Wms_Kpl_PedidoGWBR( $cli_id, $empwh_id );
						$ns->capturaDadosNf ( $notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] ['Rows'] );
						
					}
					else
					{
						$ns = new Model_Wms_Kpl_Pedido ( $cli_id, $empwh_id );
						$ns->capturaDadosNf ( $notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] ['Rows'] );
						
					}
					
					
				}
				
			} catch ( Exception $e ) {
				echo "- erros ao importar notas fiscais do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
				$array_erro[] = "- erros ao importar notas fiscais do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
			}
			if(is_array($array_erro)){
				// gravar logs de erro						
				$this->_log->gravaLogErros($cron_id, $array_erro);					
			}
			unset ( $this->_kpl );
		}
		echo "- Finalizando cron para baixar notas fiscais disponíveis KPL" . PHP_EOL;
	}
	
	/**
	 *
	 * Importa as notas fiscais disponíveis com pdf
	 */
	public function NotasFiscaisSaidaDisponiveisComPdf(){
		foreach ( $this->_clientes as $indice => $cli_id ) {
			// Inicialmente somente para Meu Espelho
			if($cli_id==77){
				
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
				echo "- importando notas fiscais do cliente {$cli_id} " .date("d/m/Y H:i:s"). PHP_EOL;
				$cron_id = $this->_log->getCronId('NotasFiscaisSaidaDisponiveisComPdf');
				try{	
					$empwh_id = $this->_kpl->getArmazem ();
					$pedido = new Model_Wms_Kpl_Pedido ( $cli_id, $empwh_id );
					$pedido->NotasFiscaisSaidaDisponiveisComPdf();
			
				} catch ( Exception $e ) {
						echo "- erros ao importar notas fiscais do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
					$array_erro[] = "- erros ao importar notas fiscais do cliente {$cli_id}: " . $e->getMessage () . PHP_EOL;
				}
			}			
			if(is_array($array_erro)){
				// gravar logs de erro						
				$this->_log->gravaLogErros($cron_id, $array_erro);					
			}			
		}
		echo "- Finalizando cron para baixar notas fiscais disponíveis KPL" . PHP_EOL;
	}
}
	
