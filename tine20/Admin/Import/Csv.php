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
     * the constructor
     *
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param mixed $_controller
     */
    public function __construct(Tinebase_Model_ImportExportDefinition $_definition, $_controller = NULL)
    {
        $this->_createMethod = 'addUser';
        parent::__construct($_definition, $_controller);
    }
    
    /**
     * import single record (create password if in data)
     *
     * @param array $_data
     * @return Tinebase_Record_Interface
     */
    protected function _importRecord($_data)
    {
        $record = parent::_importRecord($_data);
        
        if ((!isset($this->_options['dryrun']) || !$this->_options['dryrun']) && isset($_data['password'])) {
            // set password
            Admin_Controller_User::getInstance()->setAccountPassword($record, $_data['password'], $_data['password']);
        }
        
        return $record;
    }
        
    
    /**
     * add some more values (primary group)
     *
     * @return array
     */
    protected function _addData()
    {
        if ($this->_modelName == 'Tinebase_Model_FullUser') {
            $defaultUserGroup = Tinebase_Group::getInstance()->getGroupByName(
                Tinebase_Config::getInstance()->getConfig('Default User Group')->value
            );
            $result = array(
                'accountPrimaryGroup'   => $defaultUserGroup->getId()
            );
        } else {
            $result = parent::_addData();
        }
        
        return $result;
    }
}
