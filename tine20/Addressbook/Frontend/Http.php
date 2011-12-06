<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook http frontend class
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * export contact
     * 
     * @param string $filter JSON encoded string with contact ids for multi export or contact filter
     * @param string $options format or export definition id
     */
    public function exportContacts($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));
        
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
        
        $filter = new Addressbook_Model_ContactFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), Addressbook_Controller_Contact::getInstance());
    }
}
