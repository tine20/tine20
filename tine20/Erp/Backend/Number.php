<?php
/**
 * Tine 2.0
 *
 * @package     Erp
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * backend for numbers
 *
 * @package     Erp
 * @subpackage  Backend
 */
class Erp_Backend_Number extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'erp_numbers', 'Erp_Model_Number');
    }
    
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
                
        if (!$queryResult) {
            // create new number circle
            $number = new Erp_Model_Number(array(
                'type'          => $_type,
                'number'        => 1,
                'account_id'    => $_userId,
                'update_time'   => Zend_Date::now()
            ), TRUE);
            
            $number = $this->create($number);
        } else {        
            // increase and update
            $number = new Erp_Model_Number($queryResult);
            
            $number->number++;
            $number->account_id = $_userId;
            $number->update_time = Zend_Date::now();
            
            $number = $this->update($number);
        }
        
        return $number; 
    }
}
