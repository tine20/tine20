<?php
/**
 * crm pdf generation class
 *
 * @package     Crm
 * @subpackage	PDF
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * crm pdf export class
 * 
 * @package     Crm
 * @subpackage	PDF
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
	public function leadPdf ( Crm_Model_Lead $_lead )
	{
        $translate = new Zend_Translate('gettext', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale(Zend_Registry::get('locale'));		
        
        //@todo	add more fields and translations
		$leadFields = array (
				$translate->_('Lead Data') => 'separator',
				'turnover' => $translate->_('Turnover'),
				'probability' => $translate->_('Probability'),
				'start' => $translate->_('Start'),
				'end' => $translate->_('End'),
				'end_scheduled' => $translate->_('End Scheduled'),
                'container' => $translate->_('Container'),
                $translate->_('Lead IDs') => 'separator',
				'leadstate_id' => $translate->_('Leadstate ID'),
                'leadtype_id' => $translate->_('Leadtype ID'),
                'leadsource_id' => $translate->_('Leadsource ID'),
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
			
		return $this->generatePdf($record, $_lead->lead_name, "", $_lead->description, $leadFields);
		
	}

}