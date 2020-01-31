<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  File
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class of persistant temp files
 * 
 * This class handles generation of tempfiles and registers them in a tempFile table.
 * To access a tempFile, the session of the client must match
 * 
 * @package     Tinebase
 * @subpackage  File
 */
class Tinebase_TempFile extends Tinebase_Backend_Sql_Abstract implements Tinebase_Controller_Interface
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'temp_files';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_TempFile';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_TempFile
     */
    private static $_instance = NULL;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_TempFile
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_TempFile();
        }
        
        return self::$_instance;
    }
    
    /**
     * get temp file description from db
     *
     * @param mixed $_fileId
     * @return Tinebase_Model_TempFile
     */
    public function getTempFile($_fileId)
    {
        $fileId = is_array($_fileId) ? $_fileId['id'] : $_fileId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Fetching temp file with id " . print_r($fileId, true));
        
        $select = $this->_getSelect('*');
        $select->where($this->_db->quoteIdentifier('id') . ' = ?', $fileId)
               ->where($this->_db->quoteIdentifier('session_id') . ' = ?', Tinebase_Core::getSessionId(/* $generateUid */ false));

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Could not fetch row with id $fileId from temp_files table.");
            return NULL;
        }

        $result = new Tinebase_Model_TempFile($queryResult);
        return $result;
    }
    
    /**
     * uploads a file and saves it in the db
     *
     * @todo separate out frontend code
     * @todo work on a file model
     *  
     * @return Tinebase_Model_TempFile
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function uploadTempFile()
    {
        $path = self::getTempPath();
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " XMLHttpRequest style upload to path " . $path);
            
            $name =       base64_decode($_SERVER['HTTP_X_FILE_NAME']);
            $size =       (double) $_SERVER['HTTP_X_FILE_SIZE'];
            $type =       $_SERVER['HTTP_X_FILE_TYPE'];
            $error =      0;
            
            if ($name === false) {
                throw new Tinebase_Exception('Can\'t decode base64 string, no base64 provided?');
            }
            
            $success = copy("php://input", $path);
            if (! $success) {
                // try again with stream_copy_to_stream
                $input = fopen("php://input", 'r');
                if (! $input) {
                    throw new Tinebase_Exception_NotFound('No valid upload file found or some other error occurred while uploading! ');
                }
                $tempfileHandle = fopen($path, "w");
                if (! $tempfileHandle) {
                    throw new Tinebase_Exception('Could not open tempfile while uploading! ');
                }
                $size = (double) stream_copy_to_stream($input, $tempfileHandle);
                fclose($input);
                fclose($tempfileHandle);
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " successfully created tempfile at {$path}");
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " Plain old form style upload");
            
            $uploadedFile = $_FILES['file'];
            
            $name  = $uploadedFile['name'];
            $size  = (double) $uploadedFile['size'];
            $type  = $uploadedFile['type'];
            $error = $uploadedFile['error'];
            
            if ($uploadedFile['error'] == UPLOAD_ERR_FORM_SIZE) {
                throw new Tinebase_Exception('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
            }
            if (! move_uploaded_file($uploadedFile['tmp_name'], $path)) {
                throw new Tinebase_Exception_NotFound('No valid upload file found or some other error occurred while uploading! ' . print_r($uploadedFile, true));
            }
        }

        if (Tinebase_FileSystem_AVScan_Factory::MODE_OFF !== Tinebase_Config::getInstance()
                ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE}) {
            $avResult = Tinebase_FileSystem_AVScan_Factory::getScanner()->scan($fh = fopen($path, 'r'));
            fclose($fh);
            if ($avResult->result === Tinebase_FileSystem_AVScan_Result::RESULT_FOUND) {
                unlink($path);
                throw new Tinebase_Exception_Backend('av scan found: ' . $avResult->message);
            }
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);
        
        return $this->createTempFile($path, $name, $mimeType ?: $type, $size, $error);
    }
    
    /**
     * creates temp filename
     * 
     * @throws Tinebase_Exception_UnexpectedValue
     * @return string
     */
    public static function getTempPath()
    {
        $path = tempnam(Tinebase_Core::getTempDir(), 'tine_tempfile_');
        if (! $path) {
            throw new Tinebase_Exception_UnexpectedValue('Can not upload file, tempnam() could not return a valid filename!');
        }
        return $path;
    }
    
    /**
     * create new temp file
     * 
     * @param string $_path
     * @param string $_name
     * @param string $_type
     * @param integer $_size
     * @param integer $_error
     * @return Tinebase_Model_TempFile
     */
    public function createTempFile($_path, $_name = 'tempfile.tmp', $_type = 'unknown', $_size = 0, $_error = 0)
    {
        // sanitize filename (convert to utf8)
        $filename = Tinebase_Helper::mbConvertTo($_name);
        
        $id = Tinebase_Model_TempFile::generateUID();
        $tempFile = new Tinebase_Model_TempFile(array(
           'id'          => $id,
           'session_id'  => Tinebase_Core::getSessionId(/* $generateUid */ false),
           'time'        => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
           'path'        => $_path,
           'name'        => $filename,
           'type'        => ! empty($_type)  ? $_type  : 'unknown',
           'error'       => ! empty($_error) ? $_error : 0,
           'size'        => ! empty($_size)  ? (double) $_size  : (double) filesize($_path),
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " tempfile data: " . print_r($tempFile->toArray(), TRUE));
        
        $this->create($tempFile);
        
        return $tempFile;
    }
    
    /**
     * joins all given tempfiles in given order to a single new tempFile
     * 
     * @param Tinebase_Record_RecordSet $_tempFiles
     * @return Tinebase_Model_TempFile
     */
    public function joinTempFiles($_tempFiles)
    {
        $path = self::getTempPath();
        $name = preg_replace('/\.\d+\.chunk$/', '', $_tempFiles->getFirstRecord()->name);
        $type = $_tempFiles->getFirstRecord()->type;
        $size = 0.0;
        
        $fJoin = fopen($path, 'w+b');
        foreach ($_tempFiles as $tempFile) {
            $fChunk = @fopen($tempFile->path, "rb");
            if (! $fChunk) {
                throw new Tinebase_Exception_UnexpectedValue("Can not open chunk {$tempFile->id}");
            }
            
            // NOTE: stream_copy_to_stream is about 15% slower
            while (!feof($fChunk)) {
                $bytesWritten = fwrite($fJoin, fread($fChunk, 2097152 /* 2 MB */));
                $size += (double) $bytesWritten;
            }
            fclose($fChunk);
        }

        if (Tinebase_FileSystem_AVScan_Factory::MODE_OFF !== Tinebase_Config::getInstance()
                ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE}) {
            $avResult = Tinebase_FileSystem_AVScan_Factory::getScanner()->scan($fJoin);
            if ($avResult->result === Tinebase_FileSystem_AVScan_Result::RESULT_FOUND) {
                fclose($fJoin);
                unlink($path);
                foreach ($_tempFiles as $tempFile) {
                    unlink($tempFile->path);
                    try {
                        $this->delete($tempFile);
                    } catch (Exception $e) {
                        Tinebase_Exception::log($e);
                    }
                }
                throw new Tinebase_Exception_Backend('av scan found: ' . $avResult->message);
            }
        }
        
        fclose($fJoin);
        
        return $this->createTempFile($path, $name, $type, $size);
    }
    
    /**
     * remove all temp file records before $_date
     * 
     * @param Tinebase_DateTime|string $_date
     * @return bool
     */
    public function clearTableAndTempdir($_date = NULL)
    {
        $date = ($_date === NULL) ? Tinebase_DateTime::now()->subHour(6) : $_date;
        if (! $date instanceof Tinebase_DateTime) {
            $date = new Tinebase_DateTime($date);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removing all temp files prior ' . $date->toString());
        
        $tempfiles = $this->search(new Tinebase_Model_TempFileFilter(array(array(
            'field'     => 'time',
            'operator'  => 'before',
            'value'     => $date
        ))));
        
        foreach ($tempfiles as $file) {
            if (file_exists($file->path)) {
                unlink($file->path);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' File no longer found: ' . $file->path);
            }

            Tinebase_Lock::keepLocksAlive();
        }

        $result = $this->delete($tempfiles->getArrayOfIds());
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $result . ' temp files from database and filesystem.');

        $result = 0;
        foreach (new DirectoryIterator(Tinebase_Core::getTempDir()) as $directoryIterator) {
            $filename = $directoryIterator->getFilename();
            // preserve directories and dot-files
            if (strpos($filename, '.') !== 0 && $directoryIterator->isFile() && $date->isLater(new Tinebase_DateTime($directoryIterator->getMTime()))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Deleting file ' . $filename);
                unlink($directoryIterator->getPathname());
                ++$result;
            }

            Tinebase_Lock::keepLocksAlive();
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $result . ' temp files from filesystem only.');

        return true;
    }
    
    /**
     * open a temp file
     *
     * @param boolean $createTempFile
     * @throws Tinebase_Exception
     * @return resource
     */
    public function openTempFile($createTempFile = true)
    {
        $path = self::getTempPath();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Opening temp file ' . $path);
        
        $handle = fopen($path, 'w+');
        if (! $handle) {
            throw new Tinebase_Exception('Could not create temp file in ' . dirname($path));
        }

        if ($createTempFile) {
            $this->createTempFile($path);
        }
        
        return $handle;
    }

    public function createTempFileFromNode($node)
    {
        $content = Tinebase_FileSystem::getInstance()->getNodeContents($node);
        $path = self::getTempPath();
        file_put_contents($path, $content);
        return $this->createTempFile($path);
    }

    public function createTempFileFromStream($stream)
    {
        $content = stream_get_contents($stream);
        $path = self::getTempPath();
        file_put_contents($path, $content);
        return $this->createTempFile($path);
    }
}
