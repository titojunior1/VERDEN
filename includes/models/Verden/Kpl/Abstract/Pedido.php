<?php

/**
 * 
 * Classe abstrata para processamento de pedidos de venda (saída) via webservice do ERP KPL - Ábacos 
 * 
 * @author    Tito Junior <moacir.tito@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     02/07/2014
 * 
 */

abstract class Model_Wms_Kpl_Abstract_Pedido extends Model_Wms_Kpl_KplWebService {
	
	/**
	 * Caracteres especiais
	 */
	protected $_caracteres_especiais = array ( "\"", "'", "\\", "`" );
	
	/**
	 * Id do cliente.
	 *
	 * @var int
	 */
	protected $_cli_id;
	
	/**
	 * Id do warehouse.
	 *
	 * @var int
	 */
	protected $_empwh_id;
	
	/**
	 * Kpl.
	 *
	 * @var int
	 */
	protected $_kpl;

	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 */
	public function __construct ( $cli_id, $empwh_id) {

		$this->_cli_id = $cli_id;
		$this->_empwh_id = $empwh_id;
		
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
	
	public function getValorMercadorias( $not_pedido ) {
		
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
	
	public function capturaDadosNf ( $request ) {
		
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $this->_cli_id );
		}
		
		$db = Db_Factory::getDbWms ();
		$i = 0;
		$j = 0;
		if ( ! is_array ( $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] [0] ) ) {
			$array_notas [$j] ['ProtocoloNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['ProtocoloNotaFiscal'];
			$array_notas [$j] ['NumeroPedido'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroPedido'];
			$array_notas [$j] ['NumeroNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroNotaFiscal'];
			$array_notas [$j] ['DataEmissao'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['DataEmissao'];
			$array_notas [$j] ['SerieNotaFiscal'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['SerieNotaFiscal'];
			$array_notas [$j] ['NFeChave'] = $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['ChaveNfe'];
			
				$valor_mercadorias = $this->getValorMercadorias($request ["DadosNotasFiscaisSaidaDisponiveisWMS"] ['NumeroPedido']);
			
			$array_notas [$j] ['ValorMercadorias'] = $valor_mercadorias['produtos'];
			$array_notas [$j] ['ValorTotalMercadorias'] = $valor_mercadorias['total'];
		
		} else {
			foreach ( $request ["DadosNotasFiscaisSaidaDisponiveisWMS"] as $i => $d ) {
				$array_notas [$i] ['ProtocoloNotaFiscal'] = $d ['ProtocoloNotaFiscal'];
				$array_notas [$i] ['NumeroPedido'] = $d ['NumeroPedido'];
				$array_notas [$i] ['NumeroNotaFiscal'] = $d ['NumeroNotaFiscal'];
				$array_notas [$i] ['DataEmissao'] = $d ['DataEmissao'];
				$array_notas [$i] ['SerieNotaFiscal'] = $d ['SerieNotaFiscal'];
				$array_notas [$i] ['NFeChave'] = $d ['ChaveNfe'];	
				
					$valor_mercadorias = $this->getValorMercadorias($d ['NumeroPedido']);
				
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
				$num_roes = $db->NumRows ( $res_nota );
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
					throw new Exception ( $e->getMessage () );
				}
			}
		}
	
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
				echo "Pedido não existe no WMS".PHP_EOL;
				continue;
			}
			
			try{			
				$importou = $this->_importarNotaFiscalSaidaNfe($nota,$not_id);
				$gerou_pdf = $this->_geraPdfNotaFiscalSaidaXml($nota,$not_id);
				if( $importou && $gerou_pdf){
					$this->_kpl->ConfirmarRecebimentoNotaFiscalSaida($nota['ProtocoloNotaFiscal']);
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
					// status 3 - Em processo de Embarque
					$model_saida_pedido->setStatus ( 3 );
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
	 * Captura Código da Operação
	 */
	protected  function _capturaCodigoOperacao( $descricao ){
		$db = Db_Factory::getDbWms();
		$sql = "SELECT notoper_codigo FROM notas_saida_operacoes WHERE notoper_descricao = '{$descricao}' AND cli_id = {$this->_cli_id}";
		$res = $db->Execute($sql);
		if(!$res){
			return false;
		}
		if($db->NumRows($res) ==0){
			return false;
		}else{
			return 'VENDA';
		}
	}
	
	/**
	 * Remove os acentos, outros caracteres especiais e espaços extras de uma string.
	 *
	 * @param string $sTexto Texto a ser tratado
	 * @param int[opcional] $iTamanho Tamanho(caracteres) da string a ser retornada
	 * @return string Texto sem acentos, caracteres especiais e espaços duplicados
	 */
	protected function _removeAcentos ( $sTexto, $iTamanho = 0 ) {
	
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
