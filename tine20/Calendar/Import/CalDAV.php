<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Import_CalDAV
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_CalDAV extends Calendar_Import_Abstract
{
    protected $_calDAVClient = null;

    /**
     * Splits Uri into protocol + host, path
     *
     * @param $uri
     * @return array
     */
    protected function _splitUri($uri)
    {
        $uri = parse_url($uri);

        return array(
            'host' => $uri['scheme'] . '://' . $uri['host'],
            'path' => $uri['path']
        );
    }

    /**
     * import the data
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $container = Tinebase_Container::getInstance()->getContainerById($this->_options['container_id']);

        $uri = $this->_splitUri($this->_options['url']);
        
        $caldavClientOptions = array(
            'baseUri' => $uri['host'],
            'calenderUri' => $uri['path'],
            'userName' => $this->_options['username'],
            'password' => $this->_options['password'],
            'allowDuplicateEvents' => $this->_options['allowDuplicateEvents'],
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' Trigger CalDAV client with URI ' . $this->_options['url']);
        }
        
        $this->_calDAVClient = new Calendar_Import_CalDav_Client($caldavClientOptions, 'Generic', $container->name);
        $this->_calDAVClient->setVerifyPeer(false);
        $this->_calDAVClient->getDecorator()->initCalendarImport();
        $this->_calDAVClient->updateAllCalendarData();
    }

    protected function _getImportEvents($_resource, $container)
    {
        // not needed here
    }
}
