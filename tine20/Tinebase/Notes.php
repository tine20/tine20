<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement functions
 * @todo        add tests
 */

/**
 * Class for handling notes
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Notes
{
    /**
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;

    /**
     * @var Tinebase_Db_Table
     */
    protected $_noteTypesTable;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Notes
     */
    private static $_instance = NULL;
        
    /**
     * the singleton pattern
     *
     * @return Tinebase_Notes
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Notes;
        }
        
        return self::$_instance;
    }

    /**
     * the private constructor
     *
     */
    private function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
        
        $this->_noteTypesTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'note_types',
            'primary' => 'id'
        ));
        
    }
    
    /************************** notes ************************/

    /**
     * get all notes of a given record
     * - cache result if caching is activated
     * 
     * @param  string $_model     own model to get relations for
     * @param  string $_backend   own backend to get relations for
     * @param  string $_id        own id to get relations for 
     * @return Tinebase_Record_RecordSet of Tinebase_Relation_Model_Relation
     * 
     * @todo implement
     */
    public function getNotes($_model, $_backend, $_id)
    {
        
    }
    
    /**
     * add new note
     *
     * @param Tinebase_Notes_Model_Note $_note
     * 
     * @todo implement
     */
    public function addNote(Tinebase_Notes_Model_Note $_note)
    {
        
    }
    
    /**
     * delete note
     *
     * @param integer $_noteId
     * 
     * @todo implement
     */
    public function deleteNote($_noteId)
    {
        
    }
    
    /************************** note types *******************/
    
    /**
     * get all note types
     *
     * @return Tinebase_Record_RecordSet of Tinebase_Notes_Model_NoteType
     */
    public function getNoteTypes()
    {
        $types = new Tinebase_Record_RecordSet('Tinebase_Notes_Model_NoteType');
        foreach ($this->_noteTypesTable->fetchAll() as $type) {
            $types->addRecord(new Tinebase_Notes_Model_NoteType($type->toArray(), true));
        }
        return $types;         
    }
    
    /**
     * add new note type
     *
     * @param Tinebase_Notes_Model_NoteType $_noteType
     */
    public function addNoteType(Tinebase_Notes_Model_NoteType $_noteType)
    {
        if (!$_noteType->getId()) {
            $id = $_noteType->generateUID();
            $_noteType->setId($id);
        }
        
        $data = $_noteType->toArray();

        $this->_noteTypesTable->insert($data);
    }

    /**
     * delete note type
     *
     * @param integer $_noteTypeId
     */
    public function deleteNoteType($_noteTypeId)
    {
        $this->_noteTypesTable->delete($this->_db->quoteInto('id = ?', $_noteTypeId));
    }
    
}