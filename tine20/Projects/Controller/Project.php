<?php

/**
 * Project controller for Projects application
 * 
 * @package     Projects
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Project controller class for Projects application
 * 
 * @package     Projects
 * @subpackage  Controller
 */
class Projects_Controller_Project extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = Projects_Config::APP_NAME;
        $this->_modelName = Projects_Model_Project::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME        => Projects_Model_Project::class,
            Tinebase_Backend_Sql::TABLE_NAME        => Projects_Model_Project::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = true;
        $this->_handleVirtualRelationProperties = true;
    }
}
