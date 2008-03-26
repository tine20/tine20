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
		//@todo	add fields
		$leadFields = array ();
		
		//return $this->generatePdf($_lead, $_lead->lead_name, "", $leadFields );
		return $this->generatePdf($_lead, $_lead->lead_name);
		
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