<?php

/**
 * 
 * Classe de gerenciamento de atualização de estoque com a Kpl
 * @author    Rômulo Z. C. Cunha <romulo.cunha@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     30/08/2012
 *
 */

class Model_Wms_Kpl_EstoqueKpl extends Model_Wms_Kpl_KplWebService {
	
	/**
	 * Id do Cliente.
	 *
	 * @var int
	 */
	private $_cli_id;
	
	/**
	 * Warehouse do Cliente.
	 *
	 * @var int
	 */
	private $_empwh_id;
	
	/**
	 * Variavel  de Objeto da Classe Kpl.
	 *
	 * @var Model_Wms_kpl
	 */
	public $_client;
	
	/**
	 * Construtor.
	 * @param int $cli_id Id do Cliente.
	 * @param int $empwh_id Armazém do Cliente.
	 * @param object $webservice objeto com conexão. 
	 */
	public function __construct($cli_id, $empwh_id) {
		$cli_id = trim ( $cli_id );
		if (! ctype_digit ( $cli_id )) {
			throw new InvalidArgumentException ( 'ID do Cliente inválido' );
		}
		$empwh_id = trim ( $empwh_id ); // remove espaços em branco
		if (! ctype_digit ( $empwh_id )) { // verifica por caracteres numéricos
			throw new InvalidArgumentException ( 'Warehouse do Cliente inválido' );
		}
		
		$this->_cli_id = $cli_id;
		$this->_empwh_id = $empwh_id;
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
		}
	
	}
	
	/**
	 * Atualiza a quantidade de produtos no estoque.
	 * @param string $idestoque Id do Estoque
	 * @param string $idsku     Ido do Sku
	 * @param int   $quantidade Quantidade a ser atualizada 
	 * @param date $dateofav Data da atualização
	 * @return retorna mensagem em caso de erro
	 */
	private function _atualizaArmazemSku($sku, $ean_proprio, $quantidade) {
		try {
			$chaveIdentificacao = $this->_kpl->getChaveIdentificacao();
			$saldo['DadosSaldoProduto']['CodigoProduto'] = $sku;
			$saldo['DadosSaldoProduto']['CodigoBarras'] = $ean_proprio;
			$saldo['DadosSaldoProduto']['SaldoDisponivel'] = $quantidade;
			$request = array(
			"ChaveIdentificacao" => $chaveIdentificacao,
			"ListaDeSaldos" =>$saldo
			);
			$armazem_sku = $this->_kpl->AtualizarSaldoProduto($request);
			if (! empty ( $armazem_sku ['faultcode'] )) {
				// lança o erro como exception
				$erro_ws = $armazem_sku ['faultstring'] ['!'];
				throw new RuntimeException ( $erro_ws );
			}
			if($armazem_sku['AtualizarSaldoProdutoResult']['ResultadoOperacao']['Codigo']!="200002"){
				throw new Exception ('Erro ao atualizar saldo do produto ');
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * trata erros e transforma em string
	 * @param Array $array_erros
	 */
	private function _trataErro($array_erros) {
		$str_retorno = "";
		foreach ( $array_erros as $prod_id => $erro ) {
			$str_retorno .= "- produto {$prod_id}: {$erro}" . PHP_EOL;
		}
		return $str_retorno;
	}
	
	/**
	 * 
	 * Gerencia a atualização de estoque no site da Kpl
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	public function geraAtualizacaoEstoque($data_anterior, $hora_anterior) {
		return;
		$db = Db_Factory::getDbWms ();
			
		echo "- buscando produtos movimentados a partir de {$data_anterior} {$hora_anterior}: ";
		
		// busca produtos filho ativos
		$sql = "SELECT * FROM (
				SELECT DISTINCT p.prod_id, p.prod_ean_proprio,p.prod_sku, MAX(CONCAT(ah.apthist_data, ' ', ah.apthist_hora)) data_hora
				FROM produtos p
				INNER JOIN apartamentos_hist ah ON (p.prod_id = ah.prod_id)
				WHERE p.cli_id = {$this->_cli_id} 
					AND (
						(ah.apthist_data = '{$data_anterior}' AND ah.apthist_hora >= '{$hora_anterior}') 
						OR
						(ah.apthist_data > '{$data_anterior}')
					)
				GROUP BY p.prod_id
				UNION
				SELECT DISTINCT p.prod_id, p.prod_ean_proprio,p.prod_sku, MAX(CONCAT(nsie.notitemp_data, ' ', nsie.notitemp_hora)) 
				FROM produtos p
				INNER JOIN notas_saida_itens_empenho nsie ON (p.prod_id = nsie.prod_id)
				WHERE p.cli_id = {$this->_cli_id} 
					AND (
						(nsie.notitemp_data = '{$data_anterior}' AND nsie.notitemp_hora >= '{$hora_anterior}')
						OR
						(nsie.notitemp_data > '{$data_anterior}')
					)
				GROUP BY p.prod_id
				UNION
				SELECT DISTINCT p.prod_id, p.prod_ean_proprio, p.prod_sku,MAX(CONCAT(nss.notstat_data, ' ', nss.notstat_hora)) 
				FROM produtos p
				INNER JOIN notas_saida_itens nsi ON (nsi.prod_id = p.prod_id)
				INNER JOIN notas_saida_status nss ON (nsi.not_id = nss.not_id)
				WHERE p.cli_id = {$this->_cli_id} AND nss.notstat_code='1007' 
					AND (
						(nss.notstat_data = '{$data_anterior}' AND nss.notstat_hora >= '{$hora_anterior}')
						OR
						(nss.notstat_data > '{$data_anterior}')
					)
				GROUP BY p.prod_id
				) AS query ORDER BY data_hora ASC 
				";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( "Erro sistêmico ao buscar produtos" );
		}
		
		echo '- total de itens: ';
		$qtd_itens = $db->NumRows ( $res );
		echo $qtd_itens . PHP_EOL;
		
		if ($qtd_itens == 0) {
			echo '- saindo' . PHP_EOL;
			return;
		}
		
		echo '- warehouse ' . $this->_empwh_id . PHP_EOL;
		
		$row = $db->FetchAssoc ( $res );
		
		$array_erros = NULL;
		
		$estoque = new Model_Wms_Movimentacao_Endereco ();
		$qtd_empenhada = 0;
		$i=0;
		try {
			
			while ( $row ) {
				
				if($i==300){
					echo 'Pausa'.PHP_EOL;
					sleep(20);
					$i=0;
				}
				$qtd_empenhada = 0;
				
				echo '- atualizando produto: ' . $row ['prod_id'] . ' / ' . $row ['prod_ean_proprio'];
				// quantia empenhada
				$qtd_empenhada += ( int ) $estoque->getEmpenhosPendentesProduto ( $row ['prod_id'], 'vendavel' );
				$qtd_empenhada += ( int ) $estoque->getEmpenhosProduto ( $row ['prod_id'], 'vendavel' );
				$dados = array ();
				$dados ['data_hora'] = date ( 'Y-m-d H:i:s' );
				$dados ['prodap_qtd'] = 0;
				
				// seleciona último movimento do produto			
				$sql_mov = "SELECT SUM(pa.prodap_qtd) as prodap_qtd, MAX(prodapt_data_alteracao) as data_hora
					FROM produtos_apartamento pa  
					INNER JOIN warehouse_apartamento wa ON (pa.whapt_id = wa.whapt_id)
					WHERE prod_id = {$row['prod_id']} AND wa.whapt_quarentena = 0 AND wa.whapt_tipo_prod = 'vendavel'";
				
				$res_mov = $db->Execute ( $sql_mov );
				if (! $res_mov) {
					throw new RuntimeException ( "Erro sistemico ao buscar movimento" );
				}
				if ($db->NumRows ( $res_mov ) > 0) {
					$row_mov = $db->FetchAssoc ( $res_mov );
					if (empty ( $row_mov ['data_hora'] )) {
						$dados ['prodap_qtd'] = 0;
					} else {
						$dados ['prodap_qtd'] = $row_mov ['prodap_qtd'];
					}
				}
				
				// deduzir quantia empenhada
				$dados ['prodap_qtd'] = $dados ['prodap_qtd'] - $qtd_empenhada;
				if ($dados ['prodap_qtd'] < 0) {
					$dados ['prodap_qtd'] = 0;
				}
				echo '      Quantidade: ' . $dados ['prodap_qtd'] . PHP_EOL;
				try {
					$data_hora = str_replace ( ' ', 'T', $dados ['data_hora'] );
					// atualiza estoque do Kpl
					$this->_atualizaArmazemSku ( $row ['prod_sku'], $row ['prod_ean_proprio'], $dados ['prodap_qtd'] );
					//sleep(1);
					echo 'Produto atualizado: ' . "$this->_empwh_id, {$row ['prod_ean_proprio']}, {$dados ['prodap_qtd']}, $data_hora" . PHP_EOL;
				} catch ( Exception $e ) {
					$array_erros [$row ['prod_id']] = $e->getMessage ();
				}
				
				$row = $db->FetchAssoc ( $res );
				$i++;
			}
			
			if (! empty ( $array_erros ) && is_array ( $array_erros )) {
				$str_erro = $this->_trataErro ( $array_erros );
				throw new Exception ( $str_erro );
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
	/**
	 * 
	 * Gerencia a atualização de estoque no site da Kpl de todos os produtos
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	public function geraAtualizacaoEstoqueGeral() {
		$db = Db_Factory::getDbWms ();
		
		echo "Buscando todos os produtos do cliente {$this->_cli_id} ... " . PHP_EOL;
		
		$sql_prod = "SELECT prod_id, prod_ean_proprio FROM produtos WHERE cli_id={$this->_cli_id} AND prod_id_parent IS NOT NULL";
		$res_prod = $db->Execute ( $sql_prod );
		if (! $res_prod) {
			throw new RuntimeException ( 'Erro sistêmico ao buscar produtos' );
		}
		if ($db->NumRows ( $res_prod ) == 0) {
			return false;
		}
		
		echo 'Warehouse ' . $this->_empwh_id . PHP_EOL;
		
		$row_prod = $db->FetchAssoc ( $res_prod );
		while ( $row_prod ) {
			echo 'atualizando produto: ' . $row_prod ['prod_id'] . ' / ' . $row_prod ['prod_ean_proprio'];
			$array_erros = NULL;
			
			$estoque = new Model_Wms_Movimentacao_Endereco ();
			$qtd_empenhada = 0;
			
			try {
				
				// quantia empenhada
				$qtd_empenhada += ( int ) $estoque->getEmpenhosPendentesProduto ( $row_prod ['prod_id'], 'vendavel' );
				$qtd_empenhada += ( int ) $estoque->getEmpenhosProduto ( $row_prod ['prod_id'], 'vendavel' );
				$dados = array ();
				$dados ['data_hora'] = date ( 'Y-m-d H:i:s' );
				$dados ['prodap_qtd'] = 0;
				
				// seleciona último movimento do produto			
				$sql_mov = "SELECT SUM(pa.prodap_qtd) as prodap_qtd, MAX(prodapt_data_alteracao) as data_hora
					FROM produtos_apartamento pa  
					INNER JOIN warehouse_apartamento wa ON (pa.whapt_id = wa.whapt_id)
					WHERE prod_id = {$row_prod['prod_id']} AND wa.whapt_quarentena = 0 AND wa.whapt_tipo_prod = 'vendavel'";
				
				$res_mov = $db->Execute ( $sql_mov );
				if (! $res_mov) {
					throw new RuntimeException ( "Erro sistemico ao buscar movimento" );
				}
				if ($db->NumRows ( $res_mov ) > 0) {
					$row_mov = $db->FetchAssoc ( $res_mov );
					if (empty ( $row_mov ['data_hora'] )) {
						$dados ['prodap_qtd'] = 0;
					} else {
						$dados ['prodap_qtd'] = $row_mov ['prodap_qtd'];
					}
				}
				
				// deduzir quantia empenhada
				$dados ['prodap_qtd'] = $dados ['prodap_qtd'] - $qtd_empenhada;
				if ($dados ['prodap_qtd'] < 0) {
					$dados ['prodap_qtd'] = 0;
				}
				
				echo '      Quantidade: ' . $dados ['prodap_qtd'] . PHP_EOL;
				
				try {
					$data_hora = str_replace ( ' ', 'T', $dados ['data_hora'] );
					// atualiza estoque do Kpl
					$this->_atualizaArmazemSku ( $this->_empwh_id, $row_prod ['prod_ean_proprio'], $dados ['prodap_qtd'], $data_hora );
					echo 'Produto atualizado: ' . $row_prod ['prod_id'] . ' / ' . $row_prod ['prod_ean_proprio'] . PHP_EOL;
				} catch ( Exception $e ) {
					$array_erros [$row_prod ['prod_id']] = $e->getMessage ();
					echo 'Erro ao atualizar: ' . $row_prod ['prod_id'] . ' / ' . $row_prod ['prod_ean_proprio'] . $e->getMessage () . PHP_EOL;
				}
				
				if (! empty ( $array_erros ) && is_array ( $array_erros )) {
					$str_erro = $this->_trataErro ( $array_erros );
					throw new Exception ( $str_erro );
				}
			
			} catch ( Exception $e ) {
				throw new Exception ( $e->getMessage () );
			}
			$row_prod = $db->FetchAssoc ( $res_prod );
		}
	}
	
	/**
	 * 
	 * Gerencia a atualização de estoque no site do Kpl
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	public function geraAtualizacaoEstoqueProduto($prod_id) {
		$db = Db_Factory::getDbWms ();
		
		echo "Buscando estoque do produto  {$prod_id}: ";
		// busca produtos filho ativos
		$sql = "SELECT prod_ean_proprio FROM produtos p
				WHERE prod_id = {$prod_id}";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( "Erro sistêmico ao buscar produtos" );
		}
		
		$row = $db->FetchAssoc ( $res );
		
		$estoque = new Model_Wms_Movimentacao_Endereco ();
		$qtd_empenhada = 0;
		
		try {
			
			// quantia empenhada
			$qtd_empenhada += ( int ) $estoque->getEmpenhosPendentesProduto ( $prod_id, 'vendavel' );
			$qtd_empenhada += ( int ) $estoque->getEmpenhosProduto ( $prod_id, 'vendavel' );
			
			$dados = array ();
			$dados ['data_hora'] = date ( 'Y-m-d H:i:s' );
			$dados ['prodap_qtd'] = 0;
			
			// seleciona último movimento do produto			
			$sql_mov = "SELECT SUM(pa.prodap_qtd) as prodap_qtd, MAX(prodapt_data_alteracao) as data_hora
					FROM produtos_apartamento pa  
					INNER JOIN warehouse_apartamento wa ON (pa.whapt_id = wa.whapt_id)
					WHERE prod_id = {$prod_id} AND wa.whapt_quarentena = 0 AND wa.whapt_tipo_prod = 'vendavel'";
			
			$res_mov = $db->Execute ( $sql_mov );
			if (! $res_mov) {
				throw new RuntimeException ( "Erro sistemico ao buscar movimento" );
			}
			if ($db->NumRows ( $res_mov ) > 0) {
				$row_mov = $db->FetchAssoc ( $res_mov );
				if (empty ( $row_mov ['data_hora'] )) {
					$dados ['prodap_qtd'] = 0;
				} else {
					$dados ['prodap_qtd'] = $row_mov ['prodap_qtd'];
				}
			}
			
			// deduzir quantia empenhada
			$dados ['prodap_qtd'] = $dados ['prodap_qtd'] - $qtd_empenhada;
			if ($dados ['prodap_qtd'] < 0) {
				$dados ['prodap_qtd'] = 0;
			}
			
			try {
				$data_hora = str_replace ( ' ', 'T', $dados ['data_hora'] );
				// atualiza estoque do Kpl
				$this->_atualizaArmazemSku ( $this->_empwh_id, $row ['prod_ean_proprio'], $dados ['prodap_qtd'], $data_hora );
			} catch ( Exception $e ) {
				$array_erros [$prod_id] = $e->getMessage ();
			}
			
			if (! empty ( $array_erros ) && is_array ( $array_erros )) {
				$str_erro = $this->_trataErro ( $array_erros );
				throw new Exception ( $str_erro );
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}

}
