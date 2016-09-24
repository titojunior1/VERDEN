<?php

/**
 * 
 * Classe para processar o cadastro de fornecedores via webservice do ERP KPL - Ábacos 
 * 
 * @author    Rômulo Z. C. Cunha <romulo.cunha@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 * @package   wms
 * @since     30/08/2012
 * 
 */

//require_once ('erros_kpl.php');


final class Model_Wms_Kpl_Fornecedor extends Model_Wms_Kpl_KplWebService {
	private $_climov_id;
	private $_climov_tipo;
	
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
	 */
	function __construct ( $cli_id ) {

		$this->_cli_id = $cli_id;
		if ( empty ( $this->_kpl ) ) {
			$this->_kpl = new Model_Wms_Kpl_KplWebService ( $cli_id );
		}
	
	}

	/**
	 * 
	 * retorna erro utilizando erros_abacos.php.
	 * @param int $codigo						// código do erro
	 * @param string ou array $parametro		// string ou array do parametro do erro, caso seja pertinente 
	 * @param string $exception					// mensagem de exceção
	 */
	/*	private function retorna_erro($codigo, $parametro = NULL, $exception = NULL) {
		$temp = new erros_kpl ( $codigo, $parametro, $exception );
		return $temp->retorna_erro ( $codigo, $parametro, $exception );
	}*/
	/**
	 * 
	 * Processar cadastro de fornecedores via webservice
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaFornecedoresWebservice ( $request ) {

		// cria instância do banco de dados
		$db = Db_Factory::getDbWms ();
		
		$codigoproc = 1;
		
		// array para retorno dos dados ao webservice
		$array_retorno = array ();
		$array_erro = array ();
		
		$array_fornecedores = array ();
		
		if ( ! is_array ( $request ['Rows'] ['DadosFornecedorWMS'] [0] ) ) {
			$fornecedor_mestre [0] = $request ['Rows'] ['DadosFornecedorWMS'];
		
		} else {
			$fornecedor_mestre = $request ['Rows'] ['DadosFornecedorWMS'];
		}
		
		foreach ( $fornecedor_mestre as $i => $d ) {
			
			$array_fornecedores [$i] ['ProtocoloFornecedor'] = $d ['ProtocoloFornecedor'];
			$array_fornecedores [$i] ['CodigoFornecedor'] = $d ['CodigoFornecedorAbacos']; //forn_id_cli
			$array_fornecedores [$i] ['Nome'] = utf8_decode ( $d ['Nome'] );
			$array_fornecedores [$i] ['Endereco'] = utf8_decode ( $d ['Endereco'] ['Logradouro'] );
			$array_fornecedores [$i] ['EndNum'] = utf8_decode ( $d ['Endereco'] ['NumeroLogradouro'] );
			$array_fornecedores [$i] ['EndCompl'] = utf8_decode ( $d ['ComplementoLogradouro'] );
			$array_fornecedores [$i] ['Bairro'] = utf8_decode ( $d ['Endereco'] ['Bairro'] );
			$array_fornecedores [$i] ['CEP'] = utf8_decode ( $d ['Endereco'] ['Cep'] );
			$array_fornecedores [$i] ['Cidade'] = utf8_decode ( $d ['Endereco'] ['Municipio'] );
			$array_fornecedores [$i] ['UF'] = $d ['Endereco'] ['Estado'];
			$array_fornecedores [$i] ['Contato'] = $d ['ContatoNome'];
			$array_fornecedores [$i] ['Telefone'] = $d ['ContatoTelefone'];
			$array_fornecedores [$i] ['Email'] = $d ['ContatoEmail'];
			$array_fornecedores [$i] ['CNPJFornecedor'] = $d ['CPFouCNPJ'];
			$i ++;
		
		}
		
		if ( $array_fornecedores ) {
			foreach ( $array_fornecedores as $indice => $dados_fornecedores ) {
				if ( empty ( $dados_fornecedores ['CodigoFornecedor'] ) || empty ( $dados_fornecedores ['Nome'] ) ) {
					// Retorna erro se algum campo obrigatório não estiver preenchido
					$array_erro [$indice] = "Fornecedor: {$dados_fornecedores['CodigoFornecedor']} - Nome ou Código não preenchidos" . PHP_EOL;
					echo "Fornecedor: {$dados_fornecedores['CodigoFornecedor']} - Campos obrigatórios não preenchidos" . PHP_EOL;
				} else {
					// tratar sql
					foreach ( $dados_fornecedores as $key => $val ) {
						$dados_fornecedores [$key] = $db->EscapeString ( $val );
					}
					//verificar se o fornecedor já existe
					$sql = "SELECT forn_id_cli FROM fornecedores WHERE cli_id = {$this->_cli_id} AND forn_id_cli = '{$dados_fornecedores['CodigoFornecedor']}'";
					$res = $db->Execute ( $sql );
					if ( $res ) {
						if ( $db->NumRows ( $res ) == 0 ) {
							$pais_id = "BR";
							$sql = "INSERT INTO fornecedores ( pais_id, cli_id, forn_nome, forn_endereco, forn_numero, forn_complemento, forn_bairro, forn_cep, forn_cidade, forn_estado, forn_contato, forn_telefone, forn_email, forn_obs, forn_id_cli, forn_cnpj)
	                                VALUES ( '$pais_id',{$this->_cli_id},'{$dados_fornecedores["Nome"]}','{$dados_fornecedores["Endereco"]}','{$dados_fornecedores["EndNum"]}','{$dados_fornecedores["EndCompl"]}','{$dados_fornecedores["Bairro"]}','{$dados_fornecedores["CEP"]}','{$dados_fornecedores["Cidade"]}','{$dados_fornecedores["UF"]}','{$dados_fornecedores["Contato"]}','{$dados_fornecedores["Telefone"]}','{$dados_fornecedores["Email"]}','{$dados_fornecedores["Observacoes"]}', '{$dados_fornecedores["CodigoFornecedor"]}', '{$dados_fornecedores["CNPJFornecedor"]}' )";
							if ( ! $db->Execute ( $sql ) ) {
								// Se não conseguiu realizar insert, retorna erro
								throw new RuntimeException ( "Erro ao inserir fornecedor {$dados_fornecedores['Nome']}" );
							} else {
								// Inseriu ok!
								//	$array_erro [$indice] = $this->retorna_erro ( 200002, "" );
								$forn_id = $db->LastInsertId ();
							}
						} else {
							// Se o fornecedor já estiver cadastrado, retorna erro
							//							$array_erro [$indice] = $this->retorna_erro ( 300004, "Fornecedor ja cadastrado" );
							echo "Fornecedor já cadastrado" . PHP_EOL;
						}
					} else {
						// Se não conseguiu realizar select, retorna erro
						//					$array_erro [$indice] = $this->retorna_erro ( 300014, "" );
						throw new Exception ( "Erro ao consultar fornecedor" . PHP_EOL );
					}
				}
				
				//enviar protocolo de transmissão do pedido. 
				try {
					
					$this->_kpl->confirmarFornecedoresDisponiveis ( $dados_fornecedores ['ProtocoloFornecedor'] );
					
					echo "Protocolo Fornecedor: {$dados_fornecedores['ProtocoloFornecedor']}" . PHP_EOL;
				} catch ( Exception $e ) {
					echo $e->getMessage () . PHP_EOL;
				}
			
			}
		}
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}