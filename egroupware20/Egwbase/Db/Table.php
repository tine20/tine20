<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Db
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$

/**
 * the class provides functions to handle applications
 * 
 */
class Egwbase_Db_Table extends Zend_Db_Table_Abstract
{
    /**
     * wrapper around Zend_Db_Table_Abstract::fetchAll
     *
     * @param strin|array $_where OPTIONAL
     * @param string $_order OPTIONAL
     * @param string $_dir OPTIONAL
     * @param int $_count OPTIONAL
     * @param int $_offset OPTIONAL
     * @throws Exception if $_dir is not ASC or DESC
     * @return the row results per the Zend_Db_Adapter fetch mode.
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = 'ASC', $_count = NULL, $_offset = NULL)
    {
        if($_dir != 'ASC' && $_dir != 'DESC') {
            throw new Exception('$_dir can be only ASC or DESC');
        }
        
        $order = NULL;
        if($_order !== NULL) {
            $order = $_order . ' ' . $_dir;
        }
        
        $rowSet = parent::fetchAll($_where, $order, $_count, $_offset);
        
        return $rowSet;
    }
    
    /**
     * get total count of rows
     *
     */
    public function getTotalCount(array $_where)
    {
        $tableInfo = $this->info();
        
        $result = $this->getAdapter()->fetchOne('SELECT count(*) FROM ' . $tableInfo['name']);
        
        $select = $this->getAdapter()->select()
            ->from($tableInfo['name'], array('count' => 'COUNT(*)'));
            
        foreach($_where as $where) {
            $select->where($where);
        }
        
        $stmt = $this->getAdapter()->query($select);
        $result = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        //error_log(print_r($result, true));
        
        return $result['count'];
    }
    
}