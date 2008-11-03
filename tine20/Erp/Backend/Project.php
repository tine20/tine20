<?php
/**
 * Tine 2.0
 *
 * @package     Erp
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Leads.php 5174 2008-10-31 14:09:45Z p.schuele@metaways.de $
 */


/**
 * backend for projects
 *
 * @package     Erp
 * @subpackage  Backend
 */
class Erp_Backend_Project extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'erp_projects', 'Erp_Model_Project');
    }

    /**
     * Creates new entry and adds container and project number
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * 
     * @todo    move container & number to controller
     */
    public function create(Tinebase_Record_Interface $_record) {
        
        // add container
        $_record->container_id = Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Projects', 'shared')->getId();
        
        // add number
        $_record->number = $this->_getNextNumber();
        
        return parent::create($_record);
    }
    
    /************************ helper functions ************************/

    /**
     * add the fields to search for to the query
     *
     * @param   Zend_Db_Select           $_select current where filter
     * @param   Erp_Model_ProjectFilter  $_filter the string to search for
     * 
     * @todo    add container filter later
     */
    protected function _addFilter(Zend_Db_Select $_select, Erp_Model_ProjectFilter $_filter)
    {
        //$_select->where($this->_db->quoteInto('container_id IN (?)', $_filter->container));
                        
        if (!empty($_filter->query)) {
            $_select->where($this->_db->quoteInto('(title LIKE ? OR description LIKE ? OR number LIKE ?)', '%' . $_filter->query . '%'));
        }
    }

    /**
     * fetches the next incremental project number from erp_numbers
     *
     * @return integer number
     * @todo    move to controller
     */
    protected function _getNextNumber()
    {
        //$numberBackend = new Erp_Backend_Number();
        //return $numberBackend->getNext(Erp_Model_Number::TYPE_PROJECT);
        
        return 1;
    }
}
