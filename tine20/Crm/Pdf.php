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
     * @param	Crm_Model_Lead $_lead lead data
     * 
     * @return	string	the contact pdf
     * 
     * @todo    split function
     */
	public function getLeadPdf ( Crm_Model_Lead $_lead )
	{
        $locale = Zend_Registry::get('locale');
	    $translate = Tinebase_Translation::getTranslation('Crm');
        
       /*********************** build data array *************************/
        
        $leadFields = array (
            array(  'label' => /* $translate->_('Lead Data') */ "", 
                    'type' => 'separator' 
            ),
            array(  'label' => $translate->_('Leadstate'), 
                    'value' => array( 'leadstate_id' ),
            ),
            array(  'label' => $translate->_('Leadtype'), 
                    'value' => array( 'leadtype_id' ),
            ),
            array(  'label' => $translate->_('Leadsource'), 
                    'value' => array( 'leadsource_id' ),
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
                                $content[] = Zend_Locale_Format::toNumber($_lead->$key, array('locale' => $locale)) . " €";
                            } elseif ( $key === 'probability' ) {
                                $content[] = $_lead->$key . " %";
                            } elseif ( $key === 'leadstate_id' ) {
                                $state = Crm_Controller::getInstance()->getLeadState($_lead->leadstate_id);
                                $content[] = $state->leadstate;
                            } elseif ( $key === 'leadtype_id' ) {
                                $type = Crm_Controller::getInstance()->getLeadType($_lead->leadtype_id);
                                $content[] = $type->leadtype;
                            } elseif ( $key === 'leadsource_id' ) {
                                $source = Crm_Controller::getInstance()->getLeadSource($_lead->leadsource_id);
                                $content[] = $source->leadsource;
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

        /******************* build title / subtitle / description ************/
        
        $title = $_lead->lead_name; 
        $subtitle = "";
        $description = $_lead->description;
        $titleIcon = "/images/oxygen/32x32/actions/paperbag.png";

        /*********************** add linked objects *************************/

        // contacts
        $linkedObjects = array ( array($translate->_('Contacts'), 'headline') );

        $types = array (    "customer" => "/images/oxygen/32x32/apps/system-users.png", 
                            "partner" => "/images/oxygen/32x32/actions/view-process-own.png", 
                            "responsible" => "/images/oxygen/32x32/apps/preferences-desktop-user.png",
                        );        
        
        foreach ( $types as $type => /* $headline */ $icon ) {
            
            if ( !empty($_lead->$type)) {
                //$linkedObjects[] = array ( $headline, 'headline');
                
                foreach ( $_lead->$type as $linkID ) {
                    $contact = Addressbook_Controller::getInstance()->getContact($linkID);
                    
                    $contactNameAndCompany = $contact->n_fn;
                    if ( !empty($contact->org_name) ) {
                        $contactNameAndCompany .= " / " . $contact->org_name;
                    }
                    $linkedObjects[] = array ($contactNameAndCompany, 'separator', $icon);
                    
                    $postalcodeLocality = ( !empty($contact->adr_one_postalcode) ) ? $contact->adr_one_postalcode . " " . $contact->adr_one_locality : $contact->adr_one_locality;
                    $regionCountry = ( !empty($contact->adr_one_region) ) ? $contact->adr_one_region . " " : "";
                    if ( !empty($contact->adr_one_countryname) ) {
                        $regionCountry .= $locale->getCountryTranslation ( $contact->adr_one_countryname );
                    }
                    $linkedObjects[] = array ($translate->_('Address'), 
                                            array( 
                                                $contact->adr_one_street, 
                                                $postalcodeLocality,
                                                $regionCountry,
                                            )
                                        );
                    $linkedObjects[] = array ($translate->_('Telephone'), $contact->tel_work);
                    $linkedObjects[] = array ($translate->_('Email'), $contact->email);
                }
            }
        }
        
        // tasks
        if ( !empty($_lead->tasks) ) {
            $linkedObjects[] = array ( $translate->_('Tasks'), 'headline');
            foreach ( $_lead->tasks as $taskId ) {
                $task = Tasks_Controller::getInstance()->getTask($taskId);
                //@todo add tasks icon and more fields
                $linkedObjects[] = array ($translate->_('Summary'), $task->summary);
            }
        }
        
        //@todo add products to export
        
        /***************************** generate pdf now! *************************/
                    
        return $this->generatePdf($record, $title, $subtitle, $description, $titleIcon, NULL, $linkedObjects, FALSE );
        
	}

}
