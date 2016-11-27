<?php
use Mockery\Exception\RuntimeException;
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
			
			//Manipulando dados para cadastro/atualização de cliente 
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Email'] = $d->email;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['CPFouCNPJ'] = $d->customer_taxvat;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Codigo'] = $d->customer_taxvat;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['TipoPessoa'] = $d->CodigoProdutoAbacos; //Validar PF ou PJ
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Documento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Nome'] = $d->firstname.' '.$d->lastname;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['NomeReduzido'] = $d->firstname;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Sexo'] = ($d->customer_gender == '1')? 'Masculino':'Feminino';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataNascimento'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Telefone'] = $d->telephone;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Celular'] = '';
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['DataCadastro'] = 11;
			// Dados do Endereço
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Logradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['NumeroLogradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ComplementoEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Bairro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Municipio'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Estado'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Cep'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['TipoLocalEntrega'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['ReferenciaEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['Endereco'] ['Pais'] = 1;						
			// Dados do Endereço de Cobrança
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Logradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['NumeroLogradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ComplementoEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Bairro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Municipio'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Estado'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Cep'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['TipoLocalEntrega'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['ReferenciaEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndCobranca'] ['Pais'] = 1;			
			// Dados do Endereço de Entrega
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Logradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['NumeroLogradouro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ComplementoEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Bairro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Municipio'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Estado'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Cep'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['TipoLocalEntrega'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['ReferenciaEndereco'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['EndEntrega'] ['Pais'] = 1;			
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['ClienteEstrangeiro'] = 1;
			$dadosCliente [$i] ['Cliente'] ['DadosClientes'] ['RegimeTributario'] = 1;

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
			$dadosPedido [$i] ['NumeroDoPedido'] = 1;
			$dadosPedido [$i] ['Email'] = 1;
			$dadosPedido [$i] ['CPFouCNPJ'] = 1;
			$dadosPedido [$i] ['ValorPedido'] = 1;
			$dadosPedido [$i] ['ValorFrete'] = 1;
			$dadosPedido [$i] ['ValorEncargos'] = 1;
			$dadosPedido [$i] ['ValorDesconto'] = 1;
			$dadosPedido [$i] ['ValorEmbalagemPresente'] = 1;
			$dadosPedido [$i] ['ValorReceberEntrega'] = 1;
			$dadosPedido [$i] ['ValorTrocoEntrega'] = 1;
			$dadosPedido [$i] ['DataVenda'] = 1;
			$dadosPedido [$i] ['Transportadora'] = 1;
			$dadosPedido [$i] ['EmitirNotaSimbolica'] = 0; //Boolean
			$dadosPedido [$i] ['Lote'] = 1; // Cadastrar um Padrão KPL
			$dadosPedido [$i] ['DestNome'] = 1;
			$dadosPedido [$i] ['DestSexo'] = 1;
			$dadosPedido [$i] ['DestEmail'] = 1;
			$dadosPedido [$i] ['DestTelefone'] = 1;
			$dadosPedido [$i] ['DestLogradouro'] = 1;
			$dadosPedido [$i] ['DestNumeroLogradouro'] = 1;
			$dadosPedido [$i] ['DestComplementoEndereco'] = 1;
			$dadosPedido [$i] ['DestBairro'] = 1;
			$dadosPedido [$i] ['DestMunicipio'] = 1;
			$dadosPedido [$i] ['DestEstado'] = 1;
			$dadosPedido [$i] ['DestCep'] = 1;
			$dadosPedido [$i] ['DestTipoLocalEntrega'] = 1;
			$dadosPedido [$i] ['DestEstrangeiro'] = 1;
			$dadosPedido [$i] ['DestPais'] = 1;
			$dadosPedido [$i] ['DestCPF'] = 1;
			$dadosPedido [$i] ['DestTipoPessoa'] = 1;
			$dadosPedido [$i] ['DestDocumento'] = 1;
			$dadosPedido [$i] ['DestInscricaoEstadual'] = 1;
			$dadosPedido [$i] ['DestReferencia'] = 1;
			$dadosPedido [$i] ['PedidoJaPago'] = 1;
			$dadosPedido [$i] ['DataDoPagamento'] = 1;
			$dadosPedido [$i] ['OptouNFPaulista'] = 1;
			$dadosPedido [$i] ['CartaoPresenteBrinde'] = 1;
			// Formas de pagamento
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['FormaPagamentoCodigo'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['Valor'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNumero'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCodigoSeguranca'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoValidade'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNomeImpresso'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoQtdeParcelas'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCodigoAutorizacao'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoVencimento'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['BoletoNumeroBancario'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoCPFouCNPJTitular'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoDataNascimentoTitular'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaNumeroBanco'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaCodigoAgencia'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVCodigoAgencia'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaContaCorrente'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['PreAutorizadaNaPlataforma'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['DebitoEmContaDVContaCorrente'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoTID'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNSU'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CartaoNumeroToken'] = 1;
			$dadosPedido [$i] ['FormasDePagamento'] ['DadosPedidosFormaPgto'] ['CodigoTransacaoGateway'] = 1;
			// Itens
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['CodigoProduto'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['QuantidadeProduto'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['PrecoUnitario'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['EmbalagemPresente'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['MensagemPresente'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['PrecoUnitarioBruto'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['Brinde'] = 1;
			$dadosPedido [$i] ['Itens'] ['DadosPedidosItem'] ['ValorReferencia'] = 1;
			
			
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