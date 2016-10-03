<?php

/**
 * 
 * Classe para processar o cadastro de pedidos de venda (saída) via webservice do ERP KPL - Ábacos 
 * 
 * @author Tito Junior 
 * 
 */

final class Model_Verden_Kpl_Pedido extends Model_Verden_Kpl_KplWebService {
	
	/**
	 * Caracteres especiais
	 */
	private $_caracteres_especiais = array ( "\"", "'", "\\", "`" );
	
	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 */
	function __construct () {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService ();
		}			
	}

	/**
	 * 
	 * retorna erro utilizando erros_kpl.php.
	 * @param int $codigo						// código do erro
	 * @param string ou array $parametro		// string ou array do parametro do erro, caso seja pertinente 
	 * @param string $exception					// mensagem de exceção
	 */
	/*	function retorna_erro($codigo, $parametro = NULL, $exception = NULL) {
		$temp = new erros_kpl ( $codigo, $parametro, $exception );
		return $temp->retorna_erro ( $codigo, $parametro, $exception );
	}
	*/
	
	function getValorMercadorias( $not_pedido ) {
		
		$db = Db_Factory::getDbWms ();
		
		$where_cli  = !empty($this->_cli_id) ? " AND c.cli_id=".$this->_cli_id : '';
		$where_wh  = !empty($this->_empwh_id) ? " AND ew.empwh_id=".$this->_empwh_id : '';
		
		$sql = "SELECT 
					ns.not_frete 
				FROM 
					notas_saida ns 
				INNER JOIN movimentos m ON (m.climov_id = ns.climov_id)
				WHERE 
					m.cli_id = {$this->_cli_id}
				AND
					ns.not_pedido = '".$not_pedido."'";
		$res = $db->Execute ( $sql );		
		if ( ! $res ) {
			return false;
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			return false;
		}
		$row = $db->FetchAssoc ( $res );
		$frete = $row['not_frete'];
		
		$sql = "
			SELECT (nsi.notit_qtd*nsi.notit_valor) as itens_valor
			FROM notas_saida_itens nsi
			INNER JOIN notas_saida ns ON (ns.not_id = nsi.not_id)
			INNER JOIN movimentos m ON (m.climov_id = ns.climov_id)
			INNER JOIN clientes c ON (c.cli_id = m.cli_id)
			INNER JOIN empresa_warehouse ew ON (ew.empwh_id=ns.empwh_id)
			INNER JOIN produtos p ON (p.prod_id = nsi.prod_id)			
			WHERE ns.not_pedido='".$not_pedido."' 
			AND ns.not_id=nsi.not_id
			AND nsi.notit_status=2
			AND m.climov_tipo = 'S' " . $where_cli . $where_wh."
			AND ns.not_status!=6
			GROUP BY p.prod_id";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			//throw new RuntimeException ( 'Erro ao consultar pedido' );
			return false;		
		}
		$row = $db->FetchAssoc ( $res );
		$total = 0;
		while ( $row ) {
			$total += $row['itens_valor'];
			$row = $db->FetchAssoc ( $res );
		}
		$valor_mercadorias['produtos'] = $total;
		$valor_mercadorias['total'] = $total + $frete;
		return $valor_mercadorias;
	}
	
	function capturaDadosNf ( $request ) {

		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $this->_cli_id );
		}
		
		$db = Db_Factory::getDbWms ();
		$i = 0;
		$j = 0;
		if ( ! is_array ( $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] [0] ) ) {
			$array_notas [$j] ['ProtocoloNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['ProtocoloNotaFiscal'];
			$array_notas [$j] ['NumeroPedido'] = $this->_cli_id!=66? $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['CodigoPedidoAbacos']: $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroPedido'];
			$array_notas [$j] ['NumeroNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroNotaFiscal'];
			$array_notas [$j] ['DataEmissao'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['DataEmissao'];
			$array_notas [$j] ['SerieNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['SerieNotaFiscal'];
			$array_notas [$j] ['NFeChave'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['ChaveNfe'];
			
			if($this->_cli_id!=66){
				$valor_mercadorias = $this->getValorMercadorias($request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['CodigoPedidoAbacos']);
			}else{
				$valor_mercadorias = $this->getValorMercadorias($request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroPedido']);
			}
			$array_notas [$j] ['ValorMercadorias'] = $valor_mercadorias['produtos'];
			$array_notas [$j] ['ValorTotalMercadorias'] = $valor_mercadorias['total'];
		
		} else {
			foreach ( $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] as $i => $d ) {
				$array_notas [$i] ['ProtocoloNotaFiscal'] = $d ['ProtocoloNotaFiscal'];
				$array_notas [$i] ['NumeroPedido'] = $this->_cli_id!=66? $d ['CodigoPedidoAbacos']: $d ['NumeroPedido'];
				$array_notas [$i] ['NumeroNotaFiscal'] = $d ['NumeroNotaFiscal'];
				$array_notas [$i] ['DataEmissao'] = $d ['DataEmissao'];
				$array_notas [$i] ['SerieNotaFiscal'] = $d ['SerieNotaFiscal'];
				$array_notas [$i] ['NFeChave'] = $d ['ChaveNfe'];	
				
				if($this->_cli_id!=66){
					$valor_mercadorias = $this->getValorMercadorias($d ['CodigoPedidoAbacos']);
				}else{
					$valor_mercadorias = $this->getValorMercadorias($d ['NumeroPedido']);
				}
				$array_notas [$i] ['ValorMercadorias'] = $valor_mercadorias['produtos'];
				$array_notas [$i] ['ValorTotalMercadorias'] = $valor_mercadorias['total'];
			}
		}
		
		if ( count ( $array_notas ) > 0 ) {
			foreach ( $array_notas as $indice => $dados_nf ) {							
					
				//selecionar o not_id do pedido
				$sql = "SELECT ns.not_id as not_id FROM notas_saida ns 
					INNER JOIN movimentos m USING(climov_id) WHERE ns.not_pedido='{$dados_nf['NumeroPedido']}' AND m.cli_id={$this->_cli_id} AND not_status!=6
				";
				$res = $db->Execute ( $sql );
				if ( ! $res ) {
					throw new RuntimeException ( 'Erro ao consultar pedido' );
				
				}
				
				if ( $db->NumRows ( $res ) == 0 ) {
					 try {
        	            $this->_kpl->ConfirmarRecebimentoNotaFiscalSaida ( $dados_nf ['ProtocoloNotaFiscal'] );
						echo "Protocolo da NF do pedido {$dados_nf['NumeroPedido']}: {$dados_nf['ProtocoloNotaFiscal']} enviado - Pedido nao esta no WMS" . PHP_EOL;
                        	        } catch ( Exception $e ) {
						echo "Erro envio do protoloco da NF do pedido {$dados_nf['NumeroPedido']}: {$dados_nf['ProtocoloNotaFiscal']} - Pedido nao esta no WMS" . PHP_EOL;
                	                }
					continue;
				}
				$row = $db->FetchAssoc ( $res );
				
				$not_id = $row ['not_id'];
				
				
				//atualizar número da nota
				$sql = "UPDATE notas_saida SET not_numero ='{$dados_nf['NumeroNotaFiscal']}' WHERE not_id={$not_id}";
				$res = $db->Execute ( $sql );
				if ( ! $res ) {
					throw new RuntimeException ( "Erro ao atualizar número da nota - {$dados_nf['NumeroNotaFiscal']} " ) . PHP_EOL;
				}
				
				$data = explode(' ', $dados_nf ['DataEmissao']);
				$data = explode('/', $data[0]);
				$data_emissao = mktime(0,0,0,$data[1],$data[0],$data[2]);
				$data_emissao = date('Y-m-d',$data_emissao);
				
				$serie = trim ( $dados_nf ['SerieNotaFiscal'] );
				
				//verifica se já existe com a chave
				$sql_nota = "SELECT not_id, notnfe_chave FROM notas_saida_nfe WHERE not_id={$not_id} ";
				$res_nota = $db->Execute ( $sql_nota );
				if ( $db->NumRows ( $res_nota ) == 0 ) {
					
					$sql = "INSERT into notas_saida_nfe (not_id,notnfe_chave,notnfe_numero,notnfe_serie,notnfe_valor_produtos,notnfe_valor,notnfe_data_emissao)
					VALUES($not_id,'{$dados_nf['NFeChave']}',{$dados_nf['NumeroNotaFiscal']},{$serie},{$dados_nf['ValorMercadorias']},{$dados_nf['ValorTotalMercadorias']},'{$data_emissao}')";
					
					$res = $db->Execute ( $sql );
					if ( ! $res ) {
						throw new RuntimeException ( 'Erro ao inserir NF' );
					}
				}
				
				
				//inserir dados da NF
				try {
					$this->_kpl->ConfirmarRecebimentoNotaFiscalSaida ( $dados_nf ['ProtocoloNotaFiscal'] );
					echo "Protocolo da NF do pedido {$dados_nf['NumeroPedido']}: {$dados_nf['ProtocoloNotaFiscal']} enviado" . PHP_EOL;
				} catch ( Exception $e ) {
					echo $e->getMessage().PHP_EOL;	
				}
			}
		}
	
	}

	/**
	 * 
	 * Processa o array de dados dos pedidos do WebService
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaArquivoSaidaWebservice ( $request ) {

		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $this->_cli_id );
		}
		
		global $app;
		
		// cria instância do banco de dados
		$db = Db_Factory::getDbWms ();
		$dp = array ();
		// array para retorno dos dados ao webservice
		$array_retorno = array ();
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		
		// pedido de saída
		$erro = null;
		
		// array de controle (para criação dos objetos de pedidos) onde o valor será: pedido;nota_fiscal
		$array_pedidos = array ();
		
		// criar movimento - inserir os dados na tabela movimentos
		$sql = "INSERT INTO movimentos (cli_id,climov_data,climov_horainicio,climov_upload,climov_tipo) VALUES({$this->_cli_id},'" . date ( 'Y-m-d' ) . "','" . date ( 'H:i:s' ) . "',1,'S')";
		if ( ! $db->Execute ( $sql ) ) {
			throw new RuntimeException ( 'Erro ao criar movimento' );
		}
		
		//pega o último id inserido
		$this->_climov_id = $db->LastInsertId ();
		
		if ( ! is_array ( $request ['Rows'] ['DadosPedidosDisponiveisWeb'] [0] ) ) {
			$pedido_mestre [0] = $request ['Rows'] ['DadosPedidosDisponiveisWeb'];
		
		} else {
			$pedido_mestre = $request ['Rows'] ['DadosPedidosDisponiveisWeb'];
		}
		
		foreach ( $pedido_mestre as $i => $d ) {
			
			try {
				// pedido de saída
				$ns = new NotasSaida ( $this->_cli_id, $this->_empwh_id );
				
				// campos 1 a 13
				$d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'] = $this->_cli_id != 66 ?  $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['CodigoPedido']: $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'];
	
				$ns->__set ( 'not_pedido', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'] );
				$data = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DataVenda'], 0, 8 );
				$d_ano = substr ( $data, 4, 4 );
				$d_mes = substr ( $data, 2, 2 );
				$d_dia = substr ( $data, 0, 2 );
				$data_sql = "{$d_ano}-{$d_mes}-{$d_dia}";
				$ns->__set ( 'not_pedido_data', $data_sql );
				
				$not_natureza = $this->_capturaCodigoOperacao($d ['Comercializacao']);
				$ns->__set ( 'not_natureza', $not_natureza );

				
				$ns->__set ( 'not_cat_pessoa', 'NORMAL' );
				$not_isencao_icms = 0;
				if($this->_cli_id==75){
					$not_isencao_icms =1;
					$ns->__set('not_isencao_icms', $not_isencao_icms);
				}
				
				// campos 14 a 30
				$ns->__set ( 'not_nome', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestNome'] );
				$array_dados_comprador ['CompNome'] = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestNome'], 0, 50 );
				$cpf_cnpj = '';
				//validação de dados para o CPF
				if ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestTipoPessoa'] == "F" ) {
					$cpf_cnpj = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ClienteCPFouCNPJ'], strlen ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ClienteCPFouCNPJ'] ) - 11 );
				} else {
					$cpf_cnpj = $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ClienteCPFouCNPJ'];
				}
				
				$ns->__set ( 'not_cpf_cnpj', $cpf_cnpj );
				$array_dados_comprador ['CompCpfCnpj'] = trim ( $cpf_cnpj );
				$ns->__set ( 'not_insc_est', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestInscricaoEstadual'] );
				$ns->__set ( 'not_endereco', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoLogradouro'] );
				$array_dados_comprador ['CompEnd'] = str_replace ( $this->_caracteres_especiais, "", $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoLogradouro'] );
				$ns->__set ( 'not_endereco_num', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoNumero'] );
				$array_dados_comprador ['CompEndNum'] = str_replace ( $this->_caracteres_especiais, "", trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoNumero'] ) );
				
				$ns->__set ( 'not_complemento', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoComplemento'] );
				$array_dados_comprador ['CompCompl'] = str_replace ( $this->_caracteres_especiais, "", trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEnderecoComplemento'] ) );
				
				$ns->__set ( 'not_bairro', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestBairro'] );
				$array_dados_comprador ['CompBairro'] = substr ( str_replace ( $this->_caracteres_especiais, "", $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestBairro'] ), 0, 40 );
				$ns->__set ( 'not_cidade', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestMunicipio'] );
				$array_dados_comprador ['CompCidade'] = str_replace ( $this->_caracteres_especiais, "", $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestMunicipio'] );
				$ns->__set ( 'not_estado', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEstado'] );
				$array_dados_comprador ['CompEstado'] = str_replace ( $this->_caracteres_especiais, "", $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestEstado'] );
				$cep = preg_replace ( '/[^0-9]/i', '', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestCep'] );
				if ( strlen ( $cep ) == 7 ) {
					$cep = '0' . $cep;
				}
				$ns->__set ( 'not_cep', $cep );
				$array_dados_comprador ['CompCep'] = str_replace ( $this->_caracteres_especiais, "", $cep );
//				$ns->__set ( 'not_email', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'] );
//				$array_dados_comprador ['CompEmail'] = $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'];
				$ddd = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestTelefone'], 1, 2 );
				$telefone = trim ( substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestTelefone'], 4 ) );
				$telefone = str_replace ( '-', '', $telefone );
				$ns->__set ( 'not_ddd', $ddd );
				$array_dados_comprador ['CompDdd'] = str_replace ( $this->_caracteres_especiais, "", $ddd );
				$ns->__set ( 'not_telefone', $telefone );
				$array_dados_comprador ['CompTelefone'] = str_replace ( $this->_caracteres_especiais, "", $telefone );
				
				$ns->__set ( 'not_frete', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ValorFrete'] );
				
				// 31 - transportadora
				$trans_id_cli = $this->_cli_id != 66 ? trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ServicoEntrega'] ): trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['Transportadora'] );							
				$trans_id_cli = $this->_removeAcentos($trans_id_cli);
				
				$sqlt = "SELECT trans_id,trans_nome FROM transportadora WHERE cli_id={$this->_cli_id} AND trans_id_cli='{$trans_id_cli}' AND trans_status = 1";
				$rest = $db->Execute ( $sqlt );
				if ( ! $rest ) {
					throw new RuntimeException ( 'Erro ao consultar transportadora' );
				}
				if ( $db->NumRows($rest) == 0 ) {
					throw new RuntimeException ( 'Transportadora não cadastrada' );
				}
				$rowt = $db->FetchAssoc ( $rest );
				
				$trans_id = $rowt ['trans_id'];
				$trans_nome = strtolower($rowt ['trans_nome']);
				$ns->__set ( 'trans_id', $trans_id );
				
				if( $this->_cli_id == 75 && preg_match('/correios/', $trans_nome) && (strtolower($trans_id_cli)=='normal' || strtolower($trans_id_cli)=='registrada') ){
					echo "Pedido de figurinhas - {$d['ProtocoloPedido']}".PHP_EOL;	

					$this->_kpl->confirmarPedidosDisponiveis ( $d['ProtocoloPedido'] );
                    
					echo "Protocolo do pedido {$d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido']}: {$d['ProtocoloPedido']} enviado" . PHP_EOL;
					continue;
				}
				
				//verificar qual é o subcanal para impressão de embalagem específica
				

				$sub_canal = trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['SubCanal'] );
				//verificar na tabela de lojas qual é o ID correspondente. 
				$loja_id = 0;
				if( !empty($sub_canal) ){
					$sql = "SELECT loja_id FROM lojas WHERE cli_id={$this->_cli_id} AND loja_nome='{$sub_canal}'";
				}else{
					$sql = "SELECT loja_id FROM lojas WHERE cli_id={$this->_cli_id} AND loja_cod=1";
				}
				$res = $db->Execute ( $sql );
				
				if ( $db->NumRows ( $res ) == 0 ) {
					if($this->_cli_id==75){
						$loja_id = 14;
					}elseif($this->_cli_id==77){
						$loja_id = 16;
					}else{
						$loja_id = 3;
					}
				} else {
					
					$row = $db->FetchAssoc ( $res );
					
					$loja_id = $row ['loja_id'];
				}
				
				$ns->__set ( 'loja_id', $loja_id );
				
				$ns->array_dados_comprador = $array_dados_comprador;
				
				// 43 a 50 - NFe
				

				unset ( $dp );
				
				// 51 a 57 - produtos
				if ( is_array ( $d ['PedidoItens'] ['Rows'] ) ) {
					
					if ( ! is_array ( $d ['PedidoItens'] ['Rows'] ['DadosPedidoItensWeb'] [0] ) ) {
						foreach ( $d ['PedidoItens'] ['Rows'] as $j => $dp ) {
							// Verificar se o item esta marcado para presente e adaptar para importação no WMS							
							if ($dp['EmbalagemPresente'] == "S"){
								$dp['EmbalagemPresente'] = array();
								$dp['EmbalagemPresente'][0]['Tipo'] = "Embalagem_Presente"; 								
							}else{
								$dp['EmbalagemPresente'] = NULL;
							}
							
							$ns->AdicionaProduto ( $dp ['CodigoProduto'], $dp ['CodigoProdutoAbacos'], $dp ['Quantidade'], $dp ['PrecoUnitarioLiquido'], 'E', NULL, NULL, NULL, NULL, NULL, NULL, $dp ['EmbalagemPresente'], NULL );
						}
					} else {
						foreach ( $d ['PedidoItens'] ['Rows'] ['DadosPedidoItensWeb'] as $j => $dp ) {
							// Verificar se o item esta marcado para presente e adaptar para importação no WMS							
							if ($dp['EmbalagemPresente'] == "S"){
								$dp['EmbalagemPresente'] = array();
								$dp['EmbalagemPresente'][0]['Tipo'] = "Embalagem_Presente"; 								
							}else{
								$dp['EmbalagemPresente'] = NULL;
							}
							
							$ns->AdicionaProduto ( $dp ['CodigoProduto'], $dp ['CodigoProdutoAbacos'], $dp ['Quantidade'], $dp ['PrecoUnitarioLiquido'], 'E', NULL, NULL, NULL, NULL, NULL, NULL, $dp ['EmbalagemPresente'], NULL );
						
						}
					}
				}
				$array_protocolo [$d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido']] = $d ['ProtocoloPedido'];
				$this->array_notas_saida ['nota'] [$i] = $ns;
			
			} catch ( Exception $e ) {
				$this->array_notas_saida_erros ['nota'] [$i] = $ns;
				$this->array_notas_saida_erros ['erro'] [$i] = "Pedido de Saída: {$ns->__get( 'not_pedido' )} - " . $e->getMessage ();
				echo "Pedido de Saída: {$ns->__get( 'not_pedido' )} - " . $e->getMessage () . PHP_EOL;				
			}
			
		}
		
		// gravar os dados (a validação ocorre antes)
		$array_pedidos_ok = array ();
		$array_pedidos_do = array ();
		$array_pedidos_erro = array ();
		
		if ( is_array ( $this->array_notas_saida ['nota'] ) ) {
			foreach ( $this->array_notas_saida ['nota'] as $key => $ns ) {
				$flag_erro = false;
				if ( is_array ( $this->array_notas_saida_erros ['nota'] ) ) {
					foreach ( $this->array_notas_saida_erros ['nota'] as $key2 => $ns2 ) {
						if ( $ns->not_numero == $ns2->not_numero && $ns->not_pedido == $ns2->not_pedido ) 
							$flag_erro = true;
					}
				}
				if ( ! $flag_erro ) $array_pedidos_do [$key] = $ns;
			}
		}
		if ( is_array ( $array_pedidos_do ) ) {
			foreach ( $array_pedidos_do as $key => $ns ) {
				$numero_pedido = $ns->__get ( 'not_pedido' );
				$protocoloPedido = $array_protocolo [$numero_pedido];
				try {
					$ns->GravarNovo ( $this->_climov_id );
					
					//envio de protocolo
					$this->_kpl->ConfirmarRecebimentoSepararPedido ( $protocoloPedido );
					echo "Protocolo do pedido {$numero_pedido}: {$protocoloPedido} enviado" . PHP_EOL;
					
					$array_pedidos_ok [] = array ( 'not_pedido' => $ns->__get ( 'not_pedido' ), 'not_numero' => $ns->__get ( 'not_numero' ), 'not_reenvio' => $ns->__get ( 'not_reenvio' ) );
				
		// Inseriu ok!
				} catch ( Exception $e ) {
					
					echo 'Pedido: ' . $numero_pedido . ' - ' . $e->getMessage () . ' - ' . $protocoloPedido . PHP_EOL;
					if ( $e->getMessage () == "Pedido/Nota Fiscal duplicados" ) {
						$this->_kpl->ConfirmarRecebimentoSepararPedido ( $protocoloPedido );
						echo "Protocolo do pedido {$numero_pedido}: {$protocoloPedido} enviado" . PHP_EOL;
					}
				
		// Não conseguiu realizar Insert
				//	$array_pedidos_erro [$key] = $this->retorna_erro ( 300014, "" );
				}
			}
		}
		
		// erros notas saída (validação campos)
		if ( is_array ( $this->array_notas_saida_erros ['nota'] ) ) {
			foreach ( $this->array_notas_saida_erros ['nota'] as $key => $ns )
				$array_pedidos_erro [$key] = $this->array_notas_saida_erros ['erro'] [$key];
		}
		
		if ( count ( $array_pedidos_ok ) ) {
			
			// verificar duplicidade por transmissões simultâneas
			foreach ( $array_pedidos_ok as $pedido_checar ) {
				// caso não seja reenvio
				if ( $pedido_checar ['not_reenvio'] == 0 ) {
					
					$sql_add_nota = $sql_add_pedido = '';
					if ( empty ( $pedido_checar ['not_numero'] ) ) {
						$sql_add_nota = ' OR ns.not_numero IS NULL ';
					}
					if ( empty ( $pedido_checar ['not_pedido'] ) ) {
						$sql_add_pedido = ' OR ns.not_pedido IS NULL';
					}
					
					$status_gravar = '6';
					$sql = "SELECT ns.not_id, ns.climov_id FROM notas_saida ns
							INNER JOIN movimentos m ON (m.climov_id = ns.climov_id) 
							WHERE ns.climov_id < " . $this->_climov_id . " AND m.cli_id = {$this->_cli_id}
							AND (ns.not_pedido = '" . $db->EscapeString ( $pedido_checar ['not_pedido'] ) . "' {$sql_add_pedido})
			                AND (ns.not_numero = '" . $db->EscapeString ( $pedido_checar ['not_numero'] ) . "' {$sql_add_nota})
			                AND ns.not_status = '0'";
					$res = $db->Execute ( $sql );
					$row_dup = $db->FetchAssoc ( $res );
					while ( $row_dup ) {
						// processa o cancelamento do pedido
						$sql = "UPDATE notas_saida SET not_status = '{$status_gravar}' WHERE not_id = {$row_dup['not_id']} AND climov_id = {$row_dup['climov_id']}";
						$res2 = $db->Execute ( $sql );
						
						// grava o status no pedido cancelado
						$sql = "SELECT stat_id_export, stat_desc FROM status_export WHERE stat_tabela='notas_saida' AND emp_id=1 AND stat_id={$status_gravar}";
						$res2 = $db->Execute ( $sql );
						$row_status = $db->FetchObject ( $res2 );
						$array_dados = array ();
						$array_dados ['not_id'] = $row_dup ['not_id'];
						$array_dados ['notstat_data'] = date ( "Y-m-d" );
						$array_dados ['notstat_hora'] = date ( "H:i:s" );
						$array_dados ['notstat_code'] = $row_status->stat_id_export;
						$array_dados ['notstat_desc'] = $row_status->stat_desc . " (Transmissão em Duplicidade)";
						$array_dados ['func_id'] = $app->func_id;
						$db->gera_insert ( $array_dados, 'notas_saida_status' );
						
						$row_dup = $db->FetchAssoc ( $res );
					}
				}
			}
			
			// atualizar movimento
			$array_dados = array ();
			$array_dados ['climov_horafinal'] = date ( 'H:i:s' );
			
			$res = $app->gera_update ( $array_dados, 'movimentos', "climov_id=" . $this->_climov_id );
		} else {
			// apagar movimentação vazia
			$sql = "DELETE FROM movimentos WHERE climov_id=" . $this->_climov_id;
			if ( ! $db->Execute ( $sql ) ) {
				return $array_retorno;
			}
		}
		if(is_array($array_pedidos_erro)){
			$array_retorno = $array_pedidos_erro;
		}
		
		return $array_retorno;
	}
	
	
	
	
	
	public function NotasFiscaisSaidaDisponiveisComPdf () {
	
		
		$db = Db_Factory::getDbWms ();
		
		// Captura todas notas
		$notas = $this->_capturarNotas();
		if(!is_array($notas)){
			echo 'Não há notas disponíveis para integração'.PHP_EOL;
			return false;
		}
		if ( ! is_array ( $notas['NotasFiscaisSaidaDisponiveisResult']['Rows']['DadosNotasFiscaisSaidaDisponiveisWMS'] [0] ) ) {
			$notas_faturamento [0] = $notas['NotasFiscaisSaidaDisponiveisResult']['Rows']['DadosNotasFiscaisSaidaDisponiveisWMS'];
		
		} else {
			$notas_faturamento = $notas['NotasFiscaisSaidaDisponiveisResult']['Rows']['DadosNotasFiscaisSaidaDisponiveisWMS'];
		}
		foreach($notas_faturamento as $nota){
			
			// verifica se existe
			$not_id = $this->_notaSaidaNfeExists($nota['CodigoPedidoAbacos'],$nota['NumeroNotaFiscal']);
			
			if( !$not_id ){
				$this->_kpl->ConfirmarRecebimentoNotaFiscalSaida($nota['ProtocoloNotaFiscal']);
				echo "Pedido {$nota['CodigoPedidoAbacos']} não existe no WMS".PHP_EOL;
				continue;
			}
			
			try{			
				$importou = $this->_importarNotaFiscalSaidaNfe($nota,$not_id);
				$gerou_pdf = $this->_geraPdfNotaFiscalSaidaXml($nota,$not_id);
				if( $importou && $gerou_pdf){
					$this->_kpl->ConfirmarRecebimentoNotaFiscalSaida($nota['ProtocoloNotaFiscal']);
					echo "Enviado protocolo do pedido {$nota['CodigoPedidoAbacos']} - Protocolo: {$nota['ProtocoloNotaFiscal']}".PHP_EOL;
				}
				if( !$gerou_pdf ){
					// gravar status de faturado e processo de embarque
					$model_saida_pedido = new Model_Wms_Saida_Pedido ( $not_id );					
					// status 15 - Erro ao Faturar
					$model_saida_pedido->setStatus ( 15 );
					$this->_faturar($not_id, 3);
				}
			}catch ( Exception $e){
				throw new Exception ( $e->getMessage () );
			}
			
		}
	}
	
	private function _importarNotaFiscalSaidaNfe ( $nota, $not_id ) {
	
		$db = Db_Factory::getDbWms ();
		
		$valor_mercadorias = $this->getValorMercadorias($nota['CodigoPedidoAbacos']);

		$nota['ValorMercadorias'] = $valor_mercadorias['produtos'];
		$nota['ValorTotalMercadorias'] = $valor_mercadorias['total'];
		
		$data = explode(' ', $nota['DataEmissao']);
		$data = explode('/', $data[0]);
		$data_emissao = mktime(0,0,0,$data[1],$data[0],$data[2]);
		$data_emissao = date('Y-m-d',$data_emissao);
		$serie = trim ( $nota['SerieNotaFiscal'] );
		
		//verifica se já existe com a chave

		$sql_nota = "SELECT not_id, notnfe_chave FROM notas_saida_nfe WHERE not_id={$not_id} ";
		$res_nota = $db->Execute ( $sql_nota );
		if ( $db->NumRows ( $res_nota ) == 0 ) {
									
			$sql = "INSERT into notas_saida_nfe (not_id,notnfe_chave,notnfe_numero,notnfe_serie,notnfe_valor_produtos,notnfe_valor,notnfe_data_emissao)
					VALUES($not_id,'{$nota['ChaveNfe']}',{$nota['NumeroNotaFiscal']},{$serie},{$nota['ValorMercadorias']},{$nota['ValorTotalMercadorias']},'{$data_emissao}')";
			$res = $db->Execute ( $sql );
			if ( ! $res ) {
				throw new RuntimeException ( 'Erro ao inserir NF' );
			}
		}
		return true;
	}
	
	/**
	 * Gera Pdf de Notas de Saída Disponíveis
	 */
	private function _geraPdfNotaFiscalSaidaXml( $nota, $not_id ) {

		try {
			// variáveis
			$codigo_nota_fiscal = $nota['CodigoNotaFiscal'];
			$data_emissao = $this->_getDiretorioDataDeEmissao($nota['DataEmissao']);
				
			// obtem xml
			$request = array(
					"ChaveIdentificacao" => $this->_kpl->getChaveIdentificacao(),
					"CodigoNotaFiscalAbacos" => $codigo_nota_fiscal
			);
				
			$result = $this->_kpl->obterXmlNfe($request);
			if ( $result['ObterXmlNfeResult']['ResultadoOperacao']['Tipo'] != 'tdreSucesso' ) {
				throw new RuntimeException ( $result );
			}
				
			// gera pdf a partir do xml
			$xml = $result['ObterXmlNfeResult']['Rows']['DadosResultadoXMLNFE']['XmlNfe'];

			// procura pela tag ide no xml, que é essencial em printDANFE(),
			// se não existir significa que o status do pedido é cancelado e deve ser desconsiderado na importação
			if( preg_match('/ide/', $xml)){
				
				$xml = str_replace('\\','',$xml);
				$danfe = new DanfeNFePHP($xml);
				$diretorio = PATH_EDI_PDF . $data_emissao;
				if (! is_dir ( $diretorio )) {
					mkdir ( $diretorio, 0777 );
				}
				$diretorio .= '/';

				$id = $danfe->montaDANFE();
				$danfe->printDANFE($diretorio.'NFe'.$id.'.pdf','F');
				
				if($id){
					// faturamento
					$this->_enviarPedidoFaturamento($not_id);
					return true;
				}
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
		return false;
	}
	
	/**
	 * Verifcar se existe Nota Fiscal de Saída
	 */
	private function _notaSaidaNfeExists($pedido_id,$numero_nota,$throw=false){
	
		$db = Db_Factory::getDbWms ();
		$not_id = false;
	
		// verificar se o pedido possui uma nota fiscal
		$sql = "SELECT ns.not_id as not_id FROM notas_saida ns
			INNER JOIN movimentos m USING(climov_id)
			WHERE ns.not_pedido='{$pedido_id}'
			AND m.cli_id={$this->_cli_id} AND not_status!=6
		";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			return false;
		}
		if ( $db->NumRows ( $res ) == 0 ) {
			return false;
		}
		$row = $db->FetchAssoc ( $res );
		$not_id = $row ['not_id'];
		//atualizar número da nota
		$sql = "UPDATE notas_saida SET not_numero ='{$numero_nota}' WHERE not_id={$not_id}";
		$res = $db->Execute ( $sql );
		if ( ! $res ) {
			return false;
		}
		
		return $not_id;
	}
	
	/**
	 * Captura Notas Fiscais de Saída Disponíveis
	 */
	private function _capturarNotas(){
		try{
			
			if ( empty ( $this->_kpl ) ) {
				$this->_kpl = new Model_Wms_Kpl_KplWebService ( $this->_cli_id );
			}
			$chaveIdentificacao = $this->_kpl->getChaveIdentificacao ();
			$notas_fiscais = $this->_kpl->NotasFiscaisSaidaDisponiveis ( $chaveIdentificacao );
				
			if (! is_array ( $notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] )) {
				throw new Exception ( 'Erro ao buscar Produtos' );
			}
				
			if ($notas_fiscais ['NotasFiscaisSaidaDisponiveisResult'] ['ResultadoOperacao'] ['Codigo'] == 200003) {
				return false;
			}
			return $notas_fiscais;
				
		} catch ( Exception $e ) {
			echo "- erros ao capturar notas fiscais do cliente {$this->_cli_id}: " . $e->getMessage () . PHP_EOL;
		}
	}
	
	/**
	 * Enviar pedido para faturamento
	 */
	private function _enviarPedidoFaturamento( $not_id ){
		if (! ctype_digit ( $not_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}	
		$db = Db_Factory::getDbWms ();
	
		try{
			if( $this->_aguardandoFaturamento($not_id) ){
				if( $this->_emFaturamento($not_id) ){
					// coloca em faturamento
					$this->_faturar($not_id, 2);
					$model_saida_pedido = new Model_Wms_Saida_Pedido ( $not_id );
					// status 14 - Faturado
					$model_saida_pedido->setStatus ( 14 );
					echo "Pedido {$not_id} faturado".PHP_EOL;
					// status 3 - Em processo de Embarque
					$model_saida_pedido->setStatus ( 3 );
					echo "Pedido {$not_id} em processo de embarque".PHP_EOL;
				}
			}
		} catch ( Exception $e ) {
			// status 15 no pedido (erro ao Faturar) e coloca um status 3 na notas_saida_faturamento
			echo "- erro ao faturar pedido {$not_id}: " . $e->getMessage () . PHP_EOL;
		}		
	}
	
	/**
	 * Verifica se está aguardadno faturamento
	 */
	private function _aguardandoFaturamento( $not_id ){
		if (! ctype_digit ( $not_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}
		$db = Db_Factory::getDbWms();
		$sql = "SELECT not_id FROM notas_saida WHERE not_id = {$not_id} AND not_status = 13";
		$res = $db->Execute($sql);
		if(!$res){
			throw new RuntimeException("O pedido ({$not_id}) não está aguardando faturamento");
		}
		if($db->NumRows($res) ==0){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * Verifica se foi colocado em faturamento
	 */
	private function _emFaturamento( $not_id ){
		if (! ctype_digit ( $not_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}
		$db = Db_Factory::getDbWms();
		$sql = "SELECT notfat_id,not_id FROM notas_saida_faturamento WHERE not_id = $not_id AND notfat_status=1";
		$res = $db->Execute($sql);
		if(!$res){
			throw new RuntimeException("O pedido ({$not_id}) não está em faturamento");
		}
		if($db->NumRows($res) ==0){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * Coloca em faturamento
	 */
	private function _faturar( $not_id, $status ){
		if (! ctype_digit ( $not_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}
		$db = Db_Factory::getDbWms();
		$sql_insert = "INSERT INTO notas_saida_faturamento(not_id, notfat_status) VALUES({$not_id},{$status})";
		if(!$db->Execute($sql_insert)){
			throw new RuntimeException("Erro ao enviar pedido para faturamento");
		}
	}
	
	/**
	 * Captura Código da Operação
	 */
	private function _capturaCodigoOperacao( $descricao ){
		return 'VENDA';
		$db = Db_Factory::getDbWms();
		$sql = "SELECT notoper_codigo FROM notas_saida_operacoes WHERE notoper_descricao = '{$descricao}' AND cli_id = {$this->_cli_id}";
		$res = $db->Execute($sql);
		if(!$res){
			return false;
		}
		if($db->NumRows($res) ==0){
			return false;
		}else{
			$row = $db->FetchAssoc ( $res );
			// Meu espelho 77
			if(($this->_cli_id == 75 || $this->_cli_id==77 )&& preg_match('/(Reenvio|reenvio|REENVIO)/', $row['notoper_codigo'])){
				return 'REENVIO';
			}else{
				return 'VENDA';
			}
		}
	}
	
	/**
	 * Retorna o diretório data de emissão
	 */
	private function _getDiretorioDataDeEmissao($data_emissao){
		$explode = explode(' ',$data_emissao);
		$explode = explode('/',$explode[0]);
		$ano = $explode[2];
		$mes = $explode[1] < 10 ? '0'.$explode[1]: $explode[1];
		return $ano.'-'.$mes;
	}
	
	/**
	 * Remove os acentos, outros caracteres especiais e espaços extras de uma string.
	 *
	 * @param string $sTexto Texto a ser tratado
	 * @param int[opcional] $iTamanho Tamanho(caracteres) da string a ser retornada
	 * @return string Texto sem acentos, caracteres especiais e espaços duplicados
	 */
	private function _removeAcentos ( $sTexto, $iTamanho = 0 ) {
	
		// Remove espaços no inicio e fim
		$sTexto = trim ( $sTexto );
	
		// Substituir os acentos e caracteres especiais baseado em ER
		$aPatterns = array ( '/[áàâãª]/', '/[ÁÀÂÃ]/', '/[éèê]/', '/[ÉÈÊ]/', '/[íì]/', '/[ÍÌ]/', '/[óòôõº]/', '/[ÓÒÔÕ]/', '/[úùû]/', '/[ÚÙÛ]/', '/[ç]/', '/[Ç]/', '/[^A-Za-z0-9 .,-_:;!@]/i' );
		$aSubstituir = array ( 'a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'c', 'C', '' );
		$sTexto = preg_replace ( $aPatterns, $aSubstituir, $sTexto );
	
		// Remove espaços extras
		$sTexto = preg_replace ( '/\s\s+/', ' ', $sTexto );
	
		// Limita a quantidade de caracteres de retorno
		if ( ! empty ( $iTamanho ) && is_int ( $iTamanho ) ) $sTexto = substr ( $sTexto, 0, $iTamanho );
	
		// Retorna o texto tratado
		return $sTexto;
	}
}
