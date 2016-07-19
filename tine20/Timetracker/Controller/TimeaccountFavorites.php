<?php
/**
 * Timeaccount controller for Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * TimeaccountFavorites controller class for Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_TimeaccountFavorites extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Timetracker_Controller_Timeaccount
     */
    private static $_instance = null;

    /**
     * Disable container acl checks, favorites does not have any container.
     *
     * @var bool
     */
    protected $_doContainerACLChecks = false;

    /**
     * Timetracker_Controller_TimeaccountFavorites constructor.
     * @throws \Tinebase_Exception_Backend_Database
     */
    protected function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_TimeaccountFavorites();
        $this->_modelName = 'Timetracker_Model_TimeaccountFavorite';
        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
    }

    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timeaccount
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
