<?php

class TemplateFile {
	
	private $content;
	
	public function FinalTemplate
		(	
			$Header=null , $HtmlDir=null ,  $HtmlTitle=null ,  $RtlCss=null ,  $drawBackground=null , 
			$drawLogo=null , $drawPaidWatermark=null , $drawInvoiceType=null , $drawInvoiceInfo=null , $drawReturnAddress=null ,  $drawAddress=null , 
			$drawLineHeader=null , $drawInvoice=null , $SubTotals=null , $Taxes=array() , $Totals=null , $PublicNotes=null , $drawPayments=null , 
			$drawTerms=null , $Footer=null , $PrintBtn=null , $DownloadBtn=null , $PaymentBtn=null 
		) 
	{
		/*
		*
		NOTE , The $Header include all the header 
		*
		*/
		$paid_watermark = '
				<button type="button" class="btn btn-success btn-lg active" >
					<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> '. Language::_("HtmlInvoice.watermark_paid", true).'
				</button>';
		$unpaid_watermark = '
				<button type="button" class="btn btn-danger btn-lg active" >
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> '. Language::_("HtmlInvoice.watermark_unpaid", true).'
				</button>';
		$download_btn = '
				<button type="button" class="btn btn-info btn-lg " onclick="window.location.href=\''.$_SERVER['REQUEST_URI'].'/pdf/\'"> 
					<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> '. Language::_("HtmlInvoice.download_invoice", true).'
				</button>';
		$print_btn = '
				<button type="button" class="btn btn-info btn-lg" onclick="javascript:window.print();" >
				  <span class="glyphicon glyphicon-print" aria-hidden="true"></span> '. Language::_("HtmlInvoice.print_invoice", true).'
				</button>';
		$payment_btn = '
				<button type="button" class="btn btn-warning btn-lg" >
				  <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> '. Language::_("HtmlInvoice.pay_invoice", true).'
				</button>';
		$draw_terms = '
				<div class="row">
					<div class="col-xs-12">
						<h3><span class="label label-default">'. Language::_("HtmlInvoice.terms_heading", true) .'</span></h3>
						
						<div class="well well-sm">'. nl2br($drawTerms) .'</div>						
					</div>
				</div>'; 
				
		$taxe_line = "";
		
		foreach ($Taxes as $Taxe) {
			$taxe_line .='
				<tr>
					<th></th>
					<th colspan="2" class="warning text-right"><h4>'. $Taxe['label'] .'</h4></th>
					<th class="warning text-right"><h4>'. $Taxe['value'] .'</h4></th>
				</tr>'; 
		}	
				
		$content = '
		<!doctype html>
		<html dir="'. $HtmlDir .'">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0">	
				<title>'. $HtmlTitle .' </title>				
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
				'. (Language::_("AppController.lang.dir", true) == "rtl" ? '<link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/master/dist/cdnjs/3.3.1/css/bootstrap-rtl.min.css">' : '' )  .'
				<style>
					'. $drawBackground .'
					'. $RtlCss .'
					header {margin-top: 70px;}
				</style>				
			</head>
			<body>
				<div class="container">				
					<header class="header">
						<div class="row">
							<div class="col-xs-6">
								'. $drawLogo .'								
							</div>
							<div class="col-xs-6 text-right flip ">
								<h1>'. $drawInvoiceType .'</h1>
								'. nl2br($drawInvoiceInfo) .'
							</div>
						</div>
						<div class="row">
							<div class="col-xs-5">
								'. $drawReturnAddress .' 
							</div>
							<div class="col-xs-5 col-xs-offset-2 text-right">
								'. $drawAddress .'
							</div>
						</div>
						<!-- / end client details section -->
						<div class="row">
								<div class="col-xs-6 ">									
									'. ($PaymentBtn ? $payment_btn : "" ) .'
									'. ($PrintBtn ? $print_btn : "" ) .'
									'. ($DownloadBtn ? $download_btn : "" ) .'							
								</div>						
								<div class="col-xs-6 text-right">									
									'. ($drawPaidWatermark ? $paid_watermark : $unpaid_watermark ) .'									
								</div>
						</div>
						
					</header>
					<div class="content">

						
						<table class="table table-hover table-bordered">
							<thead>
								<tr>'. $drawLineHeader .'</tr>
							</thead>
							<tbody>
								'. $drawInvoice .'
							</tbody>
							<tfoot>
								<tr>
									<th></th>
									<th colspan="2" class="active text-right"><h4>'. Language::_("HtmlInvoice.subtotal_heading", true) .'</h4></th>
									<th class="active text-right"><h4>'. $SubTotals .'</h4></th>
								</tr>
								'. $taxe_line .'
								<tr>
									<th></th>
									<th colspan="2" class="info text-right"><h4>'. Language::_("HtmlInvoice.total_heading", true)  .'</h4></th>
									<th class="info text-right"><h4>'. $Totals .'</h4></th>
								</tr>								
							</tfoot>							
							
							
						</table>
						
						<div class="row ">
							<div class="col-xs-2 col-xs-offset-6">
								
							</div>
							<div class="col-xs-4 text-right">

							</div>
						</div>
						
						<div class="row">
							<div class="col-xs-5">
								'. $PublicNotes .'
							</div>
							<div class="col-xs-7">
								'. $drawPayments .'	
							</div>
						</div>
						
					</div>
					<footer class="footer">		
						'. ($drawTerms ? $draw_terms : "" ).'
						'. $Footer .'
					</footer>
					<nav>
						<ul class="pager">
							<li><a href="#"><span aria-hidden="true">&larr;</span> '. Language::_("HtmlInvoice.back", true) .'</a></li>
							<li><a href="'.$_SERVER['REQUEST_URI'].'/pdf/">'. Language::_("HtmlInvoice.download_invoice", true) .'</a></li>
							<li><a href="#">'. Language::_("HtmlInvoice.close", true) .'</a></li>
						</ul>
					</nav>					
				</div>
			</body>
		</html>';
		
		return $content ;
	}
}
?>