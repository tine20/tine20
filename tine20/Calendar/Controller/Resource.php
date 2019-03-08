<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Resources Controller
 * 
 * @package Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_Resource extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var boolean
     * 
     * just set is_delete=1 if record is going to be deleted
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = TRUE;

    /**
     * @see Tinebase_Controller_Record_Abstract
     *
     * @var boolean
     */
    protected $_resolveCustomFields = TRUE;

    /**
     * @var Calendar_Controller_Resource
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Resource';
        
        $this->_backend         = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'cal_resources'
        ));
        $this->_backend->setModlogActive(TRUE);

        $this->_getMultipleGrant = Calendar_Model_ResourceGrants::RESOURCE_READ;
        $this->_requiredFilterACLget = [Calendar_Model_ResourceGrants::RESOURCE_READ];
        $this->_requiredFilterACLupdate  = [Calendar_Model_ResourceGrants::RESOURCE_EDIT];
        $this->_requiredFilterACLsync  = [Calendar_Model_ResourceGrants::RESOURCE_SYNC];
        $this->_requiredFilterACLexport  = [Calendar_Model_ResourceGrants::RESOURCE_EXPORT];
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_Resource
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_Resource();
        }
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * we don't want the normal admin grant to be set ever
     *
     * @param Tinebase_Record_RecordSet $_grants
     * @return Tinebase_Record_RecordSet
     */
    protected function convertToEventGrants(Tinebase_Record_RecordSet $_grants)
    {
        /** @var Calendar_Model_ResourceGrants $grant */
        foreach ($_grants as $grant) {
            // unset all default grants
            foreach (Tinebase_Model_Grants::getAllGrants() as $key) {
                $grant->{$key} = false;
            }

            // enforce implicit resource grants
            if ($grant->{Calendar_Model_ResourceGrants::RESOURCE_ADMIN}) {
                $grant->{Calendar_Model_ResourceGrants::RESOURCE_EDIT}      = true;
                $grant->{Calendar_Model_ResourceGrants::RESOURCE_EXPORT}    = true;
                $grant->{Calendar_Model_ResourceGrants::RESOURCE_INVITE}    = true;
                $grant->{Calendar_Model_ResourceGrants::RESOURCE_READ}      = true;
                $grant->{Calendar_Model_ResourceGrants::RESOURCE_SYNC}      = true;
            } else {
                if ($grant->{Calendar_Model_ResourceGrants::RESOURCE_EDIT}) {
                    $grant->{Calendar_Model_ResourceGrants::RESOURCE_READ}      = true;
                }
            }

            // apply event grants
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_ADD}) {
                $grant->{Tinebase_Model_Grants::GRANT_ADD} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_DELETE}) {
                $grant->{Tinebase_Model_Grants::GRANT_DELETE} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_EDIT}) {
                $grant->{Tinebase_Model_Grants::GRANT_EDIT} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_EXPORT}) {
                $grant->{Tinebase_Model_Grants::GRANT_EXPORT} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_FREEBUSY}) {
                $grant->{Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_READ}) {
                $grant->{Tinebase_Model_Grants::GRANT_READ} = true;
            }
            if ($grant->{Calendar_Model_ResourceGrants::EVENTS_SYNC}) {
                $grant->{Tinebase_Model_Grants::GRANT_SYNC} = true;
            }
        }

        return $_grants;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        if (!empty($_record->attachments) && Tinebase_Core::isFilesystemAvailable()) {
            // fill stat cache to avoid deadlocks. Needs to happen outside a transaction
            $path = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentBasePath($_record);
            Tinebase_FileSystem::getInstance()->fileExists($path);
        }

        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $grants = null;
            if (is_array($_record->grants) && !empty($_record->grants)) {
                $grants = $this->convertToEventGrants(
                    new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class, $_record->grants));
            } else {
                $grants = new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class,
                    [new Calendar_Model_ResourceGrants([
                        'account_id' => '0',
                        'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    ])]);
            }
            unset($_record->grants);

            $appId = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId();
            // create a calendar for this resource
            $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name' => $_record->name,
                'hierarchy' => $_record->hierarchy,
                'color' => '#333399',
                'type' => Tinebase_Model_Container::TYPE_SHARED,
                'backend' => $this->_backend->getType(),
                'application_id' => $appId,
                'model' => Calendar_Model_Event::class,
                'xprops' => ['Tinebase' => ['Container' =>
                    ['GrantsModel' => Calendar_Model_ResourceGrants::class]]],
            )), $grants, true);

            $_record->container_id = $container->getId();
            $createdRecord = parent::create($_record);

            $container->xprops()['Calendar']['Resource']['resource_id'] = $createdRecord->getId();

            $this->_setIconXprops($_record, $container);


            $updateObserver = new Tinebase_Model_PersistentObserver(array(
                'observable_model'      => 'Tinebase_Model_Container',
                'observable_identifier' => $container->getId(),
                'observer_model'        => $this->_modelName,
                'observer_identifier'   => $createdRecord->getId(),
                'observed_event'        => 'Tinebase_Event_Record_Update'
            ));
            Tinebase_Record_PersistentObserver::getInstance()->addObserver($updateObserver);

            $deleteObserver = new Tinebase_Model_PersistentObserver(array(
                'observable_model'      => 'Tinebase_Model_Container',
                'observable_identifier' => $container->getId(),
                'observer_model'        => $this->_modelName,
                'observer_identifier'   => $createdRecord->getId(),
                'observed_event'        => 'Tinebase_Event_Record_Delete'
            ));
            Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteObserver);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }

        return $createdRecord;
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_Abstract && $_eventObject->persistentObserver->observable_model === 'Tinebase_Model_Container') {
            switch (get_class($_eventObject)) {
                case 'Tinebase_Event_Record_Update':
                    try {
                        $resource = $this->get($_eventObject->persistentObserver->observer_identifier);
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        return;
                    }
                    if (!$_eventObject->observable->is_deleted && ($resource->name !== $_eventObject->observable->name
                            || $resource->hierarchy !== $_eventObject->observable->hierarchy)) {
                        $resource->name = $_eventObject->observable->name;
                        $resource->hierarchy = $_eventObject->observable->hierarchy;
                        $this->update($resource);
                    }
                    break;

                case 'Tinebase_Event_Record_Delete':
                    $this->delete($_eventObject->persistentObserver->observer_identifier);
                    break;
            }
        }
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck will be ignored!
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        // we better make this in one transaction, we don't want to update them separately
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            // container does not make ACL checks, so we do it... / and we don't trust the client: use current record container id!
            $currentRecord = $this->get($_record->getId());
            $_record->container_id = $currentRecord->container_id;

            // get container
            $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
            /** @var Tinebase_Model_Container $eventContainer */

            if (is_array($_record->grants) && Tinebase_Core::getUser()
                    ->hasGrant($container, Calendar_Model_ResourceGrants::RESOURCE_ADMIN)) {
                $grants = $this->convertToEventGrants(
                    new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class, $_record->grants));

                Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, true, false);
            }
            unset($_record->grants);

            $result = parent::update($_record);

            if ($container->name !== $result->name || $container->hierarchy !== $result->hierarchy) {
                $container->name = $result->name;
                $container->hierarchy = $result->hierarchy;

                Tinebase_Container::getInstance()->update($container);
            }

            if ($currentRecord->type !== $result->type) {
                $this->_setIconXprops($_record, $container);
            }

            if ($currentRecord->name != $result->name) {
                $this->updateEventLocations($currentRecord->name, $result->name);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        }  catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }

        return $result;
    }

    /**
     * update autogenerated event locations when resource gets renamed
     *
     * @param string|string[]   $currentName
     * @param string            $newName
     * @param bool              $updatePastEvents
     */
    public function updateEventLocations($currentName, $newName, $updatePastEvents = false)
    {
        $currentName = is_array($currentName) ? $currentName : [$currentName];

        $filter = new Calendar_Model_EventFilter([
            ['field' => 'location', 'operator' => 'in', 'value' => $currentName]
        ]);

        if ($updatePastEvents !== true) {
            $filter->addFilter(new Calendar_Model_PeriodFilter('period', 'within', [
                'from'  => Tinebase_DateTime::now(),
                'until' => Tinebase_DateTime::now()->addYear(1000)
            ]));
        }

        $aclChecks = Calendar_Controller_Event::getInstance()->doContainerACLChecks(false);
        $currentUser = Tinebase_Core::getUser();
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        Tinebase_Core::set(Tinebase_Core::USER, $setupUser);

        $events = Calendar_Controller_Event::getInstance()->search($filter);

        foreach($events as $event) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Updating event {$event->id} location from '{$event->location}' to '{$newName}'");
            $event->location = $newName;
            try {
                Calendar_Controller_Event::getInstance()->update($event);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " could not update event {$event->id} location from '{$event->location}' to '{$newName}'" . $e);
            }
        }

        Tinebase_Core::set(Tinebase_Core::USER, $currentUser);
        Calendar_Controller_Event::getInstance()->doContainerACLChecks($aclChecks);
    }


    protected function _setIconXprops($_record, $_container)
    {

        $resource_type = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)->getValue($_record['type']);
        $_container->xprops()['Calendar']['Resource']['resource_icon'] = Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_TYPES)
            ->getKeyfieldRecordByValue($resource_type)['icon'];
        Tinebase_Container::getInstance()->update($_container);
    }

    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.',
            $_oldRecord = null)
    {
        if (! $this->_doContainerACLChecks) {
            return true;
        }

        if (! is_object($user = Tinebase_Core::getUser())) {
            throw new Tinebase_Exception_AccessDenied('User object required to check grants');
        }

        $hasGrant = false;

        switch ($_action) {
            case self::ACTION_GET:
                $hasGrant = $user->hasGrant($_record->container_id, Calendar_Model_ResourceGrants::RESOURCE_READ) ||
                    $user->hasGrant($_record->container_id, Calendar_Model_ResourceGrants::RESOURCE_ADMIN);
                break;
            case self::ACTION_CREATE:
                $hasGrant = $user->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
                break;
            case self::ACTION_UPDATE:
                $hasGrant = $user->hasGrant($_record->container_id, Calendar_Model_ResourceGrants::RESOURCE_EDIT) ||
                    $user->hasGrant($_record->container_id, Calendar_Model_ResourceGrants::RESOURCE_ADMIN);
                break;
            case self::ACTION_DELETE:
                $hasGrant = $user->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES);
                break;
        }

        if (! $hasGrant) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $_action . ' in container ' . $_record->container_id);
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
        }

        return $hasGrant;
    }
    
    /**
     * delete linked objects (notes, relations, ...) of record
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        try {
            Tinebase_Container::getInstance()->deleteContainer($_record->container_id, true);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Exception::log($tenf, false, $_record->toArray());
        }

        return parent::_deleteLinkedObjects($_record);
    }

    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        // event needs to be fired before the actual delete - otherwise for example the resource attender is no longer found ...
        $event = new Calendar_Event_DeleteResource();
        $event->resource = $_record;
        Tinebase_Event::fireEvent($event);

        parent::_deleteRecord($_record);
    }

    /**
     * returns recipients for a resource notification
     *
     *  users who are allowed to edit a resource, should receive a notification
     *
     * @param  Calendar_Model_Resource $_lead
     * @return array          array of int|Addressbook_Model_Contact
     */
    public function getNotificationRecipients(Calendar_Model_Resource $resource)
    {
        $recipients = array();

        $relations = Tinebase_Relations::getInstance()->getRelations(Calendar_Model_Resource::class, 'Sql',
            $resource->getId(), true);

        foreach ($relations as $relation) {
            if (Addressbook_Model_Contact::class === $relation->related_model && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }

        if (empty($recipients)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::'
                . __METHOD__ . '::' . __LINE__ . ' no responsibles found for calendar resource: ' . $resource->getId()
                . ' sending notification to all people having edit access to container ' . $resource->container_id);

            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource->container_id, true);

            /** @var Calendar_Model_ResourceGrants $grant */
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $grant[Calendar_Model_ResourceGrants::RESOURCE_EDIT] == 1) {
                    try {
                        $recipients[] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($grant['account_id'], TRUE);
                    } catch (Addressbook_Exception_NotFound $aenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__
                            . ' Do not send notification to non-existant user: ' . $aenf->getMessage());
                    }
                }
            }
        }
        return $recipients;
    }

    /**
     * @param array $_grants
     */
    public function setRequiredFilterACLget(array $_grants)
    {
        $this->_requiredFilterACLget = $_grants;
    }
}
