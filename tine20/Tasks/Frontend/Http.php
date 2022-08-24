<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the calendar application
 * 
 * @package Tasks
 */
class Tasks_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Tasks';

    /**
     * export tasks
     *
     * @param string $filter JSON encoded string with items ids for multi export or item filter
     * @param string $options format or export definition id
     */
    public function exportTasks($filter, $options)
    {
        $filter = new Tasks_Model_TaskFilter(Zend_Json::decode($filter));
        parent::_export($filter, Zend_Json::decode($options));
    }
}
