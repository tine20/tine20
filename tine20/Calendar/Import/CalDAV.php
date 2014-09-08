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
         * updates exiting events if sequence number is higher
         * @var boolean
         */
        'forceUpdateExisting'   => false,
        /**
         * container the events should be imported in
         * @var string
         */
        'container_id'     => null,
        
        /**
         * Model to be used for import
         * @var string
         */
        'model' => 'Calendar_Model_Event'
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
        $_resource['options'] = Zend_Json::decode($_resource['options']);

        $credentials = new Tinebase_Model_CredentialCache(array(
            'id'    => $_resource['options']['cid'],
            'key'   => $_resource['options']['ckey']
        ));
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($credentials);

        $uri = $this->_splitUri($_resource['remoteUrl']);

        $caldavClientOptions = array(
            'baseUri' => $uri['host'],
            'calenderUri' => $uri['path'],
            'userName' => $credentials->username,
            'password' => $credentials->password,
            'allowDuplicateEvents' => $_resource['options']['allowDuplicateEvents'],
        );

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Trying to get calendar container Name:' . $_resource['options']['container_id']);
        $container = Tinebase_Container::getInstance()->getContainerById($this->_getImportCalendarByName($_resource['options']['container_id']));
        
        if ($container === false) {
            throw new Tinebase_Exception('Could not import, aborting ..');
            return false;
        }

        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($credentials->username, $credentials->password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);

        $this->_calDAVClient = new Calendar_Import_CalDav_Client($caldavClientOptions, 'Generic', $container->name);
        $this->_calDAVClient->setVerifyPeer(false);
        $this->_calDAVClient->getDecorator()->initCalendarImport();

        $this->_calDAVClient->updateAllCalendarData();
    }
    
    /**
     * Return container id of newly created container for import
     * 
     *  - the given container name is not allowed to exist
     * 
     * @param type $containerName
     * @return boolean
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getImportCalendarByName ($containerName)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Get calendar by name for CalDAV import. Name:' . $containerName);

        $filter = new Tinebase_Model_ContainerFilter(array(
            array(
                'field' => 'name', 
                'operator' => 'equals', 
                'value' => $containerName
            ),
            array(
                'field' => 'application_id',
                'operator' => 'equals',
                'value' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
            )
        ));
        $search = Tinebase_Container::getInstance()->search($filter);

        if ($search->count() > 0) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Calendar already exists ' . $containerName . ' ID: ' . $search->getFirstRecord()->getId());
            return $search->getFirstRecord()->getId();
        }
            
        $container = new Tinebase_Model_Container(array(
            'name'              => $containerName,
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => Tinebase_User::SQL,
            'color'             => '#ff0000',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'owner_id'          => Tinebase_Core::getUser()->getId(),
            'model'             => 'Calendar_Model_Event',
        ));

        $container = Tinebase_Container::getInstance()->addContainer($container);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Added calendar ' . $containerName . ' with ID: ' . $container->getId());

        return $container->getId();
    }
}
