<?php

/**
 * 
 * Classe para trabalhar com o Webservice da KPL (Stub de Servi�o)
 * 
 * @author    Tito Junior
 * 
 */

class Model_Verden_Kpl_KplWebService {
	
	/**
	 * Endere�o do WebService.
	 *
	 * @var string
	 */
	private $_ws;
	
	/**
	 * 
	 * Chave de identifica��o do cliente na KPL.
	 * @var string
	 */
	private $_chave_identificacao;
	
	/**
	 * inst�ncia do WebService
	 *
	 * @var string
	 */
	private $_webservice;	
	
	/**
	 * Array de Mensagens de Erros.
	 *
	 * @var array
	 */
	private $_array_erros = array ();
	
	/**
	 * Habilita fun��o de Debug.
	 *
	 * @var boolean
	 */
	private $_debug = false;
	
	/**
	 * Array de Mensagens de Debug.
	 *
	 * @var array
	 */
	private $_debugMSG = array ();
	
	/**
	 * Construtor.
	 * @param string $ws Endere�o do Webservice.
	 * @param string $login Login de Conex�o do Webservice.
	 * @param string $pass Senha de Conex�o do Webservice.
	 */
	public function __construct() {
			
		$this->_ws = KPL_WSDL;
			
		try {
			// conecta nusoap
			$this->_webservice = new nusoap_client ( $this->_ws, true, NULL, NULL, NULL, NULL, 240, 240 );
			$this->_webservice->_debug_flag = 1;
			$this->_webservice->soap_defencoding = 'UTF-8';
			$this->_webservice->decode_utf8 = true;
			$this->_chave_identificacao = KPL_KEY;
		
		} catch ( Exception $e ) {
			throw new Exception ( 'Erro ao conectar no WebService da KPL' );
		}
	}
	/**
	 * 
	 * Retorna o id do armazem.
	 */
	public function getArmazem() {
		return $this->_empwh_id;
	}
	
	/**
	 * 
	 * Retorna chave de identifica��o do cliente na KPL.
	 */
	public function getChaveIdentificacao() {
		return $this->_chave_identificacao;
	}
	
	/**
	 * Adiciona mensagem de erro ao array
	 * @param String $mensagem Mensagem de Erro
	 */
	private function _Erro($mensagem) {
		$msg = "Data:" . date ( "d/m/Y H:i:s" ) . " <br>" . $mensagem;
		$this->_array_erros [] = $msg;
		throw new Exception ( $msg );
	}
	
	/**
	 * Chama uma action do WebService
	 * @param string $action Nome da Action do Webservice
	 * @param array $params Array de parametros
	 * @return Objeto de retorno da Action 
	 */
	private function _wsCall($action, $params) {
		try {
			
			$result = $this->_webservice->call ( $action, $params );

			if (! $result) {
				throw new ErrorException ( 'Erro na Execu��o do Webservice' );
			}
			return $result;
		

		} catch ( ErrorException $e ) {
			return $this->_webservice->getError ();
		}
	}
	/**
	 * Impress�o de Debug para WebService
	 * @param ('requisi�ao' - Imprime Debug da Request envia do Webservice, 'resposta' - Imprime Debug da Resposta recebida do Webservice,
	 * 'debug' - Imprime debug do Webservice, 'todos' = Imprime todos os itens  
	 */
	private function _wsDebug($acao) {
		if ($this->_debug) {
			$this->_debug = '<h2>Request</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->request, ENT_QUOTES ) . '</pre>';
			$this->_debug = '<h2>Response</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->response, ENT_QUOTES ) . '</pre>';
			$this->_debug = '<h2>Debug</h2>';
			$this->_debug = '<pre>' . htmlspecialchars ( $this->client->debug_str, ENT_QUOTES ) . '</pre>';
		}
	}
	
	/**
	 * Monta mensagem de erro em caso Exception.
	 * @param object $e Objeto do Exception 
	 */
	public function GetErrorReport($e) {
		$msg = "Erro: " . $e->getMessage () . "<br />";
		$msg .= "Arquivo: " . $e->getFile () . "<br />";
		$msg .= "Linha: " . $e->getLine () . "<br />";
		$msg .= "Trace: " . $e->getTraceAsString () . "<br />";
		$this->_Erro ( $msg );
	}
	
	/**
	 * Imprime o array de erros.
	 */
	public function PrintErros() {
		foreach ( $this->_array_erros as $key => $value ) {
			print_r ( $value );
		}
	}
	
	/**
	 * 
	 * Realiza transmiss�o de dados para o webservice
	 * @param String $nome Recebe o nome da opera��o do SOAP server (URL or path)
	 * @param Array $parametros Recebe o XML para transferir
	 * 
	 */
	public function realizaTransmissao($nome, $parametros) {
		if (empty ( $nome ) || empty ( $parametros )) {
			return NULL;
		}
		// Realiza transmiss�o
		$resultado = $this->_webservice->call ( $nome, array ('{$parametros}' => $parametros ) );
		
		if (! is_array ( $resultado )) {
			// Se n�o conseguiu enviar, retorna erro
			$mensagem .= "- ERRO! N�o foi poss�vel enviar. Retorno: " . $this->_webservice->getError () . "<br />";
		} else {
			if ($resultado ['TrackingResult'] != 'true') {
				// Se n�o houver resultado 
				$mensagem .= "- ERRO! Retornado '{$resultado['TrackingResult']}'" . "<br />";
			} else {
				// Resultado Ok!
				$mensagem .= "- OK! Retornado '{$resultado['TrackingResult']}' - " . "<br />";
			}
			$mensagem .= " - codigoMensagem = '{$resultado['codigoMensagem']}' / descricaoMensagem = '{$resultado['descricaoMensagem']}'" . "<br />";
		}
		return $mensagem;
	}
	
	public function cadastraCliente ($chaveIdentificacao, $dadosCliente){
		try {			
			return $this->_wsCall ( 'CadastrarCliente', array ('ChaveIdentificacao' => $chaveIdentificacao,
															   'ListaDeClientes' => $dadosCliente ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	public function cadastraPedidoKpl ($chaveIdentificacao, $dadosPedido){
		try {
			return $this->_wsCall ( 'InserirPedido', array (
															'ChaveIdentificacao' => $chaveIdentificacao,
															'ListaDePedidos' => array( 
																						'DadosPedidos' => $dadosPedido
																					 )
															) 
								  );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Confirma o cadastro do fornecedor para a KPL.
	 * @param string $protocoloFornecedor
	 * @throws InvalidArgumentException
	 */
	public function confirmarFornecedoresDisponiveis($protocoloFornecedor) {
		if (empty ( $protocoloFornecedor )) {
			throw new InvalidArgumentException ( 'Protocolo do Fornecedor n�o informado.' );
		}
		
		try {
			
			//realiza a transmiss�o
			$resultado = $this->_webservice->call ( 'ConfirmarFornecedores', array ('ProtocoloFornecedor' => $protocoloFornecedor ) );
			if ($resultado ['ConfirmarFornecedoresResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				throw new Exception ( "Erro ao confirmar o envio do protocolo do fornecedor" . PHP_EOL );
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		
		}
	}
	/**
	 * 
	 * Confirmar recebimento de nota de entrada.
	 * @param string $protocoloNotaFiscal
	 * @throws InvalidArgumentException
	 */
	public function confirmarRecebimentoNotaFiscalEntrada($protocoloNotaFiscal) {
		if (empty ( $protocoloNotaFiscal )) {
			throw new InvalidArgumentException ( 'Protocolo Nota Fiscal de Entrada n�o informado' );
		}
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoNotaFiscalEntrada', array ('ProtocoloNotaFiscal' => $protocoloNotaFiscal ) );
			if ($resultado ['ConfirmarRecebimentoNotaFiscalEntradaResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				echo "Erro ao confirmar o envio do protocolo do pedido" . PHP_EOL;
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * Confirmar recebimento separar pedido
	 * @param string $protocoloPedido
	 * @throws InvalidArgumentException
	 */
	public function ConfirmarRecebimentoSepararPedido($protocoloPedido) {
		if (empty ( $protocoloPedido )) {
			throw new InvalidArgumentException ( 'Protocolo do pedido n�o informado' );
		}
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoSepararPedido', array ('ProtocoloPedido' => $protocoloPedido ) );
			if ($resultado ['ConfirmarRecebimentoSepararPedidoResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				echo "Erro ao confirmar o envio do protocolo do pedido" . PHP_EOL;
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
	/**
	 * 
	 * Confirmar baixa do pedido dispon�vel.
	 * @param string $protocoloPedido
	 * @throws InvalidArgumentException
	 */
	public function confirmarPedidosDisponiveis($protocoloPedido) {
		if (empty ( $protocoloPedido )) {
			throw new InvalidArgumentException ( 'Protocolo do pedido n�o informado' );
		}
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarPedidosDisponiveis', array ('ProtocoloPedido' => $protocoloPedido ) );
			if ($resultado ['ConfirmarPedidosDisponiveisResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				echo "Erro ao confirmar o envio do protocolo do pedido" . PHP_EOL;
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	
	}
	
		
	/**
	 * 
	 * Confirmar Recebimento NF de Sa�da.
	 * @param string $protocoloNotaFiscal
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function ConfirmarRecebimentoNotaFiscalSaida($protocoloNotaFiscal) {
		if (empty ( $protocoloNotaFiscal )) {
			throw new InvalidArgumentException ( 'Protocolo da Nota n�o informado' );
		}
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoNotaFiscalSaida', array ('ProtocoloNotaFiscal' => $protocoloNotaFiscal ) );
			if ($resultado ['ConfirmarRecebimentoNotaFiscalSaidaResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				echo "Erro ao confirmar o envio do protocolo da NF" . PHP_EOL;
			}
		
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		}
	}
	/**
	 * 
	 * M�todo para confirma��o de recebimento de produto junto � KPL.
	 * @param string $protocoloProduto
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function confirmarProdutosDisponiveis($protocoloProduto) {
		if (empty ( $protocoloProduto )) {
			throw new InvalidArgumentException ( 'Protocolo do Produto n�o informado' );
		}
		
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoProduto', array ('ProtocoloProduto' => $protocoloProduto ) );
			if ($resultado ['ConfirmarRecebimentoProdutoResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				//gravar mensagem de erro.
				throw new Exception ( "Erro ao confirmar o envio do protocolo do produto" . PHP_EOL );
			
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
		
		}
		
	}
	
	/**
	 *
	 * M�todo para confirma��o de recebimento de pre�o junto � KPL.
	 * @param string $protocoloProduto
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function confirmarPrecosDisponiveis($protocoloPreco) {
		if (empty ( $protocoloPreco )) {
			throw new InvalidArgumentException ( 'Protocolo do Pre�o n�o informado' );
		}
	
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoPreco', array ('ProtocoloPreco' => $protocoloPreco ) );
			if ($resultado ['ConfirmarRecebimentoPrecoResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				//gravar mensagem de erro.
				throw new Exception ( "Erro ao confirmar o envio do protocolo do preco" . PHP_EOL );
					
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
	
		}
	
	}
	
	/**
	 *
	 * M�todo para confirma��o de recebimento de estoque junto � KPL.
	 * @param string $protocoloProduto
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function confirmarEstoquesDisponiveis($protocoloEstoque) {
		if (empty ( $protocoloEstoque )) {
			throw new InvalidArgumentException ( 'Protocolo do Estoque n�o informado' );
		}
	
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoEstoque', array ('ProtocoloEstoque' => $protocoloEstoque ) );
			if ($resultado ['ConfirmarRecebimentoEstoqueResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				//gravar mensagem de erro.
				throw new Exception ( "Erro ao confirmar o envio do protocolo do estoque" . PHP_EOL );
					
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
	
		}
	
	}
	
	/**
	 *
	 * M�todo para confirma��o de atualiza��o de status junto � KPL.
	 * @param string $protocoloStatus
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function confirmarRecebimentoStatusPedido($protocoloStatus) {
		if (empty ( $protocoloStatus )) {
			throw new InvalidArgumentException ( 'Protocolo do Status n�o informado' );
		}
	
		try {
			$resultado = $this->_webservice->call ( 'ConfirmarRecebimentoStatusPedido', array ('ProtocoloStatusPedido' => $protocoloStatus ) );
			if ($resultado ['ConfirmarRecebimentoStatusPedidoResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				//gravar mensagem de erro.
				throw new Exception ( "Erro ao confirmar o envio do protocolo do status" . PHP_EOL );
					
			}
		} catch ( Exception $e ) {
			throw new Exception ( $e->getMessage () );
	
		}
	
	}
	
	/**
	 * Retorna os fornecedores dispon�veis para integra��o.
	 * @param string $chaveIdentificacao
	 */
	public function FornecedoresDisponiveis($chaveIdentificacao) {
		try {
			return $this->_wsCall ( 'FornecedoresDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
		}
	}
	/**
	 * 
	 * Retorna as notas de entrada dispon�veis para integra��o.
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function NotasFiscaisEntradaDisponiveis($chaveIdentificacao) {
		try {
			return $this->_wsCall ( 'NotasFiscaisEntradaDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * 
	 * Retorna as notas fiscais faturadas.
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function NotasFiscaisSaidaDisponiveis($chaveIdentificacao) {
		try {
			return $this->_wsCall ( 'NotasFiscaisSaidaDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os produtos atualizados ou inseridos a partir de uma determinada data
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function ProdutosDisponiveis($chaveIdentificacao) {
		try {
			// Recebe array com produtos
			return $this->_wsCall ( 'ProdutosDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os pedidos de venda atualizados ou inseridos a partir de uma determinada data
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function PedidosDisponiveis($chaveIdentificacao) {
		try {
			// Recebe array com pedidos
			return $this->_wsCall ( 'PedidosDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os pre�os dos produto atualizados ou inseridos a partir de uma determinada data
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function PrecosDisponiveis($chaveIdentificacao) {
		try {
			// Recebe array com pedidos
			return $this->_wsCall ( 'PrecosDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os status atualizados de um pedido
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function statusPedidosDisponiveis($chaveIdentificacao) {
		try {
			// Recebe array com pedidos
			return $this->_wsCall ( 'StatusPedidoDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Retorna todos os estoques dispon�veis
	 * @param string $chaveIdentificacao
	 * @throws RuntimeException
	 */
	public function EstoquesDisponiveis($chaveIdentificacao) {
		try {
			// Recebe array com pedidos
			return $this->_wsCall ( 'EstoquesDisponiveis', array ('ChaveIdentificacao' => $chaveIdentificacao ) );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Envio das instru��es de tracking
	 * @param int pChaveIdentificacao
	 * @param int pCodigoPedidoInterno
	 * @return retorna mensagem em caso de erro ou objeto do Webservice se estiver tudo certo. 
	 */
	public function MarcarPedidosDespachados($request) {
		try {
			return $result = $this->_wsCall ( 'MarcarPedidosDespachados', $request );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Envio das instru��es de tracking
	 * @param int pChaveIdentificacao
	 * @param int pCodigoPedidoInterno
	 * @param int pNroObjeto
	 * @return retorna mensagem em caso de erro ou objeto do Webservice se estiver tudo certo.
	 */
	public function EnviarRastreioObjeto($request) {
		try {
			return $result = $this->_wsCall ( 'EnviarRastreioObjeto', $request );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 * Atualizar a quantidade de produtos ao movimentar estoque.
	 * @param string idestoque Id do Estoque
	 * @param string idsku     Id do Sku
	 * @param int    quantidade Quantidade a ser atualizada 
	 * @param date 	 dateofav Data da atualiza��o
	 * @return retorna mensagem em caso de erro ou true se estiver tudo certo. 
	 */
	public function AtualizarSaldoProduto($request) {
		
		try {
			$resultado = $this->_wsCall ( 'AtualizarSaldoProduto', $request );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
		return $resultado;
	}
	/**
	 * 
	 * M�todo para enviar checkout dos Pedidos.
	 * @param string $chaveIdentificacao
	 * @param string $numero_pedido
	 * @param array $array_itens
	 */
	public function enviarCheckoutPedido($request) {
		try {
			$resultado = $this->_wsCall ( 'CheckoutItem', $request );
			if($resultado['CheckoutItemResult']['Rows']['DadosPedidosItemResultado']['Resultado']['Codigo']=='200002'){
				echo "Pedido enviado para a KPL".PHP_EOL;
				return true;
			}else{

				if ( ! is_array ( $resultado['CheckoutItemResult']['Rows']['DadosPedidosItemResultado'] [0] ) ) {
					$array_erros [0] = $resultado['CheckoutItemResult']['Rows']['DadosPedidosItemResultado'];

				} else {
					$array_erros = $resultado['CheckoutItemResult']['Rows']['DadosPedidosItemResultado'];
				}



				foreach($array_erros as $i => $resultado_dados){
					foreach($resultado_dados as $i => $dados){

						if($dados['Codigo']=='300005'){
							$erro .= $dados['ExceptionMessage'];
						}elseif($dados['Codigo']=='200002'){
							return true;
						}

					}
				}
				//gravar mensagem de erro.
				throw new Exception ( $erro);
			}
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	/**
	 *
	 * M�todo para enviar checkout dos Pedidos com Volumes.
	 * @param string $chaveIdentificacao
	 * @param string $numero_pedido
	 * @param array $array_itens
	 */
	public function enviarCheckoutPedidoComVolumes($request) {
		try {
			$resultado = $this->_wsCall ( 'CheckoutItemComVolumes', $request );
			if($resultado['CheckoutItemComVolumesResult']['ResultadoOperacao']['Codigo']=='200002'){
				echo "Pedido enviado para a KPL".PHP_EOL;
				return true;
			}
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}
	
	public function realizarRecebimentoMercadoria($request) {
		try {
			$resultado = $this->_wsCall ( 'RealizarRecebimentoMercadoria', $request );
			if ($resultado ['RealizarRecebimentoMercadoriaResult'] ['Codigo'] == '200001') {
				return true;
			} else {
				//gravar mensagem de erro.
				throw new Exception ( $resultado['RealizarRecebimentoMercadoriaResult']['ExceptionMessage']);
			
			}
		
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
		return $resultado;
	}
	
	/**
	 *
	 * M�todo para capturar XML Nef.
	 * @param string $chaveIdentificacao
	 * @param string $CodigoNotaFiscal
	 * @param array $array_itens
	 */
	public function obterXmlNfe($request) {
		try {
			$resultado = $this->_wsCall ( 'ObterXmlNfe', $request );
			if($resultado['ObterXmlNfeResult']['ResultadoOperacao']['Codigo']=='200001'){
//				echo 'Opera��o "Obter XML das NFE" efetuada com sucesso.'.PHP_EOL;
				return $resultado;
			}
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage () );
		}
	}

}
?>
