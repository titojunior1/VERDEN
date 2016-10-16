<?php

/**
 * 
 * Classe para processar o cadastro de produtos via webservice do ERP KPL - Ábacos 
 * 
 * @author Tito Junior 
 * 
 */
class Model_Verden_Kpl_Produtos extends Model_Verden_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice KPL
	 */
	private $_kpl;
	
	/*
	 * Instancia Webservice Magento
	 */
	private $_magento;	
	
	/**
	 * 
	 * construtor.	 
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService (  );
		}
	
	}
	
	/**
	 * 
	 * Adicionar produto.
	 * @param array $dados_produtos
	 * @throws Exception
	 * @throws RuntimeException
	 */
	private function _adicionaProduto ( $dados_produtos ) {
		
		$skuNovoProduto = $dados_produtos['SKU'];
		$novoProduto =  array(
							'name' => $dados_produtos ['Nome'],
							'description' => $dados_produtos ['Descricao'],
							'short_description' => $dados_produtos ['Descricao'],
							'weight' => $dados_produtos ['Peso'],
							'status' => '1',
							'url_key' => $dados_produtos ['Nome'],
							'visibility' => '4',
							'price' => $dados_produtos ['ValorVenda'],
							'special_price' => $dados_produtos ['ValorCusto'],
							'tax_class_id' => 1,
							'meta_title' => $dados_produtos ['Nome']
						); 

		$this->_magento->cadastraProduto($skuNovoProduto, $novoProduto);

	}

	/**
	 * 
	 * Método para atualização de produtos
	 * @param array $dados_produtos
	 * @throws RuntimeException
	 */
	private function _atualizaProduto ( $dados_produtos ) {

		$idProduto = $dados_produtos['product_id'];
		$produto =  array(
							'name' => $dados_produtos ['Nome'],
							'description' => $dados_produtos ['Descricao'],
							'short_description' => $dados_produtos ['Descricao'],
							'weight' => $dados_produtos ['Peso'],
							'status' => '1',
							'url_key' => $dados_produtos ['Nome'],
							'visibility' => '4',
							'price' => $dados_produtos ['ValorVenda'],
							'special_price' => $dados_produtos ['ValorCusto'],
							'tax_class_id' => 1,
							'meta_title' => $dados_produtos ['Nome']
						); 

		$this->_magento->atualizaProduto($idProduto, $produto);
	
	}

	/**
	 * 
	 * Buscar Produto.
	 * @param string $sku
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	private function buscaProduto ( $sku ) {

		$sku = trim ( $sku );
		if ( empty ( $sku ) ) {
			throw new InvalidArgumentException ( 'SKU do produto inválido' );
		}
		
		if ( !$this->_magento ){
			$this->_magento = new Model_Verden_Magento_Produtos();
		}
		
		$retorno = $this->_magento->buscaProduto( $sku );

		return $retorno;
	
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
			$array_produtos [0] ['Nome'] = utf8_encode($request ['DadosProdutos'] ['NomeProduto']);			
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
			$array_produtos [0] ['Descricao'] =  utf8_encode(empty($request ['DadosProdutos'] ['Descricao'])? $request ['DadosProdutos'] ['NomeProduto'] : str_replace('<BR>','',$request ['DadosProdutos'] ['Descricao'])) ;
			$array_produtos [0] ['ValorCusto'] = isset($request ['DadosProdutos'] ['ValorCusto']) ? $request ['DadosProdutos'] ['ValorCusto']: '0.00';
			$array_produtos [0] ['CodigoProdutoPai'] = isset($request ['DadosProdutos'] ['CodigoProdutoPai']) ? $request ['DadosProdutos'] ['CodigoProdutoPai']: '';
			$array_produtos [0] ['Unidade'] = isset($request ['DadosProdutos'] ['Unidade']) ? $request ['DadosProdutos'] ['Unidade']: '';
		
		} else {
			
			foreach ( $request ["DadosProdutos"] as $i => $d ) {
				
				//Nome do campo no wms  =  Nome do campo no Kpl
				$array_produtos [$i] ['ProtocoloProduto'] = $d ['ProtocoloProduto'];
				$array_produtos [$i] ['Categoria'] = isset($d ['Categoria']) ? $d ['Categoria']: '';				
				$array_produtos [$i] ['Nome'] = utf8_encode($d ['NomeProduto']) ;
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
				$array_produtos [$i] ['Descricao'] =  utf8_encode(empty($d ['Descricao'])? $d ['NomeProduto'] : str_replace('<BR>','',$d  ['Descricao'])) ;
				$array_produtos [$i] ['ValorCusto'] = isset($d ['ValorCusto']) ? $d ['ValorCusto']: '0.00';				
				$array_produtos [$i] ['CodigoProdutoPai'] = isset($d ['CodigoProdutoPai']) ? $d ['CodigoProdutoPai']: '';
				$array_produtos [$i] ['Unidade'] = isset($d ['Unidade']) ? $d ['Unidade']: '';
			}
		}
		
		$qtdProdutos = count($array_produtos);
		
		echo PHP_EOL;
		echo "Produtos encontrados para integracao: " . $qtdProdutos . PHP_EOL;
		echo PHP_EOL;
		
		
		echo "Conectando ao WebService Magento... " . PHP_EOL;
		$this->_magento = new Model_Verden_Magento_Produtos();
		echo "Conectado!" . PHP_EOL;
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
					echo "Buscando cadastro do produto " . $dados_produtos ['SKU'] . PHP_EOL;
					$produto = $this->buscaProduto ( $dados_produtos ['SKU'] );
					if ( $produto == false ) {
						echo "Adicionando produto " . $dados_produtos['SKU'] . " na loja Magento" . PHP_EOL;
						$this->_adicionaProduto ( $dados_produtos );
						echo "Produto adicionado. " . PHP_EOL;						
					} else {
						echo "Atualizando produto " . $dados_produtos['SKU'] . " na loja Magento" . PHP_EOL;
						$dados_produtos['product_id'] = $produto; // ID do Produto na Loja Magento
						$this->_atualizaProduto ( $dados_produtos );
						echo "Produto atualizado. " . PHP_EOL;
					}
					
					//devolver o protocolo do produto
					$this->_kpl->confirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
					echo "Protocolo Produto: {$dados_produtos['ProtocoloProduto']} enviado com sucesso" . PHP_EOL;				

				} catch ( Exception $e ) {
					echo "Erro ao importar produto {$dados_produtos['SKU']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
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

