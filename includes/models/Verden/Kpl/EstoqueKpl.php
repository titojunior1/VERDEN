<?php

/**
 * 
 * Classe de gerenciamento de atualização de estoque com a Kpl
 * @author Tito Junior 
 *
 */

class Model_Verden_Kpl_EstoqueKpl extends Model_Verden_Kpl_KplWebService {	
	
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
	 * Método que faz a atualização do estoque de um produto
	 */
	private function _atualizaEstoque(){
		// Preencher - PARTE MAGENTO	
	}
	
	/**
	 * 
	 * Processar estoque dos produtos via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaEstoqueWebservice ( $request ) {

		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_estoques = array ();
		
		if ( ! is_array ( $request ['DadosEstoque'] [0] ) ) {
					
			$array_estoques [0] ['ProtocoloEstoque'] = $request ['DadosEstoque'] ['ProtocoloEstoque'];
			$array_estoques [0] ['CodigoProduto'] = $request ['DadosEstoque'] ['CodigoProduto'];
			$array_estoques [0] ['CodigoProduto'] = $request ['DadosEstoque'] ['CodigoProduto'];
			$array_estoques [0] ['CodigoProdutoPai'] = $request ['DadosEstoque'] ['CodigoProdutoPai'];
			$array_estoques [0] ['CodigoProdutoAbacos'] = $request ['DadosEstoque'] ['CodigoProdutoAbacos'];
			$array_estoques [0] ['SaldoMinimo'] = $request ['DadosEstoque'] ['SaldoMinimo'];
			$array_estoques [0] ['SaldoDisponivel'] = $request ['DadosEstoque'] ['SaldoDisponivel'];
			$array_estoques [0] ['NomeAlmoxarifadoOrigem'] = $request ['DadosEstoque'] ['NomeAlmoxarifadoOrigem'];
			$array_estoques [0] ['IdentificadorProduto'] = $request ['DadosEstoque'] ['IdentificadorProduto'];
			$array_estoques [0] ['CodigoProdutoParceiro'] = $request ['DadosEstoque'] ['CodigoProdutoParceiro'];
			
		} else {
			
			foreach ( $request ["DadosEstoque"] as $i => $d ) {
				
				$array_estoques [0] ['ProtocoloEstoque'] = $d ['ProtocoloEstoque'];
				$array_estoques [0] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_estoques [0] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_estoques [0] ['CodigoProdutoPai'] = $d ['CodigoProdutoPai'];
				$array_estoques [0] ['CodigoProdutoAbacos'] = $d ['CodigoProdutoAbacos'];
				$array_estoques [0] ['SaldoMinimo'] = $d ['SaldoMinimo'];
				$array_estoques [0] ['SaldoDisponivel'] = $d ['SaldoDisponivel'];
				$array_estoques [0] ['NomeAlmoxarifadoOrigem'] = $d ['NomeAlmoxarifadoOrigem'];
				$array_estoques [0] ['IdentificadorProduto'] = $d ['IdentificadorProduto'];
				$array_estoques [0] ['CodigoProdutoParceiro'] = $d ['CodigoProdutoParceiro'];
			}
		}
		
		$qtdEstoques = count($array_estoques);
		
		echo PHP_EOL;
		echo "Estoques encontrados para integracao: " . $qtdEstoques . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_estoques as $indice => $dados_estoque ) {
			$erros_estoques = 0;			
			
			if ( empty ( $dados_estoque ['SaldoDisponivel'] ) ) {
				echo "Estoque do produto {$dados_estoque['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_estoque['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_estoques ++;
			}
			if ( $erros_estoques == 0 ) {
				
				try {
					// Localizar Produto para atualizar estoque
					$produto = ''; // Inserir informações do produto
					if ( empty ( $produto ) ) {
						echo "Atualizando Estoque " . $produto['SKU'] . PHP_EOL;
						// DESCOMENTAR DEPOIS -- PARTE MAGENTO
						$this->_atualizaEstoque(); // Atualizar estoque do produto
					} 
										
					//devolver o protocolo do estoque DESCOMENTAR DEPOIS
					//$this->_kpl->ConfirmarEstoquesDisponiveis ( $dados_precos ['ProtocoloEstoque'] );
					echo "Protocolo Estoque: {$dados_estoque ['ProtocoloEstoque']} enviado com sucesso" . PHP_EOL;
					echo PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao importar estoque {$dados_estoque['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar estoque {$dados_estoque['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}		
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}	

}
