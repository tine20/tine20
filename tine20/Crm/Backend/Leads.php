<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        rename this to Crm_Backend_Lead
 */


/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Leads extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'metacrm_lead';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Lead';
    
    /**
     * getGroupCountForField
     * 
     * @param $_filter
     * @param $_field
     * @return unknown_type
     * 
     * @todo generalize
     * @todo write test
     */
    public function getGroupCountForField($_filter, $_field)
    {     
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), array(
            $_field             => $_field,
            'count'             => 'COUNT(' . $_field . ')',
        ));
        $select->group($_field);
        $this->_addFilter($select, $_filter);
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row[$_field]] = $row['count'];
        }
        
        return $result;
    }
    
}
