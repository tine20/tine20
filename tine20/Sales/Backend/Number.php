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
        // get number for model
        $select = $this->_getSelect();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('model') . ' = ? ', $model));
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (! $queryResult) {
            // find latest used number if there is already one or more numbers has been created manually
            
            $c = explode('_Model_', $model);
            $cName = $c[0] . '_Controller_' . $c[1];
            $controller = $cName::getInstance();
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
}
