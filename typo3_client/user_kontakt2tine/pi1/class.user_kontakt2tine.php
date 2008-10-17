<?php
/***************************************************************
*  Copyright notice
*
* @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'user_kontakt2tine' for the 'user_kontakt2tine' extension.
 *
 * @author  Matthias Greiling <typo3@metaways.de>
 * @comment this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */


require_once(PATH_tslib . 'class.tslib_pibase.php');

class user_kontakt2tine extends tslib_pibase {
	var $prefixId = 'user_kontakt2tine';		// Same as class name
	var $scriptRelPath = 'pi1/class.user_kontakt2tine.php';	// Path to this script relative to the extension dir.
	var $extKey = 'user_kontakt2tine';	// The extension key.
	
	
	var $templateFile = "typo3conf/ext/user_kontakt2tine/pi1/template1.html";
	var $templateFileWithoutCODE = "typo3conf/ext/user_kontakt2tine/pi1/template.html";
	
	
	var $pathCodeImageFiles = "typo3conf/ext/user_kontakt2tine/pi1/code_image/code_image_files/";
	var $fFieldNames = array('VORNAME','NACHNAME','TELEFON','EMAIL','CODEEINGABE','NACHRICHT','AUSWAHL','FIRMA','STRASSE','PLZ','LAND','ORT','COUNTRY');
	var $fFieldNamesMandatory = array('VORNAME','NACHNAME','EMAIL','CODEEINGABE','NACHRICHT','AUSWAHL');
	
	//Marker - Language Key - Relation
    var $fLabelKeys = array('LABELVORNAME' => 'firstname',
							'LABELNACHNAME' => 'lastname',
							'LABELTELEFON' => 'telephone',
							'LABELEMAIL' => 'e-mail',
							'LABELCODE' => 'code',
							'LABELCODEEINGABE' => 'codeinput',
							'LABELNACHRICHT' => 'kommentar',
							'PFLICHT' => 'pflicht',
							'LABELAUSWAHL' => 'betreff',
							'LABELFIRMA' => 'firma',
							'LABELANREDE' => 'anrede',
							'LABELMOBIL' => 'mobil',
							'LABELFAX' => 'fax',
							'LABELSTRASSE' => 'address',
							'LABELPLZ' => 'zip',
							'LABELLAND' => 'country',
							'LABELORT' => 'city'								
							);
							
	var $empfaenger;
	var $betreff = '';
	var $nachricht = '';
	var $header = '';
	

	function main($content,$conf)	
	{
		require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/config_inc.php' );
		require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/constants_inc.php' );
        require_once( PATH_site . 'typo3conf/ext/user_kontakt2tine/pi1/tine20_client/loader.php');
		//global $countryAbbrNameRel;
		//global $kontaktnumlinks;
		
		
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		
		 // parse XML data into php array
		$this->pi_initPIflexForm();
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'optioncheck') == 0)
		{
			$template = $this->cObj->fileResource($this->templateFileWithoutCODE);
		}
		else
		{
			$template = $this->cObj->fileResource($this->templateFile);
		}
		
		
		$markerArray['###ACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		//Language allerdings per Post �bergeben im GGS zu den restlichen Links auf der Homepage (wird deswegen �ber "GP" in Typoscript abgefragt)
		$markerArray['###LANGUAGE###'] =  t3lib_div::GPvar('L');
		$senden =  t3lib_div::GPvar('senden');

		

		$title 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'title');	
		$danke 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'danke');	
		$text 	= $this->formatStr($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'text'));	
		
		if(t3lib_div::GPvar('L') == 0)	
		{
			//$sprache = '&L='.t3lib_div::GPvar('L');
			$sprache = 'DE';
		}
		else	
		{
			$sprache = 'EN';
		}
		
		$danke	= 'index.php?id='.$danke;	
		# t3lib_div::debug($sprache);	
	
		
		if(strlen($title) > 0) {$title = '<h1>'.$title.'</h1>';}	

		$r=1;
		$co=1;
		$option = array();
		for($i = 1; $i < ($kontaktnumlinks + 1); $i++) {
			$curOptVal = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'auswahl_' . $i);
			
			if(strlen($curOptVal) > 0) {
				$option[$r++] = $curOptVal;
			}
		}	
		
		$markerArray['###SELECTAUSWAHL###'] = $this->getOptions($option,t3lib_div::_GP('AUSWAHL'));
		$markerArray['###TITLE###'] =  $title;
		$markerArray['###TEXT###'] =  $text;
		$markerArray['###SELECT###'] = $this->pi_getLL('select', "");
		$markerArray['###COUNTRYAUSWAHL###'] = $this->getOptions($countryAbbrNameRel[$sprache],t3lib_div::_GP('COUNTRY'));
		$markerArray['###COUNTRYSELECT###'] = $this->pi_getLL('select', "");
		
		$num = rand(1000, 9999);
		$num_codiert = md5( $num );		

		//Formularfelder belegen
		foreach($this->fFieldNames as $fFieldName) {
			$fFieldValues[$fFieldName] = t3lib_div::_GP($fFieldName);
			$markerArray['###'.$fFieldName.'###'] = $fFieldValues[$fFieldName];	 
		}					
				
		//Formularfelder - Labels sprachabh�ngig eintragen
		foreach($this->fLabelKeys as $fLabelKey => $fLabelKeyValue ) {
			$markerArray['###'.$fLabelKey.'###'] = $this->pi_getLL($fLabelKeyValue, "");
		}
				
		$markerArray['###HINWEISPFLICHTFELDER###'] = $this->pi_getLL('hinweispflichtfelder', "");
		$markerArray['###SENDENBUTTON###'] = $this->pi_getLL('sendenbutton', "");
		$markerArray['###MAILBESTAETIGUNG###'] = $this->pi_getLL('mailbestaetigung', "");
		$markerArray['###VERSENDEZEIT###'] = date('d.n.Y')." um ".date('H:i')." Uhr";
		$markerArray['###DANKE###'] = $this->pi_getLL('danke', "");
		$markerArray['###ERRABSTAND###'] = ''; 
		
		// ****************** NACH ERSTAUFRUF bzw. bei Best�tigung *********************
		if ( $senden == 1 )
		{		
			$codewrong = false;
			if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'optioncheck') == 1)
			{
				$codewrong = ( t3lib_div::_GP('BILDCODE') != md5( t3lib_div::_GP('CODEEINGABE') ) );
			}
			$err = $this->chkForm($fFieldValues, $codewrong);	
			
				
			//Wenn Fehler auftraten
			if(!$this->chkForEmptyArrayValues($err)) {
			
				$subpart_template = $this->cObj->getSubpart($template,'###TEMPLATE###');
				$subpart_pfeil = $this->cObj->getSubpart($template,'###PFEIL###');
				if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'optioncheck') == 1)
				{
					if ( ! file_exists( PATH_site . $this->pathCodeImageFiles . $num_codiert.'.png')) {include( 'code_image/image.php' );}
					$markerArray["###BILDCODEURL###"] =	 $this->pathCodeImageFiles . $num_codiert . '.png'; 				
					$markerArray["###BILDCODE###"] = $num_codiert;
					
				}	
				
				($codewrong) ?  $markerArray['###CODEEINGABEPFEIL###'] = $subpart_pfeil : $markerArray['###CODEEINGABEPFEIL###'] = "";
				
				$markerArray['###ERRABSTAND###'] = '<div class="errorabstand">&nbsp;</div>'; 
				
				foreach($this->fFieldNames as $fFieldName) {
					$markerArray['###'.$fFieldName.'###'] = t3lib_div::_GP($fFieldName);
					if(strlen($err[$fFieldName]) > 0) { 
						$markerArray['###ERR'.$fFieldName.'###'] = $err[$fFieldName]."<br>"; 
						$markerArray['###'.$fFieldName.'PFEIL###'] = $subpart_pfeil; 
					}
					else {
						$markerArray['###'.$fFieldName.'PFEIL###'] = ""; 
						$markerArray['###ERR'.$fFieldName.'###'] = "";
					}							
				}	
			}
			//wenn keine Fehler mehr auftreten, dann EMail versenden
			else {
				// begin contact2tine: 
				try
				{
					// open connection
					$connection = new Tinebase_Connection(
					   $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehost'),
					   $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostlogin'), 
                       $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostpassword')
				   );
					// login to tine2.0
					$connection->login();
				}
				catch (Exception $e) 
				{
					//echo "can not login";
					//exit;
				}
				
				try {
					$addressbook = new Addressbook_Service($connection);
				}
				catch (Exception $e) 
				{
					//echo "kein service";
					//exit;
				}	
				
				try 
				{
				    // :-) to be cleaned up when container support comes into php_client
				    $pattern =  "/\w*\[(\d*)\]/";
				    $containerId = array();
				    preg_match_all($pattern, $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'addressbook'), $containerId );
				
					// write addressbook entry
					$contactData = array(
										'container_id'					=> 34,
										'org_name' 				=> $fFieldValues['FIRMA'], 
										'n_family' 				=> $fFieldValues['NACHNAME'],
										'n_given' 				=> $fFieldValues['VORNAME'],
										'adr_one_locality'		=> $fFieldValues['ORT'],
										'adr_one_street'		=> $fFieldValues['STRASSE'],
										'adr_one_postalcode'	=> $fFieldValues['PLZ'],
										'tel_work'				=> $fFieldValues['TELEFON'],
										'email'					=> $fFieldValues['EMAIL'],
										'adr_one_countryname'	=> $fFieldValues['COUNTRY'],
										'note'					=> $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'auswahl_'
																	. $fFieldValues['AUSWAHL'] ) 
																	. "\n" 
																	. $fFieldValues['NACHRICHT'],
										'n_fileas'				=> $fFieldValues['VORNAME'] . ' ' . $fFieldValues['NACHNAME'] ,
										'n_fn'					=> $fFieldValues['VORNAME'] . ' ' . $fFieldValues['NACHNAME']
										);

					$contact = new Addressbook_Model_Contact($contactData);
					
					$contactOnly = true;
					if ($contactOnly) {
					    $addressbook->addContact($contactData);
					} else {
                        
                        // bare lead
                        $lead = new Crm_Model_Lead(array(
                            'lead_name'     => ($fFieldValues['FIRMA'] ? $fFieldValues['FIRMA'] . ': ' : '') . $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'auswahl_' . $fFieldValues['AUSWAHL']),
                            'leadstate_id'  => 1,
                            'leadtype_id'   => 42,
                            'leadsource_id' => 46,
                            'container'     => 272,
                            'start'         => Zend_Date::now(),
                            'description'   => $fFieldValues['NACHRICHT'],
                            'end'           => NULL,
                            'turnover'      => '',
                            'probability'   => 50,
                            'end_scheduled' => NULL,
                        ), true);
                        
                        // bare task
                        $task = array(
                            'container_id'         => 280,
                            'created_by'           => 8,
                            'creation_time'        => Zend_Date::now(),
                            'percent'              => 0,
                            'due'                  => Zend_Date::now()->addDay(3),
                            'summary'              => 'Kontaktaufnahme ' . $contactData['n_fn'] . ($fFieldValues['FIRMA'] ? ' (' . $fFieldValues['FIRMA'] . ')' : ''),
                        );
                        
                        // contact as customer
                        $lead->relations = array(
                            array(
                                'own_model'              => 'Crm_Model_Lead',
                                'own_backend'            => NULL,
                                'own_id'                 => NULL,
                                'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
                                'related_model'          => 'Addressbook_Model_Contact',
                                'related_backend'        => NULL,
                                'related_id'             => NULL,
                                'type'                   => 'CUSTOMER',
                                'related_record'         => $contact->toArray()
                            ),
                            array(
                                'own_model'              => 'Crm_Model_Lead',
                                'own_backend'            => NULL,
                                'own_id'                 => NULL,
                                'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
                                'related_model'          => 'Tasks_Model_Task',
                                'related_backend'        => NULL,
                                'related_id'             => NULL,
                                'type'                   => 'TASK',
                                'related_record'         => $task
                            ),
                            array(
                                'own_model'              => 'Crm_Model_Lead',
                                'own_backend'            => NULL,
                                'own_id'                 => NULL,
                                'own_degree'             => Tinebase_Model_Relation::DEGREE_CHILD,
                                'related_model'          => 'Addressbook_Model_Contact',
                                'related_backend'        => NULL,
                                'related_id'             => 8,
                                'type'                   => 'RESPONSIBLE',
                                //'related_record'         => NULL
                            ),
                        );
                        
                        $crmService = new Crm_Service($connection);
                        $crmService->addLead($lead);
                    }
                    
				}
				catch (Exception $e) 
				{
					//echo "shit";
					//exit;
				}
				
				try
				{
					$connection->logout();
				}
				catch (Exception $e) 
				{
				    //echo "could no logout";
				    //exit;
				}
				
								
				if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'optionemail') == 1)
				{
					$this->empfaenger 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'email');	
					$this->betreff =	$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'emailbetreff') 
										.' ( '.date('d.n.Y').', '.date('H:i').' Uhr )';	
	
					$subpart_mailtemplate = $this->cObj->getSubpart($template,'###MAILTEMPLATE###');
					
					$markerArray['###AUSWAHL###'] = $option[$markerArray['###AUSWAHL###']]; //special case for selection field
					
					$markerArray['###COUNTRY###'] = $countryOption[$markerArray['###COUNTRY###']];
					
					$this->nachricht = $this->cObj->substituteMarkerArray($subpart_mailtemplate, $markerArray);					
					$this->header  = 'MIME-Version: 1.0' . "\r\n";
					$this->header .= 'Content-type: text/html; charset=utf-8' . "\r\n";		
					$this->header .= 'From: <' . $fFieldValues['EMAIL'] . '>' . "\r\n";
					
					mail($this->empfaenger, $this->betreff, $this->nachricht, $this->header);
				}
				header ("Location:$danke");
			}
		
		}
		//****************************************************** ERSTAUFRUF ***************************************************
		else	{	
			$subpart_template = $this->cObj->getSubpart($template,'###TEMPLATE###');
			
			//#################### Initialisierung #####################
						
			//### BILD ###
			if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'optioncheck') == 1)
			{
				if ( ! file_exists( PATH_site . $this->pathCodeImageFiles . $num_codiert.'.png')) {include( 'code_image/image.php' );}
				$markerArray["###BILDCODEURL###"] =	 $this->pathCodeImageFiles . $num_codiert . '.png'; 
			}
			//### Pfeilmarker der Pflichtfelder entfernen ###
			foreach($this->fFieldNamesMandatory as $fFieldNameMandatory) {$markerArray['###'.$fFieldNameMandatory.'PFEIL###'] = "";}
			$markerArray["###BILDCODE###"] = $num_codiert;
			
			//### Marker der Eingabefelder entfernen ###
			foreach($this->fFieldNames as $fFieldName) {
				//$markerArray['###'.$fFieldName.'###'] = "";
				$markerArray['###ERR'.$fFieldName.'###'] = "";
			}	
		}	

		return $this->pi_wrapInBaseClass($this->cObj->substituteMarkerArray($subpart_template, $markerArray));
	}
	

	function formatStr($str) {
		if(is_array($this->conf["general_stdWrap."]))	{
			$str = $this->cObj->stdWrap($str,$this->conf["general_stdWrap."]);
		}	
		return $str;
	} 	
	
	function getOptions($arr,$selected='') {	
		///*
		
		if (empty ($arr)){
			$arr = array('hinz', 'kunz');
		}
		
		foreach($arr as $key => $val) {
			$options .= '<option value="'.$key.'" '.(($key == $selected) ? 'selected' : '').'>'.$val.'</option>';
		}
		return $options;
		//*/
	}	
	

	function chkForm($fcont,$codestat)	{	
		$errors = array();
			

		//### Auswahl ###
		if($fcont['AUSWAHL'] == '') {
			$errors['AUSWAHL'] = $this->pi_getLL('errauswahl',"");	
		}
		else { $errors['AUSWAHL'] = ""; }		
			
		//### Vorname ###
		if(strlen($fcont['VORNAME']) == 0) {
			$errors['VORNAME'] = $this->pi_getLL('errvorname',"");	
		}
		else { $errors['VORNAME'] = ""; }

		
		//### Name ###
		if(strlen($fcont['NACHNAME']) == 0) {
			$errors['NACHNAME'] = $this->pi_getLL('errnachname',"");	
		}
		else { $errors['NACHNAME'] = ""; }
	
		//### E-Mail ###
		if(!ereg("^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,4})$",$fcont['EMAIL'])) {
			$errors['EMAIL'] = $this->pi_getLL('erremail',"");
		}
		else { $errors['EMAIL'] = ""; }


		if(strlen($fcont['NACHRICHT']) == 0)
		{
			$errors['NACHRICHT'] = $this->pi_getLL('errnachricht',"");
		}
		else { $errors['NACHRICHT'] = ""; }		
		
	
		if($codestat)
		{
			$errors['CODEEINGABE'] = $this->pi_getLL('errcodeeingabe',"");		
		}
		else { $errors['CODEEINGABE'] = ""; }
		
		
		return $errors;
	}
	
	//### checks if all array elements are empty ###
	function chkForEmptyArrayValues($arr) {
		
		if( count($arr) == 0) {
			return true;
		}
		
		foreach($arr as $key => $val) {
			if(strlen($val) > 0) 	{return false;} 	
		}		
		
		return true;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_kontakt2tine/pi1/class.user_kontakt2tine.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_kontakt2tine/pi1/class.user_kontakt2tine.php']);
}

?>
