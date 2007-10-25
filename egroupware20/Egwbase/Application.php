<?php
/**
 * the class provides functions to handle applications
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Application
{
    /**
     * the table object for the egw_applications table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $applicationTable;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->applicationTable = new Egwbase_Db_Table(array('name' => 'egw_applications'));
    }
    
    /**
     * returns one application identified by app_id
     *
     * @param unknown_type $_applicationId
     */
    public function getApplicationById($_applicationId)
    {
        
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort optional the column name to sort by
     * @param string $_dir optional the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit optional how many applications to return
     * @param int $_start optional offset for applications
     * @return Egwbase_RecordSet_Application
     */
    public function getApplications($_sort = 'app_id', $_dir = 'ASC', $_filter = NULL, $_limit = NULL, $_start = NULL)
    {
        $where = NULL;
        if($_filter !== NULL) {
            // $where = array(...);
        }
        
        $rowSet = $this->applicationTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

        $result = new Egwbase_RecordSet_Application($rowSet->toArray(), 'Egwbase_Record_Application');

        return $result;
    }    
    
    /**
     * return the total number of applications installed
     *
     * @return int
     */
    public function getTotalApplicationCount()
    {
        $count = $this->applicationTable->getTotalCount();
        
        return $count;
    }
}