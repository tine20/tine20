<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Path
 * 
 * filters own ids match result of path search
 * 
 * <code>
 *      'contact'        => array('filter' => 'Tinebase_Model_Filter_Path', 'options' => array(
 *      )
 * </code>     
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Path extends Tinebase_Model_Filter_Text
{
    protected $_controller = null;

    /**
     * @var array
     */
    protected $_pathRecordIds = null;

    /**
     * get path controller
     * 
     * @return Tinebase_Record_Path
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Tinebase_Record_Path::getInstance();
        }
        
        return $this->_controller;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
            . 'Adding Path filter for: ' . $_backend->getModelName());
        
        $this->_resolvePathIds();

        $idField = (isset($this->_options['idProperty']) || array_key_exists('idProperty', $this->_options)) ? $this->_options['idProperty'] : 'id';
        $db = $_backend->getAdapter();
        $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);
        if (empty($this->_pathRecordIds)) {
            $_select->where('1=0');
        } else {
            $_select->where($db->quoteInto("$qField IN (?)", $this->_pathRecordIds));
        }
    }
    
    /**
     * resolve foreign ids
     */
    protected function _resolvePathIds()
    {
        if (! is_array($this->_pathRecordIds)) {
            // TODO this should be improved if it turns out to be a performance issue:
            //  we only need the record_ids here and not complete records, so we could directly use the path sql backend
            //  and just request the property we need
            $this->_pathRecordIds = $this->_getController()->search(new Tinebase_Model_PathFilter(array(
                array('field' => 'query', 'operator' => $this->_operator, 'value' => $this->_value)
            )))->record_id;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign ids: ' 
            . print_r($this->_pathRecordIds, TRUE));
    }
}
