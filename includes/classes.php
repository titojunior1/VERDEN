<?php
/**
 *
 * Autoload das classes utilizadas no WMS.
 *
 * @author Jonas Silveira <jonas.silveira@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 *
 */

	/**
	 * Efetua a leitura de classes numa última tentativa antes de retornar um erro.
	 *
	 * @param $class
	 */
function autoload($class) {
		switch ($class) {
			case 'nusoap_client' :
				require_once (PATH_INCLUDES . 'nusoap/nusoap.php');
				break;
			case 'DanfeNFePHP':
				require_once (PATH_INCLUDES . 'nfephp/libs/DanfeNFePHP.class.php');
				break;
			case 'Agenda' :
				require_once (PATH_INCLUDES_ANTIGO . 'agenda.class.php');
				break;
			case 'Application' :
				require_once (PATH_INCLUDES_ANTIGO . 'application.class.php');
				break;
			case 'Arquivos' :
				require_once (PATH_INCLUDES_ANTIGO . 'arquivos.class.php');
				break;
			case 'Db' :
				// clase abstrata de conexão com o banco de dados				
				require_once (PATH_INCLUDES_ANTIGO . 'db.class.php');
				break;
			case 'FormList' :
				require_once (PATH_INCLUDES_ANTIGO . 'formlist.class.php');
				break;
			case 'Formulario' :
				require_once (PATH_INCLUDES_ANTIGO . 'formulario.class.php');
				break;
			case 'Fornecedor' :
				require_once (PATH_INCLUDES_ANTIGO . 'fornecedor.class.php');
				break;
			case 'FPDF' :
				require_once (PATH_INCLUDES_ANTIGO . 'fpdf.class.php');
				break;
			case 'Ics' :
				require_once (PATH_INCLUDES_ANTIGO . 'ics.class.php');
				break;
			case 'Listagem' :
				require_once (PATH_INCLUDES_ANTIGO . 'listagem.class.php');
				break;
			case 'Loja' :
				require_once (PATH_INCLUDES_ANTIGO . 'loja.class.php');
				break;
			case 'MapaWh' :
				require_once (PATH_INCLUDES_ANTIGO . 'mapawh.class.php');
				break;
			case 'Movimento' :
				require_once (PATH_INCLUDES_ANTIGO . 'movimento.class.php');
				break;
			case 'NotasEntrada' :
				require_once (PATH_INCLUDES_ANTIGO . 'notasentrada.class.php');
				break;
			case 'NotasSaida' :
				require_once (PATH_INCLUDES_ANTIGO . 'notassaida.class.php');
				break;
			case 'Paginacao' :
				require_once (PATH_INCLUDES_ANTIGO . 'paginacao.class.php');
				break;
			case 'PedidoSaida' :
				require_once (PATH_INCLUDES_ANTIGO . 'pedidosaida.class.php');
				break;
			case 'Picking' :
				require_once (PATH_INCLUDES_ANTIGO . 'picking.class.php');
				break;
			case 'PickingIndividual' :
				require_once (PATH_INCLUDES_ANTIGO . 'pickingindividual.class.php');
				break;
			case 'Produto' :
				require_once (PATH_INCLUDES_ANTIGO . 'produto.class.php');
				break;
			
			case 'Status' :
				require_once (PATH_INCLUDES_ANTIGO . 'status.class.php');
				break;
			case 'Tabela' :
				require_once (PATH_INCLUDES_ANTIGO . 'tabela.class.php');
				break;
			case 'Transportadora' :
				require_once (PATH_INCLUDES_ANTIGO . 'transportadora.class.php');
				break;
			case 'UserAccess' :
				require_once (PATH_INCLUDES_ANTIGO . 'useraccess.class.php');
				break;
			case 'XlsHelper' :
				require_once (PATH_INCLUDES_ANTIGO . 'xls.class.php');
				break;
			default :
				break;
		}
		
	$ns = array(
		'Db_' => 'db', 'Model_' => 'models', 'Controller_' => 'controllers', 'Service_' => 'services'
	);
		
	$nsParts = explode('_', $class);
	$nsClassName = array_shift($nsParts);

	if (isset($ns["{$nsClassName}_"])) {
		$nsPath = $ns["{$nsClassName}_"];
		$tmp = explode('_', $class);
			array_shift ( $tmp );
		$path = PATH_INCLUDES . $nsPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $tmp) . '.php';
			if (! file_exists ( $path )) {
				return false;
			}
			// inclusão do arquivo necessário
			require_once ($path);
			return;
		}		
		
		return false;
}
