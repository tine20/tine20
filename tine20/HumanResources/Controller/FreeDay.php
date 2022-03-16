<?php
/**
 * FreeDay controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeDay controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeDay extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeDay();
        $this->_modelName = HumanResources_Model_FreeDay::class;
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_traitDelegateAclField = 'freetime_id';
        $this->_traitGetOwnGrants = [
            HumanResources_Model_DivisionGrants::READ_OWN_DATA,
            HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST
        ];
    }

    protected function _getCheckFilterACLTraitFilter()
    {
        return new Tinebase_Model_Filter_ForeignId('freetime_id', 'definedBy', [
            ['field' => 'employee_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ],
            ]], [
                'controller' => HumanResources_Controller_FreeTime::class,
                'filtergroup' => HumanResources_Model_FreeTimeFilter::class,
            ]);
    }

    // we do not implement this at all, we solely depend on free times doing its job (vie delegated acl)
    // protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
}
