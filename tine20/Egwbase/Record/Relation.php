<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Tinebase_Record_Relation
 * 
 * Tinebase_Record_Relation enables records to define cross application relations to other records.
 * It acts as a gneralised storage backend for the records relation property of these records.
 * 
 * Relations between records have a certain role. The standart roles are PARENT, CHILD and SIBLING.
 * However, this class is not limited to theese roles, as they could be given as free from string.
 * 
 * NOTE: Relations are viewed as time dependend properties of records. As such, relations could
 * be broken, but never become deleted.
 * 
 * @todo rethink: should be handle relations simmular to tags and give them basic acl's?
 * So users could relate records as they like, w.o spamming in global relations scope. With this, 
 * apps would define their real (for all) relations for a global scope to have them visible for anyone.
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
    	Tinebase_Setup_SetupSqlTables::createRelationTable();
    	$this->_db = new Tinebase_Db_Table(array(
    	    'name' => SQL_TABLE_PREFIX . 'record_relations',
    	    'primary' => 'identifier'
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
     * @param Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the new relation
     */
    public function addRelation( $_relation ) {
    	if ($_relation->getId()) {
    		throw new Tinebase_Record_Exception_NotAllowed('Could not add existing relation');
    	}
    	
    	$_relation->created_by = Zend_Registry::get('currentAccount')->getId();
    	$_relation->creation_time = Zend_Date::now();
    	
    	if ($_relation->isValid()) {
    		$data = $_relation->toArray();
    		
    		// resolve apps
    		$application = Tinebase_Application::getInstance();
    		$data['own_application'] = $application->getApplicationByName($_relation->own_application)->app_id;
    		$data['related_application']   = $application->getApplicationByName($_relation->related_application)->app_id;
            
    		$identifier = $this->_db->insert($data);
    		return $this->getRelationById($identifier);
    		
    	} else {
    		throw new Tinebase_Record_Exception_Validation('some fields have invalid content');
    	}
    } // end of member function addRelation

    /**
     * breaks a relation
     * 
     * @param Tinebase_Model_Relation $_relation 
     * @return void 
     */
    public function breakRelation( $_relation ) {
        if ($_relation->getId() && $_relation->isValid()) {
        	$where = array(
        	    'identifier = ' . $_relation->getId()
        	);
        	
        	$this->_db->update(array(
        	    'is_deleted'   => true,
        	    'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
        	    'deleted_time' => Zend_Date::now()->getIso()
        	), $where);
        }
    } // end of member function breakRelation

    /**
     * breaks all relations, optionally only of given role
     * 
     * @param Tinebase_Record_Interface $_record
     * @param string $_role only breaks relations of given role
     * @return void
     */
    public function breakAllRelations( $_record, $_role = NULL ) {
        if (!$_record->getApplication() || !$_record->getId()) {
            throw new Tinebase_Record_Exception_DefinitionFailure();
        }
        
        $where = array(
            'own_application = ' . Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->app_id,
            'own_identifier  = ' . $_record->getId()
        );
        if ($_role) {
        	$where['related_role'] = $_role;
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
    public function getAllRelations( $_record, $_role = NULL ) {
        if (!$_record->getApplication() || !$_record->getId()) {
            throw new Tinebase_Record_Exception_DefinitionFailure();
        }
        
    	$where = array(
    	    'own_application = ' . Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->app_id,
            'own_identifier  =' . $_record->getId(),
    	    'is_deleted      = FALSE'
    	);
    	if ($_role) {
            $where['related_role'] = $_role;
        }
        
        $relations = new Tinebase_Record_RecordSet(array(), 'Tinebase_Model_Relation');
        foreach ($this->_db->fetchAll($where) as $relation) {
        	$relations->addRecord(new Tinebase_Model_Relation($relation->toArray(), true));
        }
   		return $relations; 
    } // end of member function getAllRelations
    
    /**
     * returns a relation spechified by a given identifier
     *
     * @param int $_identifier
     * @param bool $_returnDeleted
     * @return Tinebase_Record_Relation
     */
    public function getRelationById($_identifier, $_returnDeleted = false)
    {
    	$where = "identifier = $_identifier" . ( $_returnDeleted ? '' : ' AND is_deleted = FALSE' );
    	$relationRow = $this->_db->fetchRow($where);
    	
    	if($relationRow) {
    		return new Tinebase_Model_Relation($relationRow->toArray(), true);
    	} else {
    		throw new Tinebase_Record_Exception_NotDefined("No relation with idenditier: '$_identifier' found.");
    	}
    	
    } // end of member function getRelationById
    
} // end of Tinebase_Record_Relation
?>
