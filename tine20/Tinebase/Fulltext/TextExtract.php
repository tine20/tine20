<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Fulltext
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class extract text from files / filesystem nodes
 *
 * @package     Tinebase
 * @subpackage  Fulltext

 */
class Tinebase_Fulltext_TextExtract
{
    protected $_javaBin;
    protected $_tikaJar;

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Fulltext_TextExtract
     */
    private static $_instance = NULL;

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_Fulltext_TextExtract
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Fulltext_TextExtract();
        }

        return self::$_instance;
    }

    /**
     * destroy instance of this class
     */
    public static function destroyInstance()
    {
        self::$_instance = NULL;
    }

    /**
     * constructor
     *
     * @throws Tinebase_Exception_UnexpectedValue
     */
    private function __construct()
    {
        $fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT);

        $this->_javaBin = escapeshellcmd($fulltextConfig->{Tinebase_Config::FULLTEXT_JAVABIN});
        $this->_tikaJar = escapeshellarg($fulltextConfig->{Tinebase_Config::FULLTEXT_TIKAJAR});
    }

    /**
     * @param Tinebase_Model_Tree_FileObject $_fileObject
     * @return bool|string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fileObjectToTempFile(Tinebase_Model_Tree_FileObject $_fileObject)
    {
        if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $_fileObject->type) {
            throw new Tinebase_Exception_InvalidArgument('$_fileObject needs to be of type file only!');
        }

        $tempFileName = Tinebase_TempFile::getTempPath();
        $blobFileName = $_fileObject->getFilesystemPath();

        @exec($this->_javaBin . ' -jar '. $this->_tikaJar . ' -t -eUTF8 '
            . escapeshellarg($blobFileName) . ' > ' . escapeshellarg($tempFileName) . ' 2> /dev/null', $output, $result);

        if ($result !== 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' tika did not return status 0. maybe the java runtime is missing? output:'
                . print_r($output, true) . ' ' . print_r($result, true));
            if (file_exists($tempFileName)) {
                unlink($tempFileName);
            }
            return false;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' tika success!');
        }

        return $tempFileName;
    }
}
