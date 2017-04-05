<?php

/**
 * 
 * Classe de gerenciamento de atualização de status de pedido com a Kpl
 * @author Tito Junior 
 *
 */

class Model_Verden_Kpl_StatusPedido extends Model_Verden_Kpl_KplWebService {	
	
	/**
	 * Variavel  de Objeto da Classe Kpl.
	 *
	 * @var Model_Wms_kpl
	 */
	public $_client;
	
	/**
	 * Construtor.
	 *	  
	 */
	public function __construct() {		
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService ();
		}
	
	}
	
	/**
	 * Método que faz a atualização do status de um pedido
	 */
	private function _atualizaStatusPedido( $dados_pedido ){
		
		$idPedido = $dados_pedido['NumeroPedido'];
		$comentarioStatus = $dados_pedido['ComentarioStatus'];
		$statusPedido = $dados_pedido['StatusEnvio'];
		
		$this->_magento->atualizaStatusPedido($idPedido, $statusPedido, $comentarioStatus);
		
	}
	
	/**
	 * 
	 * Processar status dos pedidos via webservice.
	 * @param array $request
	 */
	function ProcessaStatusWebservice ( $request ) {

		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_status = array ();
		
		if ( ! is_array ( $request ['DadosStatusPedido'] [0] ) ) {
					
			$array_status [0] ['ProtocoloStatusPedido'] = $request ['DadosStatusPedido'] ['ProtocoloStatusPedido'];
			$array_status [0] ['NumeroPedido'] = $request ['DadosStatusPedido'] ['NumeroPedido'];
			$array_status [0] ['CodigoStatus'] = $request ['DadosStatusPedido'] ['CodigoStatus'];
			$array_status [0] ['StatusPedido'] = $request ['DadosStatusPedido'] ['StatusPedido'];
			$array_status [0] ['CodigoMotivoCancelamento'] = $request ['DadosStatusPedido'] ['CodigoMotivoCancelamento'];
			$array_status [0] ['MotivoCancelamento'] = $request ['DadosStatusPedido'] ['MotivoCancelamento'];
			
		} else {
			
			foreach ( $request ["DadosStatusPedido"] as $i => $d ) {
				
				$array_status [$i] ['ProtocoloStatusPedido'] = $d ['ProtocoloStatusPedido'];
				$array_status [$i] ['NumeroPedido'] = $d ['NumeroPedido'];
				$array_status [$i] ['CodigoStatus'] = $d ['CodigoStatus'];
				$array_status [$i] ['StatusPedido'] = $d ['StatusPedido'];
				$array_status [$i] ['CodigoMotivoCancelamento'] = $d ['CodigoMotivoCancelamento'];
				$array_status [$i] ['MotivoCancelamento'] = $d ['MotivoCancelamento'];

			}
		}
		
		$qtdStatus = count($array_status);
		
		echo PHP_EOL;
		echo "Status encontrados para integracao: " . $qtdStatus . PHP_EOL;
		echo PHP_EOL;
		
		echo "Conectando ao WebService Magento... " . PHP_EOL;
		$this->_magento = new Model_Verden_Magento_Status();
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_status as $indice => $dados_status ) {
			$erros_status = 0;			
			
			if ( $dados_status ['NumeroPedido'] == NULL ) {
				echo "Status do pedido {$dados_status['NumeroPedido']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Status do Pedido {$dados_status['NumeroPedido']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_status ++;
			}
			if ( $erros_status == 0 ) {
				
				//Tratar status a ser enviado
				if ( !empty( $dados_status['MotivoCancelamento'] ) ){
					$dados_status['StatusEnvio'] = 'canceled';
					$dados_status['ComentarioStatus'] = utf8_decode( $dados_status['MotivoCancelamento'] );
				}
				
				try {
					
					echo "Atualizando status pedido " . $dados_status['NumeroPedido'] . PHP_EOL;
					$this->_atualizaStatusPedido( $dados_status );
					echo "Status atualizado. " . PHP_EOL; 
										
					$this->_kpl->confirmarRecebimentoStatusPedido( $dados_status ['ProtocoloStatusPedido'] );
					echo "Protocolo Status: {$dados_status ['ProtocoloStatusPedido']} enviado com sucesso" . PHP_EOL;
					echo PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao atualizar status {$dados_status['NumeroPedido']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao atualizar status Pedido {$dados_status['NumeroPedido']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}		
		
		// finaliza sessão Magento
		$this->_magento->_encerraSessao();
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}	

}
