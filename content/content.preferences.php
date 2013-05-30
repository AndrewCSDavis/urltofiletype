<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.pagemanager.php');

	class contentExtensionURLtoPDFPreferences extends AdministrationPage 
	{
		protected $driver;

		public function __viewIndex($context) 
		{
			
					
						$page = $this->Symphony.Context;
						//var_dump($page);
						$path = scandir(EXTENSIONS.'/urltopdf/assets/css');
						array_shift($path);
						$arraydiffs = array('.','..');
						$arrs = array_diff($path,$arraydiffs);
						foreach($arrs as $dirs => $key)
						{
									$v = explode('.',$key);
									array_pop($v);
									if(array_count_values($v) > 1)
									{
											$a = implode('.',$v);
											$imp[] = array(EXTENSIONS.'/urltopdf/assets/css/'.$key,'0',$a);
									}
									else
									{
											$a = implode($v);
											$imp[] = array(EXTENSIONS.'/urltopdf/assets/css/'.$key,'0',$a);
									}
						}

						$this->setPageType('form');
						$form = new XMLElement('form');
						$this->appendSubheading(__('URL to PDF')); 
						$container = new XMLElement('fieldset');
						$container->setAttribute('class', 'settings');
						$group = new XMLElement('div');
						$group->setAttribute('class', 'two columns');
						$container->appendChild($group);
						$check = file_exists(MANIFEST.'/pdf.config.php');
						if($check === false)
						{
							$alert = new XMLElement('div');
							$alert->setValue('Message:');
							$msg = new XMLElement('h3');
							$msg->setValue('You must First create the pdf.config.php file in the manifest folder, at the moment the default.css has been selected');
							$alert->appendChild($msg);
							$this->Form->appendChild($alert);
						}		
						$this->Form->appendChild($container);
						$div = new XMLElement('div');
						$div->setAttribute('class', 'actions');
						$sellabel = new XMLElement('label');
						$sellabel->setValue('PDF css file');
						$sellabel->appendChild(Widget::Select('template-css',$imp));
						$container->appendChild($sellabel);
						$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit'));
						$this->Form->appendChild($div);
						
			
		}

	

		
	}
