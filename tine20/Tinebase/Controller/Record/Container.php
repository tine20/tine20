<?php
/**
 * Container controller for models that have a matching container for acl handling
 *
 * note: use Tinebase_Controller_Record_Grants if the record itself has the grants and no container_id property
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase controller class for for models that have a matching container for acl handling
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Record_Container extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var bool
     */
    protected $_doGrantChecks = TRUE;

    /**
     * @var string
     *
     * TODO can we remove this?
     */
    protected $_grantsModel = 'Tinebase_Model_Grants';

    /**
     * @var string
     */
    protected $_manageRight = null;

    /**
     * sets personal container id if container id is missing in record - can be overwritten to set a different container
     *
     * @param $_record
     */
    protected function _setContainer(Tinebase_Record_Interface $_record)
    {
        $this->_createContainer($_record);
    }

    /**
     * set relations / tags / alarms / grants
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the original record if one exists
     * @param   boolean $returnUpdatedRelatedData
     * @param   boolean $isCreate
     * @return  Tinebase_Record_Interface
     */
    protected function _setRelatedData(Tinebase_Record_Interface $updatedRecord, Tinebase_Record_Interface $record, Tinebase_Record_Interface $currentRecord = null, $returnUpdatedRelatedData = false, $isCreate = false)
    {
        if ($record->grants instanceof Tinebase_Record_RecordSet && count($record->grants) > 0) {
            $this->setGrants($updatedRecord, $record->grants);
        }

        return parent::_setRelatedData($updatedRecord, $record, $currentRecord, $returnUpdatedRelatedData, $isCreate);
    }

    /**
     * create container for record
     *
     * @param $record
     *
     * @todo    check if container name exists ?
     */
    protected function _createContainer($record)
    {
        $containerName = $this->_getContainerName($record);
        $newContainer = new Tinebase_Model_Container(array(
            'name' => $containerName,
            'type' => Tinebase_Model_Container::TYPE_SHARED,
            'backend' => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'model' => $this->_modelName
        ));
        $grants = call_user_func($this->_grantsModel . '::getPersonalGrants', Tinebase_Core::getUser());

        // add container with grants (all grants for creator) and ignore ACL here
        $container = Tinebase_Container::getInstance()->addContainer(
            $newContainer,
            $grants,
            TRUE
        );
        $record->container_id = $container->getId();
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @return string
     */
    protected function _getContainerName(Tinebase_Record_Interface $record)
    {
        $containerName = $record->getTitle();
        return $containerName;
    }

    /**
     * Returns a set of leads identified by their id's
     * - overwritten because we use different grants here (MANAGE)
     *
     * @param   array $_ids array of record identifiers
     * @param   bool $_ignoreACL don't check acl grants
     * @param null|Tinebase_Record_Expander $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $this->_checkRight('get');

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, array(
            array('field' => 'id', 'operator' => 'in', 'value' => $_ids)
        ));
        $records = $this->search($filter);

        return $records;
    }

    /**
     * @return bool
     */
    public function doGrantChecks()
    {
        $value = (func_num_args() === 1) ? (bool)func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doGrantChecks', $value);
    }

    /**
     * wrapper for Tinebase_Container::hasGrant()
     *
     * @param Tinebase_Record_Interface $_record
     * @param array|string $_grant
     * @return boolean
     */
    public function hasGrant($_record, $_grant)
    {
        $record = $this->_backend->get($_record);

        return Tinebase_Container::getInstance()->hasGrant(
            Tinebase_Core::getUser()->getId(),
            $record->container_id,
            $_grant
        );
    }

    /**
     * get grants assigned to multiple records
     *
     * @param   Tinebase_Record_RecordSet $_records records to get the grants for
     * @param   int|Tinebase_Model_User $_accountId the account to get the grants for
     * @throws  Tinebase_Exception_NotFound
     */
    public function getGrantsOfRecords(Tinebase_Record_RecordSet $_records, $_accountId)
    {
        Tinebase_Container::getInstance()->getGrantsOfRecords($_records, $_accountId);

        foreach ($_records as $record) {
            if (isset($record->container_id['account_grants']) && is_array($record->container_id['account_grants'])) {
                $containerGrantsArray = $record->container_id['account_grants'];

                $account_grants = new $this->_grantsModel($containerGrantsArray);
                $record->account_grants = $account_grants->toArray();

                $containerId = $record->container_id;
                $containerId['account_grants'] = $record->account_grants;
                $record->container_id = $containerId;
            }
        }
    }

    /**
     * returns account_grants of given record
     * - this function caches its result (with cache tag 'container')
     *
     * @param  Tinebase_Model_User|int $_accountId
     * @param  Tinebase_Record_Interface|string $_record
     * @param  bool $_ignoreAcl
     * @return array
     */
    public function getGrantsOfAccount($_accountId, $_record, $_ignoreAcl = FALSE)
    {
        $record = ($_record instanceof Tinebase_Record_Interface) ? $_record : Tinebase_Controller_Record_Container::getInstance()->get($_record);
        $container = Tinebase_Container::getInstance()->getContainerById($record->container_id);
        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId(__METHOD__
            . Tinebase_Model_User::convertUserIdToInt($_accountId) . $record->getId()
            . $_ignoreAcl . $container->last_modified_time);
        $result = $cache->load($cacheId);

        if ($result === FALSE) {
            $containerGrantsArray = Tinebase_Container::getInstance()->getGrantsOfAccount(
                $_accountId,
                $container
            )->toArray();

            $account_grants = new $this->_grantsModel($containerGrantsArray);
            $result = $account_grants->toArray();

            $cache->save($result, $cacheId, array('container'));
        }

        return $result;
    }

    /**
     * get records by grant
     *
     * @param array|string $_grant
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function getRecordsByAcl($_grant, $_onlyIds = FALSE)
    {
        $containerIds = Tinebase_Container::getInstance()->getContainerByACL(
            Tinebase_Core::getUser()->getId(),
            $this->_modelName,
            $_grant,
            TRUE
        );

        $filter = new Tinebase_Model_Filter_FilterGroup(array());
        // NOTE: use id filter instead of container filter because of poor performance of container filter (setValue)
        $filter->addFilter(new Tinebase_Model_Filter_Id('container_id', 'in', $containerIds));

        $result = $this->_backend->search($filter);

        if ($_onlyIds) {
            $result = $result->getArrayOfIds();
        }

        return $result;
    }

    /**
     * returns all grants of a given record
     * - this function caches its result (with cache tag 'container')
     *
     * @param  Tinebase_Record_Interface $_record
     * @param  boolean $_ignoreACL
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_AccessDenied
     */
    public function getRecordGrants($_record, $_ignoreACL = FALSE)
    {
        if (! $_ignoreACL) {
            if (! $this->_hasManageRight()) {
                if (! $this->hasGrant($_record, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied("You nor have the RIGHT either the GRANT to get see all grants for this record");
                }
            }
        }

        $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId(__METHOD__. Tinebase_Core::getUser()->getId()
            . $_record->getId() . $_ignoreACL . $container->last_modified_time);
        $result = $cache->load($cacheId);

        if ($result === FALSE) {
            $result = Tinebase_Container::getInstance()->getGrantsOfContainer($container, true);
            $cache->save($result, $cacheId, array('container'));
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function _hasManageRight()
    {
        if ($this->_manageRight) {
            return $this->checkRight($this->_manageRight, FALSE);
        }

        return true;
    }


    /**
     * delete linked objects (notes, relations, attachments, alarms) of record
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        parent::_deleteLinkedObjects($_record);

        Tinebase_Container::getInstance()->deleteContainer($_record->container_id, true);
    }

    /**
     * set record grants
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_RecordSet $_grants
     * @param boolean $_ignoreACL
     * @throws Tinebase_Exception_AccessDenied
     */
    public function setGrants(Tinebase_Record_Interface $_record, Tinebase_Record_RecordSet $_grants, $_ignoreACL = FALSE)
    {
        if (!$_ignoreACL) {
            if (! $this->_hasManageRight()) {
                if (! $this->hasGrant($_record, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied(
                        "You don't have the RIGHT nor the GRANT to set grants for this record"
                    );
                }
            }
        }

        Tinebase_Container::getInstance()->setGrants($_record->container_id, $_grants, TRUE, FALSE);
    }


    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        if ($this->_getContainerName($currentRecord) !== $this->_getContainerName($updatedRecord)) {
            $container = Tinebase_Container::getInstance()->getContainerById($updatedRecord->container_id);
            $container->name = $this->_getContainerName($updatedRecord);
            Tinebase_Container::getInstance()->update($container);
        }
    }
}
