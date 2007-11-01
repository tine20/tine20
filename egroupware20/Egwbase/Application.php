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
     * @param int $_applicationId the id of the application
     * @todo code still needs some testing
     * @throws Exception if $_applicationId is not integer and not greater 0
     * @return Egwbase_Record_Application the information about the application
     */
    public function getApplicationById($_applicationId)
    {
        $applicationId = (int)$_applicationId;
        if($applicationId < 1) {
            throw new Exception('$_applicationId must be integer and greater 0');
        }
        
        $row = $this->applicationTable->fetchRow('app_id = ' . $applicationId);
        
        $result = new Egwbase_Record_Application($row->toArray());
        
        return $result;
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
        if(empty($_filter)) {
            $where = NULL;
        } elseif($_filter !== NULL) {
            // $where = array(...);
            $where = NULL;
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