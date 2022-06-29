<?php
/**
 * Tine 2.0
 *
 * @package     Bookmarks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for Bookmarks
 *
 * @package     Bookmarks
 * @subpackage  Controller
 */
class Bookmarks_Controller_Bookmark extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = true;
        $this->_applicationName = 'Bookmarks';
        $this->_modelName = 'Bookmarks_Model_Bookmark';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Bookmarks_Model_Bookmark',
            'tableName' => 'bookmarks',
            'modlogActive' => true
        ));
        $this->_purgeRecords = FALSE;
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Bookmarks_Controller_Bookmark
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Bookmarks_Controller_Bookmark
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * increase access count
     *
     * @param Bookmarks_Model_Bookmark $bookmark
     */
    public function increaseAccessCount(Bookmarks_Model_Bookmark $bookmark)
    {
        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
        
        $currentRecord = $this->_backend->get($bookmark->getId());
        $currentRecord->{Bookmarks_Model_Bookmark::FLDS_ACCESS_COUNT}++;

        // FIXME: we really shoud have an modelconfig type for it
        $currentRecord->setReadOnlyFields(array_diff($currentRecord->getReadOnlyFields(),
            [Bookmarks_Model_Bookmark::FLDS_ACCESS_COUNT]));
        
        // yes, no history etc.
        $this->_backend->update($currentRecord);

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
    }
}
