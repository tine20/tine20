<?php
/**
 * model to handle role members
 *
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines the data type for role members
 *
 * @package     Tinebase
 * @subpackage  Acl
 *  */
class Tinebase_Model_RoleMember extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'RoleMember',
        'recordsName'       => 'RoleMembers', // ngettext('RoleMember', 'RoleMembers', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,

        'titleProperty'     => 'id',
        'appName'           => 'Tinebase',
        'modelName'         => 'RoleMember',

        'fields' => array(
            'role_id'           => array(
                'label'             => 'role_id', //_('role_id')
                'type'              => 'integer',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'account_type'      => array(
                'label'             => 'account_type', //_('account_type')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'account_id'        => array(
                'label'             => 'account_id', //_('account_id')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
        )
    );

    /**
     * @param Tinebase_Record_RecordSet $_recordSet
     * @param Tinebase_Record_RecordSetDiff $_recordSetDiff
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function applyRecordSetDiff(Tinebase_Record_RecordSet $_recordSet, Tinebase_Record_RecordSetDiff $_recordSetDiff)
    {
        $model = $_recordSetDiff->model;
        if ($_recordSet->getRecordClassName() !== $model) {
            throw new Tinebase_Exception_InvalidArgument('try to apply record set diff on a record set of different model!' .
                'record set model: ' . $_recordSet->getRecordClassName() . ', record set diff model: ' . $model);
        }

        /** @var Tinebase_Record_Interface $modelInstance */
        $modelInstance = new $model(array(), true);
        $idProperty = $modelInstance->getIdProperty();

        foreach($_recordSetDiff->removed as $data) {
            $found = false;
            /** @var Tinebase_Model_RoleMember $record */
            foreach ($_recordSet as $record) {
                if (    $record->role_id        === $data['role_id']        &&
                        $record->account_id     === $data['account_id']     &&
                        $record->account_type   === $data['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $_recordSet->removeRecord($record);
            }
        }

        foreach($_recordSetDiff->modified as $data) {
            $diff = new Tinebase_Record_Diff($data);
            $found = false;
            /** @var Tinebase_Model_RoleMember $record */
            foreach ($_recordSet as $record) {
                if (    $record->role_id        === $diff->diff['role_id']        &&
                        $record->account_id     === $diff->diff['account_id']     &&
                        $record->account_type   === $diff->diff['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $record->applyDiff($diff);
            } else {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Did not find the record supposed to be modified with id: ' . $data[$idProperty]);
                throw new Tinebase_Exception_InvalidArgument('Did not find the record supposed to be modified with id: ' . $data[$idProperty]);
            }
        }

        foreach($_recordSetDiff->added as $data) {
            $found = false;
            /** @var Tinebase_Model_RoleMember $record */
            foreach ($_recordSet as $record) {
                if (    $record->role_id        === $data['role_id']        &&
                        $record->account_id     === $data['account_id']     &&
                        $record->account_type   === $data['account_type']       ) {
                    $found = true;
                    break;
                }
            }
            if (true === $found) {
                $_recordSet->removeRecord($record);
            }
            $newRecord = new $model($data);
            $_recordSet->addRecord($newRecord);
        }

        return true;
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSetOne
     * @param Tinebase_Record_RecordSet $_recordSetTwo
     * @param ?Tinebase_Record_DiffContext $context
     * @return Tinebase_Record_RecordSetDiff
     */
    public static function recordSetDiff(Tinebase_Record_RecordSet $_recordSetOne, Tinebase_Record_RecordSet $_recordSetTwo, ?Tinebase_Record_DiffContext $context = null)
    {
        $shallowCopyOne = new Tinebase_Record_RecordSet(self::class);
        $removed = new Tinebase_Record_RecordSet(self::class);
        $added = new Tinebase_Record_RecordSet(self::class);

        foreach ($_recordSetOne as $roleMemberOne) {
            $shallowCopyOne->addRecord($roleMemberOne);
        }

        /** @var Tinebase_Model_RoleMember $roleMemberTwo */
        foreach ($_recordSetTwo as $roleMemberTwo) {
            $found = false;
            /** @var Tinebase_Model_RoleMember $roleMemberTwo */
            foreach ($shallowCopyOne as $roleMemberOne) {
                if (    $roleMemberOne->role_id        === $roleMemberTwo->role_id        &&
                        $roleMemberOne->account_id     === $roleMemberTwo->account_id     &&
                        $roleMemberOne->account_type   === $roleMemberTwo->account_type       ) {
                    $found = true;
                    break;
                }
            }

            if (true === $found) {
                $shallowCopyOne->removeRecord($roleMemberOne);
            } else {
                $added->addRecord($roleMemberTwo);
            }
        }

        /** @var Tinebase_Model_RoleMember $roleMemberTwo */
        foreach ($shallowCopyOne as $roleMemberOne) {
            $removed->addRecord($roleMemberOne);
        }

        $result = new Tinebase_Record_RecordSetDiff(array(
            'model'    => self::class,
            'added'    => $added,
            'removed'  => $removed,
            'modified' => new Tinebase_Record_RecordSet('Tinebase_Record_Diff')
        ));

        return $result;
    }
}
