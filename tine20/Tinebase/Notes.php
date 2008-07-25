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
 * @todo        add changelog/historylog
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
     * default record backend
     */
    const DEFAULT_RECORD_BACKEND = 'Sql';

    /**
     * number of notes per record for activities panel
     * (NOT the tab panel)
     */
    const NUMBER_RECORD_NOTES = 3;
        
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
    
    /************************** get notes ************************/

    /**
     * search for notes
     *
     * @param Tinebase_Notes_Model_NoteFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet subtype Tinebase_Notes_Model_Note
     */
    public function searchNotes(Tinebase_Notes_Model_NoteFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'notes');
        
        $_filter->appendFilterSql($select);
        $_pagination->appendPagination($select);
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($select->__toString(), true));
        
        $rows = $this->_db->fetchAssoc($select);
        $result = new Tinebase_Record_RecordSet('Tinebase_Notes_Model_Note', $rows, true);

        return $result;
    }
    
    /**
     * count notes
     *
     * @param Tinebase_Notes_Model_NoteFilter $_filter
     * @return int notes count
     */
    public function searchNotesCount(Tinebase_Notes_Model_NoteFilter $_filter)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'notes', array('count' => 'COUNT(id)'));
        
        $_filter->appendFilterSql($select);
        
        $result = $this->_db->fetchOne($select);
        return $result;        
    }
    
    /**
     * get a single note
     *
     * @param string $_noteId
     * @return Tinebase_Notes_Model_Note
     */
    public function getNote($_noteId)
    {
        $row = $this->_notesTable->fetchRow($this->_db->quoteInto('id = ?', $_noteId));
        
        if (!$row) {
            throw new UnderflowException('note not found');
        }
        
        return new Tinebase_Notes_Model_Note($row->toArray());
    }
    
    /**
     * get all notes of a given record (calls searchNotes)
     * 
     * @param  string $_model     model of record
     * @param  string $_id        id of record
     * @param  string $_backend   backend of record
     * @return Tinebase_Record_RecordSet of Tinebase_Notes_Model_Note
     */
    public function getNotesOfRecord($_model, $_id, $_backend = 'Sql')
    {
        $backend = ucfirst(strtolower($_backend));

        $cache = Zend_Registry::get('cache');
        $cacheId = 'getNotesOfRecord' . $_model . $_id . $backend;
        $result = $cache->load($cacheId);
        
        if (!$result) {
            $filter = new Tinebase_Notes_Model_NoteFilter(array(
                array(
                    'field' => 'record_model',
                    'operator' => 'equals',
                    'value' => $_model
                ),
                array(
                    'field' => 'record_backend',
                    'operator' => 'equals',
                    'value' => $backend
                ),
                array(
                    'field' => 'record_id',
                    'operator' => 'equals',
                    'value' => $_id
                )
            ));
            
            $pagination = new Tinebase_Model_Pagination(array(
                'limit' => Tinebase_Notes::NUMBER_RECORD_NOTES,
                'sort'  => 'creation_time',
                'dir'   => 'DESC'
            ));
            
            $result = $this->searchNotes($filter, $pagination);
            
            $cache->save($result, $cacheId, array('notes'));
        }        
        
        return $result;          
    }

    /************************** set / add / delete notes ************************/
    
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
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_record[$_notesProperty], true));
                
        $currentNotesIds = $this->getNotesOfRecord($model, $_record->getId(), $backend)->getArrayOfIds();
                
        if ($_record[$_notesProperty] instanceOf Tinebase_Record_RecordSet) {
            $notesToSet = $_record[$_notesProperty];
        } else {
            $notesToSet = new Tinebase_Record_RecordSet('Tinebase_Notes_Model_Note', $_record[$_notesProperty]);
        }
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($notesToSet->toArray(), true));
                
        //$toAttach = array_diff($notesToSet->getArrayOfIds(), $currentNotesIds);
        $toDetach = array_diff($currentNotesIds, $notesToSet->getArrayOfIds());

        // delete detached/deleted notes
        $this->deleteNotes($toDetach);
        
        // add new notes        
        foreach ($notesToSet as $note) {
            //if (in_array($note->getId(), $toAttach)) {
            if (!$note->getId()) {
                $note->record_model = $model;
                $note->record_backend = $backend;
                $note->record_id = $_record->getId();                
                $this->addNote($note);
            }
        }
        
        // invalidate cache
        Zend_Registry::get('cache')->remove('getNotesOfRecord' . $model . $_record->getId() . $backend);
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

        $accountId = Zend_Registry::get('currentAccount')->getId();
        
        if (empty($accountId)) {
            throw new Exception('no account id set');
        }
        
        $_note->created_by = $accountId;
        $_note->creation_time = Zend_Date::now();        

        $data = $_note->toArray(FALSE, FALSE);

        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($data, true));
        
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
        
        $notes = $this->getNotesOfRecord($_model, $_id, $backend);
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