<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Tinebase_Record_Relation
 * 
 * Tinebase_Record_Relation enables records to define cross application relations to other records.
 * It acts as a gneralised storage backend for the records relation property of these records.
 * 
 * Relations between records have a certain degree (PARENT, CHILD and SIBLING). This degrees are defined
 * in Tinebase_Model_Relation. Moreover Relations are of a type which is defined by the application defining 
 * the relation. In case of users manually created relations this type is 'MANUAL'. This manually created
 * relatiions can also hold a free-form remark.
 * 
 * NOTE: Relations are viewed as time dependend properties of records. As such, relations could
 * be broken, but never become deleted.
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Relation
{

	/**
	 * Holds instance for SQL_TABLE_PREFIX . 'record_relations' table
	 * 
	 * @var Tinebase_Db_Table
	 */
	protected $_db;
	
	/* holdes the instance of the singleton
     *
     * @var Tinebase_Record_Relation
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
    	// temporary on the fly creation of table
    	$this->_db = new Tinebase_Db_Table(array(
    	    'name' => SQL_TABLE_PREFIX . 'record_relations',
    	    'primary' => 'id'
    	));
    	
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Record_Relation
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Record_Relation();
        }
        
        return self::$instance;
    }
    
    /**
     * adds a new relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the new relation
     */
    public function addRelation( $_relation ) {
    	if ($_relation->getId()) {
    		throw new Tinebase_Record_Exception_NotAllowed('Could not add existing relation');
    	}
    	
    	$id = $_relation->generateUID();
    	$_relation->setId($id);
    	$_relation->created_by = Zend_Registry::get('currentAccount')->getId();
    	$_relation->creation_time = Zend_Date::now();
    	
    	if ($_relation->isValid()) {
    		$data = $_relation->toArray();
    		unset($data['related_record']);

    		$this->_db->insert($data);
    		$this->_db->insert($this->_swapRoles($data));
    		
    		return $this->getRelation($id, $_relation['own_model'], $_relation['own_backend'], $_relation['own_id']);
    		
    	} else {
    		throw new Tinebase_Record_Exception_Validation('relation contains invalid data: ' . print_r($_relation->getValidationErrors(), true) );
    	}
    } // end of member function addRelation

    /**
     * breaks a relation
     * 
     * @param Tinebase_Model_Relation $_relation 
     * @return void 
     */
    public function breakRelation( $_id ) {
    	$where = array(
    	    'id = ' . $this->_db->getAdapter()->quote($_id)
    	);
    	
    	$this->_db->update(array(
    	    'is_deleted'   => true,
    	    'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
    	    'deleted_time' => Zend_Date::now()->getIso()
    	), $where);
    } // end of member function breakRelation

    /**
     * breaks all relations, optionally only of given role
     * 
     * @param  Tinebase_Record_Interface $_record
     * @param  string $_degree only breaks relations of given degree
     * @param  string $_type only breaks relations of given type
     * @return void
     */
    public function breakAllRelations( $_record, $_degree = NULL, $_type = NULL ) {
        if (!$_record->getApplication() || !$_record->getId()) {
            throw new Tinebase_Record_Exception_DefinitionFailure();
        }
        
        $where = array(
            'own_model   = ' . $this->_db->getAdapter()->quote(get_class($_record)),
            'own_id      = ' . $this->_db->getAdapter()->quote($_record->getId())
        );
        if ($_degree) {
            $where[] = $this->_db->getAdapter()->quoteInto('own_degree = ?', $_degree);
        }
        if ($_type) {
            $where[] = $this->_db->getAdapter()->quoteInto('type = ?', $_type);
        }
        
        $this->_db->update(array(
            'is_deleted'   => true,
            'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
            'deleted_time' => Zend_Date::now()->getIso()
        ), $where);
    } // end of member function breakAllRelations

    /**
     * returns all relations of a given record and optionally only of given role
     * 
     * @param Tinebase_Record_Interface $_record 
     * @param string $_role filter by role
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getAllRelations( $_record, $_degree = NULL, $_type = NULL  ) {
        if (!$_record->getApplication() || !$_record->getId()) {
            throw new Tinebase_Record_Exception_DefinitionFailure();
        }
        
    	$where = array(
    	    'own_model   = ' . $this->_db->getAdapter()->quote(get_class($_record)),
            'own_id      = ' . $this->_db->getAdapter()->quote($_record->getId()),
    	    'is_deleted      = FALSE'
    	);
    	if ($_degree) {
            $where[] = $this->_db->getAdapter()->quoteInto('own_degree = ?', $_degree);
        }
        if ($_type) {
            $where[] = $this->_db->getAdapter()->quoteInto('type = ?', $_type);
        }
        
        $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
        foreach ($this->_db->fetchAll($where) as $relation) {
        	$relations->addRecord(new Tinebase_Model_Relation($relation->toArray(), true));
        }
   		return $relations; 
    } // end of member function getAllRelations
    
    /**
     * returns a relation spechified by a given id
     *
     * @param int $_id
     * @param bool $_returnDeleted
     * @return Tinebase_Record_Relation
     */
    public function getRelation($_id, $_ownModel, $_ownBackend, $_ownId, $_returnBroken = false)
    {
        $where = array(
            $this->_db->getAdapter()->quoteInto('id = ?', $_id),
            $this->_db->getAdapter()->quoteInto('own_model = ?', $_ownModel),
            $this->_db->getAdapter()->quoteInto('own_backend = ?', $_ownBackend),
            $this->_db->getAdapter()->quoteInto('own_id = ?', $_ownId),
        );
        if ($_returnBroken !== true) {
            $where[] = 'is_deleted = FALSE';
        }
    	$relationRow = $this->_db->fetchRow($where);
    	
    	if($relationRow) {
    		return new Tinebase_Model_Relation($relationRow->toArray(), true);
    	} else {
    		throw new Tinebase_Record_Exception_NotDefined("No relation found.");
    	}
    	
    } // end of member function getRelationById
    
    /**
     * swaps roles own/related
     * 
     * @param  array data of a relation
     * @return array data with swaped roles
     */
    protected function _swapRoles($_data)
    {
        $data = $_data;
        $data['own_model']       = $_data['related_model'];
        $data['own_backend']     = $_data['related_backend'];
        $data['own_id']          = $_data['related_id'];
        $data['related_model']   = $_data['own_model'];
        $data['related_backend'] = $_data['own_backend'];
        $data['related_id']      = $_data['own_id'];
        switch ($_data['own_degree']) {
            case Tinebase_Model_Relation::DEGREE_PARENT:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_CHILD;
                break;
            case Tinebase_Model_Relation::DEGREE_CHILD:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_PARENT;
                break;
        }
        return $data;
    }
} // end of Tinebase_Record_Relation
?>
