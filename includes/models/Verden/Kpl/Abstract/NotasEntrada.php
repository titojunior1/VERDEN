<?php

/**
 * 
 * Classe para processar o cadastro de notas de entrada via webservice do ERP KPL - Ábacos 
 * 
 * @author    Tito Junior <moacir.tito@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     02/07/2014
 * 
 */

abstract class Model_Wms_Kpl_Abstract_NotasEntrada extends Model_Wms_Kpl_KplWebService {
	
	/**
	 * ID do movimento inserido
	 *
	 * @var int
	 */
	private $_climov_id;
	
	/**
	 * Tipo de movimento - E (entrada)
	 *
	 * @var int
	 */
	private $_climov_tipo;
	
	/**
	 * Id do cliente.
	 *
	 * @var int
	 */
	private $_cli_id;

	/**
	 * 
	 * Construtor.
	 * @param int $cli_id
	 * @param int $empwh_id
	 */
	function __construct ( $cli_id, $empwh_id ) {

		$this->_cli_id = $cli_id;
		$this->_empwh_id = $empwh_id;
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
		}
	
	}

	/**
	 * 
	 * Método para enviar para KPL.
	 * @param string $note_id
	 * @param string $note_numero
	 * @param string $note_serie
	 * @throws RuntimeException
	 */
	public function confirmarRecebimentoMercadoria ( $note_id, $note_numero = NULL, $note_serie = NULL ) {

		$db = Db_Factory::getDbWms ();
		
		$sql = "SELECT note_numero,note_serie,note_pedido, f.forn_cnpj as fornecedor_id FROM notas_entrada ne
					INNER JOIN fornecedores f USING(forn_id)
					WHERE note_id={$note_id}";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			throw new RuntimeException ( 'Erro ao consultar dados do pedido de entrada' );
		}
		$row = $db->FetchAssoc ( $res );
		
		if ( empty ( $row ['note_serie'] ) ) {
			
			$serie_nota = $note_serie;
		} else {
			$serie_nota = $row ['note_serie'];
		
		}
		if ( empty ( $row ['note_numero'] ) ) {
			
			$numero_nota = $note_numero;
		} else {
			$numero_nota = $row ['note_numero'];
		}
		
		$pedido = $row ['note_pedido'];
		$fornecedor_id = $row ['fornecedor_id'];
		$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
		
		$sql_itens = "SELECT p.prod_sku,p.prod_ean_proprio, nse.notit_qtd_arm as qtd FROM notas_entrada_itens nse
				INNER JOIN produtos p USING(prod_id)
				WHERE nse.note_id={$note_id}";
		$res_itens = $db->Execute ( $sql_itens );
		if ( ! $res_itens ) {
			throw new RuntimeException ( "Erro ao buscar itens da nota" );
		}
		$row_itens = $db->FetchAssoc ( $res_itens );
		
		$array_mercadoria ['NumeroNota'] = $numero_nota;
		$array_mercadoria ['SerieNota'] = $serie_nota;
		$array_mercadoria ['Fornecedor'] = $fornecedor_id;
		//selecionar os itens recebidos
		$i = 0;
		
		while ( $row_itens ) {
			
			$array_mercadoria ['Itens'] ['DadosItensMercadoria'] [$i] ['CodigoProduto'] = $row_itens ['prod_sku'];
			$array_mercadoria ['Itens'] ['DadosItensMercadoria'] [$i] ['Quantidade'] = $row_itens ['qtd'];
			$row_itens = $db->FetchAssoc ( $res_itens );
			$i ++;
		}
		$request = array ( 

		"ChaveIdentificacao" => $chaveIdentificacao, 'RecebimentoMercadoria' => $array_mercadoria );
		
		try {
			
			$retorno = $this->_kpl->realizarRecebimentoMercadoria ( $request );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () . " - Pedido: {$pedido}" );
		}
	
	}

	/**
	 * 
	 * Processar pedidos de entrada via webservice.
	 * @param array $request requisição xml do webservice
	 */
	function ProcessaNotasEntradaWebservice ( $request ) {

		global $app;
		$db = Db_Factory::getDbWms ();
		//criação do movimento
		//inserir os dados na tabela movimentos
		$sql = "INSERT INTO movimentos (cli_id,climov_data,climov_horainicio,climov_upload,climov_tipo) VALUES($this->_cli_id,'" . date ( 'Y-m-d' ) . "','" . date ( 'H:i:s' ) . "',1,'E')";
		if ( ! $db->Execute ( $sql ) ) {
		}
		$this->_climov_id = $db->LastInsertId ();
		// fornecedores do cliente
		$array_fornecedores_total = $array_fornecedores_cliente = array ();
		$sql = "SELECT f.forn_id, f.forn_id_cli FROM fornecedores f WHERE f.cli_id={$this->_cli_id}";
		$qry2 = $db->Execute ( $sql );
		if ( $qry2 ) {
			while ( $row = $db->FetchAssoc ( $qry2 ) ) {
				$array_fornecedores_total [] = $row ['forn_id'];
				$row ['forn_id_cli'] = trim ( $row ['forn_id_cli'] );
				if ( ! empty ( $row ['forn_id_cli'] ) ) $array_fornecedores_cliente [$row ['forn_id_cli']] = $row ['forn_id'];
			}
			$i = 0;
			$j = 0;
			if ( ! empty ( $request ["Rows"] ) ) {
				
				if ( ! is_array ( $request ['Rows'] ['DadosNotasFiscaisEntradaDisponiveisWMS'] [0] ) ) {
					foreach ( $request ['Rows'] as $i => $d ) {
						
						$array_pedidos [$j] ['ProtocoloNotaFiscal'] = $d ['ProtocoloNotaFiscal'];
						$array_pedidos [$j] ['NumeroPedido'] = $d ['NumeroNotaFiscal'];
						$array_pedidos [$j] ['NumeroNF'] = $d ['NumeroNotaFiscal'];
						$array_pedidos [$j] ['SerieNF'] = $d ['SerieNotaFiscal'];
						$data = substr ( $d ['DataCadastro'], 0, 10 );
						$d_ano = substr ( $data, 6, 4 );
						$d_mes = substr ( $data, 3, 2 );
						$d_dia = substr ( $data, 0, 2 );
						$array_pedidos [$j] ['TipoEstoque'] = 'vendavel';
						$array_pedidos [$j] ['HoraNF'] = $d ['HoraNF'];
						$array_pedidos [$j] ['CNPJFornecedor'] = $d ['RemetenteCPFouCNPJ'];
						$array_pedidos [$j] ['ProdutosEntradaArray'] = $d ['Itens'];
					
					}
				} else {
					foreach ( $request ['Rows'] ['DadosNotasFiscaisEntradaDisponiveisWMS'] as $i => $d ) {
						$array_pedidos [$i] ['ProtocoloNotaFiscal'] = $d ['ProtocoloNotaFiscal'];
						$array_pedidos [$i] ['NumeroPedido'] = $d ['NumeroNotaFiscal'];
						$array_pedidos [$i] ['NumeroNF'] = $d ['NumeroNotaFiscal'];
						$array_pedidos [$i] ['SerieNF'] = $d ['SerieNotaFiscal'];
						$data = substr ( $d ['DataCadastro'], 0, 10 );
						$array_pedidos [$i] ['TipoEstoque'] = 'vendavel';
						$array_pedidos [$i] ['CNPJFornecedor'] = $d ['RemetenteCPFouCNPJ'];
						$array_pedidos [$i] ['ProdutosEntradaArray'] = $d ['Itens'];
					
						$array_pedidos [$i] ['CNPJFornecedor'] = $d ['RemetenteCPFouCNPJ'];;
					}
				}
			
			} else {
				echo "Não existem dados de pedido" . PHP_EOL;
				/*$array_retorno = array ('ProcotoPedido' => $guid, 'ResultadoOperacao' => $this->retorna_erro ( 200003, "" ) );
				return $array_retorno;*/
			}
		} else {
			echo "Erro ao buscar fornecedor" . PHP_EOL;
		
		//			$array_retorno = array ('ProcotoPedido' => $guid, 'ResultadoOperacao' => $this->retorna_erro ( 300014, "", "Erro ao buscar fornecedores" ) );
		//		return $array_retorno;
		}
		$note_numero_ant = NULL;
		$pedidos_ok = 0;
		if ( count ( $array_pedidos ) > 0 ) {
			
			
			foreach ( $array_pedidos as $indice => $dados_pedidos ) {
								
				echo "Pedido a inserir: {$dados_pedidos['NumeroPedido']}" . PHP_EOL;
				$array_erro = array ();
				$erros_pedidos = 0;
				
				//verificar se o fornecedor está cadastrado
				$sql = "SELECT forn_id_cli FROM fornecedores WHERE cli_id={$this->_cli_id} and forn_cnpj='{$dados_pedidos['CNPJFornecedor']}'"; // Erro provocado
				$res = $app->DbExecute ( $sql );
				if ( ! $res ) {
					$erros_pedidos ++;
				}
				if ( $app->DbNumRows ( $res ) == 0 ) {
					$erros_pedidos ++;
				} else {
					$row = $app->DbFetchAssoc ( $res );
					$dados_pedidos ['CodigoFornecedor'] = $row ['forn_id_cli'];
				}
				//validar os dados obrigatórios 
				if ( empty ( $dados_pedidos ['CodigoFornecedor'] ) || empty ( $dados_pedidos ['NumeroPedido'] ) /* || empty ( $dados_pedidos ['ProdutosEntradaArray'] )*/) {
					$array_erro [$indice] = "Nota de Entrada - Pedido: {$dados_pedidos['NumeroPedido']} - Codigo Fornecedor ou Numero do Pedido nao preenchidos";
					echo "Pedido: {$dados_pedidos['NumeroPedido']} - Campos obrigatórios não preenchidos " . PHP_EOL;
					$erros_pedidos ++;
				}
				
				//se não houver erro no preenchimento dos dados obrigatórios
				if ( $erros_pedidos == 0 ) {
					//validar fornecedores
					if ( count ( $array_fornecedores_total ) > 0 ) {
						if ( (! in_array ( $dados_pedidos ['CodigoFornecedor'], $array_fornecedores_total ) && ! isset ( $array_fornecedores_cliente [$dados_pedidos ['CodigoFornecedor']] )) ) {
							$array_erro [$indice] = "Código do Fornecedor não informado ou inválido";
							echo "Pedido: {$dados_pedidos['NumeroPedido']} -Código do Fornecedor não informado ou inválido" . PHP_EOL;
							
							$erros_pedidos ++;
						} else {
							// Se for ID fornecedor do cliente, troca pelo ID do WMS
							$dados_pedidos ['CodigoFornecedor'] = (empty ( $dados_pedidos ['CodigoFornecedor'] )) ? $array_fornecedores_cliente [$dados_pedidos ['CodigoFornecedor']] : $dados_pedidos ['CodigoFornecedor'];
						}
					
					} else {
						// erro não encontrou nenhum cliente para este fornecedor
						$array_erro [$indice] = "Nenhum fornecedor cadastrado para esse cliente";
						echo "Pedido: {$dados_pedidos['NumeroPedido']} -Nenhum fornecedor cadastrado para esse cliente" . PHP_EOL;
						$erros_pedidos ++;
					}
				}
				//validar tipo de de estoque
				if ( $dados_pedidos ['TipoEstoque'] != 'vendavel' && $dados_pedidos ['TipoEstoque'] != 'nao_vendavel' && $dados_pedidos ['TipoEstoque'] != 'locacao' ) {
					$erros_pedidos ++;
				}
				
				//validar itens do pedido
				$sql_add_nota = $sql_add_pedido = '';
				if ( empty ( $dados_pedidos ['NumeroNF'] ) ) {
					$sql_add_nota = ' OR note_numero IS NULL ';
				}
				if ( empty ( $dados_pedidos ['NumeroPedido'] ) ) {
					$sql_add_pedido = ' OR note_pedido IS NULL';
				}
				if ( $erros_pedidos == 0 ) {
										
					// verificar nota e pedidos duplicados				
					
					$sql = "SELECT COUNT(1) AS total FROM notas_entrada ne
							INNER JOIN movimentos mov ON (mov.climov_id = ne.climov_id AND mov.cli_id = " . $this->_cli_id . ")
							WHERE 1
							AND ne.forn_id={$array_fornecedores_cliente [$dados_pedidos ['CodigoFornecedor']]}
							AND	ne.climov_id != {$this->_climov_id} 
							AND (note_pedido = '{$dados_pedidos['NumeroPedido']}' {$sql_add_pedido})
							AND (note_numero = '{$dados_pedidos['NumeroNF']}' {$sql_add_nota})
							AND ne.note_status != 3 "
							. $and_note_serie;
					$res = $app->DbExecute ( $sql );
					if ( ! $res ) {
						// erro ao tentar realizar select
						$array_erro [$indice] = "Erro ao verificar nota e pedidos duplicados";
						echo "Pedido: {$dados_pedidos['NumeroPedido']} - Erro ao verificar nota e pedidos duplicados" . PHP_EOL;
						$erros_pedidos ++;
					}
					$row = $app->DbFetchAssoc ( $res );
					if ( $row ['total'] > 0 ) {
						// erro: pedido duplicado 
						$array_erro [$indice] = "Pedido Duplicado";
						echo "Pedido: {$dados_pedidos['NumeroPedido']} - Pedido Duplicado" . PHP_EOL;
						try {
							//enviar protocolo de confirmação do pedido 
							$this->_kpl->confirmarRecebimentoNotaFiscalEntrada ( $dados_pedidos ['ProtocoloNotaFiscal'] );
							echo "Protocolo de NF de entrada {$dados_pedidos['ProtocoloNotaFiscal']} enviado" . PHP_EOL;
						} catch ( Exception $e ) {
							throw new Exception ( $e->getMessage () );
						}
						
						$erros_pedidos ++;
					}
				}
				if ( $erros_pedidos == 0 ) {
					
					$app->DbTransactionStart ();
					
					if ( ($dados_pedidos ['CodigoFornecedor'] . ":" . $dados_pedidos ['NumeroNF'] . ":" . $dados_pedidos ['NumeroPedido']) != $note_numero_ant ) {
						$note_numero_ant = $dados_pedidos ['CodigoFornecedor'] . ":" . $dados_pedidos ['NumeroNF'] . ":" . $dados_pedidos ['NumeroPedido'];
						if ( $dados_pedidos ['DataNF'] == "" || $dados_pedidos ['DataNF'] == "00/00/0000" ) {
							$dados_pedidos ['DataNF'] = date ( "Y-m-d" );
						}
						if ( $dados_pedidos ['HoraNF'] == "" || $dados_pedidos ['HoraNF'] == "00:00:00" ) {
							$dados_pedidos ['HoraNF'] = date ( "H:i:s" );
						}
					}
					
					// incluir nota no banco de dados
					$array_incluir_nota = array ();
					$array_incluir_nota ['forn_id'] = $array_fornecedores_cliente [$dados_pedidos ['CodigoFornecedor']];
					$array_incluir_nota ['climov_id'] = $this->_climov_id;
					$array_incluir_nota ['empwh_id'] = $this->_empwh_id;
					$array_incluir_nota ['note_pedido'] = $dados_pedidos ['NumeroPedido'];
					$array_incluir_nota ['note_numero'] = $dados_pedidos ['NumeroNF'];
					$array_incluir_nota ['note_serie'] = $dados_pedidos ['SerieNF'];
					$array_incluir_nota ['note_data'] = $dados_pedidos ['DataNF'];
					$array_incluir_nota ['note_hora'] = $dados_pedidos ['HoraNF'];
					$array_incluir_nota ['note_tipo_prod'] = $dados_pedidos ['TipoEstoque'];
					$row_note = $app->gera_insert ( $array_incluir_nota, "notas_entrada" );
					if ( ! $row_note ) {
						// erro ao tentar incluir nota
						$array_erro [$indice] = "Erro ao incluir nota no banco de dados";
						echo "Pedido: {$dados_pedidos['NumeroPedido']} - Erro ao incluir nota no banco de dados" . PHP_EOL;
						
						$erros_pedidos ++;
					} else {
						$note_id = $row_note->note_id;
					}
					
					if ( is_array ( $dados_pedidos ['ProdutosEntradaArray'] ) && $erros_pedidos == 0 ) {
						
						if ( ! is_array ( $dados_pedidos ['ProdutosEntradaArray'] ['Rows'] ['DadosItensNotaFiscalEntradaDisponiveisWMS'] [0] ) ) {
							$array_produtos [0] = $dados_pedidos ['ProdutosEntradaArray'] ['Rows'] ['DadosItensNotaFiscalEntradaDisponiveisWMS'];
						
						} else {
							$array_produtos = $dados_pedidos ['ProdutosEntradaArray'] ['Rows'] ['DadosItensNotaFiscalEntradaDisponiveisWMS'];
						}
						
						foreach ( $array_produtos as $i => $dados_produtos ) {
							
							//verificar se o produto está cadastrado
							$sql = "SELECT prod_id FROM produtos
								  WHERE cli_id={$this->_cli_id}
								  AND(prod_part_number='{$dados_produtos['CodigoProdutoAbacos']}' AND prod_sku='{$dados_produtos['CodigoProduto']}')";
							$res = $app->DbExecute ( $sql );
							if ( ! $res ) {
								// falha ao buscar prod_id
								$array_erro [$indice] = "Erro ao buscar o produto";
							} else {
								
								if ( $app->DbNumRows ( $res ) == 0 ) {
									// produto não cadastrado anteriormente
									$array_erro [$indice] = "Produto " . $dados_produtos ['CodigoProduto'] . " não cadastrado anteriormente";
									echo "Pedido: {$dados_pedidos['NumeroPedido']} - Produto " . $dados_produtos ['CodigoProduto'] . " não cadastrado anteriormente" . PHP_EOL;
									$array_incluir_itens_nota = '';
								
								} else {
									$row = $app->DbFetchObject ( $res );
									$array_incluir_itens_nota ['prod_id'] = $row->prod_id;
								}
								
								if ( $array_incluir_itens_nota ) {
									// incluir ítem na nota 
									$array_incluir_itens_nota ['note_id'] = $note_id;
									
									$array_incluir_itens_nota ['notit_qtd'] = $dados_produtos ['QuantidadeFiscal'];
									
									// se o valor estiver vazio, utilizar o valor do produto cadastrado
									if ( empty ( $dados_produtos ['ValorProduto'] ) ) {
										$sql = "SELECT prod_valor FROM produtos WHERE prod_id=" . $array_incluir_itens_nota ['prod_id'];
										$res = $app->DbExecute ( $sql );
										$row = $app->DbFetchObject ( $res );
										$array_incluir_itens_nota ['notit_valor'] = $row->prod_valor;
									} else {
										$array_incluir_itens_nota ['notit_valor'] = $dados_produtos ['ValorProduto'];
									}
									// verificar se o ítem já existe na nota
									$sql = "SELECT notit_qtd FROM notas_entrada_itens WHERE note_id={$note_id} AND prod_id=" . $array_incluir_itens_nota ['prod_id'];
									$res = $app->DbExecute ( $sql );
									if ( $app->DbNumRows ( $res ) > 0 ) {
										while ( $row = $app->DbFetchObject ( $res ) ) {
											$array_incluir_itens_nota ['notit_qtd'] += $row->notit_qtd;
										}
										$row_prod = $app->gera_update ( $array_incluir_itens_nota, "notas_entrada_itens", "note_id=" . $note_id . " AND prod_id=" . $array_incluir_itens_nota ['prod_id'] );
									} else {
										$row_prod = $app->gera_insert ( $array_incluir_itens_nota, "notas_entrada_itens" );
									}
								
								}
							
							}
						}
						if ( count ( $array_erro ) > 0 ) {
							$app->DbTransactionRollback ();
						} else {
							$app->DbTransactionCommit ();
							$pedidos_ok ++;
							echo "Pedido {$dados_pedidos['NumeroPedido']} inserido" . PHP_EOL;
							// Inseriu ok!
							try {
								//enviar protocolo de confirmação do pedido 
								$this->_kpl->confirmarRecebimentoNotaFiscalEntrada ( $dados_pedidos ['ProtocoloNotaFiscal'] );
								echo "Protocolo de NF de entrada {$dados_pedidos ['NumeroNF']} {$dados_pedidos['ProtocoloNotaFiscal']} enviado" . PHP_EOL;
							} catch ( Exception $e ) {
								echo "Erro ao confirmar o protocolo da nota {$dados_pedidos ['NumeroNF']} - Protocolo: {$dados_pedidos ['ProtocoloNotaFiscal']}" . PHP_EOL;
								throw new Exception ( $e->getMessage () );
							}
						
						}
					}
				
				}
			
			}
			
			// tratar movimento
			if ( $pedidos_ok > 0 ) {
				$array_dados = array ();
				$array_dados ['climov_horafinal'] = date ( 'H:i:s' );
				$res = $app->gera_update ( $array_dados, 'movimentos', "climov_id=" . $this->_climov_id );
				if ( ! $res ) {
					// erro ao tentar realizar update
					$array_erro [$indice] = "Erro ao realizar update da movimentação";
					$erros_pedidos ++;
				}
			} else {
				// apagar movimentação vazia
				$sql = "DELETE FROM movimentos WHERE climov_id=" . $this->_climov_id;
				if ( ! $app->DbExecute ( $sql ) ) {
					return $array_retorno;
				}
			}
			
			if(is_array($array_erro)){				
				$array_retorno = $array_erro;
			}
			
			return $array_retorno;
		}
	
	}

}
