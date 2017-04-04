<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var Calendar_Controller_Resource
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Resource';
        
        $this->_backend         = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'cal_resources'
        ));
        $this->_backend->setModlogActive(TRUE);
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
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // create a calendar for this resource
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => $_record->name,
            'color'             => '#333399',
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => $this->_backend->getType(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'model'             => 'Calendar_Model_Event'
        )), NULL, TRUE);
        
        if ($_record->grants instanceof Tinebase_Record_RecordSet) {
            Tinebase_Container::getInstance()->setGrants($container->getId(), $_record->grants, TRUE, FALSE);
        }
        
        $_record->container_id = $container->getId();
        $createdRecord = parent::create($_record);

        $updateObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => 'Tinebase_Model_Container',
            'observable_identifier' => $createdRecord->container_id,
            'observer_model'        => $this->_modelName,
            'observer_identifier'   => $createdRecord->getId(),
            'observed_event'        => 'Tinebase_Event_Record_Update'
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($updateObserver);

        $deleteObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => 'Tinebase_Model_Container',
            'observable_identifier' => $createdRecord->container_id,
            'observer_model'        => $this->_modelName,
            'observer_identifier'   => $createdRecord->getId(),
            'observed_event'        => 'Tinebase_Event_Record_Delete'
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteObserver);

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
                    if ($resource->name !== $_eventObject->observable->name) {
                        $resource->name = $_eventObject->observable->name;
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
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
        $container->name = $_record->name;
        Tinebase_Container::getInstance()->update($container);
        
        if ($_record->grants instanceof Tinebase_Record_RecordSet) {
            Tinebase_Container::getInstance()->setGrants($container->getId(), $_record->grants, TRUE, FALSE);
        }
        
        return parent::update($_record);
    }
    
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        $this->doContainerACLChecks($this->_doContainerACLChecks && ! Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES));
        
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }
    
    /**
     * check if user has the right to manage resources
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        $this->doContainerACLChecks($this->_doContainerACLChecks && ! Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES));
        
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Calendar', Calendar_Acl_Rights::MANAGE_RESOURCES)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage resources");
                }
                break;
            default;
               break;
        }
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

        $relations = Tinebase_Relations::getInstance()->getRelations('Calendar_Model_Resource', 'Sql', $resource->getId(), true);

        foreach ($relations as $relation) {
            if ($relation->related_model == 'Addressbook_Model_Contact' && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }

        if (empty($recipients)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for calendar resource: ' .
                $resource->getId() . ' sending notification to all people having edit access to container ' . $resource->container_id);

            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource->container_id, TRUE);

            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $grant[Tinebase_Model_Grants::GRANT_EDIT] == 1) {
                    try {
                        $recipient = Addressbook_Controller_Contact::getInstance()->getContactByUserId($grant['account_id'], TRUE);
                            $recipients[] = $recipient;
                    } catch (Addressbook_Exception_NotFound $aenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__
                            . ' Do not send notification to non-existant user: ' . $aenf->getMessage());
                    }
                }
            }
        }
        return $recipients;
    }
}
