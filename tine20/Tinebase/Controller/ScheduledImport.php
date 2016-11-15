<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Import Controller
 * 
 * @package Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_ScheduledImport extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Controller_ScheduledImport
     */
    private static $instance = null;

    const MAXFAILCOUNT = 5;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Tinebase';
        $this->_modelName = 'Tinebase_Model_Import';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'import',
            'modlogActive' => true,
        ));
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
        $this->_resolveCustomFields = false;
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Controller_ScheduledImport
     */
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Search and executed the next scheduled import
     * 
     * @return null|array
     */
    public function runNextScheduledImport()
    {
        if ($record = $this->_getNextScheduledImport()) {
            return $this->doScheduledImport($record)->toArray();
        }
        
        return null;
    }

    /**
     * @return Tinebase_Model_ImportFilter
     */
    public function getScheduledImportFilter()
    {
        $timestampBefore = null;

        $now = new Tinebase_DateTime();

        $anHourAgo = clone $now;
        $anHourAgo->subHour(1);

        $aDayAgo = clone $now;
        $aDayAgo->subDay(1);

        $aWeekAgo = clone $now;
        $aWeekAgo->subWeek(1);

        $filter = new Tinebase_Model_ImportFilter(array(array(
                array('field' => 'failcount', 'operator' => 'greater', 'value' => 5),
            ),
            array(
            'condition' => 'OR', 'filters' => array(
                array('field' => 'timestamp', 'operator' => 'isnull', 'value' => null),
                array('condition' => 'AND', 'filters' => array(
                    array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_HOURLY),
                    array('field' => 'timestamp', 'operator' => 'before', 'value' => $anHourAgo),
                )),
                array('condition' => 'AND', 'filters' => array(
                    array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_DAILY),
                    array('field' => 'timestamp', 'operator' => 'before', 'value' => $aDayAgo),
                )),
                array('condition' => 'AND', 'filters' => array(
                    array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_WEEKLY),
                    array('field' => 'timestamp', 'operator' => 'before', 'value' => $aWeekAgo),
                )),
            )
        )));

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' Filter used: ' . print_r($filter->toArray(), true));
        }

        return $filter;
    }

    /**
     * Get the next scheduled import
     * 
     * @param interval
     * @param recursive
     * @return Tinebase_Model_Import|null
     */
    protected function _getNextScheduledImport()
    {
        $filter = $this->getScheduledImportFilter();

        // Always sort by timestamp to ensure first in first out
        $pagination = new Tinebase_Model_Pagination(array(
            'limit'     => 50,
            'sort'      => 'timestamp',
            'dir'       => 'ASC'
        ));

        $records = $this->search($filter, $pagination);

        foreach ($records as $record) {
            // TODO add failcount to filter in getScheduledImportFilter as
            //   no more valid imports are run if we have 50+ failing imports
            if ($record->failcount < self::MAXFAILCOUNT) {
                return $record;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' Too many failures, skipping import');
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' No valid ScheduledImport could be found.');
        }

        return null;
    }
    
    /**
     * Downloads file to memory
     * 
     * @param string $source
     * @return string|null
     */
    protected function _getFileToImport($source)
    {
        if (strpos($source, 'http') === 0) {
            try {
                $client = new Zend_Http_Client($source);
                // 0011054: Problems with ScheduledImport of external ics calendars
                // google shows a lot of trouble with gzip in Zend_Http_Response, so let's deny it
                $client->setHeaders('Accept-encoding', 'identity');
                $requestBody = $client->request()->getBody();
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                $requestBody = null;
            }
            return $requestBody;
        } else {
            return file_get_contents($source);
        }
    }
    
    /**
     * Execute scheduled import
     *
     * @param Tinebase_Model_Import $record
     * @return Tinebase_Model_Import
     */
    public function doScheduledImport(Tinebase_Model_Import $record)
    {
        $currentUser = Tinebase_Core::getUser();
        // set user who created the import job
        $importUser = Tinebase_User::getInstance()->getUserByPropertyFromBackend('accountId', $record->user_id, 'Tinebase_Model_FullUser');
        Tinebase_Core::set(Tinebase_Core::USER, $importUser);

        $importer = $record->getOption('plugin');
        
        $options = array(
            'container_id' => $record->container_id,
            // legacy
            'importContainerId' => $record->container_id,
        );
        
        if ($record->getOption('importFileByScheduler') === true) {
            $toImport = $this->_getFileToImport($record->source);
        } else {
            $toImport = array(
                'remoteUrl'    => $record->source,
                'container_id' => $record->container_id,
                'options'      => $record->options
            );
        }

        try {
            // handle options
            $options = Zend_Json::decode($record->options);
            $options['url'] = $record->source;

            if (isset($options['cid']) && isset($options['ckey'])) {
                $credentials = new Tinebase_Model_CredentialCache($options);
                Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($credentials);

                $options['username'] = $credentials->username;
                $options['password'] = $credentials->password;
            }

            $importer = $record->getOption('plugin');
            $resource = $record->getOption('importFileByScheduler') ? $this->_getFileToImport($options['url']) : null;

            $importer = new $importer($options);
            $importer->import($resource);
            $record->failcount = 0;
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                    . ' Import failed.');
            }
            Tinebase_Exception::log($e);

            $record->lastfail = $e->getMessage();
            $record->failcount = $record->failcount + 1;
        }

        $record->timestamp = Tinebase_DateTime::now();
        $record = $this->update($record);

        Tinebase_Core::set(Tinebase_Core::USER, $currentUser);

        return $record;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Model_Import $import
     * @param   boolean $duplicateCheck
     * @return  Tinebase_Model_Import
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $import, $duplicateCheck = true)
    {
        // handle credentials
        if ($import->getOption('password')) {
            $credentialCache = Tinebase_Auth_CredentialCache::getInstance();
            $credentials = $credentialCache->cacheCredentials(
                $import->getOption('username'),
                $import->getOption('password'),
                /* key */           null,
                /* persist */       true,
                /* valid until */   Tinebase_DateTime::now()->addYear(100)
            );

            $import->deleteOption('password');
            $import->deleteOption('username');

            $import->setOption('cid', $credentials->getId());
            $import->setOption('ckey', $credentials->key);
        }

        // options over own field
        $containerId = $import->getOption('container_id') ?: $import->container_id;

        // handle container
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
        } catch (Tinebase_Exception_InvalidArgument $e) {
            $container = new Tinebase_Model_Container(array(
                'name'              => $containerId,
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'backend'           => Tinebase_User::SQL,
                'color'             => '#ff0000',
                'application_id'    => $import->application_id,
                'owner_id'          => $import->user_id,
                'model'             => $import->model,
            ));

            $container = Tinebase_Container::getInstance()->addContainer($container);
        }

        $import->setOption('container_id', $container->getId());
        $import->container_id = $container->getId();
        $import->user_id = $import->user_id ?: Tinebase_Core::getUser()->getId();

        // @TODO TBD
        $import->setOption('forceUpdateExisting', true);

        // @TODO fix schema so column id has 40 chars only
        $import->setId(Tinebase_Record_Abstract::generateUID());

        return parent::create($import, $duplicateCheck);
    }
}
