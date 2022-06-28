<?php
/**
 * FileMetadata controller
 *
 * @package     EFile
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FileMetadata controller
 *
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class EFile_Controller_FileMetadata extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EFile_Config::APP_NAME;
        $this->_modelName = EFile_Model_FileMetadata::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EFile_Model_FileMetadata::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EFile_Model_FileMetadata::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
