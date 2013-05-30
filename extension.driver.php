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
						include($file);
						
						var_dump($preferences);	
						//$preferences['style']['path'] = $output;
						//$preferences
				}	
		}
		public function generatePDF($output) 
		{
			// generatePDF($output,$csspath)
			// possibilities of tying in admin preferences to choose a css style sheet for pdf's
			// by including the chosen path from the css sheet to this function 
			require_once(EXTENSIONS . '/urltopdf/lib/MPDF56/mpdf.php');
			$params = Frontend::Page()->_param;
			$pdf=new mPDF();
			$pdf->SetTitle('Invoice');
			$file = MANIFEST.'/pdf.config.php';
				$check = file_exists($file);
				if($check === true)
				{
						include($file);
						
						//var_dump($preferences);
						$path = $preferences['style']['path'];
				}
				else
				{
						$path = EXTENSIONS.'/urltopdf/assets/css/default.css';
				}
			//and outputting the css style sheet path in the following $stylesheet line
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
					$dom->loadHTML($output);
					if($dom === false) return $output;
					$xpath = new DOMXPath($dom);
					// Copy any <link rel='stylesheet'/> or <style type='text/css'> prepend to the blocks
					$css = '';
					$styling = $xpath->query('//link[@rel="stylesheet"] | //style[@type="text/css"]');
					if($styling->length !== 0) foreach($styling as $style) 
					{
						$css .= $dom->saveXML($style);
					}
					// Find anything with @data-utp attribute set to attachment
					$blocks = $xpath->query('//*[@data-utp = "attachment"]');
					if($blocks->length !== 0) foreach($blocks as $block) {
						// Get the content in those blocks
						$data = $dom->saveXML($block);
						// Send the block to the PDF generator, saving it in /TMP
						$data = $css . $data;
						$pdf=new mPDF();
						// output the HTML content
						$pdf->writeHTML($data, true, false, true, false, '');
						// get the output of the PDF as a string and save it to a file
						// attempt to find the filename if it's provided with @data-utp-filename
						if(!$filename = $xpath->evaluate('string(//@data-utp-filename)')) {
							 $filename = md5(sprintf('%s - %s', $params['website-name'], $params['page-title']));
						}
						$filename = TMP . '/' . Lang::createFilename($filename) . '.pdf';
						General::writeFile($filename, $pdf->Output($filename, 'S'), Symphony::Configuration()->get('write_mode', 'file'));
						// Replace the attachment node with <link rel='attachment' href='{path/to/file}' />
						$link = $dom->createElement('link');
						$link->setAttribute('rel', 'attachment');
						$link->setAttribute('href', str_replace(DOCROOT, URL, $filename));
						$block->parentNode->replaceChild($link, $block);
					}

					$output = $dom->saveHTML();
		}
	}
