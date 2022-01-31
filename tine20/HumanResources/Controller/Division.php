<?php declare(strict_types=1);
/**
 * Division controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Division controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Division extends Tinebase_Controller_Record_Container
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_Division::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => HumanResources_Model_Division::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);

        $this->_grantsModel = HumanResources_Model_DivisionGrants::class;
        $this->_manageRight = HumanResources_Acl_Rights::ADD_DIVISIONS;
        $this->_purgeRecords = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        // create needs ADD_DIVISIONS
        if (self::ACTION_CREATE === $_action) {
            if (!Tinebase_Core::getUser()
                    ->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::ADD_DIVISIONS)) {
                throw new Tinebase_Exception_AccessDenied(HumanResources_Acl_Rights::ADD_DIVISIONS .
                    ' right required to ' . $_action);
            }
        }
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // standard actions are use for the division itself.
        // everybody can GET, create needs ADD_DIVISIONS which is checked in _checkRight, so nothing to do here, do not call parent!
        if (self::ACTION_GET === $_action || self::ACTION_CREATE === $_action) {
            return true;
        }
        // this needs admin
        if (self::ACTION_UPDATE === $_action || self::ACTION_DELETE === $_action) {
            if (Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                return true;
            } elseif ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                return false;
            }
        }

        // delegated acl checks from employee, wtr, etc. come in here with non standard actions
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        // everybody can see all divisions
        if (self::ACTION_GET === $_action) {
            return;
        }
        parent::checkFilterACL($_filter, $_action);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $updateObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Container::class,
            'observable_identifier' => $_createdRecord->{HumanResources_Model_Division::FLD_CONTAINER_ID},
            'observer_model'        => HumanResources_Model_Division::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Update::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($updateObserver);

        $deleteObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Container::class,
            'observable_identifier' => $_createdRecord->{HumanResources_Model_Division::FLD_CONTAINER_ID},
            'observer_model'        => HumanResources_Model_Division::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Delete::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteObserver);
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_Abstract && $_eventObject->persistentObserver
                ->observable_model === Tinebase_Model_Container::class) {
            switch (get_class($_eventObject)) {
                case Tinebase_Event_Record_Update::class:
                    if ($_eventObject->observable->is_deleted) {
                        break;
                    }
                    try {
                        $division = $this->get($_eventObject->persistentObserver->observer_identifier);
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        break;
                    }
                    if ($division->{HumanResources_Model_Division::FLD_TITLE} !== $_eventObject->observable->name) {
                        $division->{HumanResources_Model_Division::FLD_TITLE} = $_eventObject->observable->name;
                        $this->update($division);
                    }
                    break;

                case Tinebase_Event_Record_Delete::class:
                    if (static::$_deletingRecordId !== $_eventObject->persistentObserver->observer_identifier) {
                        $this->delete($_eventObject->persistentObserver->observer_identifier);
                    }
                    break;
            }
        }
    }
}
