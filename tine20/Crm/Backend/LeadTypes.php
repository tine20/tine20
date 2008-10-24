<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */


/**
 * interface for lead types class
 *
 * @package     Crm
 */
class Crm_Backend_LeadTypes extends Tinebase_Application_Backend_Sql
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'metacrm_leadtype';
        $this->_modelName = 'Crm_Model_Leadtype';
    	$this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
    }
}
