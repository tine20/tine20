<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * class of persistant temp files
 * 
 * This class handles generation of tempfiles and registers them in a tempFile table.
 * To access a tempFile, the session of the client must match
 * 
 * @todo automatic garbage collection via cron
 *
 */
class Tinebase_TempFile extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_modlogActive = FALSE;
        parent::__construct(SQL_TABLE_PREFIX . 'temp_files', 'Tinebase_Model_TempFile');
    }
    
    /**
     * get temp file description from db
     *
     * @param unknown_type $_fileId
     * @return Tinebase_Model_TempFile
     */
    public function getTempFile($_fileId)
    {
        $select = $this->_getSelect('*');
        $select->where($this->_db->quoteIdentifier('id') . ' = ?', $_fileId)
               ->where($this->_db->quoteIdentifier('session_id') . ' = ?', session_id());

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
                
        if (!$queryResult) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Could not fetch row with id $_fileId from temp_files table.");
            return NULL;
        }

        $result = new Tinebase_Model_TempFile($queryResult);
        return $result;
    }
    
    /**
     * uploads a file and saves it in the db
     *
     * @return Tinebase_Model_TempFile
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function uploadTempFile()
    {
        $uploadedFile = $_FILES['file'];
        
        $path = tempnam(session_save_path(), 'tine_tempfile_');
        if (!$path) {
            throw new Tinebase_Exception_UnexpectedValue('Can not upload file, tempnam() could not return a valid filename!');
        }
        if (! move_uploaded_file($uploadedFile['tmp_name'], $path)) {
            throw new Tinebase_Exception_NotFound('No valid upload file found!');
        }
        
        $id = Tinebase_Model_TempFile::generateUID();
        $tempFile = new Tinebase_Model_TempFile(array(
           'id'          => $id,
           'session_id'  => session_id(),
           'time'        => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
           'path'        => $path,
           'name'        => $uploadedFile['name'],
           'type'        => $uploadedFile['type'],
           'error'       => $uploadedFile['error'],
           'size'        => $uploadedFile['size'],
        ));
        
        $this->_db->insert($this->_tableName, $tempFile->toArray());
        
        return $tempFile;
    }
}
