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
				$translate->_('Lead Data') => 'separator',
				'turnover' => $translate->_('Turnover'),
				'probability' => $translate->_('Probability'),
				'start' => $translate->_('Start'),
				'end' => $translate->_('End'),
				'end_scheduled' => $translate->_('End Scheduled'),
                'container' => $translate->_('Container'),
        
/*        $translate->_('Lead IDs') => 'separator',
				'leadstate_id' => $translate->_('Leadstate ID'),
                'leadtype_id' => $translate->_('Leadtype ID'),
                'leadsource_id' => $translate->_('Leadsource ID'),*/
				);
				
        // build data array
        $record = array ();
        foreach ( $leadFields as $key => $label ) {
        	if ( $label !== 'separator' ) {
	            if ( $_lead->$key instanceof Zend_Date ) {
	                $record[$key] = $_lead->$key->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale') );
	            } elseif ( $key === 'turnover' ) {
	            	$record[$key] = $_lead->$key . " €";
	            } elseif ( $key === 'probability' ) {
	                $record[$key] = $_lead->$key . " %";
	            } else {
	                $record[$key] = $_lead->$key;
	            }
        	}
        }     
        
        // get linked contacts and add them to record array
        $links = Tinebase_Links::getInstance()->getLinks('crm', $_lead->id, 'addressbook');
                
        $linkedObjects = array ();
        if ( !empty($links)) {
	        $linkedObjects[] = array ($translate->_('Linked Contacts'), 'headline');
	        foreach ( $links as $contactLink ) {
	        	$contact = Addressbook_Controller::getInstance()->getContact($contactLink['recordId']);
	            $linkedObjects[] = array ($contact->n_fn, 'separator');
	            $linkedObjects[] = array ($translate->_('Company'), $contact->org_name);
                $linkedObjects[] = array ($translate->_('Address'), $contact->adr_one_street.", ".$contact->adr_one_postalcode." ".$contact->adr_one_locality );
                $linkedObjects[] = array ($translate->_('Telephone'), $contact->tel_work);
	            $linkedObjects[] = array ($translate->_('Email'), $contact->email);
                $linkedObjects[] = array ($translate->_('Type'), $contactLink['remark']);
	        }
        }
        
        //@todo add activities / products to export
        
        // generate pdf now!			
		return $this->generatePdf($record, "Lead: ".$_lead->lead_name, "", $_lead->description, $leadFields, NULL, $linkedObjects );
		
	}

}
