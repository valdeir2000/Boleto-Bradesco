<?php
class ControllerPaymentBoletobradesco extends Controller {
	protected function index() {
		$this->language->load('payment/boleto_bradesco');
		
		$this->data['text_instruction'] = $this->language->get('text_instruction');
		$this->data['text_instruction2'] = $this->language->get('text_instruction2');
		$this->data['text_bank'] = $this->language->get('text_bank');
		$this->data['text_payment'] = $this->language->get('text_payment');
		$this->data['text_linkboleto'] = $this->language->get('text_linkboleto');
		$this->data['text_linkboleto2'] = $this->language->get('text_linkboleto2');
		
		$this->data['button_continue'] = $this->language->get('button_continue');
		$this->data['button_back'] = $this->language->get('button_back');
		
			
		$this->load->library('encryption');
		
		$encryption = new Encryption($this->config->get('config_encryption'));

		
		$this->data['idboleto'] = $encryption->encrypt($this->session->data['order_id']);
		
		
		//
		$this->data['continue'] = $this->url->link('checkout/success');
		$this->data['back'] = $this->url->link('checkout/checkout', '', 'SSL');
		
		$this->id       = 'payment';
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/boleto_bradesco.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/boleto_bradesco.tpl';
		} else {
			$this->template = 'default/template/payment/boleto_bradesco.tpl';
		}	
		
		$this->render(); 
	}
	
	public function confirm() {
		$this->load->library('encryption');
		
		$encryption = new Encryption($this->config->get('config_encryption'));
		$order_id = $encryption->encrypt($this->session->data['order_id']);
		
		$this->load->language('payment/boleto_bradesco');
		
		$this->load->model('checkout/order');
		
		$codigo_boleto = $order_id;
		
		$comment  = $this->language->get('text_instruction') . "\n\n";
		$comment .= sprintf($this->language->get('text_linkboleto'), $codigo_boleto). "\n\n";
		$comment .= $this->language->get('text_payment');
		
		$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('boleto_bradesco_order_status_id'), $comment);
	}
	
	
	public function callback() {
		$this->load->library('encryption');
		
		$encryption = new Encryption($this->config->get('config_encryption'));
		$order_id = $encryption->decrypt(@$this->request->get['order_id']);
		
		
		$this->load->model('checkout/order');
				
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
if($order_info){
/***************************************************************************
/*                                                                        */
/*                              DADOS LOJISTA                             */
/*                                                                        */
/**************************************************************************/

// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //

// DADOS DA SUA CONTA - Bradesco
$agencia = explode('-', $this->config->get('boleto_bradesco_agencia')); //Captura numero e digito da agencia e separa pelo caracter -
$conta = explode('-', $this->config->get('boleto_bradesco_conta')); //Captura numero e digito da conta e separa pelo caracter -
$numeroAgencia = $agencia[0]; //Numero da Agencia
$digitoAgencia = $agencia[1]; //Digito da Agencia
$numeroConta = $conta[0]; //Numero da Agencia
$digitoConta = $conta[1]; //Digito da Agencia
$dadosboleto["agencia"] = $numeroAgencia; // Num da agencia, sem digito
$dadosboleto["agencia_dv"] = $digitoAgencia; // Digito do Num da agencia
$dadosboleto["conta"] = $numeroAgencia; 	// Num da conta, sem digito
$dadosboleto["conta_dv"] = $digitoConta; 	// Digito do Num da conta

// DADOS PERSONALIZADOS - Bradesco
$contaCedente = explode('-', $this->config->get('boleto_bradesco_conta_cedente')); //Captura numero e digito da conta cedente e separa pelo caracter -
$numeroContaCedente = $contaCedente[0]; //Numero da Agencia
$digitoContaCedente = $contaCedente[1]; //Digito da Agencia
$dadosboleto["conta_cedente"] = $numeroContaCedente; // ContaCedente do Cliente, sem digito (Somente Números)
$dadosboleto["conta_cedente_dv"] = $numeroContaCedente; // Digito da ContaCedente do Cliente
$dadosboleto["carteira"] = $this->config->get('boleto_bradesco_carteira');  // Código da Carteira: pode ser 06 ou 03

// SEUS DADOS
$dadosboleto["identificacao"] = $this->config->get('boleto_bradesco_identificacao');
$dadosboleto["cpf_cnpj"] = $this->config->get('boleto_bradesco_cpf_cnpj');
$dadosboleto["endereco"] = $this->config->get('boleto_bradesco_endereco');
$dadosboleto["cidade_uf"] = $this->config->get('boleto_bradesco_cidade_uf');
$dadosboleto["cedente"] = $this->config->get('boleto_bradesco_cedente');
$dadosboleto["logo"] = $this->config->get('boleto_bradesco_logo');

// INFORMACOES PARA O CLIENTE
$taxa_boleto = $this->config->get('boleto_bradesco_taxa_boleto');
$dadosboleto["demonstrativo1"] = $this->config->get('boleto_bradesco_demonstrativo1')."<br>Taxa banc&aacute;ria - R$ ".number_format($taxa_boleto, 2, ',', '');
$dadosboleto["demonstrativo2"] = $this->config->get('boleto_bradesco_demonstrativo2');
$dadosboleto["demonstrativo3"] = $this->config->get('boleto_bradesco_demonstrativo3');
$dadosboleto["instrucoes1"] = $this->config->get('boleto_bradesco_instrucoes1');
$dadosboleto["instrucoes2"] = $this->config->get('boleto_bradesco_instrucoes2');
$dadosboleto["instrucoes3"] = $this->config->get('boleto_bradesco_instrucoes3');
$dadosboleto["instrucoes4"] = $this->config->get('boleto_bradesco_instrucoes4');

/***************************************************************************
/*                                                                        */
/*                              DADOS CLIENTE                             */
/*                                                                        */
/**************************************************************************/

// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = $this->config->get('boleto_bradesco_dia_prazo_pg');
$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
$valor_cobrado = number_format($order_info['total']*$order_info['currency_value'], 2, ',', '.'); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

$dadosboleto["nosso_numero"] = $order_info['order_id'];  // Nosso numero sem o DV - REGRA: Máximo de 11 caracteres!
$dadosboleto["numero_documento"] = $dadosboleto["nosso_numero"];	// Num do pedido ou do documento = Nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $order_info['payment_firstname']." ".$order_info['payment_lastname'];
$dadosboleto["endereco1"] = $order_info['payment_address_1']." ".$order_info['payment_address_2'];
$dadosboleto["endereco2"] = $order_info['payment_city']."-".$order_info['payment_zone']."- CEP:".$order_info['payment_postcode'];

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = $valor_boleto;
$dadosboleto["aceite"] = $this->config->get('boleto_bradesco_aceite');
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";

// NÃO ALTERAR!
ob_start();
include("boleto/include/funcoes_bradesco.php"); 
$ouput = include("boleto/include/layout_bradesco.php");

//########################FIM CONFIGURAÇÃO DO BOLETO ################################//
}else {
	//erro ao gera boleto
	$ouput = "<script>
       alert(\"Atencao!\\n \\nBoleto bancario nao encontrado!\\n \\nEntre em contato com nosso atendimento.\\n \\nVocê sera redirecionado para a Central do Cliente.\");
 window.location = 'index.php?route=information/contact';
 </script>";  
	
}
		$this->response->setOutput($ouput);
		
		}
	
}
?>