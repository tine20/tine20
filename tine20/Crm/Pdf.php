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
		//@todo	add more fields
		$leadFields = array (
				'Lead Info' => 'separator',
				'turnover' => 'Turnover',
				'probability' => 'Probability',
			);

	/*
       'id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'lead_name'     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'leadstate_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'leadtype_id'   => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'leadsource_id' => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'container'     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'start'         => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'description'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'turnover'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'probability'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'end_scheduled' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
	*/	
			
		return $this->generatePdf($_lead, $_lead->lead_name, $_lead->description, $leadFields);
		
	}
	

	/**
     * create lead list pdf
     *
     * @param	array leads data
     * 
     * @return	string	the lead list pdf
     * 
     * @todo	implement
     */
	public function leadListPdf ( array $_leads )
	{
		return $this->generateListPdf($_leads);
	}
		

}