<?php

/**
 * 
 * Classe para gerenciar o webservice do Kpl
 * @author    Rômulo Z. C. Cunha <romulo.cunha@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     30/08/2012
 *
 */

class Model_Wms_Kpl_TrackingKpl {
	
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
	 * Variavel  de Objeto da Classe StubKpl.
	 *
	 * @var Model_Wms_kpl
	 */
	public $_client;
	
	public $_kpl;
	
	/**
	 * Construtor.
	 * @param int $cliente Id do Cliente.
	 * @param int  $armazem Armazem do Cliente.
	 * @param object $webservice objeto com conexão.
	 */
	public function __construct($cli_id, $empwh_id=null) {
		$cli_id = trim ( $cli_id );
		if (! ctype_digit ( $cli_id )) {
			throw new InvalidArgumentException ( 'ID do Cliente inválido' );
		}
// 		$empwh_id = trim ( $empwh_id );		// remove espaços em branco
// 		if (! ctype_digit ( $empwh_id )) {	// verifica por caracteres numéricos
// 			throw new InvalidArgumentException ( 'Warehouse do Cliente inválido' );
// 		}
		
		$this->_cli_id = $cli_id;
		//$this->_empwh_id = $empwh_id;
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
		}
	}
	
	/**
	 * Alimenta o tracking do Kpl.
	 * @param int $not_id id da nota
	 */
	private function _alimentaTracking($not_id,$dados_pedido=null,$qtd=1) {
		
		$not_id = trim ( $not_id );
		if (! ctype_digit ( $not_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}
		
		$db = Db_Factory::getDbWms ();
		
		// envia objetos para o Kpl
		try {
				
			// dados do pedido
			$model_pedido = new Model_Wms_Saida_Pedido ( $not_id );
			// verifica a transportadora
			$tipo_transportadora = $model_pedido->getTransTipo ();				
			// verifica codigo do pedido
			$CodigoPedido = $model_pedido->getPedido();				
			// verifica numero do objeto
			$pNroObjeto= $this->getNumeroObjeto($not_id, $CodigoPedido, $tipo_transportadora, $qtd);
				
			// envia rastreio
			$tdreSucesso = false;
			if( $model_pedido->getTransportadoraServico() != 'carta' ){
				foreach($pNroObjeto as $objeto){
					$request = array(
							"pChaveIdentificacao" => $this->_kpl->getChaveIdentificacao(),
							"pCodigoPedidoInterno" => $CodigoPedido,
							"pNroObjeto" =>$objeto['NumeroObjeto']
					);
						
					$result = $this->_kpl->EnviarRastreioObjeto($request);
					if($result['EnviarRastreioObjetoResult']['Tipo']=='tdreSucesso'){
						$tdreSucesso = true;
					}else{
						throw new RuntimeException ( $result['EnviarRastreioObjetoResult']['ExceptionMessage'] );
					}
				}
				if($tdreSucesso){
					return true;
				}else{
					throw new RuntimeException ( $result['EnviarRastreioObjetoResult']['ExceptionMessage'] );
				}
			}
			
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	
	}
	
	/**
	 * Retorna o número do Objeto
	 * @param int $not_id id da nota
	 * @param int $CodigoPedido código do pedido
	 * @param int $tipo_transportadora tipo da transportadora
	 */
	public function getNumeroObjeto( $not_id, $CodigoPedido, $tipo_transportadora, $qtd=1 ){
		try {
			switch ($tipo_transportadora){
				case 'correios':
					$correiosObjeto = new Model_Wms_Correios_Objeto();						
					$objetos = $correiosObjeto->getCodigoObjeto($not_id);
					$i = 0;
					foreach ($objetos as $objeto){
						$pNroObjeto[$i]['NumeroObjeto'] = $objeto;
						$i++;
					}
					break;
				case 'total':
					for($i=1; $i<=$qtd; $i++){
						if($i<10){
							$v = '00'.$i;
						}elseif($i>=10 && $i<100){
							$v = '0'.$i;
						}elseif($i>=100 && $i<=999){
							$v = $i;
						}
						$pNroObjeto[]['NumeroObjeto'] = $CodigoPedido.''.$v;
					}
					break;
				default:
					for($i=1; $i<=$qtd; $i++){
						if($i<10){
							$v = '00'.$i;
						}elseif($i>=10 && $i<100){
							$v = '0'.$i;
						}elseif($i>=100 && $i<=999){
							$v = $i;
						}
						$pNroObjeto[]['NumeroObjeto'] = $CodigoPedido.''.$v;					
					}
					break;
			}
			return $pNroObjeto;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}		
	}
	
	/**
	 * Muda Status de um Array de ID de pedidos
	 * @param int $order_id 
	 * @param String $status  
	 */
	private function _atualizaStatusPedido($order_id, $status) {
		if (! ctype_digit ( $order_id )) {
			throw new LogicException ( 'ID do pedido inválido' );
		}
		if (empty ( $status )) {
			$status = 'ETR'; // ETR = Entregue à Transportadora
		}
		
		try {
			// atualiza status do pedido
			$retorno_status = $this->_client->OrderChangeStatus ( $order_id, $status );
			if (! empty ( $retorno_status ['faultcode'] )) {
				// lança o erro como exception
				throw new RuntimeException ( $retorno_status ['faultstring'] ['!'] );
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * Monta URL de tracking baseado no layout da total express
	 * @param int $not_id id da nota
	 */
	private function _montaUrlTrackingTotal($not_id) {
		if (empty ( $not_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		
		// captura dados do pedido
		$model_pedido = new Model_Wms_Saida_Pedido ( $not_id );
		$dados_pedido = $model_pedido->getDadosPedido ( $not_id );
		
		$db = Db_Factory::getDbWms ();
		
		// captura reid do cliente
		$sql = "SELECT reid FROM clientes WHERE cli_id = {$this->_cli_id}";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( 'Erro ao buscar Reid do cliente' );
		}
		if ($db->NumRows ( $res ) == 0) {
			throw new DomainException ( 'Cliente não localizado' );
		}
		
		$row = $db->FetchAssoc ( $res );
		if (empty ( $row ['reid'] )) {
			throw new DomainException ( 'REID não preenchido' );
		}
		
		return $url_track = "http://tracking.totalexpress.com.br/poupup_track.php?reid={$row['reid']}&pedido={$dados_pedido ['not_pedido']}&nfiscal={$dados_pedido ['not_numero']}";
	}
	
	/**
	 * 
	 * Monta URL de tracking baseado no layout dos correios
	 * http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=002&P_COD_UNI=SI777946134BR
	 * @param int $not_id id da nota
	 */
	private function _montaUrlTrackingCorreio($not_id) {
		if (empty ( $not_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		
		$db = Db_Factory::getDbWms ();
		
		// busca código do objeto dos correios
		$sql = "SELECT notobj_objeto FROM notas_saida_objetos WHERE not_id = {$not_id}";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( 'Erro ao buscar Objetos de tracking' );
		}
		if ($db->NumRows ( $res ) == 0) {
			return false;
		}
		$row = $db->FetchAssoc ( $res );
		
		$array_url = array ();
		while ( $row ) {
			$array_url [] = "http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=002&P_COD_UNI={$row['notobj_objeto']}";
			$row = $db->FetchAssoc ( $res );
		}
		
		$url_track = implode ( ' | ', $array_url );
		
		return $url_track;
	}
	
	/**
	 * 
	 * Monta URL de tracking baseado no layout da JadLog.
	 * http://www.jadlog.com.br/tracking.jsp?cte=5635635
	 * @param string $pedido
	 */
	private function _montaUrlTrackingJadLog($pedido) {
		return 'http://www.jadlog.com.br/tracking.jsp?cte=' . $pedido;
	}
	
	/**
	 * 
	 * inicia o processo de alimentação de status de tracking no Kpl
	 * @param int $not_id
	 */
	public function efetuaTrackingPedido($not_id, $qtd=1) {
		
		$not_id = trim ( $not_id );
		if (! ctype_digit ( $not_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		
		try {
			
			// captura dados do pedido
			$model_pedido = new Model_Wms_Saida_Pedido ( $not_id );
			$dados_pedido = $model_pedido->getDadosPedido ( $not_id );
			
			// se for correio já alimentar o tracking
			$this->_alimentaTracking ( $not_id, $dados_pedido ['not_pedido'], $qtd );

			// altera status para embarcado
	//		$this->_atualizaStatusPedido ( $dados_pedido ['not_pedido'], 'ETR' ); // ETR = Entregue à Transportadora
		

		} catch ( Exception $e ) {
			throw new Exception ( "Pedido {$not_id}/{$dados_pedido ['not_pedido']}: " . $e->getMessage () );
		}
	}
	
	/**
	 *
	 * inicia o processo de marcar pedido despachado no Kpl
	 * @param int $not_id
	 */
public function atualizaPedidoDespachado($not_id) {
	
		$not_id = trim ( $not_id );
		if (! ctype_digit ( $not_id )) {
			throw new InvalidArgumentException ( 'ID do pedido inválido' );
		}
		
		$db = Db_Factory::getDbWms ();
	
		try {
				
			// captura dados do pedido
			$model_pedido = new Model_Wms_Saida_Pedido ( $not_id );
			$dados_pedido = $model_pedido->getDadosPedido ( $not_id );
			$CodigoPedido = $model_pedido->getPedido();
						
			$ListaDeNumerosDePedidos["DadosListaPedidos"][0]["CodigoPedido"] = $CodigoPedido;
			$request = array(
					"ChaveIdentificacao" => $this->_kpl->getChaveIdentificacao(),
					"ListaDeNumerosDePedidos" => $ListaDeNumerosDePedidos
			);
				
			$result = $this->_kpl->MarcarPedidosDespachados($request);
			if ( $result['MarcarPedidosDespachadosResult']['ResultadoOperacao']['Tipo'] != 'tdreSucesso' ) {
				// lança o erro como exception
			    $msgErro = $db->EscapeString($result['MarcarPedidosDespachadosResult']['ResultadoOperacao']['Descricao]']);
				throw new RuntimeException ( $msgErro );
			}			
	
		} catch ( Exception $e ) {
			throw new Exception ( "Pedido {$not_id}/{$dados_pedido ['not_pedido']}: " . $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * inicia o processo de alimentação de status de tracking no Kpl
	 * @param int $not_id
	 */
	public function efetuaTrackingEmbarque($emb_id) {
		
		$emb_id = trim ( $emb_id );
		if (! ctype_digit ( $emb_id )) {
			throw new InvalidArgumentException ( 'ID do embarque inválido' );
		}
		
		$db = Db_Factory::getDbWms ();
		
		// selecionar os pedidos do embarque
		$sql = "SELECT not_id FROM notas_saida WHERE emb_id = {$emb_id}";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( 'Erro ao buscar pedidos de saida do embarque' );
		}
		if ($db->NumRows ( $res ) == 0) {
			throw new DomainException ( 'Nenhum pedido encontrado para o embarque' );
		}
		$row = $db->FetchAssoc ( $res );
		$array_erros = NULL;
		while ( $row ) {
			try {
				$this->efetuaTrackingPedido ( $row ['not_id'] );
			} catch ( Exception $e ) {
				$array_erros [] = $e->getMessage ();
			}
			$row = $db->FetchAssoc ( $res );
		}
		
		if (! empty ( $array_erros )) {
			$str_erro = implode ( ' | ', $array_erros );
			throw new Exception ( $str_erro );
		}
	
	}
	
	/**
	 * Envia os links de tracking para o Kpl gerados a partir de uma determinada data.
	 * @param date $data_anterior
	 * @param time $hora_anterior
	 * @throws RuntimeException
	 * @throws Exception
	 */
	public function geraAtualizacaoTracking($data_anterior, $hora_anterior) {
		
		$db = Db_Factory::getDbWms ();
		
		echo "Buscando produtos embarcados a partir de {$data_anterior} {$hora_anterior}: ";
		
		// busca produtos filho ativos
		$sql = "SELECT DISTINCT nss.not_id, ns.not_pedido 
				FROM notas_saida_status nss
				INNER JOIN notas_saida ns USING(not_id)
				INNER JOIN movimentos m USING(climov_id)
				WHERE m.cli_id={$this->_cli_id} AND nss.notstat_data >= '{$data_anterior}' AND nss.notstat_hora >= '{$hora_anterior}' AND nss.notstat_code='1006'";
		$res = $db->Execute ( $sql );
		if (! $res) {
			throw new RuntimeException ( "Erro sistêmico ao buscar pedidos" );
		}
		
		echo '- total de itens: ';
		$qtd_itens = $db->NumRows ( $res );
		echo $qtd_itens . PHP_EOL;
		
		if ($qtd_itens == 0) {
			echo '- nenhum item a atualizar' . PHP_EOL;
			return;
		}
		
		echo $db->NumRows ( $res ) . '- pedidos encontrados ...' . PHP_EOL;
		
		$row = $db->FetchAssoc ( $res );
		$array_erros = array ();
		while ( $row ) {
			try {
				echo "- atualizando Pedido {$row['not_pedido']} ({$row['not_id']}) ... ";
				$this->atualizaPedidoDespachado ( $row ['not_id'] );
				echo "Ok!" . PHP_EOL;
			} catch ( Exception $e ) {
				$array_erros [] = $e->getMessage ();
				echo "Erro! " . $e->getMessage() . PHP_EOL;
			}
			$row = $db->FetchAssoc ( $res );
		}
		
		if (count ( $array_erros )) {
			return $array_erros;
		}
	
	}

}
?>
