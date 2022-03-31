<?php
/**
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * DeviceRecord controller class for DFCom application
 *
 * @package     DFCom
 * @subpackage  Controller
 */
class DFCom_Controller_DeviceRecord extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'DFCom';

        $this->_modelName = 'DFCom_Model_DeviceRecord';
        $this->_purgeRecords = false;
        // @todo get this from model conf??
        $this->_doContainerACLChecks = false;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName'     => $this->_modelName,
            'tableName'     => 'dfcom_device_record',
            'modlogActive'  => true
        ]);
    }

    /**
     * holds the instance of the singleton
     *
     * @var DFCom_Controller_DeviceRecord
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return DFCom_Controller_DeviceRecord
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }
}
