<?php
/**
 * crm pdf generation class
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * crm pdf export class
 * 
 * @package     Crm
  */
class Crm_Pdf extends Tinebase_Export_Pdf
{
	
	
	/**
     * create lead pdf
     *
     * @param	Crm_Model_Lead lead data
     * 
     * @return	string	the contact pdf
     */
	public function getLeadPdf ( Crm_Model_Lead $_lead )
	{
        $translate = new Zend_Translate('gettext', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale(Zend_Registry::get('locale'));		
        
        $leadFields = array (
            array(  'label' => $translate->_('Lead Data'), 
                    'type' => 'separator' 
            ),
            array(  'label' => $translate->_('Turnover'), 
                    'value' => array( 'turnover' ),
            ),
            array(  'label' => $translate->_('Probability'), 
                    'value' => array( 'probability' ),
            ),
            array(  'label' => $translate->_('Start'), 
                    'value' => array( 'start' ),
            ),
            array(  'label' => $translate->_('End'), 
                    'value' => array( 'end' ),
            ),
            array(  'label' => $translate->_('End Scheduled'), 
                    'value' => array( 'end_scheduled' ),
            ),
            
        );
        
        // add data to array
        $record = array ();
        foreach ( $leadFields as $fieldArray ) {
            if ( !isset($fieldArray['type']) || $fieldArray['type'] !== 'separator' ) {
                $values = array();
                foreach ( $fieldArray['value'] as $valueFields ) {
                    $content = array();
                    if ( is_array($valueFields) ) {
                        $keys = $valueFields;
                    } else {
                        $keys = array ( $valueFields );
                    }
                    foreach ( $keys as $key ) {
                        if ( $_lead->$key instanceof Zend_Date ) {
                            $content[] = $_lead->$key->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale') );
                        } elseif (!empty($_lead->$key) ) {
                            if ( $key === 'turnover' ) {
                                $content[] = $_lead->$key . " €";
                            } elseif ( $key === 'probability' ) {
                                $content[] = $_lead->$key . " %";
                            } else {
                                $content[] = $_lead->$key;
                            }
                        }
                    }
                    if ( !empty($content) ) {
                        $glue = ( isset($fieldArray['glue']) ) ? $fieldArray['glue'] : " ";
                        $values[] = implode($glue,$content);
                    }
                }
                if ( !empty($values) ) {
                    $record[] = array ( 'label' => $fieldArray['label'],
                                        'type'  => ( isset($fieldArray['type']) ) ? $fieldArray['type'] : 'singleRow',
                                        'value' => ( sizeof($values) === 1 ) ? $values[0] : $values,
                    ); 
                }
            } elseif ( isset($fieldArray['type']) && $fieldArray['type'] === 'separator' ) {
                $record[] = $fieldArray;
            }
        }     

        // build title / subtitle / description
        $title = "Lead: ".$_lead->lead_name; 
        $subtitle = "";
        $description = $_lead->description;

        // add linked objects               
        $linkedObjects = array ();
        $types = array (    "customer" => $translate->_('Linked Customers'), 
                            "partner" => $translate->_('Linked Partners'), 
                            "responsible" => $translate->_('Linked Contacts') );
        
        foreach ( $types as $type => $headline ) {
            
            if ( !empty($_lead->$type)) {
                $linkedObjects[] = array ( $headline, 'headline');
                
                foreach ( $_lead->$type as $linkID ) {
                    $contact = Addressbook_Controller::getInstance()->getContact($linkID);
                    
                    $linkedObjects[] = array ($contact->n_fn, 'separator');
                    $linkedObjects[] = array ($translate->_('Company'), $contact->org_name);
                    $linkedObjects[] = array ($translate->_('Address'), 
                                            // $contact->adr_one_street.", ".$contact->adr_one_postalcode." ".$contact->adr_one_locality
                                            array( 
                                                $contact->adr_one_street, 
                                                $contact->adr_one_postalcode . " " . $contact->adr_one_locality,
                                                $contact->adr_one_region . " " . $contact->adr_one_countryname,
                                            )
                                        );
                    $linkedObjects[] = array ($translate->_('Telephone'), $contact->tel_work);
                    $linkedObjects[] = array ($translate->_('Email'), $contact->email);
                }
            }
        }
        
        //@todo add tasks / products to export
        
        // generate pdf now!            
        //return $this->generatePdf($record, $title, "", $description, NULL, $linkedObjects );
        return $this->generatePdf($record, $title, $subtitle, $description, NULL, $linkedObjects );
        
	}

}
