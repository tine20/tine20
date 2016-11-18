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
class Calendar_Import_CalDAV extends Tinebase_Import_Abstract
{
    /**
     * config options
     * 
     * @var array
     */
    protected $_options = array(
        /**
         * force update of existing events 
         * @var boolean
         */
        'updateExisting'        => true,
        /**
         * update exiting events even if imported sequence number isn't higher
         * @var boolean
         */
        'forceUpdateExisting'   => false,
        /**
         * ?
         */
        'allowDuplicateEvents'  => false,
        /**
         * container the events should be imported in
         * @var string
         */
        'container_id'          => null,
        /**
         * url of calDAV service
         * @var string
         */
        'url'                   => null,
        /**
         * username for calDAV service
         * @var string
         */
        'username'              => null,
        /**
         * password for calDAV service
         * @var string
         */
        'password'              => null,
        /**
         * credential cache id instead of username/password
         * @var string
         */
        'cid'                   => null,
        /**
         * credential cache key instead of username/password
         * @var string
         */
        'ckey'                  => null,

        'model'                 => 'Calendar_Model_Event',

    );
    
    protected $_calDAVClient = null;

    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_CalDAV
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Calendar_Import_CalDAV(self::getOptionsArrayFromDefinition($_definition, $_options));
    }

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
 }
