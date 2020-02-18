<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Fulltext
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to index text content
 *
 * @package     Tinebase
 * @subpackage  Fulltext

 */
class Tinebase_Fulltext_Indexer
{
    protected $_maxBlobSize = 0;

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Fulltext_Indexer
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
     * @return Tinebase_Fulltext_Indexer
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Fulltext_Indexer();
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
     * @throws Tinebase_Exception_NotImplemented
     */
    private function __construct()
    {
        $fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT);
        if ('Sql' !== $fulltextConfig->{Tinebase_Config::FULLTEXT_BACKEND}) {
            throw new Tinebase_Exception_NotImplemented('only Sql backend is implemented currently');
        }

        $db = Tinebase_Core::getDb();
        if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $logFileSize = (int)$db->query('SHOW VARIABLES LIKE "innodb_log_file_size"')->fetchColumn(0);
            if ($logFileSize > 0) $this->_maxBlobSize = $logFileSize / 10;
            $maxPacketSize = (int)$db->query('SHOW VARIABLES LIKE "max_allowed_packet"')->fetchColumn(0);
            if ($maxPacketSize > 0 && $this->_maxBlobSize < $maxPacketSize) $this->_maxBlobSize = $maxPacketSize;
            if ($this->_maxBlobSize > 0) $this->_maxBlobSize -= 16*1024;
        }
    }

    /**
     * @param string $_id
     * @param string $_fileName
     *
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFileContentsToIndex($_id, $_fileName)
    {
        if (false === ($blob = file_get_contents($_fileName))) {
            throw new Tinebase_Exception_InvalidArgument('could not get file contents of: ' . $_fileName);
        }
        $blob = Tinebase_Core::filterInputForDatabase($blob);

        if ($this->_maxBlobSize > 0 && strlen($blob) > $this->_maxBlobSize) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' truncating full text blob for id ' . $_id);
            $blob = substr($blob, 0, $this->_maxBlobSize);
        }
        
        $db = Tinebase_Core::getDb();
        $db->delete(SQL_TABLE_PREFIX . 'external_fulltext', $db->quoteInto($db->quoteIdentifier('id') . ' = ?', $_id));
        $db->insert(SQL_TABLE_PREFIX . 'external_fulltext', array('id' => $_id, 'text_data' => $blob));
    }

    /**
     * @param string|array $_ids
     */
    public function removeFileContentsFromIndex($_ids)
    {
        if (empty($_ids)) {
            return;
        }
        $db = Tinebase_Core::getDb();
        $db->delete(SQL_TABLE_PREFIX . 'external_fulltext', $db->quoteInto($db->quoteIdentifier('id') . ' IN (?)', (array)$_ids));
    }
}