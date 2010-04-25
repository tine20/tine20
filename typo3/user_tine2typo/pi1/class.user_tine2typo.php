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
 * Plugin 'user_tine2typo' for the 'user_tine2typo' extension.
 *
 * @author  Matthias Greiling <typo3@metaways.de>
 * @comment this plugin is designed for TINE20 http://www.tine20.org
 * @version     $Id$
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once( PATH_site . 'typo3conf/ext/user_tine2typo/constants_inc.php' );

class user_tine2typo extends tslib_pibase {
    var $prefixId = 'user_tine2typo';        // Same as class name
    var $scriptRelPath = 'pi1/class.user_tine2typo.php';    // Path to this script relative to the extension dir.
    var $extKey = 'user_tine2typo';    // The extension key.
    var $templateFile = "typo3conf/ext/user_tine2typo/pi1/template2.html";
//    var $languageFile = ;
    
     function initLL() { 
                $basePath =  PATH_site . 'typo3conf/ext/user_tine2typo/locallang_db.xml'; 
                $this->LOCAL_LANG = t3lib_div::readLLfile($basePath, $this->LLkey); 
                
                if ($this->altLLkey)    { 
                        $tempLOCAL_LANG = t3lib_div::readLLfile($basePath,$this->altLLkey); 
                        $this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? 
                        $this->LOCAL_LANG : array(),$tempLOCAL_LANG); 
               } 
               $this->LOCAL_LANG_loaded = true;
        } 
    
    
    function main($content,$conf)    
    {
        global $countryAbbrNameRel;
        global $kontaktnumlinks;
        $basePath =  PATH_site . 'typo3conf/ext/user_tine2typo/locallang_db.xml'; 
        $this->LOCAL_LANG = t3lib_div::readLLfile($basePath, $this->LLkey); 
                
        
                
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        //$this->pi_loadLL();
        $this->pi_USER_INT_obj = 1;    // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        
         // parse XML data into php array
        $this->pi_initPIflexForm();
        
        
        // common template parsing
        $markerArray['###TITLE###'] =  $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'title');
        $markerArray['###TEXT###'] =  $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'text');
        $template = $this->cObj->fileResource($this->templateFile);
        
        $cols = str_replace(',', '', explode('LLL:EXT:user_tine2typo/locallang_db.xml:', 
        $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'selection')));
        
        /**
         * register autoloading for tine20 client
         */
        require_once(PATH_site . 'typo3conf/ext/user_tine2typo/pi1/tine20_client/loader.php');
        
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
        }
        
        try 
        {
            $addressbook = new Addressbook_Service($connection);
            
            //  get from [123]meier -> 123;  123 == TINE20.contactId
            $pattern =  "/\w*\[(\d*)\]/";
            $ContactIds = array();
            preg_match_all($pattern, $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'category'), $ContactIds );
                        
            foreach ($ContactIds[1] as $contactId)
            {
                try
                {
                    $Contact[] = $addressbook->getContact($contactId);
                }
                catch (Exception $e)
                {

                }
            }
        }
        catch (Exception $e) 
        {
            exit;
        }

        try
        {
			if (is_array($Contact)) {
				foreach ($Contact as $contact) {	
					if(isset($contact['jpegphoto'])) {
						$contact['jpegphoto'] = str_replace('$', '', $contact['jpegphoto']);
						
						$fileName = PATH_site .'fileadmin/images/tineContactPics/contactPic' . $contact['id'] . '.jpg';
						if(!is_file($fileName)) {
							$fd = fopen($fileName, "a+");
							if(fwrite($fd, $addressbook->getImage($contact['id'])) === FALSE) {
								echo $addressbook->getImage($contact['id']);
							}
							fclose($fd);
						}
					}	
				}
			}
			//fclose($temp); // dies entfernt die Datei
			
		    
        }
        catch (Exception $e) 
        {
        
        }
		try{$connection->logout();}
		 catch (Exception $e) 
        {
        
        }
		if (is_array($Contact))
        {
            foreach ($Contact as $contact)
            {
				$markerArray['###IMG###'] = ' src="fileadmin/images/tineContactPics/contactPic' . $contact['id'] . '.jpg"';
				$markerArray['###VORNAME###'] = htmlentities($contact['n_given']) ;
				$markerArray['###NAME###'] = htmlentities($contact['n_family']) ;
				$markerArray['###EMAIL###'] = htmlentities($contact['email']) ;
				$markerArray['###TELEFON###'] = htmlentities($contact['tel_work']);
            }
        }
        /*
        if (is_array($Contact))
        {
            foreach ($Contact as $contact)
            {
                foreach ($cols as $key => $col)
                {
                    if ($key !== 0)
                    {
                        if (!empty($contact[$col]))
                        {
							if ($col == 'jpegphoto') {
							$contact[$col] = str_replace('$','', $contact[$col]);
							
						//		$markerArray['###TDBODY###'] = '<img src=' . $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehost') . '/' . $contact[$col] . ">";
								$markerArray['###TDBODY###'] = '<img src="fileadmin/images/contactPic' . $contact['id'] . '.jpg">';
							} else {
								$markerArray['###TDBODY###'] = htmlentities(utf8_decode($contact[$col])) ;
							}
						}
                        elseif ($key !== 0)
                        {
                            $markerArray['###TDBODY###'] =  '&nbsp;';
                        }
                    
                        $trow_ .= $this->cObj->substituteMarkerArray($subpart_template_td, $markerArray);
                    }
                }
            
            $subpart_template = $this->cObj->getSubpart($template,'###TRTEMPLATE###');
            $TROW .= $this->cObj->substituteMarkerArray($subpart_template, array('###TRBODY###' => $trow_));
            unset($trow_);
            }
        }
		*/
        $subpart_template = $this->cObj->getSubpart($template,'###TEMPLATE###');
        $markerArray["###TABLEBODY###"] = $TROW;
                
                
        return $this->pi_wrapInBaseClass($this->cObj->substituteMarkerArray($subpart_template, $markerArray));
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_tine2typo/pi1/class.user_tine2typo.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_tine2typo/pi1/class.user_tine2typo.php']);
    
}

?>