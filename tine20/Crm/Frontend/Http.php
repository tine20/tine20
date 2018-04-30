<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * export lead
     * 
     * @param    string $filter JSON encoded string with lead ids for multi export
     * @param    string $options format or export definition id
     */
    public function exportLeads($filter, $options)
    {
        $decodedFilter = empty($filter) ? null : Zend_Json::decode($filter);
        $decodedOptions = Zend_Json::decode($options);

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        $filter = new Crm_Model_LeadFilter($decodedFilter);
        parent::_export($filter, $decodedOptions, Crm_Controller_Lead::getInstance());
    }    
}
