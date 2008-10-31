<?php

/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */
 

/**
 * backend to handle Snom template
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Template extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db
     */
    public function __construct($_db = NULL)
    {
        parent::__construct(SQL_TABLE_PREFIX . 'snom_templates', 'Voipmanager_Model_SnomTemplate', $_db);
    }
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select $_select current where filter
     * @param  Voipmanager_Model_SnomTemplateFilter $_filter the filter values to search for
     */
    protected function _addFilter(Zend_Db_Select $_select, Voipmanager_Model_SnomTemplateFilter $_filter)
    {
        if(!empty($_filter->query)) {
            $_select->where($this->_db->quoteInto('(model LIKE ? OR description LIKE ? OR name LIKE ?)', '%' . $_filter->query . '%'));
        }
    }                   
}
