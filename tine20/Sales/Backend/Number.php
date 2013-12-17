<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for numbers
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_Number extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_numbers';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_Number';
    
    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';

    /**
     * finds the record for the specified modelName
     * 
     * @param string $modelName
     * @return mixed
     */
    protected function _findNumberRecord($modelName)
    {
        // get number for model
        $select = $this->_getSelect();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('model') . ' = ? ', $modelName));
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        return $queryResult;
    }
    
    /**
     * get next number identified by $model (e.g. Sales_Model_Contract) and update db
     *
     * @param   string $model
     * @param   integer $userId
     * @return  integer
     * 
     * @todo    lock db
     */
    public function getNext($model, $userId)
    {
        $queryResult = $this->_findNumberRecord($model);
        
        if (! $queryResult) {
            // find latest used number if there is already one or more numbers has been created manually
            
            $controller = Tinebase_Core::getApplicationInstance($model);
            $latestRecord = $controller->getAll('number', 'DESC')->getFirstRecord();
            
            $nextNumber = $latestRecord ? intval($latestRecord->number) + 1 : 1;
            
            // create new number circle
            $number = new Sales_Model_Number(array(
                'model'       => $model,
                'number'      => $nextNumber,
                'account_id'  => $userId,
                'update_time' => Tinebase_DateTime::now()
            ), TRUE);
            
            $number = $this->create($number);
        } else {
            // increase and update
            $number = new Sales_Model_Number($queryResult);
            
            $number->number++;
            $number->account_id = $userId;
            $number->update_time = Tinebase_DateTime::now();
            
            $number = $this->update($number);
        }
        
        return $number;
    }
    
    /**
     * returns the lastest used number for the given model
     * 
     * @param string $model
     * @return number|mixed
     */
    public function getCurrent($model)
    {
        $queryResult = $this->_findNumberRecord($model);
        
        if (! $queryResult) {
            $controller = Tinebase_Core::getApplicationInstance($model);
            $latestRecord = $controller->getAll('number', 'DESC')->getFirstRecord();
            
            return $latestRecord ? $latestRecord->number : 0;
            
        } else {
            return $queryResult['number'];
        }
        
    }
    
    /**
     * sets the current number
     * 
     * @param string $model
     * @param integer $number
     * @return boolean
     */
    public function setCurrent($model, $number)
    {
        $this->_db->query('UPDATE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $this->_tableName) . ' SET ' . $this->_db->quoteIdentifier('number') . ' = ' . intval($number) . ' WHERE ' . $this->_db->quoteInto($this->_db->quoteIdentifier('model') . ' = ? ', $model));
        
        return true;
    }
}
