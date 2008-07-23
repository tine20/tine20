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
 * @todo        delete notes completely or just set the is_deleted flag?
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
    protected $_notesTable;
    
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
        
        $this->_notesTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'notes',
            'primary' => 'id'
        ));
        
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
     * @param  string $_model     model of record
     * @param  string $_backend   backend of record
     * @param  string $_id        id of record
     * @param  string $_type      type of note
     * @return Tinebase_Record_RecordSet of Tinebase_Notes_Model_Note
     */
    public function getNotes($_model, $_backend, $_id, $_type = NULL)
    {
        $backend = ucfirst(strtolower($_backend));

        $where = array(
            'record_model   = ' . $this->_db->quote($_model),
            'record_backend = ' . $this->_db->quote($backend),
            'record_id      = ' . $this->_db->quote($_id),
        );
        
        /*
        if (!$_returnAll) {
            $where[] = 'is_deleted = FALSE';
        }
        */

        if ($_type) {
            $where[] = $this->_db->getAdapter()->quoteInto('note_type_id = ?', $_type);
        }
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, true));
        
        $notes = new Tinebase_Record_RecordSet('Tinebase_Notes_Model_Note');
        foreach ($this->_notesTable->fetchAll($where) as $note) {
            $notes->addRecord(new Tinebase_Notes_Model_Note($note->toArray(), true));
        }
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($notes->toArray(), true));
        
        return $notes;         
    }
    
    /**
     * sets notes of a record
     * 
     * @param Tinebase_Record_Abstract  $_record            the record object
     * @param string                    $_backend           backend (default: 'Sql')
     * @param string                    $_notesProperty     the property in the record where the tags are in (default: 'notes')
     * 
     * @todo add update notes
     */
    public function setNotesOfRecord($_record, $_backend = 'Sql', $_notesProperty = 'notes')
    {
        $model = get_class($_record);
        $backend = ucfirst(strtolower($_backend));        
        
        $notesToSet = $_record[$_notesProperty]->getArrayOfIds();
        $currentNotes = $this->getNotes($model, $backend, $_record->getId())->getArrayOfIds();        
        
        $toAttach = array_diff($notesToSet, $currentNotes);
        $toDetach = array_diff($currentNotes, $notesToSet);

        // delete detached/deleted notes
        $this->deleteNotes($toDetach);
        
        // add new notes        
        foreach ($_record[$_notesProperty] as $note) {
            if (in_array($note->getId(), $toAttach)) {
                $note->record_model = $model;
                $note->record_backend = $backend;
                $note->record_id = $_record->getId();                
                $this->addNote($note);
            }
        }
    }
    
    /**
     * add new note
     *
     * @param Tinebase_Notes_Model_Note $_note
     */
    public function addNote(Tinebase_Notes_Model_Note $_note)
    {
        if (!$_note->getId()) {
            $id = $_note->generateUID();
            $_note->setId($id);
        }

        $_note->created_by = Zend_Registry::get('currentAccount')->getId();
        $_note->creation_time = Zend_Date::now();        
        
        $data = $_note->toArray();

        $this->_notesTable->insert($data);        
    }
    
    /**
     * delete notes
     *
     * @param array $_noteIds
     */
    public function deleteNotes(array $_noteIds)
    {
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_noteIds, true));
        
        if (!empty($_noteIds)) {
            $where = array($this->_db->quoteInto('id in (?)', $_noteIds));
            $this->_notesTable->delete($where);
        }
    }

    /**
     * delete notes
     *
     * @param  string $_model     model of record
     * @param  string $_backend   backend of record
     * @param  string $_id        id of record
     */
    public function deleteNotesOfRecord($_model, $_backend, $_id)
    {
        $backend = ucfirst(strtolower($_backend));
        
        $notes = $this->getNotes($_model, $backend, $_id);
        $this->deleteNotes($notes->getArrayOfIds());
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