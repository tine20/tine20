<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     *
     * TODO replace with generic export (via __call)
     */
    public function exportContacts($filter, $options)
    {
        $decodedFilter = empty($filter) ? null : Zend_Json::decode($filter);
        $decodedOptions = Zend_Json::decode($options);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Export filter: ' . print_r($decodedFilter, true)
            . ' Options: ' . print_r($decodedOptions, true));
        
        if (! is_array($decodedFilter)) {
            if ($decodedFilter === null && isset($decodedOptions['recordData']['id'])) {
                // get contact id from $decodedOptions
                $decodedFilter = $decodedOptions['recordData']['id'];
            }
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
        
        $filter = new Addressbook_Model_ContactFilter($decodedFilter);
        parent::_export($filter, $decodedOptions, Addressbook_Controller_Contact::getInstance());
    }

    /**
     * export list
     *
     * @param string $filter JSON encoded string with contact ids for multi export or contact filter
     * @param string $options format or export definition id
     *
     * TODO replace with generic export (via __call)
     */
    public function exportLists($filter, $options)
    {
        $decodedFilter = empty($filter) ? null : Zend_Json::decode($filter);
        $decodedOptions = Zend_Json::decode($options);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Export filter: ' . print_r($decodedFilter, true)
            . ' Options: ' . print_r($decodedOptions, true));

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        $filter = new Addressbook_Model_ListFilter($decodedFilter);
        parent::_export($filter, $decodedOptions, Addressbook_Controller_List::getInstance());
    }
}
