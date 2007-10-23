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
        $this->applicationTable = new Zend_Db_Table_Abstract(array('name' => 'egw_applications'));
    }
    
    /**
     * get list of installed applications
     *
     * @param string $_sort the column name to sort by
     * @param string $_dir the sort direction can be ASC or DESC only
     * @param string $_filter optional search parameter
     * @param int $_limit how many applications to return
     * @param int $_start offset for applications
     * @throws Exception if $_dir is not ASC or DESC
     * @return Egwbase_RecordSet_Application
     */
    public function getApplications($_sort = 'app_id', $_dir = 'ASC', $_filter = NULL, $_limit = NULL, $_start = NULL)
    {
        if($_dir != 'ASC' && $_dir != 'DESC') {
            throw new Exception('$_dir can be only ASC or DESC');
        }

        $sort = $this->applicationTable->getAdapter()->quoteInto("? $_dir", $_sort);

        $where = NULL;
        if($_filter !== NULL) {
            // $where = ...
        }
        
        $rowSet = $this->applicationTable->fetchAll($where, $sort, $_limit, $_start);
        
        $result = new Egwbase_RecordSet_Application($rowSet->toArray());
        
        return $result;
    }
}