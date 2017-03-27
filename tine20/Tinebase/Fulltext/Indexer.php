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
 * class to index text content
 *
 * @package     Tinebase
 * @subpackage  Fulltext

 */
class Tinebase_Fulltext_Indexer
{
    protected $_javaBin;
    protected $_tikaJar;

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
     */
    private function __construct()
    {
        if (null === ($fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)) || ! $fulltextConfig instanceof Tinebase_Config_Struct) {
            throw new Tinebase_Exception_UnexpectedValue('no fulltext configuration found');
        }

        if ('Sql' !== $fulltextConfig->{Tinebase_Config::FULLTEXT_BACKEND}) {
            throw new Tinebase_Exception_NotImplemented('only Sql backend is implemented currently');
        }
    }

    public function addFileContentsToIndex($_id, $_fileName)
    {
        if (false === ($blob = file_get_contents($_fileName))) {
            throw new Tinebase_Exception_UnexpectedValue('could not get file contents of: ' . $_fileName);
        }

        $db = Tinebase_Core::getDb();
        $db->delete(SQL_TABLE_PREFIX . 'external_fulltext', $db->quoteInto($db->quoteIdentifier('id') . ' = ?', $_id));
        $db->insert(SQL_TABLE_PREFIX . 'external_fulltext', array('id' => $_id, 'text_data' => $blob));
    }
}