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
     */
    private function __construct()
    {
        if (null === ($fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)) || ! $fulltextConfig instanceof Tinebase_Config_Struct) {
            throw new Tinebase_Exception_UnexpectedValue('no fulltext configuration found');
        }

        $this->_javaBin = escapeshellcmd($fulltextConfig->{Tinebase_Config::FULLTEXT_JAVABIN});
        $this->_tikaJar = escapeshellarg($fulltextConfig->{Tinebase_Config::FULLTEXT_TIKAJAR});
    }

    public function nodeToTempFile(Tinebase_Model_Tree_Node $_node)
    {
        $tempFileName = Tinebase_TempFile::getTempPath();
        $blobFileName = Tinebase_FileSystem::getInstance()->getRealPathForNode($_node);

        exec($this->_javaBin . ' -jar '. $this->_tikaJar . ' -t -eUTF8 ' . escapeshellarg($blobFileName) . ' > ' . escapeshellarg($tempFileName));

        return $tempFileName;
    }
}