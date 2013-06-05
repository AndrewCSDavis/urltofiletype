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
					'callback' => 'generateTYPEfromURL'
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
		 
		public function generateTYPEfromURL(array &$context = null) {
			$page_data = Frontend::Page()->pageData();
			if(EXTENSIONS.'/urltopdf/lib/MPDF56/tmp' == false){
			$director_created = mkdir(EXTENSIONS.'/urltopdf/lib/MPDF56/tmp');
			}
			if(!isset($page_data['type']) || !is_array($page_data['type']) || empty($page_data['type'])) return;

			foreach($page_data['type'] as $type) {
				if($type == 'pdf') 
				{
						$this->generatePDF($context['output']);
				}elseif($type == 'doc')
				{
					$this->generateDOC($context['output']);
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
					$bool = array_key_exists('style',$settings);
					if($bool == true)
					{
							$path = $settings['style']['path'];
					}else
					{
							$path = EXTENSIONS.'/urltopdf/assets/css/default.css';
					}			
			}
			else
			{
					$path = EXTENSIONS.'/urltopdf/assets/css/default.css';
			}
			
			$stylesheet = file_get_contents($path);
			$pdf->WriteHTML($stylesheet,1);
			$pdf->WriteHTML($output);
			$_GET['download'] == 'pdf' ?	$m = 'D': $m = 'I';
			header("Content-Disposition: inline");
			$pdf->Output($params['website-name'].'_'.$params['page-title'], $m);
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
		public function generateDOC($output)
		{
								/**
				*  Example of use of HTML to docx converter
				*/
				$params = Frontend::Page()->_param;
				// Load the files we need:
				require_once 'lib/HTMLtoDOCX/phpword/PHPWord.php';
				require_once 'lib/HTMLtoDOCX/simplehtmldom/simple_html_dom.php';
				require_once 'lib/HTMLtoDOCX/htmltodocx_converter/h2d_htmlconverter.php';
				require_once 'lib/HTMLtoDOCX/example_files/styles.inc';

				// HTML fragment we want to parse:
				 
				// New Word Document:
				$phpword_object = new PHPWord();
				$section = $phpword_object->createSection();

				// HTML Dom object:
				$html_dom = new simple_html_dom();
				$html_dom->load('<html><body>' . $output. '</body></html>');
				// Note, we needed to nest the html in a couple of dummy elements.

				// Create the dom array of elements which we are going to work on:
				$html_dom_array = $html_dom->find('html',0)->children();

				// Provide some initial settings:
				$initial_state = array(
				  // Required parameters:
				  'phpword_object' => &$phpword_object, // Must be passed by reference.
				  'base_root' => 'http://test.local', // Required for link elements - change it to your domain.
				  'base_path' => '/htmltodocx/', // Path from base_root to whatever url your links are relative to.
				  
				  // Optional parameters - showing the defaults if you don't set anything:
				  'current_style' => array('size' => '11'), // The PHPWord style on the top element - may be inherited by descendent elements.
				  'parents' => array(0 => 'body'), // Our parent is body.
				  'list_depth' => 0, // This is the current depth of any current list.
				  'context' => 'section', // Possible values - section, footer or header.
				  'pseudo_list' => TRUE, // NOTE: Word lists not yet supported (TRUE is the only option at present).
				  'pseudo_list_indicator_font_name' => 'Wingdings', // Bullet indicator font.
				  'pseudo_list_indicator_font_size' => '7', // Bullet indicator size.
				  'pseudo_list_indicator_character' => 'l ', // Gives a circle bullet point with wingdings.
				  'table_allowed' => TRUE, // Note, if you are adding this html into a PHPWord table you should set this to FALSE: tables cannot be nested in PHPWord.
				  'treat_div_as_paragraph' => TRUE, // If set to TRUE, each new div will trigger a new line in the Word document.
					  
				  // Optional - no default:    
				  'style_sheet' => htmltodocx_styles_example(), // This is an array (the "style sheet") - returned by htmltodocx_styles_example() here (in styles.inc) - see this function for an example of how to construct this array.
				  );    

				// Convert the HTML and put it into the PHPWord object
				htmltodocx_insert_html($section, $html_dom_array[0]->nodes, $initial_state);

				// Clear the HTML dom object:
				$html_dom->clear(); 
				unset($html_dom);

				// Save File
				$h2d_file_uri = tempnam('', 'htd');
				$objWriter = PHPWord_IOFactory::createWriter($phpword_object, 'Word2007');
				$objWriter->save($h2d_file_uri);

				// Download the file:
				
				if($_GET['download'] == 'docx')
				{
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header("Content-Disposition: attachment; filename=".$params['website-name'].'_'.$params['page-title'].".docx");
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize($h2d_file_uri));
					ob_clean();
					flush();
					$status = readfile($h2d_file_uri);
					unlink($h2d_file_uri);
					exit;
				}
				else
				{
					header("Content-Disposition: inline");
					//header('Content-type: application/msword');
					//$status = readfile($h2d_file_uri);
					$status =  file_get_html($h2d_file_uri)->plaintext; 
					//exit;
				}
				
						
		}
	}
