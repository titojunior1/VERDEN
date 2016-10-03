<?php

/**
 * 
 * Classe para processar o cadastro de produtos via webservice do ERP KPL - Ábacos 
 * 
 * @author Tito Junior 
 * 
 */
class Model_Verden_Kpl_Produtos extends Model_Verden_Kpl_KplWebService {
	
	/**
	 * Id do cliente.
	 *
	 * @var int
	 */
	private $_cli_id;
	
	/**
	 * Id do warehouse.
	 *
	 * @var int
	 */
	private $_empwh_id;
	
	private $_kpl;
	
	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 * @param int $empwh_id
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService (  );
		}
	
	}

	/**
	 * 
	 * retorna erro utilizando erros_kpl.php.
	 * @param int $codigo						// código do erro
	 * @param string ou array $parametro		// string ou array do parametro do erro, caso seja pertinente 
	 * @param string $exception					// mensagem de exceção
	 */
	/*private function retorna_erro($codigo, $parametro = NULL, $exception = NULL) {
		$temp = new erros_kpl ( $codigo, $parametro, $exception );
		return $temp->retorna_erro ( $codigo, $parametro, $exception );
	}*/
	
	/**
	 * 
	 * Adicionar produto.
	 * @param array $dados_produtos
	 * @throws Exception
	 * @throws RuntimeException
	 */
	private function _adicionaProduto ( $dados_produtos ) {

		$dados_produtos ['Nome'] = $db->EscapeString ( $dados_produtos ['Nome'] );
		$dados_produtos ['Descricao'] = $db->EscapeString ( $dados_produtos ['Descricao'] );
		$dados_produtos ['Classificacao'] = $db->EscapeString ( $dados_produtos ['Classificacao'] );
		$dados_produtos ['PartNumber'] = $db->EscapeString ( $dados_produtos ['PartNumber'] );
		$dados_produtos ['SKU'] = $db->EscapeString ( $dados_produtos ['SKU'] );
		$dados_produtos ['Unidade'] = $db->EscapeString ( $dados_produtos ['Unidade'] );
		if ( empty ( $dados_produtos ['Categoria'] ) ) {
			$dados_produtos ['Categoria'] = 1;
		}

		// PARTE MAGENTO		
		return true;
	}

	/**
	 * 
	 * Método para atualização de produtos
	 * @param int $prod_id
	 * @param array $dados_produtos
	 * @throws RuntimeException
	 */
	private function _atualizaProduto ( $prod_id, $dados_produtos ) {

		$dados_produtos ['Nome'] = $db->EscapeString ( $dados_produtos ['Nome'] );
        // PARTE MAGENTO
	
	}

	/**
	 * 
	 * Buscar Produto.
	 * @param string $sku
	 * @param string $part_number
	 * @param int $ean_proprio
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function buscaProduto ( $sku, $part_number, $ean_proprio ) {

		$sku = trim ( $sku );
		if ( empty ( $sku ) ) {
			throw new InvalidArgumentException ( 'SKU do produto inválido' );
		}
		
		$ean_proprio = trim ( $ean_proprio );
		if ( empty ( $ean_proprio ) ) {
			throw new InvalidArgumentException ( 'Ean Próprio do produto inválido' );
		}
		
		// verificar se o produto existe
		
		// BUSCAR PRODUTO MAGENTO		
	
	}

	/**
	 * 
	 * Processar cadastro de produtos via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaProdutosWebservice ( $request ) {

		// produtos
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_produtos = array ();
		
		if ( ! is_array ( $request ['DadosProdutos'] [0] ) ) {
					
			$array_produtos [0] ['ProtocoloProduto'] = $request ['DadosProdutos'] ['ProtocoloProduto'];
			$array_produtos [0] ['Categoria'] = isset($request ['DadosProdutos'] ['Categoria']) ? $request ['DadosProdutos'] ['Categoria']: '';
			$array_produtos [0] ['Nome'] = $request ['DadosProdutos'] ['NomeProduto'];			
			$array_produtos [0] ['Classificacao'] = isset($request ['DadosProdutos'] ['Classificacao']) ? $request ['DadosProdutos'] ['Classificacao']: '';
			$array_produtos [0] ['Altura'] = $request ['DadosProdutos'] ['Altura'];
			$array_produtos [0] ['Largura'] = $request ['DadosProdutos'] ['Largura'];
			$array_produtos [0] ['Comprimento'] = $request ['DadosProdutos'] ['Comprimento'];
			$array_produtos [0] ['Peso'] = $request ['DadosProdutos'] ['Peso'];
			$array_produtos [0] ['PartNumber'] = $request ['DadosProdutos'] ['CodigoProdutoAbacos'];
			$array_produtos [0] ['SKU'] = $request ['DadosProdutos'] ['CodigoProduto'];
			$array_produtos [0] ['EanProprio'] = $request ['DadosProdutos'] ['CodigoBarras'];
			$array_produtos [0] ['EstoqueMinimo'] = $request ['DadosProdutos'] ['QtdeMinimaEstoque'];
			$array_produtos [0] ['ValorVenda'] = '0.00';
			$array_produtos [0] ['Descricao'] =  empty($request ['DadosProdutos'] ['Descricao'])? $request ['DadosProdutos'] ['NomeProduto'] : str_replace('<BR>','',$request ['DadosProdutos'] ['Descricao']) ;
			$array_produtos [0] ['ValorCusto'] = isset($request ['DadosProdutos'] ['ValorCusto']) ? $request ['DadosProdutos'] ['ValorCusto']: '0.00';
			$array_produtos [0] ['CodigoProdutoPai'] = isset($request ['DadosProdutos'] ['CodigoProdutoPai']) ? $request ['DadosProdutos'] ['CodigoProdutoPai']: '';
			$array_produtos [0] ['Unidade'] = isset($request ['DadosProdutos'] ['Unidade']) ? $request ['DadosProdutos'] ['Unidade']: '';
		
		} else {
			
			foreach ( $request ["DadosProdutos"] as $i => $d ) {
				
				//Nome do campo no wms  =  Nome do campo no Kpl
				$array_produtos [$i] ['ProtocoloProduto'] = $d ['ProtocoloProduto'];
				$array_produtos [$i] ['Categoria'] = isset($d ['Categoria']) ? $d ['Categoria']: '';				
				$array_produtos [$i] ['Nome'] = $d ['NomeProduto'] ;
				$array_produtos [$i] ['Classificacao'] = isset($d ['Classificacao']) ? $d ['Classificacao']: '';
				$array_produtos [$i] ['Altura'] = $d ['Altura'];
				$array_produtos [$i] ['Largura'] = $d ['Largura'];
				$array_produtos [$i] ['Comprimento'] = $d ['Comprimento'];
				$array_produtos [$i] ['Peso'] = $d ['Peso'];
				$array_produtos [$i] ['PartNumber'] = $d ['CodigoProdutoAbacos'];
				$array_produtos [$i] ['SKU'] = $d ['CodigoProduto'];
				$array_produtos [$i] ['EanProprio'] = $d ['CodigoBarras'];
				$array_produtos [$i] ['EstoqueMinimo'] = $d ['QtdeMinimaEstoque'];
				$array_produtos [$i] ['ValorVenda'] = '0.00';				
				$array_produtos [$i] ['Descricao'] =  empty($d ['Descricao'])? $d ['NomeProduto'] : str_replace('<BR>','',$d  ['Descricao']) ;
				$array_produtos [$i] ['ValorCusto'] = isset($d ['ValorCusto']) ? $d ['ValorCusto']: '0.00';				
				$array_produtos [$i] ['CodigoProdutoPai'] = isset($d ['CodigoProdutoPai']) ? $d ['CodigoProdutoPai']: '';
				$array_produtos [$i] ['Unidade'] = isset($d ['Unidade']) ? $d ['Unidade']: '';
			}
		}
		
		$qtdProdutos = count($array_produtos);
		
		echo PHP_EOL;
		echo "Produtos encontrados para integracao: " . $qtdProdutos . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de produtos
		foreach ( $array_produtos as $indice => $dados_produtos ) {
			$erros_produtos = 0;
			$array_inclui_produtos = array ();
			$prod_id = NULL;
			$incluir_produto = false;
			// validar campos obrigatórios
			
			if ( empty ( $dados_produtos ['Nome'] ) || empty ( $dados_produtos ['Descricao'] ) || empty ( $dados_produtos ['PartNumber'] ) || empty ( $dados_produtos ['SKU'] ) || empty ( $dados_produtos ['EanProprio'] ) ) {
				echo "Produto {$dados_produtos['SKU']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_produtos['SKU']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_produtos ++;
			}
			if ( $erros_produtos == 0 ) {
				
				try {
					$produto = ''; //$this->buscaProduto ( $dados_produtos ['SKU'], $dados_produtos ['PartNumber'], $dados_produtos ['EanProprio'] );
					if ( empty ( $produtos ) ) {
						echo "Adicionando produto " . $dados_produtos['SKU'] . PHP_EOL;
						// DESCOMENTAR DEPOIS -- PARTE MAGENTO
						//$this->_adicionaProduto ( $dados_produtos );
					} else {
						$prod_id = $produtos ['prod_id'];
						//verificar se existe alguma atualização no produto
						if ( ($dados_produtos ['Nome'] != $produtos ['prod_nome'])  || ($dados_produtos ['EanProprio'] != $produtos ['prod_ean_proprio']) ) {
							echo "Atualizando produto " . $dados_produtos['SKU'] . PHP_EOL;
							//atualizar o produto
							// DESCOMENTAR DEPOIS -- PARTE MAGENTO
							//$this->_atualizaProduto ( $prod_id, $dados_produtos );
							echo "Atualizacao de produto -{$dados_produtos['SKU']} -  ";
						} else {
							echo "Produto {$dados_produtos['SKU']} existente - Sem atualizacao -";
						}
					}
					
					//devolver o protocolo do produto DESCOMENTAR DEPOIS
					//$this->_kpl->confirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
					echo "Protocolo Produto: {$dados_produtos['ProtocoloProduto']} enviado com sucesso" . PHP_EOL;
					echo PHP_EOL;
				
		//verifica se o produto existe
				} catch ( Exception $e ) {
					echo "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}
		
		/*	if (empty ( $array_erro )) {
			// Se o array de erro não estiver preenchido, então retorna mensagem de Ok, porém sem dados 
			$array_retorno = array ('ProcotoPedido' => $guid, 'ResultadoOperacao' => $this->retorna_erro ( 200003, "" ) );
		} else {
			// retorna mensagens de erros			
			$array_retorno = array ('ProcotoPedido' => $guid, 'ResultadoOperacao' => $array_erro );
		}
		*/
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}

