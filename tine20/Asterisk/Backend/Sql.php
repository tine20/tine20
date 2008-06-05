<?php
/**
 * Tine 2.0
 *
 * @package     Asterisk Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * 
 *
 * @package  Asterisk
 */
class Asterisk_Backend_Sql implements Asterisk_Backend_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    
	/**
	* Instance of Asterisk_Backend_Sql_Phones
	*
	* @var Asterisk_Backend_Sql_Phones
	*/
    protected $phoneTable;
    
	/**
	* the constructor
	*
	*/
    public function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->phoneTable      		= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'asterisk_snom_phones'));
    }
    
	/**
	 * get Phones
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
	 */
    public function getPhones($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        if(!empty($_filter)) {
            $_fields = "macaddress,phonemodel,phoneipaddress,description";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        
        $select = $this->_db->select()
            ->from(array('asterisk' => SQL_TABLE_PREFIX . 'asterisk_snom_phones'), array(
                'id',
                'macaddress',
                'phonemodel',
                'phoneswversion',
                'phoneipaddress',
                'lastmodify',
                'class_id',
                'description')
            );

        $select->order($_sort.' '.$_dir);

         foreach($where as $whereStatement) {
              $select->where($whereStatement);
         }               
       //echo  $select->__toString();
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Asterisk_Model_Phone', $rows);
		
        return $result;
	}
    
    
     /**
     * add a phone
     *
     * @param Asterisk_Model_Phone $_phoneData the phonedata
     * @return Asterisk_Model_Phone
     */
    public function addPhone (Asterisk_Model_Phone $_phoneData)
    {
        if (! $_phoneData->isValid()) {
            throw new Exception('invalid phone');
        }
        $phoneData = $_phoneData->toArray();
        if (empty($_phoneData->id)) {
            unset($phoneData['id']);
        }

        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_snom_phones', $phoneData);
        $id = $this->_db->lastInsertId(SQL_TABLE_PREFIX . 'asterisk_snom_phones', 'id');
        // if we insert a phone without an id, we need to get back one
        if (empty($_phoneData->id) && $id == 0) {
            throw new Exception("returned phone id is 0");
        }
        // if the phone had no phoneId set, set the id now
        if (empty($_phoneData->id)) {
            $_phoneData->id = $id;
        }
        return $this->getPhoneById($_phoneData->id);
    }
    
    
    /**
     * update an existing phone
     *
     * @param Asterisk_Model_Phone $_phoneData the phonedata
     * @return Asterisk_Model_Phone
     */
    public function updatePhone (Asterisk_Model_Phone $_phoneData)
    {
        if (! $_phoneData->isValid()) {
            throw new Exception('invalid phone');
        }
        $phoneId = Asterisk_Model_Phone::convertPhoneIdToInt($_phoneData);
        $phoneData = $_phoneData->toArray();
        unset($phoneData['id']);

        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_snom_phones', $phoneData, $where);
        return $this->getPhoneById($phoneId);
    }    
    
    
    /**
     * delete phone identified by phone id
     *
     * @param int $_phoneId phone id
     * @return int the number of row deleted
     */
    public function deletePhone ($_phoneId)
    {
        $phoneId = Asterisk_Model_Phone::convertPhoneIdToInt($_phoneId);
        $where = array($this->_db->quoteInto('id = ?', $phoneId) , $this->_db->quoteInto('id = ?', $phoneId));
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_snom_phones', $where);
        return $result;
    }    
    
    
	/**
	 * get Phone by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
	 */
    public function getPhoneById($_phoneId)
    {	
        $phoneId = Asterisk_Model_Phone::convertPhoneIdToInt($_phoneId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'asterisk_snom_phones')->where($this->_db->quoteInto('id = ?', $phoneId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('phone not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Asterisk_Model_Phone', $row);
        $result = new Asterisk_Model_Phone($row);
        return $result;
	}    
    
    
   /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
     */
    protected function _getSearchFilter($_filter, $_fields)
    {
        $where = array();
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            $search_fields = explode(",", $_fields);
            foreach($search_fields AS $search_field) {
                $fields .= " OR " . $search_field . " LIKE ?";    
            }
            $fields = substr($fields,3);
        
            foreach($search_values AS $search_value) {
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('('.$fields.')', '%' . $search_value . '%');                            
            }
        }
        return $where;
    }    
   
}
