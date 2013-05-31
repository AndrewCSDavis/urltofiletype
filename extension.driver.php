<?php

	Class Extension_URLtoPDF extends Extension {

		public function about() {
			return array(
				'name' => 'URL to PDF',
				'version' => '0.1',
				'release-date' => '2011-07-13',
				'author' => array(
					'name' => 'Andrew Davis',
					'email' => 'tudor50rise@hotmail.co.uk'
				),
				'description' => 'Uses the MPDF library to take your HTML page and output it as a PDF'
			);
		}
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendOutputPostGenerate',
					'callback' => 'generatePDFfromURL'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'preferences'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostGenerate',
					'callback'=> 'postedPreferences'
				)
			);
		}
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('Blueprints'),
					'name'		=> __('URL to PDF'),
					'link'		=> '/preferences/'
				)
			);
		}
	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/

		/**
		 * Generate a PDF from a complete URL
		 */
		 
		public function generatePDFfromURL(array &$context = null) {
			$page_data = Frontend::Page()->pageData();

			if(!isset($page_data['type']) || !is_array($page_data['type']) || empty($page_data['type'])) return;

			foreach($page_data['type'] as $type) {
				if($type == 'pdf') 
				{
					$this->generatePDF($context['output']);
				}
				else if($type == 'pdf-attachment') 
				{
					// Page has the 'pdf-attachment' type set, so lets generate some attachments
					$this->generatePDFAttachments($context['output']);
					
				}
			}
		}
		public function postedPreferences( $page , array &$context = null)
		{
				$file = MANIFEST.'/pdf.config.php';
				$check = file_exists($file);
				if($check === true)
				{
						Symphony::Configuration()->flush();
						if(isset($_POST['template-css']))
						{			
								
								Symphony::Configuration()->set("path",$_POST['template-css'],"style");
								Symphony::Configuration()->write($file,'755');
						}
						if(isset($_POST['template-file']))
						{
								Symphony::Configuration()->set("path",$_POST['template-file'],"template");
								Symphony::Configuration()->write($file,'755');
						}
				}	
		}
		public function generatePDF($output) 
		{
			require_once(EXTENSIONS . '/urltopdf/lib/MPDF56/mpdf.php');
			$params = Frontend::Page()->_param;
			$pdf=new mPDF();
			$pdf->SetTitle('Invoice');
			$file = MANIFEST.'/pdf.config.php';
			include($file);
			$check = file_exists($file);
			if($check === true)
			{
					if($settings['style']['path'] != ''){
					$path = $settings['style']['path'];
					}else
					{
					$path = $settings['style']['default'];
					}
			}
			else
			{
					$path = EXTENSIONS.'/urltopdf/assets/css/default.css';
			}
			$stylesheet = file_get_contents($path);
			$pdf->WriteHTML($stylesheet,1);
			$pdf->WriteHTML($output);
			$pdf->Output(sprintf('%s - %s', $params['website-name'], $params['page-title']), 'I');

		}
		public function generatePDFAttachments(&$output) 
		{
					require_once(EXTENSIONS . '/urltopdf/lib/MPDF56/mpdf.php');
					$params = Frontend::Page()->_param;

					$dom = new DOMDocument('1.0', 'UTF-8');
					$doc->formatOutput = true;
					
					libxml_use_internal_errors(true);
					$dom->loadHTML($output);
					libxml_clear_errors();
					
					if($dom === false) return $output;
					$xpath = new DOMXPath($dom);
					$css = '';
					$styling = $xpath->query('//link[@rel="stylesheet"] | //style[@type="text/css"]');
					if($styling->length !== 0) foreach($styling as $style) 
					{
						$css .= $dom->saveXML($style);
					}
					// access query string for switch between in browser pdf and force download pdf
					$blocks = $xpath->query('//*[@data-utp = "attachment"]');
					
					if($blocks->length !== 0) foreach($blocks as $block) 
					{
						$data = $dom->saveXML($block);
						$data = $css . $data;
						$pdf = new mPdf();
						$html = $pdf->writeHTML($data);	
						$filename1 = md5(sprintf('%s - %s', $params['website-name'], $params['page-title']));
					}
					if($_GET['download'] == '')
					{
						$pdf->Output($filename1, 'D');
					}else
					{
						$pdf->Output($filename1, 'I');
					}
		}
		
	}
