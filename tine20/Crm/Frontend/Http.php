<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 5090 2008-10-24 10:30:05Z p.schuele@metaways.de $
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Crm application
 *
 * @package     Crm
 */
class Crm_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * application name
     * 
     * @var string
     */
    protected $_applicationName = 'Crm';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     * 
     * @todo    make js include order depend on app depencies and remove redundant js files from other apps
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Crm/js/Model.js',
            'Crm/js/Crm.js',
            'Crm/js/LinkGridPanel.js',
            'Crm/js/LeadGridContactFilter.js',
            'Crm/js/LeadGridPanel.js',
            'Crm/js/LeadGridDetailsPanel.js',
            'Crm/js/LeadEditDialog.js',
        // admin settings panel
            'Crm/js/AdminPanel.js',
        // lead state/source/type
            'Crm/js/LeadState.js',
            'Crm/js/LeadStateFilterModel.js',
            'Crm/js/LeadSource.js',
            'Crm/js/LeadType.js',
        // contact grid
            'Addressbook/js/SearchCombo.js',
            'Crm/js/Contact.js',
        // product grid
            'Crm/js/Product.js',
        // task grid
            'Crm/js/Task.js',
        );
    }
    
   	/**
     * export lead
     * 
     * @param	string JSON encoded string with lead ids for multi export
     * @param	format	pdf or csv or ...
     */
	public function exportLead($_filter, $_format = 'pdf')
	{
        $filter = new Crm_Model_LeadFilter(Zend_Json::decode($_filter));
	    parent::_export($filter, $_format, Crm_Controller_Lead::getInstance());
	}    
}
