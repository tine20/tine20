<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Tree_Node_Json extends Tinebase_Convert_Json
{
    /**
     * resolves child records before converting the record set to an array
     *
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $records->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
        /** @noinspection PhpUndefinedMethodInspection */
        $records->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION);

        $this->_resolveXProps($records);

        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveXProps($_records)
    {
        $groups = array();
        $users =  array();
        foreach ($_records as $record) {
            if (!empty($record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION))) {
                foreach ($record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION) as $val) {
                    if (Tinebase_Acl_Rights::ACCOUNT_TYPE_USER ===
                        $val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE]) {
                        $users[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]] = true;
                    } else {
                        $groups[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]] = true;
                    }
                }
            }
        }

        if (count($groups) > 0) {
            foreach (Tinebase_Group::getInstance()->getMultiple(array_keys($groups)) as $group) {
                $groups[$group->getId()] = $group->name;
            }
        }

        if (count($users) > 0) {
            foreach (Tinebase_User::getInstance()->getMultiple(array_keys($users), 'Tinebase_Model_FullUser') as $user) {
                $users[$user->getId()] = $user->accountDisplayName;
            }
        }

        foreach ($_records as $record) {
            if (!empty($record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION))) {
                foreach ($record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION) as &$val) {
                    if (Tinebase_Acl_Rights::ACCOUNT_TYPE_USER ===
                        $val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE]) {
                        if (isset($users[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]])) {
                            $val['accountName'] = $users[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]];
                        }
                    } else {
                        if (isset($groups[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]])) {
                            $val['accountName'] = $groups[$val[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]];
                        }
                    }
                }
            }
        }
    }

    /**
     * resolves child records after converting the record set to an array
     *
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     *
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false)
    {
        $result = parent::_resolveAfterToArray($result, $modelConfiguration, $multiple);
        $result = $this->_resolveGrants($result);
        return $result;
    }


    protected function _resolveGrants($result)
    {
        if (isset($result['grants'])) {
            $result['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($result['grants']);
        } else {
            foreach ($result as &$record) {
                if (isset($record['grants'])) {
                    $record['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($record['grants']);
                }
            }
        }

        return $result;
    }
}
