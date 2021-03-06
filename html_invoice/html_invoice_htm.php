<?php
/**
 * Default Invoice Template
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates.templates.default
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
// Uses the TcpdfWrapper for rendering the PDF
// Loader::load(COMPONENTDIR . "invoice_templates" . DS . "tcpdf_wrapper.php");
Loader::load(HELPERDIR . "html" . DS . "html.php");

class HtmlInvoiceHtm extends Html {
	/**
	 * @var array The standard number format options
	 */
	private static $standard_num_options = array('prefix'=>false,'suffix'=>false,'code'=>false,'with_separator'=>false);
	/**
	 * @var array An array of meta data for this invoice
	 */
	public $meta = array();
	/**
	 * @var CurrencyFormat The CurrencyFormat object used to format currency values
	 */
	public $CurrencyFormat;
	/**
	 * @var Date The Date object used to format date values
	 */
	public $Date;
	/**
	 * @var array An array of invoice data for this invoice
	 */
	public $invoice = array();

	private $buffer; 
	/**
	 * @param boolean Whether to include the to address or not
	 */
	
	// Change This if you want ***/
	public $include_address = true;
	public $show_print_btn = true;
	public $show_download_btn = true;
	public $show_payment_btn = true;	
	public $show_edit_btn = true;	
	
	
	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8') {	
			
		Language::loadLang("html_invoice", null, dirname(__FILE__) . DS . "language" . DS);	
		Loader::loadModels($this, array("Companies", "Transactions"));
		$company_id = Configure::get("Blesta.company_id");
		$this->company = $this->Companies->get($company_id);
			
		$this->Html = new Html();
		
		$buffer = '';
		$this->rtl = '';		
		
	}
	

	public function Output($template) {	
		
		if (file_exists(dirname(__FILE__) . DS . "template" . DS . $template ) ) {		
			Loader::load(dirname(__FILE__) . DS . "template" . DS . $template );	
			$this->TemplateFile = new TemplateFile();
			print_r(
				$this->TemplateFile->FinalTemplate
				(
					$this->Header() , $this->HtmlDir() , $this->HtmlTitle() , $this->RtlCss() , $this->drawBackground() ,  
					$this->drawLogo() , $this->drawPaidWatermark() , $this->drawInvoiceType() , $this->drawInvoiceInfo() , $this->drawReturnAddress() , $this->drawAddress() , 
					$this->drawLineHeader() , $this->drawInvoice() , $this->SubTotals() , $this->Taxes() , $this->Totals() , $this->PublicNotes() , $this->drawPayments() ,  
					$this->drawTerms() ,  $this->Footer() , $this->PrintBtn() , $this->DownloadBtn() , $this->PaymentBtn() ,  $this->EditBtn()
				)
			);
		}		
		else {
			throw new Exception("Template : " . $template . " not found");
		}	
	}
	
	private function Header() {
			
		$buffer = '
		<html dir="'.  $this->HtmlDir() .'">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0">	
				<title>'. $this->HtmlTitle() .' </title>
				'. $this->RtlCss() .'					
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
				<style>
					'. $this->drawBackground() .'
				</style>				
			</head>';
		  
		return $buffer;
	}

	private function HtmlDir() {
		return Language::_("AppController.lang.dir", true) ? Language::_("AppController.lang.dir", true) : 'ltr';
	}	

	private function HtmlTitle() {
		return Language::_("HtmlInvoice.type_" . $this->invoice->status, true) . " - ". $this->invoice->id_code ." - ". $this->Html->ifSet($this->meta['company_name']) ;
	}

	private function RtlCss() {
		if (Language::_("AppController.lang.dir", true) == "rtl") {
			$buffer = '
				.pull-right{float:left!important}
				.pull-left{float:right!important}
				.text-right {text-align: left;}
				.text-left {text-align: right;}
			';
			
			return $buffer;
		}
	}

	private function drawBackground() {		
		if (file_exists($this->meta['background'])) {
		
			$type = pathinfo($this->meta['background'] , PATHINFO_EXTENSION);
			$data = file_get_contents($this->meta['background']);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);		
			
			$buffer = '
			.container{
				background-image:url('. $base64 .');
				background-size: cover;
				background-repeat: no-repeat;
				background-position: center center;					
			}';
			
			return $buffer;
		}		
		
	}
	
	private function drawLogo() {
		if ($this->meta['display_logo'] == "true" && file_exists($this->meta['logo'])){
		
			$type = pathinfo($this->meta['logo'] , PATHINFO_EXTENSION);
			$data = file_get_contents($this->meta['logo']);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);		
		
			return '<img src="'. $base64 .'" />';
		} 		
	}	
	
	private function drawInvoiceType() {
		return Language::_("HtmlInvoice.type_" . $this->invoice->status, true) ;
	}

	private function drawInvoiceInfo() {	
	
		$buffer = array() ;
		
		$buffer['invoice_id'] = $this->invoice->id_code ;
		$buffer['invoice_view'] = $this->invoice->id ;
		$buffer['client_id'] = $this->invoice->client->id_code ;
		$buffer['client_view'] = $this->invoice->client->id ;
		$buffer['date_billed'] = $this->Date->cast($this->invoice->date_billed, $this->invoice->client->settings['date_format']) ;
		$buffer['date_due'] = $this->Date->cast($this->invoice->date_due, $this->invoice->client->settings['date_format'])  ;
		
		return $buffer ;

	}

	private function drawTaxId() {		
		if (isset($this->meta['tax_id']) && $this->meta['tax_id'] != "") {		
			return Language::_("HtmlInvoice.tax_id", true) . " ". $this->meta['tax_id']; 
		}
	}
	
	private function drawTaxIdClient() {		
		if (isset($this->meta['tax_id']) && $this->meta['tax_id'] != "") {		
			return Language::_("HtmlInvoice.client_tax_id", true) . " ". $this->invoice->client->settings['tax_id'] ; 
		}
	}	

	private function drawAddress() {
	
		if ($this->include_address) {
		
			$address = "" ;
			if (strlen($this->invoice->billing->company) > 0)
				$address .= $this->invoice->billing->company . "\n";
			$address .= $this->invoice->billing->address1 . "\n";
			if (strlen($this->invoice->billing->address2) > 0)
				$address .= $this->invoice->billing->address2 . "\n";
			$address .= $this->invoice->billing->city . ", " . $this->invoice->billing->state . " " . $this->invoice->billing->zip . " " . $this->invoice->billing->country->name;
					
			$buffer = '
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>'. Language::_("HtmlInvoice.to", true) . $this->invoice->billing->first_name . " " . $this->invoice->billing->last_name .'</h4>
				</div>
				<div class="panel-body">
					<p>
						'. nl2br($address) .'
						'. $this->drawTaxIdClient() .'
					</p>
				</div>
			</div>';
			
			return $buffer ; 
		}
		
	}

	private function drawReturnAddress() {
		if ($this->meta['display_companyinfo'] == "true") {
			$buffer = '
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4>'. Language::_("HtmlInvoice.from", true) . $this->meta['company_name'] .'</h4>
				</div>
				<div class="panel-body">
					<p>
						'. nl2br($this->meta['company_address']) .'
						'. $this->drawTaxId() .'				
					</p>
				</div>
			</div>';
			
			return $buffer ; 
		}		
	}	

	private function drawPaidWatermark() {
		// Show paid watermark
		if (!empty($this->meta['display_paid_watermark']) && $this->meta['display_paid_watermark'] == "true" && ($this->invoice->date_closed != null)) {			
			return true ;
		}
	}	

	private function drawLineHeader() {
	
		$buffer ="";
		$data = array(
			array(
				'name'=>'name',
				'value'=>Language::_("HtmlInvoice.lines_description", true)
			),
			array(
				'name'=>'qty',
				'value'=>Language::_("HtmlInvoice.lines_quantity", true)
			),
			array(
				'name'=>'unit_price',
				'value'=>Language::_("HtmlInvoice.lines_unit_price", true)
			),
			array(
				'name'=>'price',
				'value'=>Language::_("HtmlInvoice.lines_cost", true)
			)
		);		
		
		foreach ($data as $item) {
			$buffer .= "<th><h4>". $item['value'] ."</h4></th>";
		}
		return $buffer ;
		
	}

	private function drawInvoice() {
		
		$buffer ="";
		// Build line items
		$lines = array();

		for ($i=0; $i<count($this->invoice->line_items); $i++) {
			$lines[] = array(
				'name'=>$this->invoice->line_items[$i]->description,
				'qty'=>$this->CurrencyFormat->truncateDecimal($this->invoice->line_items[$i]->qty, 0),
				'unit_price'=>$this->CurrencyFormat->format($this->invoice->line_items[$i]->amount, $this->invoice->currency, self::$standard_num_options),
				'price'=>$this->CurrencyFormat->format($this->invoice->line_items[$i]->total, $this->invoice->currency, self::$standard_num_options),
			);
		}
		
		foreach ($lines as $item ) {
			$buffer .= '
			<tr>
				<th><h4>'. $item['name'] .'</h4></th>
				<th><h4>'. $item['qty'] .'</h4></th>
				<th class="text-right"><h4>'. $item['unit_price'] .'</h4></th>
				<th class="text-right"><h4>'. $item['price'] .'</h4></th>			
			</tr>';
		}
		
		return $buffer ;

	}	

	private function PublicNotes() {		
		// Draw notes
		if (!empty($this->invoice->note_public)) {
			$buffer ='	
			<div class="panel panel-info">
				<div class="panel-heading">
					'. Language::_("HtmlInvoice.notes_heading", true) .'
				</div>
				<div class="panel-body">
					<p>'. nl2br($this->invoice->note_public).'</p>
				</div>
			</div>';
			
			return $buffer ;
		}
	}

	private function SubTotals() {

		// $buffer = Language::_("HtmlInvoice.subtotal_heading", true) ." : ". $this->CurrencyFormat->format($this->invoice->subtotal, $this->invoice->currency, self::$standard_num_options) ."\n";
	
		return $this->CurrencyFormat->format($this->invoice->subtotal, $this->invoice->currency, self::$standard_num_options) ;
		
	}
	
	private function Taxes() {

		$buffer = array();
		$i = 0;
		foreach ($this->invoice->taxes as $tax ) {
			$buffer[$i]['label'] =   Language::_("HtmlInvoice.tax_heading", true, $tax->name, $tax->amount) ;
			$buffer[$i]['value'] =  $this->CurrencyFormat->format($tax->tax_total, $this->invoice->currency, self::$standard_num_options) ;
			$i++;
		}
		
		return $buffer ;
		
	}
	
	private function Totals() {
		
		// $buffer = Language::_("HtmlInvoice.total_heading", true)  ." : ".  $this->CurrencyFormat->format($this->invoice->total, $this->invoice->currency) ."\n";

		return $this->CurrencyFormat->format($this->invoice->total, $this->invoice->currency)  ;
		
	}	

	private function drawPayments() {
		if (!empty($this->meta['display_payments']) && $this->meta['display_payments'] == "true") {
			// Set the payment rows
			$rows = array();
			$payment = '';
			
			for ($i=0; $i<count($this->invoice->applied_transactions); $i++) {
				// Only show approved transactions
				if ($this->invoice->applied_transactions[$i]->status != "approved")
					continue;
				
				$rows[] = array(
					'applied_date' => $this->Date->cast($this->invoice->applied_transactions[$i]->applied_date, $this->invoice->client->settings['date_format']),
					'type_name' => $this->invoice->applied_transactions[$i]->type_real_name,
					'transaction_id' =>$this->invoice->applied_transactions[$i]->transaction_id,
					'applied_amount' => $this->CurrencyFormat->format($this->invoice->applied_transactions[$i]->applied_amount, $this->invoice->applied_transactions[$i]->currency, self::$standard_num_options)
				);
			}
			
			// Don't draw the table if there are no payments
			if (empty($rows))
				return "";
				
			foreach ($rows as $item ) {
				$payment .= '
				<tr>
					<th>'. $item['applied_date'] .'</th>
					<th>'. $item['type_name'] .'</th>
					<th>'. $item['transaction_id'] .'</th>
					<th class="text-right">'. $item['applied_amount'] .'</th>			
				</tr>';
			}
			
			// Set balance due at bottom of table
			$class = ($this->invoice->due == "0" ? "" : "danger") ;
			$balance = '
				<tr >
					<th colspan="3" class="text-right" >'. Language::_("HtmlInvoice.balance_heading", true) .'</th>
					<th class="'. $class .' text-right">'. $this->CurrencyFormat->format($this->invoice->due, $this->invoice->currency) .'</th>			
				</tr>';	

			
			$buffer = '	
			<div class="panel panel-success">
				<div class="panel-heading">
					'. Language::_("HtmlInvoice.payments_heading", true) .'
				</div>
				<div class="panel-body">
					<table class="table table-hover table-bordered table-condensed ">
						<thead>
							<tr>
								<th>'. Language::_("HtmlInvoice.payments_applied_date", true) .'</th>
								<th>'. Language::_("HtmlInvoice.payments_type_name", true) .'</th>
								<th>'. Language::_("HtmlInvoice.payments_transaction_id", true) .'</th>
								<th>'. Language::_("HtmlInvoice.payments_applied_amount", true) .'</th>
							</tr>
						</thead>
						<tbody>
							'. $payment .'
						</tbody>
						<tfoot>
							'. $balance .'
						</tfoot>
					</table>	
				</div>
			</div>';
			
			return $buffer ;
		}
	}

	private function drawTerms() {
		if (!empty($this->meta['terms'])){
			return $this->meta['terms'] ;
		}
	}
	
	private function PrintBtn() {
		if ($this->show_print_btn){
			return true;	
		}
	}	
	
	private function DownloadBtn() {
		if ($this->show_download_btn){
			return true;	
		}
	}

	private function PaymentBtn() {
		if ( ($this->show_payment_btn) && (empty($this->invoice->date_closed) )){
			return true;	
		}
	}	
	
	private function EditBtn() {
		if ( ($this->show_edit_btn) && (empty($this->invoice->date_closed) )){
			return true;	
		}
	}
	
	private function Footer() {
			$footer = "" ;			
			// $footer .= $this->Html->ifSet($this->company->phone) . "\n";
			// $footer .= $this->Html->ifSet($this->company->phax) . "\n";
			// $footer .= $this->Html->ifSet($this->company->hostname) . "\n";
			
		return $footer ;
	}	
	
 
}
?>