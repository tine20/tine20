<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql backend class for async jobs
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 */
class Tinebase_AsyncJob_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'async_job';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_AsyncJob';
    
    /**
     * get max sequence
     * 
     * @return integer
     */
    public function getMaxSeq()
    {
        $select = $this->_db->select()->from($this->getTablePrefix() . $this->getTableName(), array('MAX(seq)'));
        //    ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name));
        
        $result = $this->_db->fetchOne($select);
        
        return $result;
    }
}
