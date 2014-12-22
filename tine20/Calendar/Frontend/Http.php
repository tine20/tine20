<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * @package     Calendar
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the calendar application
 *
 * @package     Calendar
 * @subpackage  Server
 */
class Calendar_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Calendar';

    /**
     * export events
     *
     * @param string $filter JSON encoded string with items ids for multi export or item filter
     * @param string $options format or export definition id
     */
    public function exportEvents($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        $filter = new Calendar_Model_EventFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), Calendar_Controller_Event::getInstance());
    }
}
