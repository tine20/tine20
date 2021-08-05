<?php declare(strict_types=1);
/**
 * Webauthn Public Key controller
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Webauthn Public Key controller
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_WebauthnPublicKey extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tinebase_Model_WebauthnPublicKey::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tinebase_Model_WebauthnPublicKey::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Tinebase_Model_WebauthnPublicKey::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
