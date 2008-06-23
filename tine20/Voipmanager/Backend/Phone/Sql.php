<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * 
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Phone_Sql implements Voipmanager_Backend_Phone_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    
    
	/**
	 * the constructor
	 */
    public function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    

    
    /*
	 * get Lines
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Line
	 */
    public function getAsteriskLines($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "callerid,context,fullcontact,ipaddr";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        
        $select = $this->_db->select()
            ->from(array('voipmanager' => SQL_TABLE_PREFIX . 'asterisk_lines'), array(
                  'id',
                  'name',
                  'accountcode',
                  'amaflags',
                  'callgroup',
                  'callerid',
                  'canreinvite',
                  'context',
                  'defaultip',
                  'dtmfmode',
                  'fromuser',
                  'fromdomain',
                  'fullcontact',
                  'host',
                  'insecure',
                  'language',
                  'mailbox',
                  'md5secret',
                  'nat',
                  'deny',
                  'permit',
                  'mask',
                  'pickupgroup',
                  'port',
                  'qualify',
                  'restrictcid',
                  'rtptimeout',
                  'rtpholdtimeout',
                  'secret',
                  'type',
                  'username',
                  'disallow',
                  'allow',
                  'musiconhold',
                  'regseconds',
                  'ipaddr',
                  'regexten',
                  'cancallforward',
                  'setvar',
                  'notifyringing',
                  'useclientcode',
                  'authuser',
                  'call-limit',
                  'busy-level')
                  );

        $select->order($_sort.' '.$_dir);

        foreach($where as $whereStatement) {
            $select->where($whereStatement);
        }               
        //echo  $select->__toString();
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Line', $rows);
		
        return $result;
	}
    
    
	/**
	 * get Line by id
	 * 
     * @param string $_id
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Line
	 */
    public function getLineById($_lineId)
    {	
        $lineId = Voipmanager_Model_Line::convertLineIdToInt($_lineId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'asterisk_lines')->where($this->_db->quoteInto('id = ?', $lineId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('line not found');
        }
#       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_Line', $row);
        $result = new Voipmanager_Model_Line($row);
        return $result;
	}
	   
    /**
     * add new line
     *
     * @param Voipmanager_Model_Line $_line the line data
     * @return Voipmanager_Model_Line
     */
    public function addLine (Voipmanager_Model_Line $_line)
    {
        if (! $_line->isValid()) {
            throw new Exception('invalid line');
        }

        if ( empty($_line->id) ) {
            $_line->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $line = $_line->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_lines', $line);

        return $this->getLineById($_line->getId());
    }
    
    /**
     * update an existing line
     *
     * @param Voipmanager_Model_Line $_line the line data
     * @return Voipmanager_Model_Line
     */
    public function updateLine (Voipmanager_Model_Line $_line)
    {
        if (! $_line->isValid()) {
            throw new Exception('invalid line');
        }
        $lineId = $_line->getId();
        $lineData = $_line->toArray();
        unset($lineData['id']);

        $where = array($this->_db->quoteInto('id = ?', $lineId));
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_lines', $lineData, $where);
        
        return $this->getLineById($lineId);
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
