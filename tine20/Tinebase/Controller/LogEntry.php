<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for Tinebase
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_LogEntry extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Tinebase';
        $this->_modelName = 'Tinebase_Model_LogEntry';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_LogEntry',
            'tableName' => 'logentries'
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
     * @var Tinebase_Controller_LogEntry
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Controller_LogEntry
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Controller_LogEntry();
        }

        return self::$_instance;
    }

    public function cleanUp(Tinebase_DateTime $before = null)
    {
        if (! $before) {
            $before = Tinebase_DateTime::now()->subWeek(3);
        }

        $deleteFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Tinebase_Model_LogEntry::class, [
                ['field' => 'timestamp', 'operator' => 'before', 'value' => $before]
            ]
        );
        $this->deleteByFilter($deleteFilter);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Cleaned up log entries before ' . $before->toString());

        return true;
    }
}
