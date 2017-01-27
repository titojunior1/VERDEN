<?php
/**
 * 
 * Classe para processar as atualizações de preço no ERP KPL - Ábacos 
 * 
 * @author    Tito Junior 
 * 
 */

class Model_Verden_Kpl_Precos extends Model_Verden_Kpl_KplWebService {
	
	/*
	 * Instancia Webservice Magento
	*/
	private $_magento;
	
	/*
	 * Instancia Webservice KPL
	*/
	private $_kpl;
	
	/**
	 * 
	 * construtor.
	 * @param int $cli_id
	 * @param int $empwh_id
	 */
	function __construct() {
		
		if (empty ( $this->_kpl )) {
			$this->_kpl = new Model_Verden_Kpl_KplWebService (  );
		}
	
	}

	/**
	 * 
	 * Método para atualização de preço dos produtos	 
	 * @throws RuntimeException
	 */
	private function _atualizaPreco ( $dados_precos ) {
				
        $idProduto = $dados_precos['product_id'];
		$produto =  array(
							'price' => $dados_precos ['PrecoTabela'],
							'special_price' => $dados_precos ['PrecoPromocional'],
							'special_from_date' => $dados_precos ['DataInicioPromocao'],
							'special_to_date' => $dados_precos ['DataTerminoPromocao'],
						); 

		$this->_magento->atualizaProduto($idProduto, $produto);
	
	}

	/**
	 * 
	 * Buscar Produto.
	 * @param string $sku
	 * @param string $part_number
	 * @param int $ean_proprio
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function buscaProduto ( $sku, $part_number, $ean_proprio ) {		
		
		// verificar se o produto existe
		
		// BUSCAR PRODUTO MAGENTO		
	
	}

	/**
	 * 
	 * Processar cadastro de preços via webservice.
	 * @param string $guid
	 * @param array $request
	 */
	function ProcessaPrecosWebservice ( $request ) {

		// erros
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		if ( ! is_array ( $request ['DadosPreco'] [0] ) ) {
					
			$array_precos [0] ['ProtocoloPreco'] = $request ['DadosPreco'] ['ProtocoloPreco'];
			$array_precos [0] ['CodigoProduto'] = $request ['DadosPreco'] ['CodigoProduto'];
			$array_precos [0] ['CodigoProdutoPai'] = $request ['DadosPreco'] ['CodigoProdutoPai'];
			$array_precos [0] ['CodigoProdutoAbacos'] = $request ['DadosPreco'] ['CodigoProdutoAbacos'];
			$array_precos [0] ['NomeLista'] = $request ['DadosPreco'] ['NomeLista'];
			$array_precos [0] ['PrecoTabela'] = $request ['DadosPreco'] ['PrecoTabela'];
			$array_precos [0] ['PrecoPromocional'] = $request ['DadosPreco'] ['PrecoPromocional'];
			list($dataInicioPromo, $horaInicioPromo) = explode(' ', $request ['DadosPreco'] ['DataInicioPromocao']);
			$dia=substr($dataInicioPromo,0,2); 
			$mes=substr($dataInicioPromo,2,2);
			$ano=substr($dataInicioPromo,4,4);
			$dataInicioPromo = $dia.'/'.$mes.'/'.$ano;
			$array_precos [0] ['DataInicioPromocao'] = $dataInicioPromo;
			list($dataFimPromo, $horaFimPromo) = explode(' ', $request ['DadosPreco'] ['DataTerminoPromocao'] );
			$dia=substr($dataFimPromo,0,2);
			$mes=substr($dataFimPromo,2,2);
			$ano=substr($dataFimPromo,4,4);
			$dataFimPromo = $dia.'/'.$mes.'/'.$ano;
			$array_precos [0] ['DataTerminoPromocao'] = $dataFimPromo;
			$array_precos [0] ['DescontoMaximoProduto'] = $request ['DadosPreco'] ['DescontoMaximoProduto'];
			$array_precos [0] ['CodigoProdutoParceiro'] = $request ['DadosPreco'] ['CodigoProdutoParceiro'];
			
		} else {
			
			foreach ( $request ["DadosPreco"] as $i => $d ) {
				
				$array_precos [$i] ['ProtocoloPreco'] = $d ['ProtocoloPreco'];
				$array_precos [$i] ['CodigoProduto'] = $d ['CodigoProduto'];
				$array_precos [$i] ['CodigoProdutoPai'] = $d ['CodigoProdutoPai'];
				$array_precos [$i] ['CodigoProdutoAbacos'] = $d ['CodigoProdutoAbacos'];
				$array_precos [$i] ['NomeLista'] = $d ['NomeLista'];
				$array_precos [$i] ['PrecoTabela'] = $d ['PrecoTabela'];
				$array_precos [$i] ['PrecoPromocional'] = $d ['PrecoPromocional'];
				list($dataInicioPromo, $horaInicioPromo) = explode(' ', $d ['DataInicioPromocao']);
				$array_precos [$i] ['DataInicioPromocao'] = $dataInicioPromo;
				list($dataFimPromo, $horaFimPromo) = explode(' ', $d ['DataTerminoPromocao']);
				$array_precos [$i] ['DataTerminoPromocao'] = $dataFimPromo;
				$array_precos [$i] ['DescontoMaximoProduto'] = $d ['DescontoMaximoProduto'];
				$array_precos [$i] ['CodigoProdutoParceiro'] = $d ['CodigoProdutoParceiro'];
			}
		}
		
		
		$qtdPrecos = count($array_precos);
		
		echo PHP_EOL;
		echo "Precos encontrados para integracao: " . $qtdPrecos . PHP_EOL;
		echo PHP_EOL;
		
		echo "Conectando ao WebService Magento... " . PHP_EOL;
		$this->_magento = new Model_Verden_Magento_Precos();
		echo "Conectado!" . PHP_EOL;
		echo PHP_EOL;
		
		// Percorrer array de preços
		foreach ( $array_precos as $indice => $dados_precos ) {
			$erros_precos = 0;
			$array_inclui_precos = array ();			
			
			if ( empty ( $dados_precos ['PrecoTabela'] ) ) {
				echo "Preco do produto {$dados_precos['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$array_erro [$indice] = "Produto {$dados_precos['CodigoProduto']}: Dados obrigatórios não preenchidos" . PHP_EOL;
				$erros_precos ++;
			}
			if ( $erros_precos == 0 ) {
				
				try {
					echo PHP_EOL;
					echo "Buscando cadastro do produto " . $dados_precos['CodigoProduto'] . PHP_EOL;
					$produto = $this->_magento->buscaProduto($dados_precos['CodigoProduto']);
					if ( !empty ( $produto ) ) {
						echo "Atualizando Preco " . $dados_precos['CodigoProduto'] . PHP_EOL;
						$dados_precos['product_id'] = $produto; // ID do Produto na Loja Magento
						$this->_atualizaPreco( $dados_precos );
						echo "Preco atualizado. " . PHP_EOL;
						
					}else{
						throw new RuntimeException( 'Produto não encontrado' );
					} 
										
					$this->_kpl->confirmarPrecosDisponiveis ( $dados_precos ['ProtocoloPreco'] );
					echo "Protocolo Preco: {$dados_precos ['ProtocoloPreco']} enviado com sucesso" . PHP_EOL;		

				} catch ( Exception $e ) {
					echo "Erro ao importar preco {$dados_precos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
					echo PHP_EOL;
					$array_erro [$indice] = "Erro ao importar preco {$dados_precos['CodigoProduto']}: " . $e->getMessage() . PHP_EOL;
				}
			
			}
		}		
		
		// finaliza sessão Magento
		$this->_magento->_encerraSessao();
		
		if(is_array($array_erro)){
			$array_retorno = $array_erro;
		}
		return $array_retorno;
	
	}

}

