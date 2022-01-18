<?php declare(strict_types=1);
/**
 * Division controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Division controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Division extends Tinebase_Controller_Record_Container
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_Division::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => HumanResources_Model_Division::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);

        $this->_grantsModel = HumanResources_Model_DivisionGrants::class;
        $this->_manageRight = HumanResources_Acl_Rights::MANAGE_DIVISIONS;
        $this->_purgeRecords = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        // everybody can GET, anything else needs MANAGE_DIVISIONS
        if (self::ACTION_GET !== $_action) {
            if (!Tinebase_Core::getUser()
                    ->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_DIVISIONS)) {
                throw new Tinebase_Exception_AccessDenied(HumanResources_Acl_Rights::MANAGE_DIVISIONS .
                    ' right required to ' . $_action);
            }
        }
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        // standard actions are use for the division itself. everybody can GET, anything else needs MANAGE_DIVISIONS which is checked in _checkRight, so nothing to do here, do not call parent!
        if (in_array($_action, [self::ACTION_GET, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE])) {
            return true;
        }
        // delegated acl checks from employee, wtr, etc. come in here with non standard actions
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        // everybody can see all divisions
        if (self::ACTION_GET === $_action) {
            return;
        }
        parent::checkFilterACL($_filter, $_action);
    }
}
