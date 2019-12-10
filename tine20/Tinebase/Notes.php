<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        delete notes completely or just set the is_deleted flag?
 */

/**
 * Class for handling notes
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Notes implements Tinebase_Backend_Sql_Interface 
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
    const NUMBER_RECORD_NOTES = 8;

    /**
     * max length of note text
     * 
     * @var integer
     */
    const MAX_NOTE_LENGTH = 10000;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * holds the instance of the singleton
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

        $this->_db = Tinebase_Core::getDb();
        
        $this->_notesTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'notes',
            'primary' => 'id'
        ));
        
        $this->_noteTypesTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'note_types',
            'primary' => 'id'
        ));
    }
    
    /************************** sql backend interface ************************/
    
    /**
     * get table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'notes';
    }
    
    /**
     * get table prefix
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->_db->table_prefix;
    }
    
    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }
    
    /**
     * returns the db schema
     * 
     * @return array
     */
    public function getSchema()
    {
        return Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'notes', $this->_db);
    }
    
    /************************** get notes ************************/

    /**
     * search for notes
     *
     * @param Tinebase_Model_NoteFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $ignoreACL
     * @return Tinebase_Record_RecordSet subtype Tinebase_Model_Note
     */
    public function searchNotes(Tinebase_Model_NoteFilter $_filter, Tinebase_Model_Pagination $_pagination = NULL, $ignoreACL = true)
    {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'))
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');
        
        if (! $ignoreACL) {
            $this->_checkFilterACL($_filter);
        }
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $_filter, $this);
        if ($_pagination !== NULL) {
            $_pagination->appendPaginationSql($select);
        }
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Note', $rows, true);

        return $result;
    }
    
    /**
     * checks acl of filter
     * 
     * @param Tinebase_Model_NoteFilter $noteFilter
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_NoteFilter $noteFilter)
    {
        $recordModelFilter = $noteFilter->getFilter('record_model');
        if (empty($recordModelFilter)) {
            throw new Tinebase_Exception_AccessDenied('record model filter required');
        }
        
        $recordIdFilter = $noteFilter->getFilter('record_id');
        if (empty($recordIdFilter) || $recordIdFilter->getOperator() !== 'equals') {
            throw new Tinebase_Exception_AccessDenied('record id filter required or wrong operator');
        }
        
        $recordModel = $recordModelFilter->getValue();
        if (! is_string($recordModel)) {
            throw new Tinebase_Exception_AccessDenied('no explicit record model set in filter');
        }

        $recordId = $recordIdFilter->getValue();
        if (empty($recordId)) {
            $recordIdFilter->setValue('');
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' record ID is empty');
        } else {
            try {
                Tinebase_Core::getApplicationInstance($recordModel)->get($recordId);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Do not fetch record notes because user has no read grant for container');
                $recordIdFilter->setValue('');
            }
        }
    }
    
    /**
     * count notes
     *
     * @param Tinebase_Model_NoteFilter $_filter
     * @param boolean $ignoreACL
     * @return int notes count
     */
    public function searchNotesCount(Tinebase_Model_NoteFilter $_filter, $ignoreACL = true)
    {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'), array('count' => 'COUNT(' . $this->_db->quoteIdentifier('id') . ')'))
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');
        
        if (! $ignoreACL) {
            $this->_checkFilterACL($_filter);
        }
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $_filter, $this);
        
        $result = $this->_db->fetchOne($select);
        return $result;
    }
    
    /**
     * get a single note
     *
     * @param   string $_noteId
     * @return  Tinebase_Model_Note
     * @throws  Tinebase_Exception_NotFound
     */
    public function getNote($_noteId)
    {
        $row = $this->_notesTable->fetchRow($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ? AND '
            . $this->_db->quoteIdentifier('is_deleted') . ' = 0', (string) $_noteId));
        
        if (!$row) {
            throw new Tinebase_Exception_NotFound('Note not found.');
        }
        
        return new Tinebase_Model_Note($row->toArray());
    }
    
    /**
     * get all notes of a given record (calls searchNotes)
     * 
     * @param  string $_model     model of record
     * @param  string $_id        id of record
     * @param  string $_backend   backend of record
     * @param  boolean $_onlyNonSystemNotes get only non-system notes per default
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Note
     */
    public function getNotesOfRecord($_model, $_id, $_backend = 'Sql', $_onlyNonSystemNotes = TRUE)
    {
        $backend = ucfirst(strtolower($_backend));

        $filter = $this->_getNotesFilter($_id, $_model, $backend, $_onlyNonSystemNotes);
        
        $pagination = new Tinebase_Model_Pagination(array(
            'limit' => Tinebase_Notes::NUMBER_RECORD_NOTES,
            'sort'  => 'creation_time',
            'dir'   => 'DESC'
        ));
        
        $result = $this->searchNotes($filter, $pagination);
            
        return $result;
    }
    
    /**
     * get all notes of all given records (calls searchNotes)
     * 
     * @param  Tinebase_Record_RecordSet  $_records       the recordSet
     * @param  string                     $_notesProperty  the property in the record where the notes are in (defaults: 'notes')
     * @param  string                     $_backend   backend of record
     * @return Tinebase_Record_RecordSet|null
     */
    public function getMultipleNotesOfRecords($_records, $_notesProperty = 'notes', $_backend = 'Sql', $_onlyNonSystemNotes = TRUE)
    {
        if (count($_records) == 0) {
            return null;
        }
        
        $modelName = $_records->getRecordClassName();
        $filter = $this->_getNotesFilter($_records->getArrayOfIds(), $modelName, $_backend, $_onlyNonSystemNotes);
        
        // search and add index
        $notesOfRecords = $this->searchNotes($filter);
        $notesOfRecords->addIndices(array('record_id'));
        
        // add notes to records
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting ' . count($notesOfRecords) . ' notes for ' . count($_records) . ' records.');
        foreach($_records as $record) {
            //$record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($modelName, $record->getId(), $_backend);
            $record->{$_notesProperty} = $notesOfRecords->filter('record_id', $record->getId());
        }

        return $notesOfRecords;
    }
    
    /************************** set / add / delete notes ************************/
    
    /**
     * sets notes of a record
     * 
     * @param Tinebase_Record_Interface  $_record            the record object
     * @param string                    $_backend           backend (default: 'Sql')
     * @param string                    $_notesProperty     the property in the record where the tags are in (default: 'notes')
     * 
     * @todo add update notes ?
     */
    public function setNotesOfRecord($_record, $_backend = 'Sql', $_notesProperty = 'notes')
    {
        $model = get_class($_record);
        $backend = ucfirst(strtolower($_backend));
        
        $currentNotes = $this->getNotesOfRecord($model, $_record->getId(), $backend);
        $notes = $_record->$_notesProperty;
        
        if ($notes instanceOf Tinebase_Record_RecordSet) {
            $notesToSet = $notes;
        } else {
            if (count($notes) > 0 && $notes[0] instanceOf Tinebase_Record_Interface) {
                // array of notes records given
                $notesToSet = new Tinebase_Record_RecordSet('Tinebase_Model_Note', $notes);
            } else {
                // array of arrays given
                $notesToSet = new Tinebase_Record_RecordSet('Tinebase_Model_Note');
                foreach($notes as $noteData) {
                    if (!empty($noteData)) {
                        $noteArray = (!is_array($noteData)) ? array('note' => $noteData) : $noteData;
                        if (!isset($noteArray['note_type_id'])) {
                            // get default note type
                            $defaultNote = $this->getNoteTypeByName('note');
                            $noteArray['note_type_id'] = $defaultNote->getId();
                        }
                        try {
                            $note = new Tinebase_Model_Note($noteArray);
                            $notesToSet->addRecord($note);
                            
                        } catch (Tinebase_Exception_Record_Validation $terv) {
                            // discard invalid notes here
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                . ' Note is invalid! '
                                . $terv->getMessage()
                            );
                        }
                    }
                }
            }
            $_record->$_notesProperty = $notesToSet;
        }
        
        $toDetach = array_diff($currentNotes->getArrayOfIds(), $notesToSet->getArrayOfIds());
        $toDelete = new Tinebase_Record_RecordSet('Tinebase_Model_Note');
        foreach ($toDetach as $detachee) {
            $toDelete->addRecord($currentNotes->getById($detachee));
        }

        // delete detached/deleted notes
        $this->deleteNotes($toDelete);

        if (count($notesToSet) > 0) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Adding ' . count($notesToSet) . ' new note(s) to record.');
            foreach ($notesToSet as $note) {
                if (!$note->getId()) {
                    $note->record_model = $model;
                    $note->record_backend = $backend;
                    $note->record_id = $_record->getId();
                    $this->addNote($note);
                }
            }
        }
    }
    
    /**
     * add new note
     *
     * @param Tinebase_Model_Note $_note
     * @param boolean $skipModlog
     */
    public function addNote(Tinebase_Model_Note $_note, $skipModlog = false)
    {
        if (!$_note->getId()) {
            $id = $_note->generateUID();
            $_note->setId($id);
        }

        if (! $skipModlog) {
            $seq = (int)$_note->seq;
            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($_note, 'create');
            $_note->seq = $seq;
        }
        
        $data = $_note->toArray(FALSE, FALSE);

        if (mb_strlen($data['note']) > 65535) {
            $data['note'] = mb_substr($data['note'], 0, 65535);
        }
        
        $this->_notesTable->insert($data);
    }

    /**
     * add new system note
     *
     * @param Tinebase_Record_Interface|string $_record
     * @param string|Tinebase_Mode_User $_userId
     * @param string $_type (created|changed)
     * @param Tinebase_Record_RecordSet|string $_mods (Tinebase_Model_ModificationLog)
     * @param string $_backend   backend of record
     * @return Tinebase_Model_Note|boolean
     * 
     * @todo get field translations from application?
     * @todo attach modlog record (id) to note instead of saving an ugly string
     */
    public function addSystemNote($_record, $_userId = NULL, $_type = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_mods = NULL, $_backend = 'Sql', $_modelName = NULL)
    {
        if (empty($_mods) && $_type === Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' Nothing changed -> do not add "changed" note.');
            return FALSE;
        }
        
        $id = $_record instanceof Tinebase_Record_Interface ? $_record->getId() : $_record;
        $seq = $_record instanceof Tinebase_Record_Interface && $_record->has('seq') ? $_record->seq : 0;
        $modelName = ($_modelName !== NULL) ? $_modelName : (($_record instanceof Tinebase_Record_Interface) ? get_class($_record) : 'unknown');
        if (($_userId === NULL)) {
            $_userId = Tinebase_Core::getUser();
        }
        $user = ($_userId instanceof Tinebase_Model_User) ? $_userId : Tinebase_User::getInstance()->getUserById($_userId);
        
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $noteText = $translate->_($_type) . ' ' . $translate->_('by') . ' ' . $user->accountDisplayName;
        
        if ($_mods !== NULL) {
            if ($_mods instanceof Tinebase_Record_RecordSet && count($_mods) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    .' mods to log: ' . print_r($_mods->toArray(), TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    .' Adding "' . $_type . '" system note note to record (id ' . $id . ')');
                
                $noteText .= ' | ' .$translate->_('Changed fields:');
                foreach ($_mods as $mod) {
                    $modifiedAttribute = $mod->modified_attribute;
                    if (empty($modifiedAttribute)) {
                        $noteText.= ' ' . $this->_getSystemNoteChangeText($mod, $translate);
                    } else {
                        $noteText .= ' ' . $translate->_($mod->modified_attribute) . ' (' . $this->_getSystemNoteChangeText($mod) . ')';
                    }
                }
            } else if (is_string($_mods)) {
                $noteText = $_mods;
            }
        }
        
        $noteType = $this->getNoteTypeByName($_type);
        $note = new Tinebase_Model_Note(array(
            'note_type_id'      => $noteType->getId(),
            'note'              => mb_substr($noteText, 0, self::MAX_NOTE_LENGTH),
            'record_model'      => $modelName,
            'record_backend'    => ucfirst(strtolower($_backend)),
            'record_id'         => $id,
            'seq'               => $seq,
        ));
        
        return $this->addNote($note);
    }
    
    /**
     * get system note change text
     * 
     * @param Tinebase_Model_ModificationLog $modification
     * @param Zend_Translate $translate
     * @return string
     */
    protected function _getSystemNoteChangeText(Tinebase_Model_ModificationLog $modification, Zend_Translate $translate = null)
    {
        $recordProperties = [];
        /** @var Tinebase_Record_Interface $model */
        if (($model = $modification->record_type) && ($mc = $model::getConfiguration())) {
            $recordProperties = $mc->recordFields;
        }
        $modifiedAttribute = $modification->modified_attribute;

        // new ModificationLog implementation
        if (empty($modifiedAttribute)) {
            $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
            $return = '';
            foreach ($diff->diff as $attribute => $value) {

                if (is_array($value) && isset($value['model']) && isset($value['added'])) {
                    $tmpDiff = new Tinebase_Record_RecordSetDiff($value);

                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' fetching translated text for diff: ' . print_r($tmpDiff->toArray(), true));

                    $return .= ' ' . $translate->_($attribute) . ' (' . $tmpDiff->getTranslatedDiffText() . ')';
                } else {
                    $oldData = $diff->oldData[$attribute];

                    if (isset($recordProperties[$attribute]) && ($oldData || $value) &&
                            isset($recordProperties[$attribute]['config']['controllerClassName']) && ($controller =
                            $recordProperties[$attribute]['config']['controllerClassName']::getInstance()) &&
                            method_exists($controller, 'get')) {
                        if ($oldData) {
                            try {
                                $oldDataString = $controller->get($oldData, null, false, true)->getTitle();
                            } catch(Tinebase_Exception_NotFound $e) {
                                $oldDataString = $oldData;
                            }
                        } else {
                            $oldDataString = '';
                        }
                        if ($value) {
                            try {
                                $valueString = $controller->get($value, null, false, true)->getTitle();
                            } catch(Tinebase_Exception_NotFound $e) {
                                $valueString = $value;
                            }
                        } else {
                            $valueString = '';
                        }
                    } else {
                        if (is_array($oldData)) {
                            $oldDataString = '';
                            foreach ($oldData as $key => $val) {
                                if (is_object($val)) {
                                    $val = $val->toArray();
                                }
                                $oldDataString .= ' ' . $key . ': ' . (is_array($val) ? (isset($val['id']) ? $val['id'] : print_r($val,
                                        true)) : $val);
                            }
                        } else {
                            $oldDataString = $oldData;
                        }
                        if (is_array($value)) {
                            $valueString = '';
                            foreach ($value as $key => $val) {
                                if (is_object($val)) {
                                    $val = $val->toArray();
                                }
                                $valueString .= ' ' . $key . ': ' . (is_array($val) ? (isset($val['id']) ? $val['id'] : print_r($val,
                                        true)) : $val);
                            }
                        } else {
                            $valueString = $value;
                        }
                    }

                    if (null !== $oldDataString || (null !== $valueString && '' !== $valueString)) {
                        $return .= ' ' . $translate->_($attribute) . ' (' . $oldDataString . ' -> ' . $valueString . ')';
                    }
                }
            }

            return $return;

        // old ModificationLog implementation
        } else {
            // check if $modification->new_value is json string and record set diff
            // @see 0008546: When edit event, history show "code" ...
            if (Tinebase_Helper::is_json($modification->new_value)) {
                $newValueArray = Zend_Json::decode($modification->new_value);
                if ((isset($newValueArray['model']) || array_key_exists('model', $newValueArray)) && (isset($newValueArray['added']) || array_key_exists('added', $newValueArray))) {
                    $diff = new Tinebase_Record_RecordSetDiff($newValueArray);

                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' fetching translated text for diff: ' . print_r($diff->toArray(), true));

                    return $diff->getTranslatedDiffText();
                }
            }

            return $modification->old_value . ' -> ' . $modification->new_value;
        }
    }
    
    /**
     * add multiple modification system nodes
     * 
     * @param Tinebase_Record_RecordSet $_mods
     * @param string $_userId
     * @param string $modelName
     */
    public function addMultipleModificationSystemNotes($_mods, $_userId, $modelName = null)
    {
        $_mods->addIndices(array('record_id'));
        foreach ($_mods->record_id as $recordId) {
            $modsOfRecord = $_mods->filter('record_id', $recordId);
            $this->addSystemNote($recordId, $_userId, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $modsOfRecord, 'Sql', $modelName);
        }
    }

    /**
     * delete notes
     *
     * @param Tinebase_Record_RecordSet $notes
     */
    public function deleteNotes(Tinebase_Record_RecordSet $notes)
    {
        $sqlBackend = new Tinebase_Backend_Sql(
            array(
                'tableName' => $this->getTableName(),
                'modelName' => 'Tinebase_Model_Note'
            ),
            $this->getAdapter());

        foreach($notes as $note) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($note, 'delete', $note);
            $sqlBackend->update($note);
        }
    }

    /**
     * undelete notes
     *
     * @param array $ids
     */
    public function unDeleteNotes(array $ids)
    {
        $sqlBackend = new Tinebase_Backend_Sql(
            array(
                'tableName' => $this->getTableName(),
                'modelName' => 'Tinebase_Model_Note'
            ),
            $this->getAdapter());

        $notes = $sqlBackend->getMultiple($ids);
        foreach($notes as $note) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($note, 'undelete', $note);
            $sqlBackend->update($note);
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

        $this->deleteNotes($notes);
    }
    
    /**
     * get note filter
     * 
     * @param string|array $_id
     * @param string $_model
     * @param string $_backend
     * @param boolean|optional $onlyNonSystemNotes
     * @return Tinebase_Model_NoteFilter
     */
    protected function _getNotesFilter($_id, $_model, $_backend, $_onlyNonSystemNotes = TRUE)
    {
        $backend = ucfirst(strtolower($_backend));
        
        $filter = new Tinebase_Model_NoteFilter(array(
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
                'operator' => 'in',
                'value' => (array) $_id
            ),
            array(
                'field' => 'note_type_id',
                'operator' => 'in',
                'value' => $this->getNoteTypes($_onlyNonSystemNotes, true)
            )
        ));
        
        return $filter;
    }
    
    /************************** note types *******************/
    
    /**
     * get all note types
     *
     * @param boolean|optional $onlyNonSystemNotes
     * @return Tinebase_Record_RecordSet of Tinebase_Model_NoteType
     */
    public function getNoteTypes($onlyNonSystemNotes = false, $onlyIds = false)
    {
        $select = $this->_db->select()
            ->from(array('note_types' => SQL_TABLE_PREFIX . 'note_types'), ($onlyIds ? 'id' : '*'));
        
        if ($onlyNonSystemNotes) {
            $select->where($this->_db->quoteIdentifier('is_user_type') . ' = 1');
        }
        
        $stmt = $this->_db->query($select);
        
        if ($onlyIds) {
            $types = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
        } else {
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            $types = new Tinebase_Record_RecordSet('Tinebase_Model_NoteType', $rows, true);
        }
        
        return $types;
    }

    /**
     * get note type by name
     *
     * @param string $_name
     * @return Tinebase_Model_NoteType
     * @throws  Tinebase_Exception_NotFound
     */
    public function getNoteTypeByName($_name)
    {
        $row = $this->_noteTypesTable->fetchRow($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name));
        
        if (!$row) {
            throw new Tinebase_Exception_NotFound('Note type not found.');
        }
        
        return new Tinebase_Model_NoteType($row->toArray());
    }
    
    /**
     * add new note type
     *
     * @param Tinebase_Model_NoteType $_noteType
     */
    public function addNoteType(Tinebase_Model_NoteType $_noteType)
    {
        if (!$_noteType->getId()) {
            $id = $_noteType->generateUID();
            $_noteType->setId($id);
        }
        
        $data = $_noteType->toArray();

        $this->_noteTypesTable->insert($data);
    }

    /**
     * update note type
     *
     * @param Tinebase_Model_NoteType $_noteType
     */
    public function updateNoteType(Tinebase_Model_NoteType $_noteType)
    {
        $data = $_noteType->toArray();

        $where  = array(
            $this->_noteTypesTable->getAdapter()->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $_noteType->getId()),
        );
        
        $this->_noteTypesTable->update($data, $where);
    }
    
    /**
     * delete note type
     *
     * @param integer $_noteTypeId
     */
    public function deleteNoteType($_noteTypeId)
    {
        $this->_noteTypesTable->delete($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $_noteTypeId));
    }

    /**
     * Search for records matching given filter
     *
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @param  array|string|boolean $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotImplemented
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     * @throws Tinebase_Exception_NotImplemented
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Return a single record
     *
     * @param string $_id
     * @param boolean $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Returns a set of records identified by their id's
     *
     * @param string|array $_ids Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_Record_RecordSet of Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getMultiple($_ids, $_containerIds = NULL)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface|NULL
     * @throws Tinebase_Exception_NotImplemented
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $data = $_record->toArray(false);

        if (!isset($data['id'])) throw new Tinebase_Exception_Backend('id not set');
        if (mb_strlen($data['note']) > 65535) {
            $data['note'] = mb_substr($data['note'], 0, 65535);
        }

        $this->_notesTable->update($data, $this->_db->quoteInto('id = ?', $data['id']));

        return $_record;
    }

    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     * @throws Tinebase_Exception_NotImplemented
     */
    public function updateMultiple($_ids, $_data)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     * @throws Tinebase_Exception_NotImplemented
     */
    public function delete($_identifier)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * get backend type
     *
     * @return string
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getType()
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * sets modlog active flag
     *
     * @param $_bool
     * @return Tinebase_Backend_Sql_Abstract
     */
    public function setModlogActive($_bool)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * checks if modlog is active or not
     *
     * @return bool
     */
    public function getModlogActive()
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * fetch a single property for all records defined in array of $ids
     *
     * @param array|string $ids
     * @param string $property
     * @return array (key = id, value = property value)
     */
    public function getPropertyByIds($ids, $property)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * get all Notes, including deleted ones, no ACL check
     *
     * @ param boolean $ignoreACL
     * @ param boolean $getDeleted
     * @return Tinebase_Record_RecordSet subtype Tinebase_Model_Note
     */
    public function getAllNotes($orderBy = null, $limit = null, $offset = null)
    {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'));
        if (null !== $orderBy) {
            $select->order($orderBy);
        }
        if (null !== $limit) {
            $select->limit($limit, $offset);
        }

        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Note', $rows, true);

        return $result;
    }

    /**
     * permanently delete notes by id
     *
     * @param array $_ids
     * @return int
     */
    public function purgeNotes(array $_ids)
    {
        return $this->_db->delete(SQL_TABLE_PREFIX . 'notes', $this->_db->quoteInto('id IN (?)', $_ids));
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }
}
