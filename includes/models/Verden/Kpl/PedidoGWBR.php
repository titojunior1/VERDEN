<?php

/**
 *
 * Classe para processar o cadastro de pedidos de venda (saída) via webservice do ERP KPL - Ábacos
 *
 * @author    Tito Junior <moacir.tito@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     02/07/2014
 *
 */


final class Model_Wms_Kpl_PedidoGWBR extends Model_Wms_Kpl_Abstract_Pedido {

	/**
	 *
	 * Processa o array de dados dos pedidos do WebService
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaArquivoSaidaWebservice ( $request ) {

		$this->_kpl = new Model_Wms_Kpl_KplWebService($this->_cli_id);
		$app = new UserAccess();

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
				$d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'] = $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'];

				$ns->__set ( 'not_pedido', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['NumeroPedido'] );
				$data = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DataVenda'], 0, 8 );
				$d_ano = substr ( $data, 4, 4 );
				$d_mes = substr ( $data, 2, 2 );
				$d_dia = substr ( $data, 0, 2 );
				$data_sql = "{$d_ano}-{$d_mes}-{$d_dia}";
				$ns->__set ( 'not_pedido_data', $data_sql );

				$not_natureza = $this->_capturaCodigoOperacao(trim($d ['GrupoComercializacao']));
				$ns->__set ( 'not_natureza', $not_natureza );


				$ns->__set ( 'not_cat_pessoa', 'NORMAL' );
				$not_isencao_icms = 0;

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
				$d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'] = empty($d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'])? 'teste@teste.com.br': $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'];
				$ns->__set ( 'not_email', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'] );
				$array_dados_comprador ['CompEmail'] = $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['EMail'];
				$ddd = substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestTelefone'], 1, 2 );
				$telefone = trim ( substr ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['DestTelefone'], 4 ) );
				$telefone = str_replace ( '-', '', $telefone );
				$ns->__set ( 'not_ddd', $ddd );
				$array_dados_comprador ['CompDdd'] = str_replace ( $this->_caracteres_especiais, "", $ddd );
				$ns->__set ( 'not_telefone', $telefone );
				$array_dados_comprador ['CompTelefone'] = str_replace ( $this->_caracteres_especiais, "", $telefone );

				$ns->__set ( 'not_frete', $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['ValorFrete'] );

				// 31 - transportadora
				$trans_id_cli = trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['Transportadora'] );
				$trans_id_cli = $this->_removeAcentos($trans_id_cli);

				$sqlt = "SELECT trans_id,trans_nome FROM transportadora WHERE cli_id={$this->_cli_id} AND trans_id_cli='{$trans_id_cli}'";
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

				//verificar qual é o subcanal para impressão de embalagem específica

				$sub_canal = trim ( $d ['PedidoWeb'] ['Rows'] ['DadosPedidoWeb'] ['SubCanal'] );
				//verificar na tabela de lojas qual é o ID correspondente.

				if( !empty($sub_canal) ){
					$sql = "SELECT loja_id FROM lojas WHERE cli_id={$this->_cli_id} AND loja_nome='{$sub_canal}'";
				}else{
					$sql = "SELECT loja_id FROM lojas WHERE cli_id={$this->_cli_id} AND loja_cod=1";
				}
				$res = $db->Execute ( $sql );

				if ( $db->NumRows ( $res ) == 0 ) {
					$loja_id = 20;
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
}
