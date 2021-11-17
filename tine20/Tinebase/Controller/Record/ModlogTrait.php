<?php

/**
 * Trait to create modlogs. Needs to be used in Fake Controllers to work around controller pattern break outs, like groups, users, etc.
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Trait to create modlogs
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
trait Tinebase_Controller_Record_ModlogTrait
{

    /**
     * application backend class
     *
     * @var Tinebase_Backend_Sql_Interface
     */
    protected $_backend;


    /**
     * omit mod log for this records
     *
     * @var boolean
     */
    protected $_omitModLog = FALSE;

    /**
     * get backend type
     *
     * @return string
     */
    protected function _getBackendType()
    {
        $type = $this->_backend && method_exists($this->_backend, 'getType') ? $this->_backend->getType() : 'Sql';
        return $type;
    }

    /**
     * write modlog
     *
     * @param Tinebase_Record_Interface|null $_newRecord
     * @param Tinebase_Record_Interface|null $_oldRecord
     * @return NULL|Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _writeModLog($_newRecord, $_oldRecord)
    {
        if (null !== $_newRecord) {
            $notNullRecord = $_newRecord;
        } else {
            $notNullRecord = $_oldRecord;
        }
        if (! is_object($notNullRecord)) {
            throw new Tinebase_Exception_InvalidArgument('record object expected');
        }

        $bchub = Tinebase_BroadcastHub::getInstance();
        if ($bchub->isActive()) {
            if (null === $_newRecord) {
                $verb = 'delete';
                $cId = $notNullRecord->getContainerId();
            } elseif (null === $_oldRecord) {
                $verb = 'create';
                $cId = $notNullRecord->getContainerId();
            } else {
                $verb = 'update';
                $cId = $_oldRecord->getContainerId();
            }
            $id = $notNullRecord->getId();
            $bchub->push($verb, get_class($notNullRecord), $id, $cId);
        }

        if (! $notNullRecord->has('created_by') || $this->_omitModLog === TRUE) {
            return NULL;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Writing modlog for ' . get_class($notNullRecord));

        $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLog($_newRecord, $_oldRecord, $this->_modelName,
            $this->_getBackendType(), $notNullRecord->getId());

        return $currentMods;
    }

    /**
     * set/get modlog active
     *
     * @param  boolean $setTo
     * @return bool
     * @throws Tinebase_Exception_NotFound
     */
    public function modlogActive($setTo = null)
    {
        if (! $this->_backend) {
            $currValue = ! $this->_omitModLog;
        } else {
            $currValue = $this->_backend->getModlogActive();
        }

        if (NULL !== $setTo) {
            $setTo = (bool)$setTo;
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Resetting modlog active to ' . (int) $setTo);
            if ($this->_backend) {
                $this->_backend->setModlogActive($setTo);
            }
            $this->_omitModLog = ! $setTo;
        }

        return $currValue;
    }
}
