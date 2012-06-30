<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * get next number identified by $_type (i.e. contract) and update db
     *
     * @param   string $_type
     * @param   integer $_userId
     * @return  integer
     * 
     * @todo    lock db
     */
    public function getNext($_type, $_userId)
    {
        // get number for type
        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier('type') . ' = ?', $_type);
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            // create new number circle
            $number = new Sales_Model_Number(array(
                'type'          => $_type,
                'number'        => 1,
                'account_id'    => $_userId,
                'update_time'   => Tinebase_DateTime::now()
            ), TRUE);
            
            $number = $this->create($number);
        } else {
            // increase and update
            $number = new Sales_Model_Number($queryResult);
            
            $number->number++;
            $number->account_id = $_userId;
            $number->update_time = Tinebase_DateTime::now();
            
            $number = $this->update($number);
        }
        
        return $number;
    }
}
