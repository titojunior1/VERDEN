<?php

/**
 * 
 * Classe para processar o cadastro de produtos via webservice do ERP KPL - Ábacos Específico para Glamour WBR
 * 
 * @author    Tito Junior <moacir.tito@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     13/06/2014
 * 
 */


final class Model_Wms_Kpl_ProdutosGWBR extends Model_Wms_Kpl_Abstract_Produtos {


	/**
	 *
	 * Adicionar produto.
	 * @param array $dados_produtos
	 * @throws Exception
	 * @throws RuntimeException
	 */
	protected function _adicionaProduto ( $dados_produtos ) {

		if ( ! is_array ( $dados_produtos ) ) {
			throw new Exception ( "Erro ao inserir produto" );
		}
		$db = Db_Factory::getDbWms ();
		$dados_produtos ['Nome'] = $db->EscapeString ( $dados_produtos ['Nome'] );
		$dados_produtos ['Descricao'] = $db->EscapeString ( $dados_produtos ['Descricao'] );
		$dados_produtos ['Classificacao'] = $db->EscapeString ( $dados_produtos ['Classificacao'] );
		$dados_produtos ['PartNumber'] = $db->EscapeString ( $dados_produtos ['PartNumber'] );
		$dados_produtos ['SKU'] = $db->EscapeString ( $dados_produtos ['SKU'] );
		$dados_produtos ['Unidade'] = $db->EscapeString ( $dados_produtos ['Unidade'] );
		$dados_produtos ['QtdDiasVencimento'] = $db->EscapeString ( $dados_produtos ['QtdDiasVencimento'] );
		if ( empty ( $dados_produtos ['Categoria'] ) ) {
			$dados_produtos ['Categoria'] = 1;
		}
		$sql = "INSERT INTO produtos (cli_id, amb_id,cat_id, prod_descricao, prod_nome, prod_custo,  prod_sku, prod_ean_proprio, prod_part_number,prod_alt,prod_larg,prod_comp,prod_peso,prod_qtd_dias_vencimento)
		VALUES ({$this->_cli_id}, 1,{$dados_produtos['Categoria']}, '{$dados_produtos['Descricao']}', '{$dados_produtos['Nome']}', '{$dados_produtos['ValorCusto']}',
		'{$dados_produtos ['SKU']}', '{$dados_produtos ['EanProprio']}', '{$dados_produtos ['PartNumber']}',{$dados_produtos['Altura']},{$dados_produtos['Largura']},{$dados_produtos['Comprimento']},{$dados_produtos['Peso']},{$dados_produtos['QtdDiasVencimento']})";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao inserir produto' );
		}

		return true;
	}

	/**
	 *
	 * Processar cadastro de produtos via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	public function ProcessaProdutosWebservice ( $request ) {

		// cria instância do banco de dados
		$db = Db_Factory::getDbWms ();

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
			$array_produtos [0] ['QtdDiasVencimento'] = '90';
			$array_produtos [0] ['Descricao'] =  empty($request ['DadosProdutos'] ['Descricao'])? $request ['DadosProdutos'] ['NomeProduto'] : str_replace('<BR>','',$request ['DadosProdutos'] ['Descricao']) ;
			$array_produtos [0] ['ValorCusto'] = isset($request ['DadosProdutos'] ['ValorCusto']) ? $request ['DadosProdutos'] ['ValorCusto']: '0.00';
			$array_produtos [0] ['CodigoProdutoPai'] = isset($request ['DadosProdutos'] ['CodigoProdutoPai']) ? $request ['DadosProdutos'] ['CodigoProdutoPai']: '';
			$array_produtos [0] ['Unidade'] = isset($request ['DadosProdutos'] ['Unidade']) ? $request ['DadosProdutos'] ['Unidade']: '';

		} else {

			foreach ( $request ["DadosProdutos"] as $i => $d ) {
				// 			 Nome do campo no wms  =  Nome do campo no Kpl
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
				$array_produtos [$i] ['QtdDiasVencimento'] = '90';
				$array_produtos [$i] ['Descricao'] =  empty($d ['Descricao'])? $d ['NomeProduto'] : str_replace('<BR>','',$d  ['Descricao']) ;
				$array_produtos [$i] ['ValorCusto'] = isset($d ['ValorCusto']) ? $d ['ValorCusto']: '0.00';
				$array_produtos [$i] ['CodigoProdutoPai'] = isset($d ['CodigoProdutoPai']) ? $d ['CodigoProdutoPai']: '';
				$array_produtos [$i] ['Unidade'] = isset($d ['Unidade']) ? $d ['Unidade']: '';
			}
		}

		// Percorrer array de produtos
		foreach ( $array_produtos as $indice => $dados_produtos ) {
			$erros_produtos = 0;
			$array_inclui_produtos = array ();
			$prod_id = NULL;
			$incluir_produto = false;
			// validar campos obrigatórios


			if ( empty ( $dados_produtos ['CodigoProdutoPai'] ) && $this->_cli_id != '78') {

				//enviar protocolo do produto...
				try {

					$this->_kpl->ConfirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
					echo "Protocolo Produto Pai: {$dados_produtos['ProtocoloProduto']}" . PHP_EOL;
				} catch ( Exception $e ) {
					echo $e->getMessage () . PHP_EOL;
				}

				continue;
					
			}

			if ( empty ( $dados_produtos ['Nome'] ) || empty ( $dados_produtos ['Descricao'] ) || empty ( $dados_produtos ['PartNumber'] ) || empty ( $dados_produtos ['SKU'] ) || empty ( $dados_produtos ['EanProprio'] ) ) {
				echo "Produto {$dados_produtos['SKU']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_produtos['SKU']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_produtos ++;
			}
			if ( $erros_produtos == 0 ) {

				try {
					$produtos = $this->buscaProduto ( $dados_produtos ['SKU'], $dados_produtos ['PartNumber'], $dados_produtos ['EanProprio'] );
				if ( empty ( $produtos ) ) {
					$this->_adicionaProduto ( $dados_produtos );
				} else {
					$prod_id = $produtos ['prod_id'];
					//verificar se existe alguma atualização no produto
					if ( ($dados_produtos ['Nome'] != $produtos ['prod_nome'])  || ($dados_produtos ['EanProprio'] != $produtos ['prod_ean_proprio']) ) {
					//atualizar o produto
						$this->_atualizaProduto ( $prod_id, $dados_produtos );
						echo "Atualizacao de produto -{$dados_produtos['SKU']} -  ";
					} else {
						echo "Produto {$dados_produtos['SKU']} existente - Sem atualizacao -";
					}
				}
							
				//devolver o protocolo do produto
				$this->_kpl->ConfirmarProdutosDisponiveis ( $dados_produtos ['ProtocoloProduto'] );
				echo "Protocolo Produto: {$dados_produtos['ProtocoloProduto']}" . PHP_EOL;
	
						//verifica se o produto existe
				} catch ( Exception $e ) {
					echo "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
					$array_erro [$indice] = "Erro ao importar produto {$dados_produtos['EanProprio']}: " . $e->getMessage() . PHP_EOL;
				}

			}
		}

		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;

	}
}

