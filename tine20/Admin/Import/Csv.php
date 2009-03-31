<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Admin csv import class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Abstract $_record
     * @return Tinebase_Record_Interface
     */
    protected function _importRecord($_record)
    {
        // add prefix to login name if given
        if (isset($this->_options['accountLoginNamePrefix']) && isset($_record['accountLoginName'])) {
            $_record['accountLoginName'] = $this->_options['accountLoginNamePrefix'] . $_record['accountLoginName'];
        }
        
        Tinebase_Events::fireEvent(new Admin_Event_BeforeImportUser($_record, $this->_options));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_record->toArray(), true));
        
        // generate passwd
        $password = $_record['accountLoginName'];
        if (isset($this->_options['password'])) {
            $password = $this->_options['password'];
        }
        if (isset($_record['password']) && !empty($_record['password'])) {
            $password = $_record['password'];
        }
            
        if ($_record->isValid()) {   
            if (!$this->_options['dryrun']) {
                $record = $this->_controller->create($_record, $password, $password);
                return $record;
            } else {
                return $_record;
            }
        } else {
            // log it
        }
    }
    
    /**
     * add some more values (primary group)
     *
     * @return array
     */
    protected function _addData()
    {
        if ($this->_modelName == 'Tinebase_Model_FullUser') {
            if (isset($this->_options['group_id'])) {
                $groupId = $this->_options['group_id'];
            } else {
                // add default user group
                $defaultUserGroup = Tinebase_Group::getInstance()->getGroupByName(
                    Tinebase_Config::getInstance()->getConfig('Default User Group')->value
                );
                $groupId = $defaultUserGroup->getId();
            }
            $result = array(
                'accountPrimaryGroup'   => $groupId
            );
        } else {
            $result = parent::_addData();
        }
        
        return $result;
    }
}
