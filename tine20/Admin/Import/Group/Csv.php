<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Admin csv import groups class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_Group_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_Ical
     * 
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new static(self::getOptionsArrayFromDefinition($_definition, $_options));
    }
    
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Abstract $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        $members = explode(' ', $_record->members);
        $_record->members = null;
        unset($_record->members);
        
        $this->_setController();
        
        $record = parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        
        $group = Admin_Controller_Group::getInstance()->get($_record->getId());
        $list = Addressbook_Controller_List::getInstance()->createByGroup($group);
        $group->list_id = $list->getId();
        $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
        $be = new Tinebase_Group_Sql();
        $be->updateGroupInSqlBackend($group);
        
        $memberUids = array();
        
        if (! empty($members)) {
            foreach($members as $member) {
                try {
                    $userRecord = Tinebase_User::getInstance()->getUserByLoginName($member);
                    $be->addGroupMember($_record->getId(), $userRecord->accountId);
                } catch (Exception $e) {
                }
            }
        }
        
        return $record;
    }
    
    /**
     * overwrite (non-PHPdoc)
     * @see Tinebase_Import_Abstract::_handleTags()
     */
    protected function _handleTags($_record, $_resolveStrategy = NULL)
    {}
    
    /**
     * set controller
     */
    protected function _setController()
    {
        $this->_controller = Tinebase_Group::getInstance();
    }
}
