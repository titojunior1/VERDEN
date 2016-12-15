<?php
/**
 *
 * Classe para processar o cadastro de Pedidos via webservice no ERP da Magento
 *
 * @author Tito Junior
 *
 */
class Model_Verden_Magento_Pedidos extends Model_Verden_Magento_MagentoWebService{
	
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
	 */
	function __construct () {
	
		if (empty ( $this->_magento )) {
			$this->_magento = new Model_Verden_Magento_MagentoWebService();
		}
		
	}
	
	/**
	 * Verificar CNPJ
	 * @param int $cnpj
	 * @param bool $formatar
	 * @return string | bool
	 */
	public static function validaCnpj($cnpj, $formatar = false) {
	
		// remove tudo que não for número
		$cnpj = self::Numeros ( $cnpj );
	
		if ($formatar) {
			$cnpj_formatado = substr ( $cnpj, 0, 2 ) . '.' . substr ( $cnpj, 2, 3 ) . '.' . substr ( $cnpj, 5, 3 ) . '/' . substr ( $cnpj, 8, 4 ) . '-' . substr ( $cnpj, 12, 2 );
			return $cnpj_formatado;
		} else {
			// cpf falso
			$array_cnpj_falso = array ( '00000000000000', '11111111111111', '22222222222222', '33333333333333', '44444444444444', '55555555555555', '66666666666666', '77777777777777', '88888888888888', '99999999999999', '12345678912345' );
				
			if (empty ( $cnpj ) || strlen ( $cnpj ) != 14 || in_array ( $cnpj, $array_cnpj_falso )) {
				return false;
			} else {
	
				$rev_cnpj = strrev ( substr ( $cnpj, 0, 12 ) );
				for($i = 0; $i <= 11; $i ++) {
					$i == 0 ? $multiplier = 2 : $multiplier;
					$i == 8 ? $multiplier = 2 : $multiplier;
					$multiply = ($rev_cnpj [$i] * $multiplier);
					$sum = $sum + $multiply;
					$multiplier ++;
				}
	
				$rest = $sum % 11;
				if ($rest == 0 || $rest == 1) {
					$dv1 = 0;
				} else {
					$dv1 = 11 - $rest;
				}
	
				$sub_cnpj = substr ( $cnpj, 0, 12 );
				$rev_cnpj = strrev ( $sub_cnpj . $dv1 );
				unset ( $sum );
	
				for($i = 0; $i <= 12; $i ++) {
					$i == 0 ? $multiplier = 2 : $multiplier;
					$i == 8 ? $multiplier = 2 : $multiplier;
					$multiply = ($rev_cnpj [$i] * $multiplier);
					$sum = $sum + $multiply;
					$multiplier ++;
				}
				$rest = $sum % 11;
	
				if ($rest == 0 || $rest == 1) {
					$dv2 = 0;
				} else {
					$dv2 = 11 - $rest;
				}
	
				if ($dv1 == $cnpj [12] && $dv2 == $cnpj [13]) {
					return true;
				} else {
					return false;
				}
			}
		}
	}
	
	/**
	 * Verificar CPF
	 * @param int $cpf
	 * @param bool $formatar
	 * @return string | bool
	 */
	public static function validaCpf($cpf, $formatar = false) {
		if ($formatar) {
				
			$cpf = self::Numeros ( $cpf );
			$cpf_formatado = substr ( $cpf, 0, 3 ) . '.' . substr ( $cpf, 3, 3 ) . '.' . substr ( $cpf, 6, 3 ) . '-' . substr ( $cpf, 9, 2 );
			return $cpf_formatado;
		} else {
			// cpf falso
			$array_cpf_falso = array ( '00000000000', '11111111111', '22222222222', '33333333333', '44444444444', '55555555555', '66666666666', '77777777777', '88888888888', '99999999999', '12345678912' );
				
			// remove tudo que não for número
			$cpf = self::Numeros ( $cpf );
				
			if (empty ( $cpf ) || strlen ( $cpf ) != 11 || in_array ( $cpf, $array_cpf_falso )) {
				return false;
			} else {
	
				$sub_cpf = substr ( $cpf, 0, 9 );
	
				for($i = 0; $i <= 9; $i ++) {
					$dv += ($sub_cpf [$i] * (10 - $i));
				}
	
				if ($dv == 0) {
					return false;
				}
	
				$dv = 11 - ($dv % 11);
	
				if ($dv > 9) {
					$dv = 0;
				}
	
				if ($cpf [9] != $dv) {
					return false;
				}
	
				$dv *= 2;
	
				for($i = 0; $i <= 9; $i ++) {
					$dv += ($sub_cpf [$i] * (11 - $i));
				}
	
				$dv = 11 - ($dv % 11);
	
				if ($dv > 9) {
					$dv = 0;
				}
	
				if ($cpf [10] != $dv) {
					return false;
				}
	
				return true;
			}
		}
	}
	
	/**
	 * Deixa somente os números
	 * @param string $var
	 * @return string
	 */
	public static function Numeros($var) {
		return preg_replace ( '/[^0-9]/i', '', $var );
	}	
	
	/**
	 *
	 * Processar cadastro de clientes via webservice.
	 * @param array $request
	 */
	function ProcessaPedidosWebservice ( $request ) {
		

		// erros
		$erro = null;
		
		// coleção de erros, no formato $array_erros[$registro][] = erro
		$array_erros = array ();
		$array_erro_principal = array ();
		$array_precos = array ();
		
		foreach ( $request as $i => $d ) {
		
			$dadosCliente = array();
			$dadosPedido = array();
			
			// formatar CPF
			$cpfFormatado = $this->Numeros($d->customer_taxvat);
			// formata sexo
			$sexoCliente = ($d->customer_gender == '1')? 'tseMasculino':'tseFeminino';
			
			//Manipulando dados para cadastro/atualização de cliente 
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Email'] = $d->email;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] = $cpfFormatado;			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Codigo'] = $cpfFormatado;
			
			//valida se é pessoa PF, caso não é PJ
			$validaCpf = $this->validaCpf($d->customer_taxvat);
			if ( $validaCpf ){				
				$tipoPessoa = 'tpeFisica';
			}else{
				$tipoPessoa = 'tpeJuridica';
			}			
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['TipoPessoa']	= $tipoPessoa;		 
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Documento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Nome'] = $d->firstname.' '.$d->lastname;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['NomeReduzido'] = $d->firstname;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Sexo'] = $sexoCliente;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataNascimento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Telefone'] = $d->telephone;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Celular'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataCadastro'] = '';
			
			$infosAdicionaisPedido = $this->_magento->buscaInformacoesAdicionaisPedido($d->increment_id);

			$cepEntregaFormatado = $this->Numeros($infosAdicionaisPedido->shipping_address->postcode);
			$cepCobrancaFormatado = $this->Numeros($infosAdicionaisPedido->billing_address->postcode);
			
			// Dados do Endereço			
			list($dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'],
				 $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'],
				 $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'],
				 $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro']
				) = explode("\n", $infosAdicionaisPedido->shipping_address->street);		
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Municipio'] = $infosAdicionaisPedido->shipping_address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Estado'] = $infosAdicionaisPedido->shipping_address->region;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informação não vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Pais'] = $infosAdicionaisPedido->shipping_address->country_id;						
			// Dados do Endereço de Cobrança
			list($dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Logradouro'],
				 $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['NumeroLogradouro'],
			     $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ComplementoEndereco'],
				 $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Bairro']
			) = explode("\n", $infosAdicionaisPedido->billing_address->street);
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Municipio'] = $infosAdicionaisPedido->billing_address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Estado'] = $infosAdicionaisPedido->billing_address->region;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Cep'] = $cepCobrancaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informação não vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Pais'] = $infosAdicionaisPedido->billing_address->country_id;			
			// Dados do Endereço de Entrega
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Logradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['NumeroLogradouro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ComplementoEndereco'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'];
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Bairro'] = $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'];
			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Municipio'] = $infosAdicionaisPedido->shipping_address->city;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Estado'] = $infosAdicionaisPedido->shipping_address->region;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Cep'] = $cepEntregaFormatado;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['TipoLocalEntrega'] = 'tleeDesconhecido'; // informação não vem da magento
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ReferenciaEndereco'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Pais'] = $infosAdicionaisPedido->shipping_address->country_id;						
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['ClienteEstrangeiro'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['RegimeTributario'] = '';

			echo "Conectando ao WebService Kpl... " . PHP_EOL;
			$this->_kpl = new Model_Verden_Kpl_Clientes();
			echo "Conectado!" . PHP_EOL;
			echo PHP_EOL;
			
			try {
				echo "Efetuando cadastro/atualizacao de cliente " . $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] . PHP_EOL;
				$this->_kpl->adicionaCliente( $dadosCliente [$i] ['Cliente'] );
				echo "Cliente adicionado com sucesso " . PHP_EOL;
			} catch (Exception $e) {
				echo "Erro ao cadastrar cliente " . $dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] . PHP_EOL;
				throw new RuntimeException('Erro: ' . $e->getMessage());
			}
			
			//Seguindo com criação de Pedidos
			$dadosPedido [$i] ['NumeroDoPedido'] = $infosAdicionaisPedido->increment_id;
			$dadosPedido [$i] ['EMail'] = $infosAdicionaisPedido->customer_email;
			$dadosPedido [$i] ['CPFouCNPJ'] = $cpfFormatado;
			$dadosPedido [$i] ['CodigoCliente'] = $cpfFormatado;
			$dadosPedido [$i] ['CondicaoPagamento'] = 'COMPRAS'; //Validar			
			$dadosPedido [$i] ['ValorPedido'] = number_format($d->subtotal, 2, '.', ',');
			$dadosPedido [$i] ['ValorFrete'] = number_format($d->shipping_amount, 2, '.', ',');
			$dadosPedido [$i] ['ValorDesconto'] = number_format($d->discount_amount, 2, '.', ',');
			$dadosPedido [$i] ['ValorEncargos'] = '0.00';
			$dadosPedido [$i] ['ValorEmbalagemPresente'] = '0.00';
			$dadosPedido [$i] ['ValorReceberEntrega'] = '0.00';
			$dadosPedido [$i] ['ValorTrocoEntrega'] = '0.00';
			
			//Tratamento específico pra data
			list($data, $hora) = explode(' ', $infosAdicionaisPedido->created_at);		
			list($ano, $mes, $dia) = explode('-', $data);
			$dataFormatada = $dia.$mes.$ano.' '.$hora;
			
			$dadosPedido [$i] ['DataVenda'] = $dataFormatada;
			$dadosPedido [$i] ['Transportadora'] = $infosAdicionaisPedido->axado_tnt;
			$dadosPedido [$i] ['EmitirNotaSimbolica'] = 0; //Boolean
			$dadosPedido [$i] ['Lote'] = 1; // Cadastrar um Padrão KPL
			$dadosPedido [$i] ['DestNome'] = $infosAdicionaisPedido->shipping_address->firstname . ' ' . $infosAdicionaisPedido->shipping_address->lastname ;
			$dadosPedido [$i] ['DestSexo'] = $sexoCliente;
			$dadosPedido [$i] ['DestEmail'] = $infosAdicionaisPedido->customer_email;
			$dadosPedido [$i] ['DestTelefone'] = $infosAdicionaisPedido->shipping_address->telephone;
			
			// Dados do Endereço
			list($dadosPedido [$i] ['DestLogradouro'],
				 $dadosPedido [$i] ['DestNumeroLogradouro'],
				 $dadosPedido [$i] ['DestComplementoEndereco'],
				 $dadosPedido [$i] ['DestBairro']
			) = explode("\n", $infosAdicionaisPedido->shipping_address->street);
			
			
			/*$dadosPedido [$i] ['DestMunicipio'] = $infosAdicionaisPedido->billing_address->city;
			$dadosPedido [$i] ['DestEstado'] = $infosAdicionaisPedido->shipping_address->region;
			$dadosPedido [$i] ['DestCep'] = $cepEntregaFormatado;
			$dadosPedido [$i] ['DestTipoLocalEntrega'] = 'tleeDesconhecido';
			$dadosPedido [$i] ['DestEstrangeiro'] = '';
			$dadosPedido [$i] ['DestPais'] = $infosAdicionaisPedido->shipping_address->country_id;
			$dadosPedido [$i] ['DestCPF'] = $cpfFormatado;
			$dadosPedido [$i] ['DestTipoPessoa'] = $tipoPessoa;
			$dadosPedido [$i] ['DestDocumento'] = $cpfFormatado;
			$dadosPedido [$i] ['DestInscricaoEstadual'] = '';
			$dadosPedido [$i] ['DestReferencia'] = "";
			$dadosPedido [$i] ['PedidoJaPago'] = 1; //Boolean
			$dadosPedido [$i] ['DataDoPagamento'] = '';
			$dadosPedido [$i] ['OptouNFPaulista'] = ''; //Necessário verificar essa opção
			//$dadosPedido [$i] ['CartaoPresenteBrinde'] = 1;
			
			// Formas de pagamento devem ser tratadas caso a caso
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['FormaPagamentoCodigo'] = $infosAdicionaisPedido->payment->method;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['Valor'] = $infosAdicionaisPedido->payment->amount_ordered;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNumero'] = $infosAdicionaisPedido->payment->cc_number_enc;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCodigoSeguranca'] = $infosAdicionaisPedido->payment->cc_last4;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoValidade'] = $infosAdicionaisPedido->payment->cc_exp_month. '/' .$infosAdicionaisPedido->payment->cc_exp_year;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNomeImpresso'] = $infosAdicionaisPedido->payment->cc_owner;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoQtdeParcelas'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCodigoAutorizacao'] = ''; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoVencimento'] = ''; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoNumeroBancario'] = ''; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCPFouCNPJTitular'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoDataNascimentoTitular'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaNumeroBanco'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaCodigoAgencia'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVCodigoAgencia'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaContaCorrente'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['PreAutorizadaNaPlataforma'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoTID'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNSU'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNumeroToken'] = 1; // Necessário integrar API pagar.me
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CodigoTransacaoGateway'] = 1; // Necessário integrar API pagar.me
			// Itens
			foreach ($infosAdicionaisPedido->items as $it => $item){
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['CodigoProduto'] = $item->sku;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['QuantidadeProduto'] = (int) $item->qty_ordered;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitario'] = $item->original_price;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['EmbalagemPresente'] = '';
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['MensagemPresente'] = $item->gift_message_available;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['PrecoUnitarioBruto'] = $item->price;
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['Brinde'] = '';
				$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] [$it] ['ValorReferencia'] = '';
			}*/
			
			try {
				
				echo "Criando pedido " . $dadosPedido [$i] ['NumeroDoPedido'] . PHP_EOL;
				$this->_kpl->cadastraPedido( $dadosPedido );
				echo "Pedido importado" . PHP_EOL;
				
				$order = Mage::getModel('sales/order')->loadByIncrementId($dadosPedido [$i] ['NumeroDoPedido']);
				$state = 'processing';
				$status = 'Em Separação'; //status criado por nós anteriormente.
				$comment = '';
				$order->setState($state, $status, $comment, false);
				$order->save();
				
				
				
			} catch (Exception $e) {
				echo "Erro ao importar pedido " . $dadosPedido [$i] ['NumeroDoPedido'] . PHP_EOL;
				throw new RuntimeException('Erro: ' . $e->getMessage());
			}
			
		}
		
		var_dump($arrayClientes);
	}
	
}